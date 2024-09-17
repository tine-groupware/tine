<template>
  <div class="bootstrap-scope">
    <BAlert :model-value="true" v-bind:variant="variant"><span v-html="label"/></BAlert>
  </div>
</template>

<script setup>
import {defineProps} from 'vue'

defineProps({
  label: String,
  variant: String,
})
</script>
