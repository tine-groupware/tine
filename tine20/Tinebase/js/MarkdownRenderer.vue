<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <tleuschel@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <div class="tb-markdown" v-html="renderedHtml"></div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { escape } from 'lodash'

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
      gfm: true,
      breaks: true
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
    const html = await markedInstance.parse(props.content)
    renderedHtml.value = purify.sanitize(html)
  } catch (error) {
    console.error('Error rendering markdown:', error)
    // never fall back to unsanitized html
    renderedHtml.value = escape(props.content)
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
