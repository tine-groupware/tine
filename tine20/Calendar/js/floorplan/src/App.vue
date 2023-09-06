<template>
  <div class="container">
    <div class="row mt-3 justify-content-between">
      <div class="col-md-4">
        <div class="row justify-content-center">
          <div class="col-1 col-md-1 d-flex justify-content-end align-items-center p-0 d-inline-block">
            <img @click="selectPrevDay"
                 role="button"
                 src="images/icon-set/icon_arrow_left.svg">
          </div>
          <div class="col-5 col-md-7 col-sm-5 p-0 d-flex align-items-center">
            <BFormInput v-model="selectedDate" type="date" class="text-center"/>
          </div>
          <div class="col-1 col-md-1 d-flex justify-content-start align-items-center p-0 d-inline-block">
            <img @click="selectNextDay"
                 role="button"
                 src="images/icon-set/icon_arrow_right.svg">
          </div>
        </div>
      </div>
      <div class="col-md-3 d-flex justify-content-center mx-2">
        <BFormRadioGroup v-model="currentFloor" :options="floorOptions" buttons button-variant="outline-primary"/>
      </div>
    </div>
    <div class="img-container">
      <BOverlay :show="fetchingData" spinner-variant="primary" :opacity="0.20">
        <img :src="currentFloor?.image" alt="" id="floor-plan-image" ref="floorImage" @load="debouncedDraw">
        <canvas :height="imgHeight" :width="imgWidth" id="floor-plan-canvas" ref="overlayCanvas"
                @click="checkIfDeskClicked"></canvas>
      </BOverlay>
    </div>
  </div>
  <BModal v-model="modalShowing" :title="modalProps.modalTitle" :okDisabled="!modalProps.modalAction">
    <template #default>
      <div v-if="modalShowing">
        <p>{{ modalProps.modalContent }}</p>
      </div>
    </template>
    <template #footer>
      <div>
        <BButton class="mx-1" @click="modalShowing=false">Cancel</BButton>
        <BButton class="mx-1" variant="primary" @click="handleModalOkClick" :disabled="!modalProps.modalAction">Ok
        </BButton>
      </div>
    </template>
  </BModal>
</template>

<script setup>
import _ from "lodash"

import {computed, onMounted, reactive, ref, watch, shallowRef, inject} from "vue"
import {useElementSize} from "@vueuse/core"

import {checkInside} from "./utils";
import {useReservationOperations} from "./composables"
import {translationHelper} from "./keys";

const formatMessage = inject(translationHelper)

// table colors definition
const TABLE_COLOR_DELETABLE = "#5bf528"
const TABLE_COLOR_RESERVABLE = "#0062a7"
const TABLE_COLOR_RESERVED = "rgb(93, 221, 188)"

const debug = (e) => {
  const inside = checkIfCoordinateInsideReservables(e.offsetX, e.offsetY)
  console.log(inside)
};

// initialData provided by the server
const currentConfig = shallowRef(null)

const selectedDate = ref(new Date().toLocaleDateString("sv"))
watch(selectedDate, async nv => {
  console.info("Selected Date", nv)
  await fetchReservations()
})
const selectNextDay = () => selectedDate.value = addOffSet(selectedDate.value, 24 * 60 * 60 * 1000)
const selectPrevDay = () => selectedDate.value = addOffSet(selectedDate.value, -24 * 60 * 60 * 1000)
const addOffSet = function (currentDate, offset) {
  const cd = new Date(currentDate)
  return new Date(cd.getTime() + offset).toLocaleDateString("sv")
}

const currentFloor = shallowRef()
watch(currentFloor, async nv => {
  console.info("currentFloor:", nv)
  await fetchReservations()
})

// floorOptions following the option required by bootstrap-vue-next
const floorOptions = computed(() => {
  return currentConfig.value?.floorplans?.map(element => {
    return {value: element, text: element.name}
  })
})

// template reference to the loaded image
const floorImage = ref(null)
// reactive image properties - used to track image resize
const {width: imgWidth, height: imgHeight} = useElementSize(floorImage)
// redraw the canvas if the window is resized
watch(imgWidth, (nv, ov) => {
  console.info("Image width changed: Tables will be rerendered on next tick. Width:", nv, ov)
  debouncedDraw()
}, {flush: "post"})

const {
  fetchingData,
  reservationData,
  resourceNameToObjMap,
  reserveTable,
  fetchReservations,
  deleteReservation
} = useReservationOperations(currentConfig, selectedDate, currentFloor)

watch(reservationData, (nv) => {
  // change triggered by reservationData change
  console.info("Reservation Data updated:", nv)
  debouncedDraw()
})

// a map containing the resourceName to resource pointMap (which shows the res location)
// structure: {String: [{x:Int, y:Int}]}
const reservableCoordinateDict = computed(() => {
  console.info("reevaluatingCoordinates")
  const retDict = {}
  const refWidth = currentFloor.value.referenceImageDim[0][0]
  if (!refWidth) return retDict
  const scalingFactor = imgWidth.value / refWidth
  const resources = currentFloor.value.resources
  if (resources) {
    let [l_x, l_y, u_x, u_y] = [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER, 0, 0]
    for (let i = 0; i < resources.length; i++) {
      const t = resources[i]
      retDict[t.resourceName] = t.polygon[0].map(point => {
        let [x, y] = point
        x = x * scalingFactor
        y = y * scalingFactor
        l_x = l_x >= x ? x : l_x
        l_y = l_y >= y ? y : l_y
        u_x = u_x <= x ? x : u_x
        u_y = u_y <= y ? y : u_y
        return {x, y}
      })
    }
    retDict['meta'] = [{x: l_x, y: l_y}, {x: l_x, y: u_y}, {x: u_x, y: u_y}, {x: u_x, y: l_y}]
  }
  return retDict
})

