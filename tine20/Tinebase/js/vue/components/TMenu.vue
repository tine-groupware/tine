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
  <BPopover
    :target="target"
    role="dialog"
    aria-modal="true"
    tabindex="0"
    container="body"
    v-model="_visible"
    manual
    :delay="0"
    inline
    :placement="placement"
    :floating-middleware="_floatingMiddleware"
    :id="popoverId"
    :style="`
    z-index: ${zIndex} !important;
    --backgroundColor: ${backgroundColor};
    --darkModeBackgroundColor: ${darkModeBackgroundColor};
    `"
    @keyup.esc="hide"
    class="tmenu"
    @hidden="handleAfterHide"
  >
    <div class="bootstrap-scope" ref="menu" role="menu" @keydown="handleMenuNavigation">
      <slot></slot>
    </div>
  </BPopover>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { offset, shift, size, hide as hideFloatMiddleWare } from '@floating-ui/vue'
import { onClickOutside } from '@vueuse/core'

const props = defineProps({
  target: {},
  visible: { type: Boolean, default: false },
  placement: { type: String, default: 'bottom-end' },
  offset: {
    type: Object,
    default: () => {
      return { mainAxis: 4, crossAxis: 3 }
    }
  },
  padding: { type: Number },

  backgroundColor: { type: String, default: '#F0F0F0' },
  darkModeBackgroundColor: { type: String, default: '#f2f2f2' }
})

const popoverId = computed(() => `${props.target}-menu`)

const _visible = ref(false)

const _floatingMiddleware = computed(() => {
  const arr = [offset({
    mainAxis: props.offset.mainAxis,
    crossAxis: props.offset.crossAxis
  })]
  arr.push(shift())
  arr.push(hideFloatMiddleWare({ padding: props.padding }))
  arr.push(size(
    {
      apply ({ availableWidth, availableHeight, elements }) {
        Object.assign(elements.floating.style, {
          maxWidth: `${availableWidth}px`,
          maxHeight: `${availableHeight}px`
        })
      }
    }
  ))
  return arr
})

const zIndex = ref()
const setZIndex = (index) => {
  zIndex.value = index
}
const hide = (e) => {
  _visible.value = false
  emits('hide', e)
}

const handleAfterHide = () => {
  if (props.target) {
    const targetId = typeof props.target === 'string' ? props.target : props.target?.id
    if (targetId) {
      setTimeout(() => {
        const triggerEl = document.getElementById(targetId)
        if (triggerEl) {
          triggerEl.focus()
        }
      }, 50)
    }
  }
}
const menu = ref()
onClickOutside(menu, hide)
const winMgrProxy = {
  eventManager: window.mitt(),
  setZIndex,
  on: (ev, handler) => {
    winMgrProxy.eventManager.on(ev, handler)
  },
  un: (ev, handler) => {
    winMgrProxy.eventManager.off(ev, handler)
  },
  isVisible: () => {
    return props.visible
  },
  hide,
  setActive: () => {},
  id: 'tmenu-window-proxy'
}

const handleMenuNavigation = (event) => {
  if (!menu.value) return

  const focusableItems = Array.from(
    menu.value.querySelectorAll('[tabindex="0"], li, a, button')
  ).filter(el => el.offsetParent !== null)

  if (focusableItems.length === 0) return

  const activeEl = document.activeElement
  const index = focusableItems.indexOf(activeEl)
  const lastIndex = focusableItems.length - 1

  if (event.key === 'ArrowDown') {
    event.preventDefault()
    event.stopPropagation()
    const nextIndex = (index >= lastIndex || index === -1) ? 0 : index + 1
    focusableItems[nextIndex]?.focus()
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    event.stopPropagation()
    const prevIndex = (index <= 0) ? lastIndex : index - 1
    focusableItems[prevIndex]?.focus()
  }
}

watch(() => props.visible, async (newVal) => {
  _visible.value = newVal

  if (newVal) {
    Ext.WindowMgr.bringToFront(winMgrProxy)

    await nextTick()
    if (menu.value) {
      const firstItem = menu.value.querySelector('[tabindex="0"], li, a, button')
      if (firstItem) {
        firstItem.focus()
      }
    }
  }
}, { immediate: true })

const emits = defineEmits(['hide'])

onMounted(() => Ext.WindowMgr.register(winMgrProxy))
onUnmounted(() => Ext.WindowMgr.unregister(winMgrProxy))
</script>

<style>
.tmenu .popover-body{
  background-color: var(--backgroundColor);
}

.dark-mode .tmenu .popover-body {
  background-color: var(--darkModeBackgroundColor);
}

.tmenu .popover-arrow{
  display: none !important;
}
</style>
