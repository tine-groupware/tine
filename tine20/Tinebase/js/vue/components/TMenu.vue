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
    teleport-to="body"
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
    @shown="handleShown"
    @hidden="handleAfterHide"
  >
    <div class="bootstrap-scope" ref="menu" role="menu" tabindex="-1" @keydown="handleMenuNavigation">
      <slot></slot>
    </div>
  </BPopover>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { offset as floatingOffset, shift, size, hide as hideFloatMiddleWare } from '@floating-ui/vue'
import { onClickOutside } from '@vueuse/core'
import { createFocusTrap } from 'focus-trap'

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
  darkModeBackgroundColor: { type: String, default: '#f2f2f2' },

  focusTrapStack: { type: Object, default: null }
})

const popoverId = computed(() => `${props.target}-menu`)

const _visible = ref(false)

const _floatingMiddleware = computed(() => {
  const arr = [floatingOffset({
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
let returnFocusTimer = null
const clearReturnFocusTimer = () => {
  if (returnFocusTimer) {
    clearTimeout(returnFocusTimer)
    returnFocusTimer = null
  }
}
const hide = (e) => {
  clearReturnFocusTimer()
  deactivateTrap()
  _visible.value = false
  emits('hide', e)
}

const handleAfterHide = () => {
  deactivateTrap()
  clearReturnFocusTimer()

  if (props.target) {
    const targetId = typeof props.target === 'string' ? props.target : props.target?.id
    if (targetId) {
      returnFocusTimer = setTimeout(() => {
        returnFocusTimer = null

        if (_visible.value) return

        const triggerEl = document.getElementById(targetId)
        if (
          triggerEl &&
          document.contains(triggerEl) &&
          triggerEl.offsetParent !== null &&
          !triggerEl.disabled
        ) {
          triggerEl.focus()
        }
      }, 50)
    }
  }
}

const menu = ref()
let ft = null

const handleShown = async () => {
  clearReturnFocusTimer()
  await nextTick()
  await activateTrap()

  if (menu.value) {
    const firstItem = menu.value.querySelector('[tabindex="0"], li, a, button')
    if (firstItem) firstItem.focus()
  }
}

const activateTrap = async () => {
  await nextTick()

  deactivateTrap()

  if (!menu.value) return

  ft = createFocusTrap(menu.value, {
    trapStack: props.focusTrapStack,
    escapeDeactivates: false,
    returnFocusOnDeactivate: false,
    allowOutsideClick: true
  })
  try {
    ft.activate()
  } catch (e) {
    const msg = 'Your focus-trap must have at least one container with at least one tabbable node in it at all times'
    if (e.message !== msg) throw e
    else deactivateTrap()
  }
}

const deactivateTrap = () => {
  const trap = ft
  ft = null
  try {
    trap?.deactivate({
      returnFocus: false
    })
  } catch (e) {
    const msg = 'Your focus-trap must have at least one container with at least one tabbable node in it at all times'
    if (e.message !== msg) throw e
  }
}

onClickOutside(menu, hide, {
  ignore: [`#${props.target}`]
})
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

watch(() => props.visible, (newVal) => {
  _visible.value = newVal
  if (newVal) {
    clearReturnFocusTimer()
    Ext.WindowMgr.bringToFront(winMgrProxy)
  } else {
    clearReturnFocusTimer()
    deactivateTrap()
  }
}, { immediate: true })

const emits = defineEmits(['hide'])

onMounted(() => Ext.WindowMgr.register(winMgrProxy))

onUnmounted(() => {
  clearReturnFocusTimer()
  deactivateTrap()
  Ext.WindowMgr.unregister(winMgrProxy)
})

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
