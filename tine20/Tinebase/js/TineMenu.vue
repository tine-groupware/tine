<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <sdeshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<template>
  <BPopover
    :target="popoverTarget"
    container="body"
    v-model="visibleInternal"
    manual
    :delay="0"
    inline
    :placement="'bottom-start'"
    :floating-middleware="floatingMiddleware"
    :id="'tine-app-menu'"
    :style="`--tine-app-menu-z-index: ${zIndex}`"
  >
    <div class="bootstrap-scope menu-container" ref="menu">
      <div class="d-flex flex-wrap align-content-start justify-content-evenly mt-2">
        <div
          v-for="item in menuItems"
          :key="item.text"
          class="menu-item d-flex align-items-center flex-column mb-2 cursor-pointer"
          :class="{'menu-item-active': item.text === activeApp }"
          @click="itemClicked(item)"
        >
          <div class="icon-container" :class="item.iconCls"/>
          <div>{{item.text}}</div>
        </div>
      </div>
    </div>
  </BPopover>
</template>

<script setup>
/* eslint-disable */
import {computed, inject, onBeforeMount, ref, watch} from "vue";

import { hide , offset, shift, size } from "@floating-ui/vue"

import { onClickOutside } from "@vueuse/core";

const props = defineProps({
  popoverTarget: { type: String, required: true },
  menuItems: { type: Object },
  injectKey: { type: String },
  visible: { type: Boolean, default: false },
  activeApp: { type: String, default: null },
  zIndex: { type: Number, default: 15000 },
})

const visibleInternal = ref(false)

watch(() => props.visible, newVal => {
  visibleInternal.value = newVal
})

const floatingMiddleware = computed(() => {
  const arr = [offset({crossAxis: 5})]
  arr.push(shift())
  arr.push(hide({padding: 10}))
  arr.push(size(
    {
      apply({availableWidth, availableHeight, elements}) {
        Object.assign(elements.floating.style, {
          width: `350px`,
          maxWidth: `${availableWidth}px`,
          maxHeight: `${availableHeight}px`,
        });
    },
    }
  ))
  return arr
})

const EventBus = inject(props.injectKey)

const menu = ref()
const _hide = () => {
  visibleInternal.value = false
  EventBus.emit('hide')
}
onClickOutside(menu, _hide)

const itemClicked = (item) => {
  item.handler.call()
  _hide()
}

onBeforeMount(() => {
  console.log(props)
})
</script>


<style lang="scss">
#tine-app-menu {
  z-index: var(--tine-app-menu-z-index) !important;

  .popover-arrow{
    display: none;
  }

  .icon-container {
    width: 40px;
    height: 40px;
  }

  .menu-item {
    width: 100px;
    padding: 3px;
  }

  .menu-item:hover{
    background-color: var(--selection-color);
  }

  .menu-item:active{
    background-color: var(--tab-color);
  }

  /* todo */
  .menu-item-active{
  }

  .cursor-pointer{
    cursor: pointer;
  }
}
</style>
