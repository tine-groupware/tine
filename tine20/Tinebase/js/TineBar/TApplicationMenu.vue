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
    :placement="placement"
    @hide="hide($event)"
    @keydown.esc="hide($event)"
    @keydown.enter="selectAppInFocus($event)"
    @keydown.up.down.left.right.stop="moveFocusAround($event)"
  >
    <div class="tine-application-menu">
      <div class="container">
        <BFormInput class="mb-3 mt-2" v-model="searchTerm" ref="searchField"/>
        <hr class="my-1">
        <div class="row row-cols-3">
          <div
            v-for="(item, idx) in menuItemsInternal"
            :key="item.name"
            class="col application-menu-item"
            role="button"
            @click="emits('itemClicked', item.name)"
            :class="{
                'application-menu-item__active': appInFocus === idx,
              }"
          >
            <div class="d-flex align-items-center flex-column p-2 cursor-pointer">
              <div class="application-menu-item__icon" :class="item.iconCls"/>
              <div class="application-menu-item__text text-nowrap">{{item.text}}</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </TMenu>
</template>

<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import TMenu from '../vue/components/TMenu.vue'

const props = defineProps({
  popoverTarget: { type: String, required: true },
  visible: { type: Boolean, default: false },
  placement: { type: String, default: 'right-start' }
})

const searchTerm = ref('')

const availableApps = computed(() => {
  const APPLICATION_MENU_ID_PREFIX = 'tine-application-menu'
  const appItems = []
  Tine.Tinebase.appMgr.getAll().each(function (app) {
    if (Tine.Tinebase.common.hasRight('mainscreen', app.appName) && app.hasMainScreen) {
      appItems.push({
        text: app.getTitle(),
        iconCls: app.getIconCls(),
        name: app.name,
        id: `${APPLICATION_MENU_ID_PREFIX}-${app.name.toLowerCase().replace(' ', '-')}`
      })
    }
  }, this)

  return _.sortBy(appItems, 'text')
})

const menuItemsInternal = computed(() => {
  if (searchTerm.value === '') {
    return availableApps.value
  } else {
    return _.filter(availableApps.value, (item) => {
      return item.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        item.text.toLowerCase().includes(searchTerm.value.toLowerCase())
    })
  }
})

const emits = defineEmits(['hide', 'itemClicked'])

const visibleInternal = ref(false)

const searchField = ref()
watch(() => props.visible, newVal => {
  if (newVal) {
    appInFocus.value = 0
    searchTerm.value = ''
    nextTick(() => {
      searchField.value.focus()
    })
  }
  visibleInternal.value = newVal
}, { immediate: true })

const hide = (e) => {
  visibleInternal.value = false
  emits('hide', e)
}

const appInFocus = ref(0)

watch(menuItemsInternal, (newVal) => {
  if (appInFocus.value > newVal.length) appInFocus.value = newVal.length - 1
})

const selectAppInFocus = (e) => {
  const app = menuItemsInternal.value[appInFocus.value]
  emits('itemClicked', app.name)
  hide(e)
}

const moveFocusAround = (e) => {
  function moveFocusToIdx (idx) {
    if (idx < 0 || idx > menuItemsInternal.value.length - 1) return
    appInFocus.value = idx
  }

  switch (e.key) {
    case 'ArrowLeft':
      moveFocusToIdx(appInFocus.value - 1)
      break
    case 'ArrowRight':
      moveFocusToIdx(appInFocus.value + 1)
      break
    case 'ArrowUp':
      moveFocusToIdx(appInFocus.value - 3)
      break
    case 'ArrowDown':
      moveFocusToIdx(appInFocus.value + 3)
      break
    default:
  }
}

</script>

<style scoped lang="scss">
.tine-application-menu {
  width: 320px;
  height: 100%;
  font-size: 11px !important;

  .application-menu-item{

    &__icon{
      width: 40px;
      height: 40px;
      background-repeat: no-repeat !important;
      background-position: center !important;
      background-size: 40px 40px !important;
    }

    &__active{
      background-color: var(--selection-color);
    }

    @media (hover: hover){
      &:hover{
        background-color: var(--selection-color);
      }
    }

    &:active{
      background-color: var(--selection-color) !important;
    }
  }
}

.cursor-pointer{
  cursor: pointer;
}
</style>
