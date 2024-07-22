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

const props = defineProps({
  record: Object,
  truncate: { type: Boolean, default: false },
  maxDisplayLength: { type: Number, default: 30 },
  minDisplayLength: { type: Number, default: 10 }
})

const recordRenderer = inject('recordRenderer')

const emits = defineEmits(['remove'])

const displayTitle = computed(() => {
  const t = title.value
  return props.truncate
    ? t?.length < props.minDisplayLength ? t : t.substring(0, props.minDisplayLength).trim().concat('...')
    : t?.length < props.maxDisplayLength ? t : t.substring(0, props.maxDisplayLength).trim().concat('...')
})

const title = ref('')

onBeforeMount(async () => {
  let t = props.record ? (recordRenderer ? recordRenderer(props.record, {}) : props.record.getTitle()) : ''
  if (typeof t !== 'string') t = await t.asString()
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
  <div ref="containerRef" class="record d-flex text-nowrap" @click.stop>
    <div class="flex-grow-1">{{displayTitle}}&nbsp;&nbsp;</div>
    <div class="remove-record" @click.stop="emits('remove')">X</div>
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
}

.record:active{
  background-color: #0062a7;
}

</style>
