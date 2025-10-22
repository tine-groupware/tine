<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <sdeshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->
<script setup>
/* eslint-disable vue/no-mutating-props */
import PasswordField from './widgets/dialog/vue/components/PasswordField.vue'
import { onMounted, ref, inject, nextTick } from 'vue'
import { BFormGroup } from 'bootstrap-vue-next'

const props = defineProps({
  _this: Object,
  injectKey: String,
  formState: {
    username: String,
    usernameValid: Boolean,
    password: String,
    passwordValid: Boolean
  }
})

// --------------------------------------------------------------------------

const i18n = window.i18n

// @DISCUSS: good idea to include Tine here
const Tine = window.Tine
const isSetup = window.location.pathname.includes('setup.php')

// dont show ext idp options in setup.php
const showExtIDPOptions = !isSetup

// setup.php login only possible via username and password
const allowPasskeyLogin = !isSetup && Tine.Tinebase.registry.get('allowPasskeyLogin')

// used only to show password field
const allowPasswordLessLogin = Tine.Tinebase.registry.get('allowPasswordLessLogin')

const browserSupport = props._this._getBrowserSupportStatus()
const licenseCheck = Tine.Tinebase.registry.get('licenseStatus')
const extIdpConfig = Tine.Tinebase.registry.get('loginExternalIdps')

const eventBus = inject(props.injectKey)

const usernameTRef = ref()
const passwordTRef = ref()

const onLoginPress = () => {
  eventBus.emit('onLoginPress')
}

const onExtIDPLoginPress = (idpId) => {
  eventBus.emit('onExtIDPLoginPress', idpId)
}

const triggerBrowserCredentialLogin = async (conditional = false) =>
  /* eslint-disable no-useless-call */
  await props._this.triggerBrowserCredentialLogin.call(props._this, conditional)

const langChooserRef = ref()
onMounted(async () => {
  // render ext langChooser
  new Tine.widgets.LangChooser().render(langChooserRef.value)

  eventBus.on('focusPWField', focusPWField)
  eventBus.on('focusUsernameField', focusUsernameField)

  if (allowPasswordLessLogin) props.formState.password = null
  usernameTRef.value.focus()

  if (
    allowPasskeyLogin &&
    window.PublicKeyCredential &&
    window.PublicKeyCredential.isConditionalMediationAvailable
  ) {
    const isCMA = await window.PublicKeyCredential.isConditionalMediationAvailable()
    if (isCMA) {
      await props._this.triggerBrowserCredentialLogin(true)
    }
  }
})

const pwFieldVisible = ref(!allowPasswordLessLogin)
const focusPWField = () => {
  pwFieldVisible.value = true
  nextTick(() => {
    passwordTRef.value.focus()
  })
}
const focusUsernameField = () => usernameTRef.value.focus()

const isDark = document.body.classList.contains('dark-mode')
const logoUrl = `logo/i/300x100/image%2Fsvg%2Bxml/${isDark ? 'dark' : 'light'}`

</script>

<template>
  <div class="bootstrap-scope main-container" style="height: 100%; width: 100%; min-height: fit-content">
    <div class="login-left">
      <div class="login-container">
        <div class="login-logo">
          <a target="_blank" rel="noopener noreferrer" :href="Tine.websiteUrl">
            <img :src="logoUrl" class="dark-reverse logo-image">
          </a>
          <h2 class="mt-4">{{ i18n._('Login') }}</h2>
          <h5>{{ String.format(i18n._('Login with {0} account'), Tine.title)}}</h5>
          <div class="lang-chooser-container mt-5 mb-3">
            <label>{{ i18n._('Language') }}</label>
            <div ref="langChooserRef" class="my-2" id="langChooser"/>
          </div>
        </div>
        <BForm validated="true" novalidate class="login-form">
          <BFormGroup
              :label="i18n._('Username')"
          >
            <BFormInput
                ref="usernameTRef"
                class="login-input fs-5 ps-3"
                v-model="formState.username"
                :state="formState.usernameValid"
                autocomplete="username webauthn"
                name="username"
            />
          </BFormGroup>
          <BButton variant="link" class="mt-2 px-0" underline-opacity="0" v-if="!pwFieldVisible" @click.prevent.stop="focusPWField">{{i18n._('Login with Password?')}}</BButton>
          <Transition name="pw-field-transition">
            <BFormGroup
              :label="i18n._('Password')"
              class="mt-4"
              v-if="pwFieldVisible"
            >
              <PasswordField
                :allow-browser-password-manager="_this.allowBrowserPasswordManager"
                :un-lockable="!_this.allowBrowserPasswordManager"
                :clipboard="false"
                :name="'password'"
                ref="passwordTRef"
                :state="formState.passwordValid"
                v-model="formState.password"
                class="login-input"
                :bstp-input-field-class="'fs-5 ps-3'"
                :autocomplete="'current-password'"
                :darkReverse="false"
              />
            </BFormGroup>
          </Transition>
          <div class="d-flex mt-4 justify-content-end">
            <BButton @click="onLoginPress" variant="primary" class="dark-reverse fs-5 px-4" pill>{{ i18n._('Login') }}</BButton>
          </div>
