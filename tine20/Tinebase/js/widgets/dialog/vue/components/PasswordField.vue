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
  <div class="mb-2">
    <label :for="id" class="mb-1" v-if="label">{{label}}</label>
    <BInputGroup>
      <BFormInput
        :type="inputType"
        :id="id"
        ref="inputRef"
        @keypress="onKeypress"
        @keydown="onKeydown"
        @paste="onPaste"
        v-model="shownValue"
        :class="computedBstpInputFieldClass"
        :autocomplete="autocomplete"
        :disabled="disabled"
        :tabindex="tabindex"
        :state="validationState"
        :name="name"
      />
      <BInputGroupAppend v-if="!disabled">
        <BInputGroupText
          v-if="unLockable"
          @click="handlePWToggle"
          variant="outline-primary"
          :title="window.formatMessage('Cleartext/Hidden')"
        >
          <img v-if="_locked"
               :class="{'dark-reverse': darkReverse}"
               src="images/icon-set/icon_preview.svg"
               alt="show password"
               :style="{width: '1.5em'}"
               class="enlarge-on-hover shrink-on-click">
          <img v-else
               :class="{'dark-reverse': darkReverse}"
               src="images/icon-set/icon_preview_disabled.svg"
               alt="hide password"
               :style="{width: '1.5em'}"
               class="enlarge-on-hover shrink-on-click">
        </BInputGroupText>
        <BInputGroupText
          v-if="clipboard"
          @click="handleCopy"
          :title="window.formatMessage('Copy to Clipboard')"
        >
          <img
            :class="{'dark-reverse': darkReverse}"
            src="images/icon-set/icon_copy_to_clipboard.svg"
            alt="copy-to-clipboard" :style="{width: '1.4em'}"
            class="enlarge-on-hover shrink-on-click">
        </BInputGroupText>
      </BInputGroupAppend>
    </BInputGroup>
  </div>
</template>

<script setup>
/* eslint-disable */
import {
  computed, nextTick,
  onBeforeMount, ref, watch, defineModel, watchEffect
} from 'vue'
import {BInputGroup} from "bootstrap-vue-next";

const props = defineProps({
  modelValue: String,
  id: String,
  label: { type: String, default: null },
  unLockable: { type: Boolean, default: true },
  locked: { type: Boolean, default: true },
  clipboard: { type: Boolean, default: true },
  allowBrowserPasswordManager: { type: Boolean, default: false },
  enableKeyEvents: { type: Boolean, default: false },

  editable: { type: Boolean, default: true },
  readOnly: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  autocomplete: { type: String, default: "off" },

  revealPasswordFn: { type: Function, default: null },

  bstpInputFieldClass: { type: String, default: '' },

  // default input attributes
  tabindex: { type: String, default: null },
  name: { type: String, default: null },

  darkReverse: { type: Boolean, default: true }, // dumb-fix @fixme: something more elegant
})

const emit = defineEmits(['keypress', 'keydown', 'paste', 'update:modelValue'])

const _modelValue = ref(props.modelValue)
watch(_modelValue, (eV) => emit('update:modelValue', eV))

const onKeypress = (e) => {
  emit('keypress', e)
  transformInput(e)
}

const onKeydown = (e) => {
  emit('keydown', e)
  transformInput(e)
}

const onPaste = (e) => {
  emit('paste', e)
  transformInput(e)
}

const computedBstpInputFieldClass = computed(() => {
  return {
    [`${props.bstpInputFieldClass}`]: true
  }
})

const inputRef = ref()

const inputType = ref('text')

const actualValue = ref('')
const shownValue = ref(props.modelValue)

const revealedPassword = ref('')

const _locked = ref()

const hiddenPasswordChr = '●'

const validationState = ref(null)

const setValidationState = (val) => {
  validationState.value = val
}

const init = () => {
  inputType.value = (props.locked && props.allowBrowserPasswordManager) ? 'password' : 'text'
  _locked.value = props.locked
}

// watch([shownValue, actualValue], ([sv, av]) => {
//   console.warn('sv', sv)
//   console.warn('av', av)
// })
const getValue = () => _modelValue.value

watchEffect(() => {
  if(inputType.value === 'password') _modelValue.value = shownValue.value
  else _modelValue.value = _locked.value ? actualValue.value : shownValue.value
})
const setValue = (val) => {
  if (!props.allowBrowserPasswordManager) {
    actualValue.value = val
    shownValue.value = _locked.value ? hiddenPasswordChr.repeat(props.record?.id ? 8 : actualValue.value.length) : val
  } else {
    actualValue.value = val
  }
}

