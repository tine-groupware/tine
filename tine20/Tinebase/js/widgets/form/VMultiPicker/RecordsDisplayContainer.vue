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
        class="flex-grow-1 d-flex align-items-center mid-container overflow-hidden"
        ref="containerDiv"
        :class="{'height-17 overflow-hidden': !props.multiLine}"
      >
        <div class="d-flex align-items-center record-tag-container" :class="{'flex-wrap': props.multiLine}" ref="contentDiv">
          <RecordTag
            v-for="record in recordsInDisplayContainer"
            :key="record.getId()"
            :id="record.getId()"
            :record="record"
            :show-popover="true"
            :truncate="truncateTitle"
            :readOnly="readOnly"
            @remove="removeRecord(record.getId())"
          />
          <div
            id="ellipsis-show-more"
            @click.stop
            v-if="recordsCountInPopover"
            ref="popoverEllipsis"
            class="d-flex align-items-center record tabler-icons-dots pe-4"
          />
        </div>
      </div>
      <div v-if="!readOnly" class="d-flex align-items-center">
        <div class="x-form-trigger x-form-arrow-trigger embedded-icon dark-reverse"></div>
      </div>
    </div>
    <BPopover
      :target="popoverTarget"
      click
      realtime
      placement="top"
      container="body"
      :delay="0"
      :hide="!recordsCountInPopover"
      v-if="recordsCountInPopover"
    >
      <div class="bootstrap-scope">
        <div class="d-flex flex-wrap">
          <RecordTag
            v-for="record in recordsInPopover"
            class="mb-1 dark-reverse text-wrap"
            :key="record.getId()"
            @click.stop
            :id="record.getId()"
            :record="record"
            :truncate="false"
            @remove="removeRecord(record.getId())"
          />
        </div>
      </div>
    </BPopover>
  </div>
</template>

<script setup>
/* eslint-disable */
import {computed, inject, ref, watchEffect, provide, watch, nextTick} from 'vue'
import RecordTag from './RecordTag.vue'
import {useElementBounding, useElementSize} from '@vueuse/core'

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

provide('recordRenderer', props.recordRenderer)

const eventBus = inject(props.injectKey)
const containerDiv = ref()
const popoverEllipsis = ref()

const popoverTarget = computed(() => {
  return popoverEllipsis.value ? popoverEllipsis.value.id : null
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

const truncateTitle = ref(true)

const EXPANDED_TAG_WIDTH = 170
const CONTRACTED_TAG_WIDTH = 100

watch([containerDivWidth, containerDivHeight], ([nW,nH]) => {
  eventBus.emit('pickerResize', {w: nW, h: nH})
})

const removeRecord = (recordId) => {
  eventBus.emit('removeRecord', recordId)
  recordsCountInPopover.value = Math.max(0, recordsCountInPopover.value - 1) // hack to rerun the watchEffect
  return false
}

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

  } else {
    if (contentDivWidth.value < containerDivWidth.value ) {
      if (truncateTitle.value && recordsCountInPopover.value === 0) {
        if (recordsInDisplayContainer.value.length * EXPANDED_TAG_WIDTH < containerDivWidth.value) truncateTitle.value = false
      } else {
        if (recordsCountInPopover.value !== 0 && (contentDivWidth.value + CONTRACTED_TAG_WIDTH < containerDivWidth.value)) {
          recordsCountInPopover.value = Math.max(0, recordsCountInPopover.value - 1)
        }
      }
    } else {
      // if content doesn't fit, first truncate the title
      // if it still doesn't fit, take one record into popover
      if (!truncateTitle.value) {
        truncateTitle.value = true
      } else {
        recordsCountInPopover.value = Math.min(props.records.size, recordsCountInPopover.value + 1)
      }
    }
  }
}, {
  // onTrigger: (e) => {
  //   console.warn(
  //     'triggered',
  //     contentDivWidth.value,
  //     containerDivWidth.value,
  //     recordsCountInPopover.value,
  //     checkWidth.value)
  // },
  flush: 'post'
})

const triggerCombo = () => {
  eventBus.emit('onTriggerClick')
}

</script>

<style scoped>
.mid-container{
  margin-bottom: 1px;
}

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
  font-size: 11px;
  background-color: white;
  height: 100%;
  cursor: text;
}

.dark-mode .main-container{
  background-color: #333333;
  filter: invert(1) hue-rotate(180deg);
}

.height-17 {
  height: 17px;
}

.embedded-icon {
  width: 16px;
  height: 16px !important;
  cursor: pointer;
}

.tabler-icons-dots {
  background-image: url(../../../node_modules/@tabler/icons/icons/outline/dots.svg) !important;
  width: 15px;
  height: 13px;
  background-repeat: no-repeat;
  background-size: 13px 13px;
  background-position: center !important;
  filter: invert(1) hue-rotate(180deg);
}

#ellipsis-show-more {
  border-right: none;
  mask: conic-gradient(from -135deg at right,#0000,#000 1deg 89deg,#0000 90deg) 50%/100% 4px;
}
</style>