const checkIfCoordinateInsideReservables = (x, y) => {
  if (!checkInside(reservableCoordinateDict.value['meta'], {x, y})) return
  for (const [id, pointMap] of Object.entries(reservableCoordinateDict.value)) {
    if (id === "meta") continue
    if (checkInside(pointMap, {x, y})) {
      return id
    }
  }
  return null
}

const modalShowing = ref(false);
const modalData = reactive({
  reservationEvent: null,
  reservableObj: null
});
const modalProps = computed(() => {
  let [modalTitle, modalContent, modalAction] = ["", "", null];
  if (!modalData.reservableObj) {
    return {modalTitle, modalContent, modalAction}
  } else {
    if (!modalData.reservationEvent) {
      modalTitle = formatMessage('Reserve Resource: {name}', {name: modalData.reservableObj.name})
      modalContent = formatMessage("Reserve the resource {name} on floor {floorName} for {date} ?", {
        name: modalData.reservableObj.name,
        floorName: currentFloor.value.name,
        date: selectedDate.value
      })
      modalAction = async () => {
        await reserveTable(modalData.reservableObj.name)
      }
    } else if (modalData.reservationEvent.deletable) {
      modalTitle = formatMessage("Resource {name} reserved by you", {name: modalData.reservableObj.name})
      modalContent = formatMessage(`Do you want to delete your reservation ?`)
      modalAction = async () => {
        await deleteReservation(modalData.reservableObj.name)
      }
    } else {
      modalTitle = formatMessage(`Resource: {name} already reserved`, {name: modalData.reservableObj.name})
      modalContent = formatMessage(`The resource {name} on floor {floorName} is reserved for selected date: {date} by {name1}`, {
        name: modalData.reservableObj.name,
        floorName: currentFloor.value.name,
        date: selectedDate.value,
        name1: modalData.reservationEvent.reservedBy.n_fn
      })
    }
  }
  return {modalTitle, modalContent, modalAction}
})

const handleModalOkClick = async () => {
  try {
    // TODO: error handling on action failure
    // possible reasons for action failure:
    // table reserved privately
    await modalProps.value.modalAction()
  } catch (e) {
    console.log(e)
  } finally {
    modalShowing.value = false
  }
}


// clickHandler on canvas, which checks if one of tables was clicked
const checkIfDeskClicked = async (e) => {
  const {offsetX: x, offsetY: y} = e
  const resourceName = checkIfCoordinateInsideReservables(x, y)
  if (resourceName) {
    modalData.reservableObj = resourceNameToObjMap.value[resourceName].model
    modalData.reservationEvent = reservationData[resourceName]
    console.log(modalData)
    modalShowing.value = true
  }
}

const overlayCanvas = ref(null)
const debouncedDraw = _.debounce(() => drawTables(), 50)
const drawTables = function () {
  const getColor = function (resId) {
    if (!reservationData[resId]) return TABLE_COLOR_RESERVABLE
    else if (!reservationData[resId].deletable) return TABLE_COLOR_RESERVED
    else return TABLE_COLOR_DELETABLE
  }

  const getReserverName = function (resName) {
    const r = reservationData[resName]
    if (!r) return null
    else {
      return r.reservedBy.n_given ? r.reservedBy.n_given : r.reservedBy.n_family
    }
  }
  console.info("Drawing Tables")
  if (!Object.keys(reservableCoordinateDict.value).length || !overlayCanvas.value) {
    console.info("currentFloor/overlayCanvas not inited")
    return
  }
  const ctx = overlayCanvas.value.getContext("2d")
  ctx.clearRect(0, 0, overlayCanvas.value.width, overlayCanvas.value.height)
  for (const [resourceName, pointMap] of Object.entries(reservableCoordinateDict.value)) {
    if (resourceName === "meta") continue
    ctx.fillStyle = getColor(resourceName)
    ctx.beginPath()
    const start = pointMap[0]
    ctx.moveTo(start.x, start.y)
    pointMap.slice(1,).forEach(point => {
      ctx.lineTo(point.x, point.y)
    })
    ctx.fill()
    const t = getReserverName(resourceName)
    if (t) {
      const secondPt = pointMap[1]
      const midY = Math.ceil((secondPt.y + start.y) / 2)
      ctx.font = '13px Arial'
      ctx.fillStyle = "black"
      ctx.fillText(t, start.x + 10, midY)
    }
  }
}

onMounted(() => {
  let waitIRef = null
  waitIRef = setInterval(() => {
    if (window.initialData && !currentConfig.value) {
      currentConfig.value = window.initialData
      currentFloor.value = currentConfig.value.floorplans[0]
      document.getElementsByClassName("tine-viewport-waitcycle")[0].style.display = "none";
      clearInterval(waitIRef)
      console.info("CurrentConfig:", currentConfig.value)
      fetchReservations()
    }
  }, 0)
})
</script>

<style scoped>
.img-container {
  margin: auto;
  position: relative;
}

#floor-plan-image {
}

#floor-plan-canvas {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10;
}
</style>