const selectTextTimeout = ref()
const selectText = (start, end) => {
  const s = start || 0
  const e = end || actualValue.value.length
  inputRef.value.$el.setSelectionRange(s, e)
}
const transformInput = (e) => {
  // console.debug('Ignore this!')
  if (!props.allowBrowserPasswordManager) {
    e = e.browserEvent || e
    if (!_locked.value || !props.editable) return
    if (e.type === 'keydown' && e.keyCode === 229) return _.defer(_.bind(transformIMEInput, this, e))
    if (e.type === 'keydown' && _.indexOf([8 /* BACKSPACE */, 46/* DELETE */], e.keyCode) < 0) return
    if (e.type === 'keypress' && e.metaKey /* APPLE CMD */ && e.keyCode === 118 /* v */) return
    if (e.type === 'keypress' && _.indexOf([13, /* ENTER */ 10/* CTRL+ENTER */], e.keyCode) >= 0) return
    e.stopPropagation()
    e.preventDefault()

    clearTimeout(selectTextTimeout.value)
    let start = e.target.selectionStart
    const end = e.target.selectionEnd
    const valueArray = (getValue() || '').split('')
    const replacement = e.clipboardData ? e.clipboardData.getData('text') : String.fromCharCode(e.keyCode)

    // NOTE: keydown & keypress have different keyCodes!
    if (e.type === 'keydown' && _.indexOf([8/* BACKSPACE */, 46/* DELETE */], e.keyCode) > -1) {
      start = start - (e.keyCode === 8 /* BACKSPACE */ && start === end)
      valueArray.splice(start, Math.abs(end - start) || 1)
    } else {
      valueArray.splice(start, end - start, replacement)
      start = start + replacement.length
    }
    setValue(valueArray.join(''))
    selectTextTimeout.value = setTimeout(() => { selectText(start, start) }, 20)
  }
}

const transformIMEInput = (e) => {
  e.stopPropagation()
  e.preventDefault()
  clearTimeout(selectTextTimeout.value)

  const start = e.target.selectionStart
  const valueArray = (getValue() || '').split('')
  const raw = shownValue.value
  const replacement = raw.split(hiddenPasswordChr).join('')
  const deleteCount = valueArray.length - (raw.length - replacement.length)

  valueArray.splice(start - replacement.length, deleteCount, replacement)

  setValue(valueArray.join(''))
  selectTextTimeout.value = setTimeout(() => { selectText(start, start) }, 20)
}

const handlePWToggle = async () => {
  if (props.readOnly || props.disabled) return

  if (props.allowBrowserPasswordManager) {
    inputType.value = _locked.value ? 'text' : 'password'
  } else {
    if (props.revealPasswordFn && _locked.value && revealedPassword.value !== actualValue.value) {
      try {
        // TODO test the password reveal functionality
        actualValue.value = await props.revealPasswordFn()
        revealedPassword.value = actualValue.value
      } catch (e) {}
    }
    actualValue.value = _locked.value ? actualValue.value : shownValue.value
    shownValue.value = _locked.value ? actualValue.value : hiddenPasswordChr.repeat(actualValue.value.length)
  }
  _locked.value = !_locked.value
  await nextTick(() => {
    const l = inputRef.value.$el.value.length
    inputRef.value.focus()
    inputRef.value.$el.setSelectionRange(l, l)
  })
}

// watch([shownValue, actualValue], ([newShown, newActual]) => {
//   console.log('newShown: ', newShown, 'newActual: ', newActual)
// })

const handleCopy = async () => {
  if (props.readOnly || props.disabled) return
  await copyToClipBoard(actualValue.value)
}

// inspired by useClipboard
// https://github.com/vueuse/vueuse/blob/main/packages/core/useClipboard/index.ts
const copyToClipBoard = async (value) => {
  // eslint-disable-next-line
  const legacyCopy = (v) => {
    // NOTE: method to copy to clipboard on hosts that don't have 'clipboard-write' permission
    // eg: `http://web`
    // FIXME: this doesn't seem to work in eg: http://web
    /*
    const ta = document.createElement('textarea')
    ta.value = v ?? ''
    ta.style.position = 'absolute'
    ta.style.opacity = '0'
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    ta.remove()
     */
  }
  if (value != null) {
    const isSupported = navigator && 'clipboard' in navigator
    if (isSupported) {
      const permissionWrite = await navigator.permissions.query({ name: 'clipboard-write' })
      if (permissionWrite.state !== 'denied') {
        console.debug('modern copy')
        await navigator.clipboard.writeText(value)
      } else legacyCopy(value)
    } else legacyCopy(value)
  }
}

onBeforeMount(() => init())

const $inputEl = computed(() => inputRef.value.$el)
const focus = () => inputRef.value.focus()

defineExpose({ getValue, setValue, setValidationState, $inputEl, focus })

</script>

<style scoped lang="scss">
.enlarge-on-hover:hover{
  scale: 1.1;
}

.shrink-on-click:active{
  scale: 0.9;
}
</style>
