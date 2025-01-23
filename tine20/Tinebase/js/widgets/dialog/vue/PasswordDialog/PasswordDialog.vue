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
      variant="outline-primary"
      class="mb-2"
    >{{ window.i18n._('Generate password') }}</BButton>
  </div>

</template>

<script setup>
import PasswordField from '../components/PasswordField.vue'
import { ref, inject, computed } from 'vue'

const props = defineProps({
  passwordLabel: { type: String, default: 'Password' },
  questionText: { type: String, default: null },
  hasPwGen: { type: Boolean, default: false },
  allowBlank: { type: Boolean, default: false },
  locked: { type: Boolean, default: true },
  clipboard: { type: Boolean, default: true },
  pwMandatoryByPolicy: { type: Boolean, default: true },

  injectKey: { type: String }
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

defineExpose({ getValue, disableok, initialFocus })
</script>

<style scoped>

</style>
