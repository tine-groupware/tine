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
  <div>
    <p v-if="questionText">{{ questionText }}</p>
    <PasswordField
      ref="passwordFieldRef"
      @paste="onPaste"
      @keydown="onKeydown"
      @keypress="onKeypress"
      :clipboard="clipboard"
      :locked="locked"
      :label="passwordLabel"
    />
    <BButton
      v-if="hasPwGen"
      @click="onPWGenClick"
      variant="primary"
      class="mb-2"
    >{{ window.i18n._('Generate password') }}</BButton>
    <div class="me-3 d-flex mt-4">
      <Component
        v-for="(item, idx) in _additionalFields"
        :is="item.__component"
        :key="idx"
        :itemCfg="item"
      />
    </div>
  </div>
</template>

<script setup>
import PasswordField from '../components/PasswordField.vue'
import { ref, inject, computed, markRaw } from 'vue'
import ExtAction from '../../../../TineBar/barItems/ExtAction.vue'

const props = defineProps({
  passwordLabel: { type: String, default: 'Password' },
  questionText: { type: String, default: null },
  hasPwGen: { type: Boolean, default: false },
  allowBlank: { type: Boolean, default: false },
  locked: { type: Boolean, default: true },
  clipboard: { type: Boolean, default: true },
  pwMandatoryByPolicy: { type: Boolean, default: true },
  injectKey: { type: String },
  additionalFields: { type: Array, default: () => [] }
})

const { genPW, eventBus } = inject(props.injectKey)

const onPWGenClick = function () {
  const p = genPW()
  passwordFieldRef.value.setValue(p)
}

const onPaste = () => {}
const onKeydown = () => {}
const onKeypress = (e) => {
  if (!disableok.value && e.key === 'Enter') {
    eventBus.emit('apply', passwordFieldRef.value.getValue())
  }
}

const passwordFieldRef = ref()

const getValue = () => passwordFieldRef.value?.getValue() || ''

const disableok = computed(() => props.allowBlank ? false : passwordFieldRef.value?.getValue()?.length === 0)

const initialFocus = computed(() => passwordFieldRef.value.$inputEl)

const _additionalFields = computed(() => {
  let t = _.sortBy(props.additionalFields, item => {
    return item.registerdItemPos
  })
  t = t.map(item => {
    item.__component = item.__component || markRaw(ExtAction)
    return item
  })
  return t
})

defineExpose({ getValue, disableok, initialFocus })
</script>

<style scoped>

</style>
