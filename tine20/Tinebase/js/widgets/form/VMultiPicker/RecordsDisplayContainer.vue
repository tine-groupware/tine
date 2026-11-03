<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <s.deshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <div class="bootstrap-scope" ref="mainContainer">
    <div class="main-container w-100 h-100 d-flex" @click="readOnly ? null : triggerCombo()">
      <div
        class="form-select flex-grow-1 d-flex align-items-center overflow-hidden"
        ref="containerDiv"
        :class="{'overflow-hidden': !props.multiLine}"
      >
        <div class="d-flex align-items-center record-tag-container" :class="{'flex-wrap': props.multiLine}" ref="contentDiv">
          <RecordTag
            v-for="record in recordsInDisplayContainer"
            :key="record.getId()"
            :id="record.getId()"
            :record="record"
            :title="titles[record.getId()]"
            :max-width="tagMaxWidth"
            :show-popover="true"
            :readOnly="readOnly"
            @remove="removeRecord(record.getId())"
          />
          <div
            id="ellipsis-show-more"
            @click.stop
            v-if="recordsCountInPopover"
            ref="popoverEllipsis"
            class="d-flex align-items-center record pe-4"
          >
            <div class="tabler-icons-dots"/>
          </div>
        </div>
      </div>
    </div>
    <BPopover
      :target="popoverTarget"
      click
      realtime
      placement="top"
      teleport-to="body"
      class="bootstrap-scope"
      :style="{ zIndex: popoverZIndex }"
      :delay="0"
      @show="registerPopover"
      @hidden="unregisterPopover"
      :hide="!recordsCountInPopover"
      v-if="recordsCountInPopover"
    >
      <div class="bootstrap-scope">
        <div class="d-flex flex-wrap">
          <RecordTag
            v-for="record in recordsInPopover"
            class="mb-1"
            :key="record.getId()"
            @click.stop
            :id="record.getId()"
            :record="record"
            :title="titles[record.getId()]"
            :wrap="true"
            @remove="removeRecord(record.getId())"
          />
        </div>
      </div>
    </BPopover>
  </div>
</template>

<script setup>
/* eslint-disable */
import {computed, inject, ref, toRaw, watchEffect, watch, nextTick} from 'vue'
import RecordTag from './RecordTag.vue'
import {useElementBounding, useElementSize} from '@vueuse/core'
import { fitTags } from './fitTags'
import { useWindowMgrZIndex } from '../../../vue/composables/useWindowMgrZIndex'

const props = defineProps({
  records: Object,
  recordRenderer: Function,
  injectKey: String,
  emptyText: String,
  readOnly: Boolean,
  multiLine: {
    type: Number,
    default: undefined
  }
})

const eventBus = inject(props.injectKey)
const containerDiv = ref()
const popoverEllipsis = ref()

const { zIndex: popoverZIndex, register: registerPopover, unregister: unregisterPopover } = useWindowMgrZIndex()

const popoverTarget = computed(() => {
  return popoverEllipsis.value ? popoverEllipsis.value.id : null
})

const titles = ref({})
const requestedTitles = new WeakSet()
watchEffect(() => {
  props.records.forEach(async (record) => {
    const id = record.getId()
    if (requestedTitles.has(toRaw(record))) return
    requestedTitles.add(toRaw(record))

    let t = props.recordRenderer ? props.recordRenderer(record, {}) : record.getTitle()
    if (t && typeof t !== 'string') t = await t.asString()
    titles.value[id] = t || ''
  })
})

const recordsCountInPopover = ref(0)
const recordsInDisplayContainer = computed(() => {
  return Array.from(props.records.values()).reverse().slice(0, props.records.size - recordsCountInPopover.value)
})

const recordsInPopover = computed(() => {
  return Array.from(props.records.values()).reverse().slice(props.records.size - recordsCountInPopover.value, props.records.size)
})

const { width: containerDivWidth, height: containerDivHeight } = useElementSize(containerDiv)

const contentDiv = ref()
const { width: contentDivWidth, height: contentDivHeight, right: contentDivRight } = useElementBounding(contentDiv)

// min (outer) width of a shrunk tag before tags get moved into the popover
const MIN_TAG_WIDTH = 60
// estimated (outer) width of the ellipsis until it got rendered
const OVERFLOW_WIDTH = 40
const CONTRACTED_TAG_WIDTH = 100

const tagCap = ref(Infinity)
const tagMargin = ref(0)
const tagMaxWidth = computed(() => {
  if (props.multiLine) {
    return containerDivWidth.value ? Math.floor(containerDivWidth.value - tagMargin.value) : undefined
  }
  return tagCap.value === Infinity ? undefined : Math.floor(tagCap.value - tagMargin.value)
})

watch([containerDivWidth, containerDivHeight], ([nW,nH]) => {
  eventBus.emit('pickerResize', {w: nW, h: nH})
})

const removeRecord = (recordId) => {
  eventBus.emit('removeRecord', recordId)
  if (props.multiLine) {
    recordsCountInPopover.value = Math.max(0, recordsCountInPopover.value - 1) // hack to rerun the watchEffect
  }
  return false
}

