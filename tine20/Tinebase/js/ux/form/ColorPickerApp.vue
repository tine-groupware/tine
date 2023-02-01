<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jan Evers <j.evers@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <div>
    <ColorPicker
      theme="light"
      :color="color"
      @changeColor="(value) => {color = value.hex}"
    />
  </div>
</template>

<script setup>
import { ref, onBeforeMount } from 'vue'
import { ColorPicker } from 'vue-color-kit'

const props = defineProps({
  initialColor: { type: String }
})

const color = ref()
onBeforeMount(() => {
  if (props.initialColor) {
    color.value = '#' + props.initialColor
  } else {
    color.value = '#90C0FF'
  }
})

function getColor () {
  return color.value
}
defineExpose({
  getColor
})
</script>

<style>
@import url('vue-color-kit/dist/vue-color-kit.css');
</style>
