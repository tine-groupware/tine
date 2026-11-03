<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <s.deshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<script setup>
import { computed, ref } from 'vue'
import { useElementSize } from '@vueuse/core'
import { BPopover } from 'bootstrap-vue-next'
import { useWindowMgrZIndex } from '../../../vue/composables/useWindowMgrZIndex'

const props = defineProps({
  record: Object,
  title: { type: String, default: '' },
  colorSchema: { type: Object, default: null },
  maxWidth: { type: Number, default: undefined },
  wrap: { type: Boolean, default: false },
  id: { type: String, required: true },
  showPopover: { type: Boolean, default: false },
  readOnly: { type: Boolean, default: false }
})

const emits = defineEmits(['remove'])

const titleRef = ref()
const { width: titleWidth } = useElementSize(titleRef)

const { zIndex, register, unregister } = useWindowMgrZIndex()

// NOTE: colored tags are shown in their real colors in dark mode too (dark-reverse)
const colorStyle = computed(() => {
  return props.colorSchema ? { backgroundColor: props.colorSchema.color, color: props.colorSchema.text } : null
})

const tagStyle = computed(() => {
  return {
    ...colorStyle.value,
    ...(props.maxWidth !== undefined ? { maxWidth: `${props.maxWidth}px` } : {})
  }
})

const isTruncated = computed(() => {
  // NOTE: title is accessed to recompute on title changes
  return !!props.title && titleWidth.value > 0 && titleRef.value.scrollWidth > titleRef.value.clientWidth
})

</script>

<template>
  <div
    class="record d-flex"
    :class="{ 'flex-shrink-1': wrap, 'dark-reverse': colorStyle }"
    :style="tagStyle"
    @click.stop
    :id="`${id}-record-div`"
  >
    <div ref="titleRef" class="record-title" :class="wrap ? 'text-wrap' : 'text-truncate'">
      {{title}}
    </div>
    <div v-if="!readOnly" class="remove-record ms-1 tabler-icons-cross" @click.stop="emits('remove')"/>
    <BPopover
      v-if="showPopover && isTruncated"
      :target="`${id}-record-div`"
      click
      realtime
      placement="top"
      teleport-to="body"
      class="bootstrap-scope"
      :style="{ zIndex }"
      :delay="0"
      @show="register"
      @hidden="unregister"
    >
      <div class="record d-flex" :class="{ 'dark-reverse': colorStyle }" :style="colorStyle" @click.stop>
        <div class="flex-grow-1">{{title}}&nbsp;&nbsp;</div>
      </div>
    </BPopover>
  </div>
</template>

<style scoped lang="scss">
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
  margin-top: 1px;
  flex-shrink: 0;
  min-width: 0;

  .dark-mode &:not(.dark-reverse) {
    color: black;
  }
}

.record-title {
  flex: 0 1 auto;
  min-width: 0;
}

.record:active{
  background-color: #0062a7;
}

.tabler-icons-cross {
  flex-shrink: 0;
  // NOTE: icon is a mask so it has the text color
  background-color: currentColor;
  mask: url(../../../node_modules/@tabler/icons/icons/outline/x.svg) center / 13px 13px no-repeat;
  width: 15px;
  height: 15px;
}

</style>
