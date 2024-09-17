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
    class="bootstrap-scope"
    style="height:100%;width:100%"
    @click.left.stop.prevent
    @click.right.stop.prevent
    @drop.stop
    @dragstart.stop
  >
    <div class="w-100 h-100 tine-dock" ref="tineDockRef">
      <div class="docked-app-container" ref="dockContainerRef">
        <div
          v-for="(app, idx) in dockedAppsInternal"
          :key="app.name"
          :class="{
          'dragged-item': idx === draggedIdx
          }"
          :id="app.id"
          draggable="true"
          @dragstart.stop="dragStart(idx, $event)"
          @dragenter.prevent
          @dragover.prevent.stop
          @dragend="dragEnd"
          @drop.stop="dragFinish(idx, app.id, $event)"
          @click.right.prevent="activateContextMenu(app)"
          @click.left.prevent="activateApp(app.name)"
        >
          <div
            class="dock-item d-flex align-items-center flex-column"
            ref="appRef"
            :class="{
            'dock-item__active': app.name === state.activeApp,
            }"
          >
            <div :class="app.iconCls" class="h-100 w-100 dock-item__icon"/>
            <span class="dock-item__label">{{getTitle(app.text)}}</span>
          </div>
        </div>
      </div>
      <div class="arrow arrow__up" v-if="isOverflowing && !arrivedState.top" @click="y -= 100"/>
      <div class="arrow arrow__down" v-if="isOverflowing && !arrivedState.bottom" @click="y += 100"/>
      <TMenu
        :visible="ctxMenuVisible"
        :target="ctxMenuTarget?.id"
        :placement="'right-start'"
        @hide="hideCtxMenu"
      >
        <div class="context-container">
          <div
            class="ctx-option ps-1"
            @click="removeApp(ctxMenuTarget?.name)"
            :class="{'ctx-option__disabled': ctxMenuTarget?.name === state.activeApp}"
          >
            {{ window.i18n._('Remove') }}
          </div>
        </div>
      </TMenu>
    </div>
  </div>
</template>

<script setup>
/* eslint-disable vue/no-mutating-props */
/* eslint-disable */
import {computed, ref, watch, inject, onMounted, onUpdated} from 'vue'
import {useScroll, useWindowSize} from '@vueuse/core'
import TMenu from "../vue/components/TMenu.vue";

const props = defineProps({
  parentId: { type: String, required: true },
  injectKey: { type: String },
  parentWidth: { type: String, default: '50px' },
  state: { type: Object }
})

const eventBus = inject(props.injectKey)
watch(() => props.state, () => {
  eventBus.emit('syncState')
}, { deep: true })

const activateApp = (appName) => {
  const app = Tine.Tinebase.appMgr.get(appName)
  Tine.Tinebase.MainScreen.activate(app)
  menuVisible.value = false
  props.state.activeApp = appName
  if (_.indexOf(props.state.dockedApps, appName) === -1) {
    props.state.dockedApps.push(appName)
  }
}

/* Tine Menu */
const menuVisible = ref(false)

/* Docked App Stuff */
const DOCKED_APP_ID_PREFIX = 'tine-docked-app'
const availableApps = computed(() => {
  const appItems = []
  Tine.Tinebase.appMgr.getAll().each(function (app) {
    if (Tine.Tinebase.common.hasRight('mainscreen', app.appName) && app.hasMainScreen) {
      appItems.push({
        text: app.getTitle(),
        iconCls: app.getIconCls(),
        name: app.name,
        id: `${DOCKED_APP_ID_PREFIX}-${app.name.toLowerCase().replace(' ', '-')}`
      })
    }
  }, this)

  return _.sortBy(appItems, 'text')
})

const dockedAppsInternal = computed(() => {
  return props.state.dockedApps
    ? props.state.dockedApps.map(appName => {
      return _.find(availableApps.value, availableApp => {
        return availableApp.name === appName
      })
    })
    : []
})

const getTitle = (appTitle) => {
  if (!appTitle) return null
  const MAX_LABEL_LENGTH = 8
  return appTitle.length > MAX_LABEL_LENGTH
    ? appTitle.toUpperCase().slice(0, MAX_LABEL_LENGTH).trim().concat('...')
    : appTitle.toUpperCase()
}

const ctxMenuTarget = ref(null)
const ctxMenuVisible = ref(false)
const hideCtxMenu = () => {
  ctxMenuVisible.value = false
  ctxMenuTarget.value = ''
}

