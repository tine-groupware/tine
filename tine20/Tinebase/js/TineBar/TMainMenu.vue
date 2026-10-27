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
    @hide="hideMenuEmit($event)"
    tabindex="0"
    class="t-main-menu"
  >
    <!-- bootstrap dropdown (as in navbars) - the popover only positions it -->
    <ul role="menu" class="main-menu dropdown-menu show">
    <li
      v-for="action in userActionsInternal"
      role="menuitem"
      :key="action.text"
      class="main-menu-item dropdown-item d-flex align-items-center gap-2"
      @click="menuItemClicked(action, $event)"
      @keydown.enter="menuItemClicked(action, $event)"
      tabindex="0"
      @keydown.esc="hideMenu"
    >
      <div class="main-menu-item__icon" :class="action.iconCls"></div>
      <div class="main-menu-item__text" :title="action.text">
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

const hideMenuEmit = (e) => {
  if (!visibleInternal.value) return
  emits('hide', e)
}
</script>

<style scoped lang="scss">

// the popover positions the menu - the dropdown is in its flow and grows with the longest item (product name
// may vary), the popover limits it to the available width
.main-menu.dropdown-menu{
  position: static;
  margin: 0;
  border: 0;
}

.main-menu-item{
  cursor: pointer;

  &__icon{
    flex: none;
    width: 20px;
    height: 20px;
  }

  &__text{
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}
</style>

<style lang="scss">
// the popover is just the frame of the dropdown - take over its look
// NOTE: the --bs-dropdown-* vars are defined on .dropdown-menu only, these are their defaults
.tmenu.t-main-menu{
  --bs-popover-border-color: var(--bs-border-color-translucent);
  --bs-popover-border-radius: var(--bs-border-radius);
  // max-width (available width) includes the border
  box-sizing: border-box;

  .popover-body{
    padding: 0;
  }
}
</style>
