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
  const { default: img } = await import(/* webpackChunkName: "Tinebase/js/[request]" */`images/dialog-personas/${getIconPath(props.iconName)}.svg`)
  skinColor.value = props.skinColor ? props.skinColor : skinShades[Math.floor(Math.random() * skinShades.length)]
  const svg = window.atob(img.split(',')[1])
  rawSvg.value = prefixSvgClasses(svg, `persona-svg-${props.iconName.replace(/[^a-z0-9_-]/gi, '-')}-`)
}

onBeforeMount(async () => {
  await init()
})

function prefixSvgClasses (svg, prefix) {
  const ignoredClasses = ['skin']

  const classNames = new Set()

  svg.replace(/class\s*=\s*["']([^"']+)["']/g, function (match, classValue) {
    classValue.split(/\s+/).filter(Boolean).forEach(function (className) {
      if (!ignoredClasses.includes(className)) {
        classNames.add(className)
      }
    })

    return match
  })

  svg.replace(/\.(-?[_a-zA-Z]+[_a-zA-Z0-9-]*)/g, function (match, className) {
    if (!ignoredClasses.includes(className)) {
      classNames.add(className)
    }

    return match
  })

  Array.from(classNames)
    .sort(function (a, b) {
      return b.length - a.length
    })
    .forEach(function (className) {
      const escapedClassName = className.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      const prefixedClassName = `${prefix}${className}`

      svg = svg.replace(
        new RegExp(`(class\\s*=\\s*["'][^"']*)\\b${escapedClassName}\\b`, 'g'),
        `$1${prefixedClassName}`
      )

      svg = svg.replace(
        new RegExp(`\\.${escapedClassName}\\b`, 'g'),
        `.${prefixedClassName}`
      )
    })

  return svg
}
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