const activateContextMenu = (app) => {
  if (ctxMenuTarget.value?.name === app.name) {
    hideCtxMenu()
  } else {
    ctxMenuTarget.value = app
    ctxMenuVisible.value = true
  }
}
const removeApp = (appName) => {
  hideCtxMenu()
  props.state.dockedApps.splice(props.state.dockedApps.indexOf(appName), 1)
}

/* Drag and drop stuff */
const draggedIdx = ref(null)
const DROPPABLE_ID_PREFIX = DOCKED_APP_ID_PREFIX

const dragStart = (idx, e) => {
  e.dataTransfer.setData('text/plain', idx)
  e.dataTransfer.effectAllowed = 'move'
  draggedIdx.value = idx
}

const dragFinish = (idx, appId, e) => {
  if (!appId.startsWith(DROPPABLE_ID_PREFIX)) return
  props.state.dockedApps.splice(idx, 0, props.state.dockedApps.splice(draggedIdx.value, 1)[0])
}

const dragEnd = () => {
  draggedIdx.value = null
}

// hiding the dock for small screen devices
const { width } = useWindowSize()
onMounted(() => {
  const t = Tine.Tinebase.MainScreen.getDock()
  if(width.value <= 500){
    t.hide()
    Tine.Tinebase.MainScreen.doLayout()
  }
})

watch(width, (value) => {
  const t = Tine.Tinebase.MainScreen.getDock()
  if(value <= 500){
    t.hide()
    Tine.Tinebase.MainScreen.doLayout()
  } else {
    t.show()
    Tine.Tinebase.MainScreen.doLayout()
  }
})

// TODO: fixme: arrows showing only after user interaction with the dock
const tineDockRef = ref()
const dockContainerRef = ref()

const isOverflowing = ref()
const updateOverflowing = () => {
  isOverflowing.value = dockContainerRef.value?.scrollHeight > tineDockRef.value?.clientHeight
}

onUpdated(() => {
  updateOverflowing()
})

const { y, arrivedState } = useScroll(tineDockRef, { behavior: 'smooth' })
</script>

<style scoped lang="scss">
.tine-dock {
  background-color: #F0F0F0;
  border-right: 1px solid var(--selection-color);

  -ms-overflow-style: none;  /* Internet Explorer and Edge */
  scrollbar-width: none;  /* Firefox */

  /* Hide the scrollbar for Chrome, Safari and Opera */
  &::-webkit-scrollbar {
    width: 0;
  }

  overflow-y: scroll;

  .dark-mode &{
    //background-color: #ffffff;
    //filter: brightness(0.8);
    //border-right: 1px #d9d9d9 solid !important;
    ////background-color: #d5d5d5;
    //background-color: #d5d5d5;
    //border-right: 1px #d9d9d9 solid !important;
    background-color: #cccccc;
    border-right: 2px #e3e3e3 solid !important;
  }

  .arrow{
    height: 30px;
    width: 100%;
    cursor: pointer;

    &__up{
      background: url('../../../images/icon-set/icon_arrow_up.svg'), #f0f0f0 /*, linear-gradient(#f0f0f0 80%, transparent)*/;
      background-repeat: no-repeat;
      background-size: 20px 20px;
      background-position: center !important;
      position: absolute;
      top: 0;
    }

    &__down{
      background: url('../../../images/icon-set/icon_arrow_down.svg'), #f0f0f0 /* linear-gradient(transparent 20%, #f0f0f0)*/;
      background-repeat: no-repeat;
      background-size: 20px 20px;
      background-position: center !important;
      position: absolute;
      bottom: 0;
    }
  }

  .docked-app-container{
    width: 100%;
    position: relative;

    .dock-item{
      width: 100%;
      height: 45px;
      cursor: pointer;
      padding-bottom: 3px;

      @media (hover: hover){
        &:hover {
          background-color: var(--selection-color);
        }
      }

      &:active, &__active{
        background-color: var(--selection-color);
      }

      &__icon{
        width: 100%;
        height: 100%;
        background-repeat: no-repeat;
        background-size: 30px 30px;
        background-position: center !important;
      }

      &__label{
        font-size: 8px;
      }
    }

    .dragged-item{
      opacity: 0.5;
      cursor: pointer;
    }
  }
}

.context-container{
  width: 100px;

  .ctx-option {
    cursor: pointer;
    border-radius: 2px;

    @media (hover: hover){
      &:hover {
        background-color: var(--selection-color);
      }
    }

    &__disabled{
      pointer-events: none;
      opacity: 0.5;
    }
  }
}

</style>
