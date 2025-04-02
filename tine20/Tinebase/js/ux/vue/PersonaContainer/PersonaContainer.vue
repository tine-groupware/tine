<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <s.deshar@metaways.de>
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <div v-html="rawSvg" class="vue-msg-box-svg-container" :style="`--skin-color: ${skinColor}`"></div>
</template>

<script setup>
import { onBeforeMount, ref } from 'vue'

import getIconPath from './helpers'

const props = defineProps({
  iconName: {
    type: String,
    required: true
  },
  skinColor: {
    type: String,
    required: false
  }
})

const rawSvg = ref(null)
const skinColor = ref(null)

const skinShades = ['#ffffff', '#fad9b4', '#fcbf89', '#ec8f2e', '#d97103', '#b75b01', '#924500']
const init = async function () {
  const { default: img } = await import(/* webpackChunkName: "Tinebase/js/[request]" */`../../../../../images/dialog-personas/${getIconPath(props.iconName)}.svg`)
  skinColor.value = props.skinColor ? props.skinColor : skinShades[Math.floor(Math.random() * skinShades.length)]
  rawSvg.value = window.atob(img.split(',')[1])
}

onBeforeMount(async () => {
  await init()
})
</script>

<style lang="scss">
  .vue-msg-box-svg-container {
    svg{
      height: 100%;
      width: auto;
    }
    .skin {
      fill: var(--skin-color) !important;
    }
  }
</style>
