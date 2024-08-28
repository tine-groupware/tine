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
  >
    <div class="tine-application-menu">
      <div class="container">
        <BFormInput class="mb-3 mt-2" v-model="searchTerm" ref="searchField"/>
        <hr class="my-1">
        <div class="row row-cols-3">
          <div
            v-for="item in menuItemsInternal"
            :key="item.name"
            class="col application-menu-item"
            @click="emits('itemClicked', item.name)"
            tabindex="0"
            role="button"
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
  visibleInternal.value = newVal
  if (newVal) {
    nextTick(() => {
      searchField.value.focus()
    })
  }
}, { immediate: true })

const hide = (e) => {
  visibleInternal.value = false
  searchTerm.value = ''
  emits('hide', e)
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
    }

    &__active{
      // todo
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
