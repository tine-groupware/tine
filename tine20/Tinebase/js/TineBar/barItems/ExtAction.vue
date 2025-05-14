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
  <div class="d-flex align-items-center">
    <div ref="itemContainerRef" v-if="config.iconCls" :class="config.iconCls" @click="config?.handler" class="action-bar-item"/>
    <a class="action-bar-item__text" v-if="itemCfg._showText || config.showText" @click="config?.handler">
      {{config.text}}
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  itemCfg: { type: Object, required: true }
})

const config = computed(() => props.itemCfg.initialConfig)
</script>

<style scoped lang="scss">
.action-bar-item{
  /* margin-top: 2px;*/
  width: 30px;
  height: 30px;
  filter: invert(1);
  cursor: pointer;

  .dark-mode & {
    filter: invert(0);
  }
}

.action-bar-item__text{
  cursor: pointer;
  font-size: 13px;
  color: black;

  .dark-mode &{
    filter: invert(1);
  }

  &:hover{
    color: black;
    border-bottom: transparent 3px solid;
  }
}
</style>
