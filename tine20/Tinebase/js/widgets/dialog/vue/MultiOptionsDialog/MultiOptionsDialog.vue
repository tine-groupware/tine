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
    <div v-if="questionText?.trim?.().startsWith('<')" v-html="questionText" class="mb-3"></div>
    <p v-else>{{questionText}}</p>
    <BFormRadioGroup v-if="!props.allowMultiple" v-model="selectedOption" :options="_options" stacked/>
    <BFormCheckboxGroup v-if="props.allowMultiple" v-model="selectedOption" :options="_options" stacked/>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  questionText: String,
  options: Object,
  allowMultiple: Boolean
})

const selectedOption = ref(props.options.find(el => el.checked)?.inputValue)

const _options = computed(() => {
  return props.options.map(el => {
    return {
      value: el.inputValue,
      text: el.boxLabel,
      disabled: el.disabled
    }
  })
})

// onBeforeMount(() => {
//   console.debug('MultiOptionsDialog: Props:', props)
// })
const disableok = computed(() => !selectedOption.value)

const getValue = () => {
  return selectedOption.value
}

defineExpose({ getValue, disableok })

</script>