<!--          <div class="auth-divider text-center mt-3">{{i18n._('Or')}}</div>-->
          <div class="d-flex mt-3 justify-content-end" v-if="allowPasskeyLogin">
            <BButton variant="link" class="fs-5 pr-0" underline-opacity="0" @click="triggerBrowserCredentialLogin(false)">
              <img src="images/icon-set/Icon_key.svg" class="d-inline-block" style="height: 1.5em; width: 1.5em"/>
              <span class="ms-1">{{ i18n._('Login with Passkey') }}</span>
            </BButton>
          </div>
          <div
              v-if="_this?.headsUpText"
              class="fs-2 mt-3">
            {{ _this.headsUpText }}
          </div>
        </BForm>
      </div>
      <div class="mt-4 external-idp-login" v-if="showExtIDPOptions && extIdpConfig?.length > 0">
        <p class="fs-3 fw-bolder text-center">{{i18n._('or').toUpperCase()}}</p>
        <div class="d-grid gap-2">
          <div
            v-for="config in extIdpConfig"
            :key="config.id"
            class="d-flex justify-content-center"
          >
            <div
              class="cursor-pointer"
              v-if="!config.label && (config.logo_light || config.logo_dark)"
              @click.prevent.stop="onExtIDPLoginPress(config.id)">
              <img
                :src="isDark ? config.logo_dark ?? config.logo_light : config.logo_light ?? config.logo_dark"
                class="sso-img" />
            </div>
            <div v-if="config.label"
              @click.prevent.stop="onExtIDPLoginPress(config.id)"
              class="rounded-pill dark-reverse fs-4 px-4 py-2 external-idp-login-btn mt-2 align-items-center btn-primary d-flex">
              <img
                v-if="config.logo_dark || config.logo_light"
                :src="config.logo_dark ?? config.logo_light"/>
              <div class="flex-grow-1 d-flex justify-content-center align-items-center">
                <span class="ps-2">
                  {{config.label}}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-if="_this.infoText" class="info-text-container fs-3 mt-3 text-center">
        <span v-if="_this.infoText.trim().startsWith('<')" v-html="_this.infoText"/>
        <p v-else>
          {{ _this.infoText }}
        </p>
      </div>
    </div>
    <div class="login-right">
      <div v-if="browserSupport !== 'compatible'">
        <p class="fw-semibold fs-3">
          {{ i18n._('Browser incompatible') }}
        </p>
        <p v-if="browserSupport === 'incompatible'">
          {{ i18n._('Your browser is not supported by Tine 2.0.') }}
        </p>
        <p v-else-if="browserSupport === 'unknown'">
          {{ i18n._('You are using an unrecognized browser. This could result in unexpected behavior.') }}
        </p>
        <p>{{ i18n._('You might try one of these browsers:') }}<br/>
          <a href="https://www.google.com/chrome" target="_blank">Google Chrome</a><br/>
          <a href="https://www.mozilla.com/firefox/" target="_blank">Mozilla Firefox</a><br/>
          <a href="https://www.apple.com/safari/" target="_blank">Apple Safari</a><br/>
          <a href="https://www.microsoft.com/en-us/windows/microsoft-edge" target="_blank">Microsoft Edge</a>
          <br/></p>
      </div>
      <div v-if="licenseCheck === 'status_no_license_available' || licenseCheck === 'status_license_invalid'">
        <div v-if="licenseCheck === 'status_license_invalid'">
          <p class="fw-semibold fs-3">{{ String.format(i18n._('Your {0} license expired.'), Tine.title) }}</p>
          <p>
            {{ String.format(i18n._('Your {0} license has expired! Users cannot log in anymore. Please contact Metaways Infosystems GmbH to buy a new license.'), Tine.title) }}</p>
        </div>
        <div v-else>
          <p class="fw-bold fs-3">{{ String.format(i18n._('{0} trial'), Tine.title) }}</p>
          <p>{{ i18n._('Please contact Metaways Infosystems GmbH to buy a valid license.') }}</p>
        </div>
      </div>
    </div>
    <div
        class="tine-viewport-poweredby"
        style='position: absolute; bottom: 10px; right: 10px; font:normal 12px arial, helvetica,tahoma,sans-serif;'>
      {{ i18n._('Powered by:') }}
      <a target='_blank' :href="Tine.weburl" :title="i18n._('online open source groupware and crm')">
        {{ Tine.title }}
      </a>
    </div>
  </div>
