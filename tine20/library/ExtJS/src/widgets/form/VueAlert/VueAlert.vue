<template>
  <div class="bootstrap-scope">
    <BAlert :model-value="true" variant="primary"><span v-html="label"/></BAlert>
  </div>
</template>

<script setup>
import {defineProps} from 'vue'

defineProps({
  label: String,
})
</script>
