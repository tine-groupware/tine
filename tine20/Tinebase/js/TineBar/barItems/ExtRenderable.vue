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
  <div ref="containerRef"/>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const containerRef = ref()

const props = defineProps({
  itemCfg: { type: Object, required: true }
})

onMounted(() => props.itemCfg.render(containerRef.value))
</script>
