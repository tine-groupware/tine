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
            <CustomDatePicker v-model="selectedDate" :locale="currentConfig?.locale"/>
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
import {useReservationOperations, useTineJsonRPC} from "./composables"
import {translationHelper} from "./keys";

import {init} from "./broadcastClient_spa";

import CustomDatePicker from "./CustomDatePicker.vue";

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
  deleteReservation,
  jsonRPC
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
      retDict[t.resourceName] = {
        pointMap: t.polygon[0].map(point => {
          let [x, y] = point
          x = x * scalingFactor
          y = y * scalingFactor
          l_x = l_x >= x ? x : l_x
          l_y = l_y >= y ? y : l_y
          u_x = u_x <= x ? x : u_x
          u_y = u_y <= y ? y : u_y
          return {x, y}
        }),
        displayName: t.resourceDisplayName
      }
    }
    retDict['meta'] = [{x: l_x, y: l_y}, {x: l_x, y: u_y}, {x: u_x, y: u_y}, {x: u_x, y: l_y}]
  }
  return retDict
})

const checkIfCoordinateInsideReservables = (x, y) => {
  if (!checkInside(reservableCoordinateDict.value['meta'], {x, y})) return
  for (const [id, {pointMap}] of Object.entries(reservableCoordinateDict.value)) {
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
      modalTitle = formatMessage('Reserve Resource: {name}', {name: modalData.reservableObj.displayName})
      modalContent = formatMessage("Do you want to reserve the resource {name} on floor {floorName} for {date} ?", {
        name: modalData.reservableObj.displayName,
        floorName: currentFloor.value.name,
        date: selectedDate.value
      })
      modalAction = async () => {
        await reserveTable(modalData.reservableObj.name)
      }
    } else if (modalData.reservationEvent.deletable) {
      modalTitle = formatMessage("Resource: {name} reserved by you", {name: modalData.reservableObj.displayName})
      modalContent = formatMessage('Do you want to delete your reservation ?')
      modalAction = async () => {
        await deleteReservation(modalData.reservableObj.name)
      }
    } else {
      modalTitle = formatMessage('Resource: {name} already reserved', {name: modalData.reservableObj.displayName})
      modalContent = formatMessage('The resource {name} on floor {floorName} is reserved for selected date: {date} by {name1}.', {
        name: modalData.reservableObj.displayName,
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
    const t = resourceNameToObjMap.value[resourceName]
    modalData.reservableObj = t.model
    modalData.reservableObj.displayName = t.config.resourceDisplayName
    modalData.reservationEvent = reservationData[resourceName]
    console.log(modalData)
    modalShowing.value = true
  }
}

const overlayCanvas = ref(null)
const drawTables = function () {
  const getColor = function (resId) {
    if (!reservationData[resId]){
      return resourceNameToObjMap[resId]?.model.color || TABLE_COLOR_RESERVABLE
    }
    else if (!reservationData[resId].deletable) {
      return reservationData[resId].event.container_id.color || TABLE_COLOR_RESERVED
    }
    else return TABLE_COLOR_DELETABLE
  }

  const getReserverName = function (resName) {
    const r = reservationData[resName]
    if (!r) return null
    else {
      const {n_given: first, n_family: last} = r.reservedBy
      if (first && last){
        return `${first} ${last[0]}.`
      } else if (first){
        return first
      } else {
        return last
      }
    }
  }
  console.info("Drawing Tables")
  if (!Object.keys(reservableCoordinateDict.value).length || !overlayCanvas.value) {
    console.info("currentFloor/overlayCanvas not inited")
    return
  }
  const ctx = overlayCanvas.value.getContext("2d")
  ctx.clearRect(0, 0, overlayCanvas.value.width, overlayCanvas.value.height)
  for (const [resourceName, {pointMap, displayName}] of Object.entries(reservableCoordinateDict.value)) {
    if (resourceName === "meta") continue
    const start = pointMap[0]
    const second = pointMap[1]
    // drawing background
    // ctx.fillStyle = TABLE_COLOR_RESERVABLE
    // ctx.beginPath()
    // ctx.moveTo(start.x, start.y)
    // pointMap.slice(1,).forEach(point => {
    //   ctx.lineTo(point.x, point.y)
    // })
    // ctx.fill()
    // drawing the left bar
    ctx.fillStyle = getColor(resourceName)
    ctx.beginPath()
    ctx.moveTo(start.x, start.y)
    ctx.lineTo(second.x, second.y)
    ctx.lineTo(second.x + 10, second.y)
    ctx.lineTo(start.x + 10, start.y)
    ctx.fill()
    // Writing resource display name on canvas
    const posX = start.x + 15
    const posY = start.y + 14
    ctx.font = 'bold 14px Arial'
    ctx.fillStyle = 'white'
    ctx.fillText(displayName, posX, posY)
    // Writes reserver name on canvas if resource is reserved
    const t = getReserverName(resourceName)
    if (t) {
      ctx.font = '13px Arial'
      ctx.fillStyle = "white"
      ctx.fillText(t, second.x + 15, second.y-10)
    }
  }
}
const debouncedDraw = _.debounce(drawTables, 100)

const initWS = async () => {
  const bhConfig = currentConfig.value?.broadcasthubConfig
  const relevantContainerIds = currentConfig.value.resources.map(element => element.container_id)
  const debouncedReservationFetch = _.debounce(_.bind(fetchReservations, this, false), 1000)
  const cb = (data) => {
    console.log(data)
    const topicPrefix = String(data.model).replace('_Model_', '.');
    const containerId = data.containerId
    if (topicPrefix === 'Calendar.Event' && relevantContainerIds.includes(containerId)){
      debouncedReservationFetch()
    }
  }
  if (bhConfig && bhConfig?.active){
    const wsUrl = bhConfig.url
    try{
      await init(wsUrl, jsonRPC, cb)
    } catch(e){
      window.location.reload()
    }
  }
}

onMounted(() => {
  let waitIRef = null
  waitIRef = setInterval(() => {
    if (window.initialData) {
      currentConfig.value = window.initialData
      currentFloor.value = currentConfig.value.floorplans[0]
      document.getElementsByClassName("tine-viewport-waitcycle")[0].style.display = "none";
      const last = document.URL.split('/').pop()
      if(last.includes('-') && new Date(last).toString() !== "Invalid Date") selectedDate.value = last
      clearInterval(waitIRef)
      console.info("CurrentConfig:", currentConfig.value)
      fetchReservations()
      initWS()
    } else {
      window.location.reload()
    }
  }, 5)
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