let canvas
const getOuterWidth = (el) => {
  const style = el.ownerDocument.defaultView.getComputedStyle(el)
  return el.getBoundingClientRect().width + parseFloat(style.marginLeft) + parseFloat(style.marginRight)
}

// tag widths are computed from title text width and the tag decoration (padding, margin, remove icon)
// so we can also compute the widths of tags which are currently in the popover
const measureTags = () => {
  const tagEl = contentDiv.value?.querySelector(':scope > .record:not(#ellipsis-show-more)')
  const titleEl = tagEl?.querySelector('.record-title')
  if (!titleEl) return null

  const style = titleEl.ownerDocument.defaultView.getComputedStyle(titleEl)
  canvas = canvas || titleEl.ownerDocument.createElement('canvas')
  const ctx = canvas.getContext('2d')
  ctx.font = `${style.fontStyle} ${style.fontWeight} ${style.fontSize} ${style.fontFamily}`

  const tagWidth = getOuterWidth(tagEl)
  tagMargin.value = tagWidth - tagEl.getBoundingClientRect().width
  const decorationWidth = tagWidth - titleEl.getBoundingClientRect().width

  return Array.from(props.records.values()).reverse().map((record) => {
    const title = titles.value[record.getId()] || ''
    return Math.ceil(ctx.measureText(title).width) + 1 + decorationWidth
  })
}

const layoutTags = () => {
  if (props.multiLine || !containerDivWidth.value) return

  const widths = measureTags()
  if (!widths) return

  const contentStyle = contentDiv.value.ownerDocument.defaultView.getComputedStyle(contentDiv.value)
  const available = containerDivWidth.value - parseFloat(contentStyle.marginLeft) - parseFloat(contentStyle.marginRight)
  const { visibleCount, cap } = fitTags(widths, available, {
    minWidth: MIN_TAG_WIDTH,
    overflowWidth: popoverEllipsis.value ? getOuterWidth(popoverEllipsis.value) : OVERFLOW_WIDTH
  })

  tagCap.value = cap
  recordsCountInPopover.value = widths.length - visibleCount
}

watch([containerDivWidth, titles, () => props.records.size, popoverEllipsis], layoutTags, { deep: true, flush: 'post' })

const checkWidth = ref(false)
watchEffect(async () => {
  if (props.multiLine) {
    const LINE_HEIGHT = 14
    const MAX_HEIGHT = props.multiLine * LINE_HEIGHT

    if (!checkWidth.value) {
      // if height available pop
      if ( LINE_HEIGHT <= MAX_HEIGHT - contentDivHeight.value) {
        if (recordsCountInPopover.value !== 0) {
          recordsCountInPopover.value = Math.max(0, recordsCountInPopover.value - 1)
          checkWidth.value = true
        }
      } else if ( MAX_HEIGHT < contentDivHeight.value){ // push if height unavailable
        recordsCountInPopover.value = Math.min(props.records.size, recordsCountInPopover.value + 1)
        await nextTick()
        if (MAX_HEIGHT < contentDivHeight.value) {
          recordsCountInPopover.value = Math.min(props.records.size, recordsCountInPopover.value + 1)
        }
        checkWidth.value = true
      }
    } else {
      if (popoverEllipsis.value && recordsCountInPopover.value !== 0) {
        const ellRect = popoverEllipsis.value.getBoundingClientRect()
        if (contentDivRight.value - ellRect.right > CONTRACTED_TAG_WIDTH) {
          recordsCountInPopover.value = Math.max(0, recordsCountInPopover.value - 1)
        } else {
          checkWidth.value = false
        }
      } else {
        checkWidth.value = false
      }
    }
  }
}, {
  flush: 'post'
})

const triggerCombo = () => {
  eventBus.emit('onTriggerClick')
}

</script>

<style scoped>

.record {
  background-color: #3a8acc;
  color: white;
  border-radius: 3px;
  font-size: 10px;
  padding-left: 3px;
  padding-right: 3px;
  margin-right: 2px;
  margin-left: 2px;
  cursor: pointer;
}

.record-tag-container{
  margin-left: 2px;
}

.main-container {
  font-size: 12px;
  height: 100%;
  cursor: text;
}

.form-select:before {
  content: "\200b";
}

.embedded-icon {
  width: 16px;
  height: 16px !important;
  cursor: pointer;
}

.tabler-icons-dots {
  background-image: url(../../../node_modules/@tabler/icons/icons/outline/dots.svg) !important;
  width: 15px;
  height: 15px;
  background-repeat: no-repeat;
  background-size: 13px 13px;
  background-position: center !important;
}

.tabler-icons-dots:not(.dark-mode *) {
  filter: invert(1) hue-rotate(180deg);
}

#ellipsis-show-more {
  height: 15px;
  margin-top: 1px;
  border-right: none;
  mask: conic-gradient(from -135deg at right,#0000,#000 1deg 89deg,#0000 90deg) 50%/100% 4px;
}
</style>
