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

const isTruncated = computed(() => {
  // NOTE: title is accessed to recompute on title changes
  return !!props.title && titleWidth.value > 0 && titleRef.value.scrollWidth > titleRef.value.clientWidth
})

</script>

<template>
  <div
    class="record d-flex"
    :class="{ 'flex-shrink-1': wrap }"
    :style="maxWidth !== undefined ? { maxWidth: `${maxWidth}px` } : null"
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
      <div class="record d-flex" @click.stop>
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

  .dark-mode & {
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
  background-image: url(../../../node_modules/@tabler/icons/icons/outline/x.svg) !important;
  width: 15px;
  height: 15px;
  background-repeat: no-repeat;
  background-size: 13px 13px;
  background-position: center !important;
  &:not(.dark-mode *) {
    filter: invert(1) hue-rotate(180deg);
  }
}

</style>
