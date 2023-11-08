<script setup>
import { ref, computed, defineProps, defineEmits, watch } from 'vue'
const props = defineProps({
  modelValue: {
    type: String
  },
  locale: {
    type: String,
    default: window.navigator.language
  }
})

const emit = defineEmits(['update:modelValue'])
const _date = ref(props.modelValue);
const dayVal = computed(() => {
  const d = new Date(_date.value)
  if (d.toString() === "Invalid Date") {
    return "ddd"
  } else {
    return d.toLocaleString(props.locale, { weekday: 'short' })
  }
})

watch(_date, (newVal) => {
  emit('update:modelValue', newVal)
})
watch(() => props.modelValue, (newVal) => {
  _date.value = newVal
})
</script>

<template>
  <div class="CustomDatePicker">
    <span class="CustomDatePicker__day-span">{{ dayVal }},</span>
    <input type="date" class="CustomDatePicker__date-selector" v-model="_date">
  </div>
</template>

<style scoped lang="scss">
.CustomDatePicker {
  display: inline-flex;
  border: 1px black solid;
  border-radius: 0.4em;
  width: 100%;
  justify-content: center;
  padding: 0.5em 0;
  &__day-span{
    min-width: 40px;
  }

  &__date-selector {
    outline: none;
    border: none;

    &:focus {
      outline: none;
    }
  }
}
</style>

