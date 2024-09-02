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
  <div>
    <div
      ref="itemContainerRef"
      @click="visible = true"
      class="tine-bar-item color-scheme-selector"
      :class="config.iconCls"
      :id="popoverTarget"
    />
<!--    TODO: fixme-->
<!--    <ExtAction-->
<!--      :item-cfg="itemCfg"-->
<!--      :id="popoverTarget"-->
<!--      @click="visible = true"-->
<!--    />-->
    <TMenu
      :target="popoverTarget"
      :visible="visible"
      :placement="'bottom-start'"
      @hide="hideMenu"
      class="color-scheme-selector__menu"
    >
      <ul class="p-0 m-0">
        <li
          v-for="action in config.menu"
          :key="action.text"
          class="action-menu-item px-3 d-flex align-items-center pe-5"
          @click="menuItemClicked(action, $event)"
        >
          <div class="action-menu-item__icon d-flex align-items-center">
            <div v-if="action._name === config.getActiveColorScheme()" class="action-menu-item__icon__selected"/>
          </div>
          <span class="ms-1">
            {{window.i18n._(action.text)}}
          </span>
        </li>
      </ul>
    </TMenu>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import TMenu from '../../vue/components/TMenu.vue'
// import ExtAction from './ExtAction.vue'

const props = defineProps({
  itemCfg: { type: Object, required: true }
})

const config = computed(() => props.itemCfg.initialConfig)

const visible = ref()

const popoverTarget = computed(() => {
  const PREFIX = 'tine-bar-item'
  return `${PREFIX}-${config.value.itemId}`
})

const menuItemClicked = (action, ev) => {
  hideMenu(ev)
  action.checkHandler()
}

const hideMenu = (e) => {
  visible.value = false
  e.stopPropagation()
}
</script>

<style>
.color-scheme-selector__menu .popover-body{
  /*padding: 0*/
}
</style>

<style scoped lang="scss">
.action-menu-item{
  cursor: pointer;
  font-size: 15px;
  //height: 22px;
  width: 170px;

  &:hover, &__active{
    background-color: #d9d9d9;
    border-left: #a6a6a6 solid 2px;
  }

  &__icon{
    width: 14px;
    height: 14px;

    &__selected{
      width: 9px !important;
      height: 9px !important;
      border-radius: 50%;
      background-color: #1A4D8F;
    }
  }

  &__check-box-unchecked{
    width: 14px;
    height: 14px;
    background-image: url('../../../../images/icon-set/icon_check-box.svg');
    background-repeat: no-repeat;
    background-position: center;
    background-size: 14px 14px;
  }
}
</style>

<style scoped lang="scss">
//TODO: fixme
.tine-bar-item{
  /* margin-top: 2px;*/
  width: 30px;
  height: 30px;
  filter: invert(1);
  cursor: pointer;

  .dark-mode & {
    filter: invert(0);
  }
}
</style>