</template>

<style scoped lang="scss">

.auth-divider::before {
  right: .5em;
}

.auth-divider::after{
  left: .5em;
}

.auth-divider::before, .auth-divider::after {
  position: relative;
  display: inline-block;
  width: 45%;
  height: 1px;
  vertical-align: middle;
  content: "";
  background-color: grey;
}

//breakpoints
$mobile: 450px;
$tablet: 700px;
$monitor: 1000px;

.main-container {
  background-color: #f0f0f0;
  box-sizing: border-box;
}

.login-container {
  transition: all 0.15s ease-in-out;
}

.login-form {
  transition: all 0.15s ease-in-out;
}

.dark-mode .main-container {
  background-color: #ddd;
}

.login-box {
  background-color: #f0f0f0;
}

.dark-mode .login-box {
  background-color: #ccc;
}

.login-left {
  margin: 0 2em;
  padding: 2em 0;
}

.login-right {
  margin: 0 2em;
  padding: 2em 0;
  max-width: 400px;
}

.logo-image {
  min-width: 70px;
  min-height: 40px;
  max-width: 300px;
  max-height: 70px;
}

.cursor-pointer {
  cursor: pointer;
}

.sso-img {
  min-width: 215px;
  max-width: 300px;
}

.external-idp-login-btn {
  @extend .cursor-pointer;
  min-width: 320px;
  color: white;
  background-color: var(--focus-color);

  img {
    width: 18px;
    height: 18px;
  }
}

@media screen and (min-width: $mobile) {

  .main-container {
    background-color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .login-left {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    //width: 400px;
  }

  .login-container {
    width: 400px;
    padding: 20px;
    background-color: #f0f0f0;
    border-radius: 25px;
  }

  .dark-mode .login-container {
    background-color: #ccc;
  }
}

@media screen and (min-width: $tablet) {
  .main-container {
    display: flex;
    flex-direction: row;
  }

  .login-right {
    padding: 0;
    margin: 0;
    padding-right: 2em;
  }

}

@media screen and (min-width: $monitor) {
  .main-container {
    flex-direction: column;
  }
  .login-container {
    display: flex;
    flex-direction: row;
    width: 800px;

    .login-logo {
      width: 350px;

      .logo-image {
        min-width: 80px;
        max-height: 80px;
      }
    }

    .login-form {
      width: 400px;
      margin-top: 20px;
      align-content: center;
    }
  }
  .login-right {
    max-width: 800px;
  }
}

// pw-field-transition
.pw-field-transition-enter-active,
.pw-field-transition-leave-active {
  transition: all 0.15s ease-in-out;
}

.pw-field-transition-enter-from,
.pw-field-transition-leave-to {
  transform: translateY(-30px);
  opacity: 0;
}

.pw-btn-transition-enter-active,
.pw-btn-transition-leave-active {
  transition: all 0.10s ease-in-out;
}

.pw-btn-transition-enter-from,
.pw-btn-transition-leave-to {
  opacity: 0;
}
</style>
