<!--
entry-point GDPR/manageConsent (without contact id) gets a (welcome text)  (new config like MANAGE_CONSENT_PAGE_EXPLAIN_TEXT) with an email-field to submit
this is a unspecific “registration” where user can deside which dip'r he wan't to consent for later
-->
<template>
  <div class="main-container" v-if="!loading || responseData">
      <div v-if="!subscriptionComplete">
        <div class="text-container" v-html="templates?.emailPageWelcomeTextTemplate"></div>
        <BFormInput class="mb-3 mt-2"
            id="email-addr"
            v-model="email"
            :placeholder="formatMessage('E-Mail')"
            type="email"
        ></BFormInput>
        <div class="d-grid gap-2">
          <BButton @click="onButtonClicked" variant="primary">{{ formatMessage("Send")}}</BButton>
        </div>
      </div>
      <div v-else>
        <div class="text-container" v-html="templates?.afterSendEmailTemplate"></div>
      </div>
  </div>
</template>

<script setup>

import {computed, onBeforeMount, ref} from "vue"
import {useFormatMessage} from "./index.es6";

const { formatMessage } = useFormatMessage();
const email = ref("")
const subscriptionComplete = ref(false)
const loading = ref(true);
const responseData = ref(null);
const templates = ref(null);

onBeforeMount(async () => {
  loading.value = true;
  try {
    await fetchData();
  } catch (e) {}

  document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
  loading.value = false;
})
const fetchData = async () => {
  try {
    const response = await fetch(window.location.pathname.replace('/view/', '/'))
    responseData.value = await response.json();
    templates.value = responseData.value.templates[__WEBPACK_DEFAULT_EXPORT__.__name];

    // Try to extract from path if it's part of the URL path
    const pathParts = window.location.pathname.split('/');
    const lastPart = pathParts[pathParts.length - 1];

    if (lastPart && lastPart.includes('@')) {
      email.value = lastPart;
    }
  } catch (e) {
    console.log('Error fetching data:', responseData.value.error)
  }
}

const onButtonClicked = async () => {
  const emailValue = email.value;
  if (!emailValue) return;

  await fetch(window.location.pathname.replace('/view/', '/'), {
    method: 'POST',
    body: JSON.stringify({
      email: emailValue,
    })
  }).then(data => {
      subscriptionComplete.value = true;
    })
}

</script>
