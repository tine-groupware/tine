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
  allowMultiple: Boolean,
  allowEmpty: Boolean
})

const selectedOption = ref(props.options[props.allowMultiple ? 'filter' : 'find'](el => el.checked)?.inputValue)

const _options = computed(() => {
  return props.options.map(el => {
    return {
      value: el.inputValue,
      text: el.boxLabel,
      disabled: el.disabled,
      originalOptObj: el.originalOptObj
    }
  })
})

// onBeforeMount(() => {
//   console.debug('MultiOptionsDialog: Props:', props)
// })
const disableok = computed(() => !props.allowEmpty && (props.allowMultiple ? !selectedOption?.value?.length : !selectedOption.value))

const getValue = () => {
  let option = null
  if (props.allowMultiple) {
    option = selectedOption?.value?.length
      ? props.options.filter((option) => {
        return selectedOption.value.indexOf(option.originalOptObj.name) >= 0
      })
      : null
    option = _.map(option, 'originalOptObj')
  } else {
    option = selectedOption.value ? selectedOption.value : null
    option = _.find(props.options, { originalOptObj: { name: option } })?.value || option
  }
  option = option ? JSON.parse(JSON.stringify(option)) : null
  return option
}

defineExpose({ getValue, disableok })

</script>
