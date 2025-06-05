<!--
this is the same page as GDPR/manageConsent but with the description of the dip also shown
register for: name
descriptiong: descriptoin
-->
<template>
  <div class="main-container" v-if="!loading || responseData">
    <div v-if="!submissionComplete">
      <div class="text-container" v-html="templates?.registrationViewTemplate"></div>
      <BFormInput class="mb-3 mt-2"
        id="email-addr"
        :placeholder="formatMessage('E-mail')"
        type="email"
        v-model="email"
        :readonly=!!email
      ></BFormInput>
      <BFormInput class="mb-3 mt-2"
        id="first-name"
        :placeholder="formatMessage('First Name')"
        type="text"
        v-model="n_given"
      ></BFormInput>
      <BFormInput class="mb-3 mt-2"
        id="last-name"
        :placeholder="formatMessage('Last Name')"
        type="text"
        v-model="n_family"
      ></BFormInput>
      <BFormInput class="mb-3 mt-2"
        id="organization"
        :placeholder="formatMessage('Organization')"
        type="text"
        v-model="org_name"
      ></BFormInput>
      <div class="d-grid gap-2">
        <BButton block variant="primary" @click="onButtonClicked">{{ formatMessage('Register') }}</BButton>
      </div>
    </div>
    <div v-else>
      <div v-if="contactId">
        <div class="text-container" v-html="templates?.afterClickRegistrationTemplate"></div>
        <router-link :to="{
          name: 'manage-consent',
          params: { contactId: contactId }
        }">
        <div class="d-grid gap-2">
          <BButton block variant="primary">{{ formatMessage('Go to my manage consent link') }}</BButton>
        </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>

import {
  onBeforeMount,
  defineProps,
  ref,
} from "vue"

const loading = ref(true);
const responseData = ref(null);
const templates = ref(null);
const email = ref("")
const n_family = ref("")
const n_given = ref("")
const org_name = ref("")
const submissionComplete = ref(false)
const contactId = ref('')

const fetchData = async () => {
  const response = await fetch(window.location.pathname.replace('/view/', '/'))
  responseData.value = await response.json();
  if(!response.ok) console.error('Error fetching data:', responseData.value)

  templates.value = responseData.value.templates[__WEBPACK_DEFAULT_EXPORT__.__name];
  email.value = responseData.value.email;
}

onBeforeMount(async () => {
  loading.value = true;
  try {
    await fetchData();
  } catch (e) {}
  document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
  loading.value = false;
})

const props = defineProps({
  submissionComplete: {type: Boolean, default: false},
})


const onButtonClicked = async () => {
  const body = {
    'email': email.value ?? '',
    'n_family': n_family.value ?? '',
    'n_given': n_given.value ?? '',
    'org_name': org_name.value ?? '',
  };
  await fetch(window.location.pathname.replace('/view/', '/'), {
    method: 'POST',
    body:    JSON.stringify(body),
  })
    .then(async response => {
      // show than you
      responseData.value = await response.json();
      contactId.value = responseData.value?.current_contact?.id
      submissionComplete.value = true;
    })
    .catch((e) => {
      // show failed but thank you
      debugger
    })
}

</script>
