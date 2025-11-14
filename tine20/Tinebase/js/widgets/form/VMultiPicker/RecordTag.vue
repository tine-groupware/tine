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
import { computed, onBeforeMount, ref, inject } from 'vue'
import { useElementSize } from '@vueuse/core'
import { BPopover } from 'bootstrap-vue-next'

const props = defineProps({
  record: Object,
  truncate: { type: Boolean, default: false },
  maxDisplayLength: { type: Number, default: 30 },
  minDisplayLength: { type: Number, default: 10 },
  id: { type: String, required: true },
  showPopover: { type: Boolean, default: false },
  readOnly: { type: Boolean, default: false }
})

const recordRenderer = inject('recordRenderer')

const emits = defineEmits(['remove'])

const displayTitle = computed(() => {
  const t = title.value || ''
  return props.truncate
    ? t?.length <= props.minDisplayLength ? t : t.substring(0, props.minDisplayLength).trim()
    : t?.length <= props.maxDisplayLength ? t : t.substring(0, props.maxDisplayLength).trim()
})

const title = ref('')

onBeforeMount(async () => {
  let t = props.record ? (recordRenderer ? recordRenderer(props.record, {}) : props.record.getTitle()) : ''
  if (t && typeof t !== 'string') t = await t.asString()
  title.value = t
})

const containerRef = ref()

const { width, height } = useElementSize(containerRef)

const titleLength = computed(() => {
  return title.value.length !== 0 ? Math.min(title.value.length, props.maxDisplayLength) : props.maxDisplayLength
})

defineExpose({
  width, height, titleLength
})

</script>

<template>
  <div ref="containerRef" class="record d-flex" @click.stop :id="`${id}-record-div`">
    <div class="flex-grow-1">
      {{showPopover ? displayTitle : title}}
    </div>
    <div
      v-if="showPopover && title?.length > (truncate ? minDisplayLength : maxDisplayLength)"
      class="tabler-icons-dots"
    />
    <div v-if="!readOnly" class="remove-record ms-1 tabler-icons-cross" @click.stop="emits('remove')"/>
    <BPopover
      v-if="showPopover && title?.length > (truncate ? minDisplayLength : maxDisplayLength)"
      :target="`${id}-record-div`"
      click
      realtime
      placement="top"
      container="body"
      class="bootstrap-scope"
      :delay="0"
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
}

.record:active{
  background-color: #0062a7;
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

.tabler-icons-cross {
  background-image: url(../../../node_modules/@tabler/icons/icons/outline/x.svg) !important;
  width: 13px;
  height: 13px;
  background-repeat: no-repeat;
  background-size: 13px 13px;
  background-position: center !important;
  filter: invert(1) hue-rotate(180deg);
}

</style>
