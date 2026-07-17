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
  <TMenu
    ref="itemContainerRef"
    :target="popoverTarget"
    :visible="visibleInternal"
    @hide="emits('hide', $event)"
    tabindex="0"
  >
    <ul role="menu" class="p-0 m-0" style="list-style: none;">
    <li
      v-for="action in userActionsInternal"
      role="menuitem"
      :key="action.text"
      class="main-menu-item px-3 py-1 d-flex align-items-center pe-5"
      @click="menuItemClicked(action, $event)"
      @keydown.enter="menuItemClicked(action, $event)"
      tabindex="0"
      @keydown.esc="hideMenu"
    >
      <div class="main-menu-item__icon" :class="action.iconCls"></div>
      <div class="ms-2">
        {{action.text}}
      </div>
    </li>
    </ul>
  </TMenu>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import TMenu from '../vue/components/TMenu.vue'

const props = defineProps({
  popoverTarget: { type: String, required: true },
  mainMenuItems: { type: Array, default: () => [] },
  visible: { type: Boolean, default: false }
})

const userActionsInternal = computed(() => {
  return props.mainMenuItems.map(action => action.initialConfig)
})

const visibleInternal = ref(false)

watch(() => props.visible, newVal => {
  visibleInternal.value = newVal
}, { immediate: true })

const itemContainerRef = ref(null)

const emits = defineEmits(['hide'])
const menuItemClicked = (action, e) => {
  action.handler.call(action?.scope)
  emits('hide', e)
}

const menuItemsInternal = computed(() => {
  return props.mainMenuItems.value
})

const appInFocus = ref(0)

watch(menuItemsInternal, (newVal) => {
  if (appInFocus.value > newVal.length) appInFocus.value = newVal.length - 1
})

const hideMenu = (e) => {
  if (e) {
    e.stopPropagation()
    e.preventDefault()
  }
  emits('hide', e)
}
</script>

<style scoped lang="scss">

.main-menu-item{
  font-size: 15px;
  /*height: 22px;*/
  width: 320px;
  cursor: pointer;

  &__icon{
    width: 20px;
    height: 20px;
  }

  &:focus{
    background-color: #d9d9d9;
    border-left: #a6a6a6 solid 2px;
  }

  @media (hover: hover) {
    &:hover{
      background-color: #d9d9d9;
      border-left: #a6a6a6 solid 2px;
    }
  }
}
</style>
