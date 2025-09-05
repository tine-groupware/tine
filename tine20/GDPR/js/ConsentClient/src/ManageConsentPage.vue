<template>
    <div class="main-container" v-if="!loading || responseData">
      <div v-if="!emailPage">
        <div class="text-container" v-html="templates?.manageConsentPageExplainText"></div>
        <DataIntendedPurposeGrid v-model:consentConfig="responseData"/>
      </div>
      <div v-else>
        <EmailPage v-model:consentConfig="responseData"> </EmailPage>
      </div>
    </div>
</template>

<script setup>
import {
    onBeforeMount,
    ref,
    computed
} from 'vue';
import EmailPage from './EmailPage.vue'
import { useFormatMessage } from './index.es6';
import DataIntendedPurposeGrid from "./DataIntendedPurposeGrid.vue";

const { formatMessage } = useFormatMessage();
const responseData = ref(null);
const templates = ref(null);
const loading = ref(true);
const _CONTACT_EMAIL = "current_contact.email"
const email = computed(() => _.get(responseData.value, _CONTACT_EMAIL) || "__")
const emailPageButtonText = computed(() => {
  if (email.value === '__') {
    return formatMessage('Register');
  } else {
    return formatMessage('I am not {email}', {email: email.value});
  }})
const handleNotSomeone = () => {
  emailPage.value = true
}

const emailPage = ref(false)

const fetchData = async () => {
  const response = await fetch(window.location.pathname.replace('/view/', '/'))
  responseData.value = await response.json();
  if(!response.ok) {
    console.log('Error fetching data:', responseData.value.error)
  }
  templates.value = responseData.value.templates[__WEBPACK_DEFAULT_EXPORT__.__name + '.html'];
}

onBeforeMount(async () => {
    loading.value = true;
    try{
        await fetchData();
    } catch(e) {
        emailPage.value = true
    }
    document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
    loading.value = false;
})
</script>

<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

.manage-consent .main-container {
  max-width: unset;

  h1 {
    text-align: left;
  }
}

</style>
