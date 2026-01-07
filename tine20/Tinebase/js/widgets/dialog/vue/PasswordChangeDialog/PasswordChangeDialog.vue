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
  <BForm class="mb-4">
    <p v-if="dialogText !== null" class="mb-4">{{ dialogText }}</p>
    <PasswordField
      id="oldPassword"
      name="oldPassword"
      :label="String.format(window.i18n._('Old {0}'), passwordLabel)"
      :clipboard="false"
      ref="oldPasswordField"
      v-if="askOldPassword"
    />
    <PasswordField
      id="newPassword"
      name="newPassword"
      :label="String.format(window.i18n._('New {0}'), passwordLabel)"
      ref="newPasswordField"
    />
    <PasswordField
      id="newPasswordSecondTime"
      name="newPasswordSecondTime"
      :label="String.format(window.i18n._('Repeat new {0}'), passwordLabel)"
      ref="newPasswordSecondTimeField"
    />
  </BForm>
</template>

<script setup>
import PasswordField from '../components/PasswordField.vue'
import { ref, computed } from 'vue'

// eslint-disable-next-line
const props = defineProps({
  passwordLabel: { type: String, default: 'Password' },
  dialogText: { type: String, default: null },
  askOldPassword: { type: Boolean, default: true }
})

const oldPasswordField = ref()
const newPasswordField = ref()
const newPasswordSecondTimeField = ref()

const getValue = () => {
  return {
    oldPassword: props.askOldPassword ? oldPasswordField.value.getValue() : '',
    newPassword: newPasswordField.value.getValue(),
    newPasswordSecondTime: newPasswordSecondTimeField.value.getValue()
  }
}

const initialFocus = computed(() => oldPasswordField.value.$inputEl)

defineExpose({ getValue, initialFocus }
)

// onBeforeMount(() => {
//   console.log(props.dialogText)
// })
</script>

<style scoped lang="scss">

</style>
