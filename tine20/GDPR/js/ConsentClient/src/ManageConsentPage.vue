<template>
    <div class="main-container" v-if="!loading || responseData">
      <div class="text-container" v-html="templates?.manageConsentPageExplainText"></div>
      <DataIntendedPurposeGrid v-model:consentConfig="responseData"/>
    </div>
</template>

<script setup>
import {
    onBeforeMount,
    ref,
} from 'vue';

import DataIntendedPurposeGrid from "./DataIntendedPurposeGrid.vue";
import { useRouter } from 'vue-router';

const router = useRouter();
const responseData = ref(null);
const templates = ref(null);
const loading = ref(true);

const fetchData = async () => {
  const response = await fetch(window.location.pathname.replace('/view/', '/'))
  responseData.value = await response.json();
  if(!response.ok) {
    console.log('Error fetching data:', responseData.value.error)
  }
  templates.value = responseData.value.templates[__WEBPACK_DEFAULT_EXPORT__.__name + '.html'];
  if (responseData.value.error) {
    router.push({
      name: 'email-page',
      params: { dipId: '' }
    });
  }
}

onBeforeMount(async () => {
    loading.value = true;
    try{
        await fetchData();
    } catch(e) {
        console.error(e);
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
