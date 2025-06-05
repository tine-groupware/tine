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
  <div
    class="bootstrap-scope tine-bar"
    style="height: 100%; width: 100%"
  >
    <div class="h-100 d-flex align-items-center justify-content-between">
      <div class="color d-flex align-items-center cursor-pointer">
        <div class="action_menu application-menu-btn"
             @click.stop="toggleAppMenuVisibility($event)"
             :id="menuIconId"/>
        <TApplicationMenu
          :popoverTarget="menuIconId"
          :visible="appMenuVisible"
          @hide="hideApplicationMenu($event)"
          @itemClicked="activateApp"
          :placement="'bottom-end'"
        />
        <div class="tine-favicon mx-2"/>
        <span class="tine-bar__active-app ms-3">{{activeApp}}</span>
      </div>
      <div class="me-3 d-flex">
        <Component
          v-for="(item, idx) in _barItems"
          :is="item.__component"
          :key="idx"
          :itemCfg="item"
          class="me-3"
        />
        <TAvatar
          class="color"
          @click="toggleMenuVisibility($event)" :id="avatarId"/>
        <TMainMenu
          :popoverTarget="avatarId"
          :visible="mainMenuVisible"
          :mainMenuItems="mainMenuItems"
          @hide="hideMenu($event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import TAvatar from './TAvatar.vue'
import TMainMenu from './TMainMenu.vue'
import { computed, markRaw, ref } from 'vue'
import TApplicationMenu from './TApplicationMenu.vue'
import ExtRenderable from './barItems/ExtRenderable.vue'
import ExtActionMenu from './barItems/ExtActionMenu.vue'
import ExtAction from './barItems/ExtAction.vue'

const props = defineProps({
  parentId: String,
  injectKey: String,

  parentWidth: String,
  parentHeight: String,

  mainMenuItems: { type: Array, default: () => [] },
  barItems: { type: Array, default: () => [] },

  activeApp: String
})

const menuIconId = computed(() => `tine-bar-menu-popover-trigger-${props.parentId}`)

const _barItems = computed(() => {
  let t = _.sortBy(props.barItems, item => {
    return item.registerdItemPos
  })
  t = t.map(item => {
    if (item.render) {
      item.__component = item.__component || markRaw(ExtRenderable)
    } else {
      if (item.initialConfig) {
        if (item.initialConfig.menu) {
          item.__component = item.__component || markRaw(ExtActionMenu)
        } else {
          item.__component = item.__component || markRaw(ExtAction)
        }
      }
    }
    return item
  })
  return t
})

const mainMenuVisible = ref(false)

const activeApp = computed(() => {
  const dockState = Tine.Tinebase.MainScreen.getDock().vueProps.state || null
  if (!dockState) return ''
  const _activeApp = Tine.Tinebase.appMgr.getAll().find(app => app.name === dockState.activeApp)
  return _activeApp ? _activeApp.getTitle() : 'No valid main screen found'
})

const appMenuVisible = ref(false)
const hideApplicationMenu = (e) => {
  appMenuVisible.value = false
  e.__skip_toggle = true
}
const toggleAppMenuVisibility = (e) => {
  if (e.__skip_toggle) return
  appMenuVisible.value = !appMenuVisible.value
}
const activateApp = (appName) => {
  appMenuVisible.value = false
  Tine.Tinebase.MainScreen.getDock().activateApp(appName)
}

const hideMenu = (e) => {
  mainMenuVisible.value = false
  e.__skip_toggle = true
}
const toggleMenuVisibility = (e) => {
  if (e.__skip_toggle) return
  mainMenuVisible.value = !mainMenuVisible.value
}

const avatarId = computed(() => `tine-avatar-main-menu-popover-trigger-${props.parentId}`)
</script>

<style scoped lang="scss">
.tine-bar{
  box-sizing: border-box;
  background-color: var(--focus-color) !important;
  //border-bottom: 1px solid var(--selection-color);

  .dark-mode &{
    //background-color: #ddd !important;
    background-color: var(--selection-color) !important;
    border-bottom: 1px solid var(--selection-color);
  }

  .action_menu{
    background-repeat: no-repeat;
    background-position: center !important;
    background-size: 30px 30px !important;
  }

  .application-menu-btn{
    width: 60px;
    height: 40px;
  }

  .tine-favicon {
    width: 30px;
    height: 30px;
    background-repeat: no-repeat;
    background-size: 30px 30px !important;
    background-position: center !important;
    display: none;
  }

  &__active-app{
    font-size: 18px;
    /*font-weight: bolder;*/
  }
}

.color {
  color: black;
  filter: invert(1);
}

.dark-mode .color{
  filter: invert(0);
}

.cursor-pointer{
  cursor: pointer;
}
</style>
