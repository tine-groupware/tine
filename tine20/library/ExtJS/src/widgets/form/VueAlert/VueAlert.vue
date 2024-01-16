<template>
  <div class="bootstrap-scope">
    <BAlert :model-value="true" variant="primary" class="h-25">{{label}}</BAlert>
  </div>
</template>

<script setup>
import {defineProps} from 'vue'

defineProps({
  label: String,
})
</script>
