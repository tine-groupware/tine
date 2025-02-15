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
    <BAlert v-else-if="questionText != null && questionText?.trim?.().length != 0" :variant="props.alertVariant ?? 'info'" :model-value="true">{{questionText}}</BAlert>
    <BFormRadioGroup v-if="!props.allowMultiple" v-model="selectedOption" stacked>
      <BFormRadio v-for="option in _options" :value="option.value" :key="option.value" :disabled="option.disabled">
        <span v-if="option.text.trim().startsWith('<')" v-html="option.text"/>
        <span v-else>{{option.text}}</span>
      </BFormRadio>
    </BFormRadioGroup>
    <BFormCheckboxGroup v-if="props.allowMultiple" v-model="selectedOption" stacked>
      <BFormCheckbox v-for="option in _options" :value="option.value" :key="option.value" :disabled="option.disabled">
        <span v-if="option.text.trim().startsWith('<')" v-html="option.text"/>
        <span v-else>{{option.text}}</span>
      </BFormCheckbox>
    </BFormCheckboxGroup>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  questionText: String,
  options: Object,
  allowMultiple: Boolean,
  allowEmpty: Boolean,
  alertVariant: String // 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'dark',
})

const selectedOption = ref(props.allowMultiple ? props.options.filter(e => e.checked).map(e => e.inputValue) : props.options.find(el => el.checked)?.inputValue)

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
const disableok = computed(() => !props.allowEmpty && (props.allowMultiple ? !selectedOption?.value?.length : !(selectedOption.value || selectedOption.value === 0)))

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
    option = selectedOption.value ?? null
    option = _.find(props.options, { originalOptObj: { name: option } })?.value ?? option
  }
  option = option || option === 0 ? JSON.parse(JSON.stringify(option)) : null
  return option
}

defineExpose({ getValue, disableok })

</script>
