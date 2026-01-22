<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <tleuschel@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<!-- components/MarkdownRenderer.vue -->
<template>
  <div class="tb-markdown" v-html="renderedHtml"></div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  content: {
    type: String,
    default: ''
  }
})

const renderedHtml = ref('')

let marked
let DOMPurify

const getMarked = async () => {
  if (!marked) {
    const markedModule = await import('marked')
    marked = markedModule.marked || markedModule.default || markedModule
    marked.use({
      pedantic: false,
      gfm: true
    })
  }
  return marked
}

const getDOMPurify = async () => {
  if (!DOMPurify) {
    DOMPurify = (await import('dompurify')).default
  }
  return DOMPurify
}

const renderMarkdown = async () => {
  if (!props.content) {
    renderedHtml.value = ''
    return
  }

  try {
    const markedInstance = await getMarked()
    const purify = await getDOMPurify()

    // marked.parse() returns a Promise, so we need to await it
    const html = await markedInstance.parse(props.content)
    renderedHtml.value = purify.sanitize(html)
  } catch (error) {
    console.error('Error rendering markdown:', error)
    renderedHtml.value = props.content // Fallback to plain text
  }
}

onMounted(() => {
  renderMarkdown()
})

watch(() => props.content, () => {
  renderMarkdown()
})
</script>

<style scoped lang="scss">

</style>
