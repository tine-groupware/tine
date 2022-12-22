<template>
  <div>
      <ColorPicker
          theme="light"
          :color="color"
          @changeColor="(value) => {color = value.hex}"
      />
  </div>
</template>

<script setup>
import { ref, onBeforeMount} from 'vue';
import { ColorPicker } from 'vue-color-kit'

const props = defineProps({
initialColor: { type: String}
})

const color = ref();
onBeforeMount(() => {
    console.log('mounted');
    if(props.initialColor){
      color.value = "#"+props.initialColor
    } else {
      color.value = '#90C0FF'
    }
})

function getColor() {
  return color.value;
}
defineExpose({
getColor,
})
</script>

<style>
@import url('vue-color-kit/dist/vue-color-kit.css');
</style>
