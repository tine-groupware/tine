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
    :target="popoverTarget"
    :visible="visibleInternal"
    @hide="emits('hide', $event)"
  >
    <li
      v-for="action in userActionsInternal"
      :key="action.text"
      class="main-menu-item px-3 py-1 d-flex align-items-center pe-4"
      @click="menuItemClicked(action, $event)"
    >
      <div class="main-menu-item__icon" :class="action.iconCls"></div>
      <div class="ms-2">
        {{action.text}}
      </div>
    </li>

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

const emits = defineEmits(['hide'])
const menuItemClicked = (action, e) => {
  action.handler.call(action?.scope)
  emits('hide', e)
}
</script>

<style scoped lang="scss">

.main-menu-item{
  font-size: 12px;
  /*height: 22px;*/
  width: 240px;
  cursor: pointer;

  &__icon{
    width: 20px;
    height: 20px;
  }

  @media (hover: hover) {
    &:hover{
      background-color: #d9d9d9;
      border-left: #a6a6a6 solid 2px;
    }
  }
}
</style>
