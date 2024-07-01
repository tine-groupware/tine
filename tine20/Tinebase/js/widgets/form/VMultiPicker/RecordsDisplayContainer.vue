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
    <div class="main-container w-100 h-100 d-flex" @click="triggerCombo">
      <div class="height-17 flex-grow-1 d-flex align-items-center overflow-hidden mid-container" ref="containerDiv">
        <div class="d-flex align-items-center record-tag-container" ref="contentDiv">
          <RecordTag
            v-for="record in recordsInDisplayContainer"
            :key="record.getId()"
            :id="record.getId()"
            :record="record"
            :truncate="truncateTitle"
            @remove="removeRecord(record.getId())"
          />
          <div
            id="ellipsis-show-more"
            @click.stop
            v-if="recordsCountInPopover"
            ref="popoverEllipsis"
            class="d-flex align-items-center record"
          >
            ...
          </div>
        </div>
      </div>
      <div class="x-tool-toggle embedded-icon dark-reverse"></div>
    </div>
    <BPopover
      :target="popoverTarget"
      click
      realtime
      placement="top"
      container="body"
      :delay="0"
      :hide="!recordsCountInPopover"
    >
      <div class="bootstrap-scope">
        <div class="d-flex flex-wrap">
          <RecordTag
            v-for="record in recordsInPopover"
            class="mb-1 dark-reverse"
            :key="record.getId()"
            @click.stop
            :id="record.getId()"
            :record="record"
            :truncate="true"
            @remove="removeRecord(record.getId())"
          />
        </div>
      </div>
    </BPopover>
  </div>
</template>

<script setup>
import { computed, inject, ref, watchEffect } from 'vue'
import RecordTag from './RecordTag.vue'
import { useElementSize } from '@vueuse/core'

const props = defineProps({
  records: Object,
  injectKey: String,
  emptyText: String
})

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

const { width: containerDivWidth } = useElementSize(containerDiv)

const contentDiv = ref()
const { width: contentDivWidth } = useElementSize(contentDiv)

const truncateTitle = ref(true)

watchEffect(() => {
  const EXPANDED_TAG_WIDTH = 170
  const CONTRACTED_TAG_WIDTH = 100
  if (contentDivWidth.value < containerDivWidth.value) {
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
}, {
  // onTrigger: () => {
  //   console.log('triggered', contentDivWidth.value, containerDivWidth.value, recordsCountInPopover.value)
  // },
  flush: 'sync'
})

const removeRecord = (recordId) => {
  eventBus.emit('removeRecord', recordId)
  return false
}

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
}
</style>
