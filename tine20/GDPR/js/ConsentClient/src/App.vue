<template>
  <div :class="['center-container', currentRouteClass]">
    <div>
      <img v-if="logoLink" :src="logoLink" class="logo"/>
    </div>
    <div class="route-view">
      <RouterView/>
    </div>
    <footer class="footer">
      <div v-html="templates?.impressionTemplate" class="footer-content"></div>
    </footer>
  </div>

</template>

<script setup>
import {computed, onMounted, ref, onBeforeMount} from "vue";
import { useFormatMessage } from './index.es6';
const { formatMessage } = useFormatMessage();
const templates = ref(null);
const responseData = ref(null);
import { useRoute } from 'vue-router';
const route = useRoute();
const logoLink = computed(() => {
  const link = responseData.value
    ? responseData.value.installLogo
      ? responseData.value.installLogo
      : responseData.value.brandingLogo
    : null
  return link ? `${window.location.origin}/${link}` : null
})

// Compute a class based on the current route
const currentRouteClass = computed(() => {
  return route.name ?? '';
});

const fetchData = async () => {
  const response = await fetch(window.location.pathname.replace('/view/', '/'))
  responseData.value = await response.json();
  if(!response.ok) console.error('Error fetching data:', responseData.value)
  templates.value = responseData.value.templates[__WEBPACK_DEFAULT_EXPORT__.__name];
}

onBeforeMount(async () => {
  try{
    await fetchData();
  } catch(e) {
  }
})

 onMounted(() => {
    const poweredBy = document.getElementsByClassName("tine-viewport-poweredby")[0] ?? null;
    const footer = document.getElementsByClassName("footer")[0] ?? null;
    if (footer && poweredBy) {
      footer.parentNode.insertBefore(poweredBy, footer);
    }
})
</script>

<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

.center-container {
  min-height: 100vh; /* Use viewport height to ensure it takes full window height */
  display: flex;
  flex-direction: column;
}

.main-container {
  margin: 20px 0;
  max-width: 550px;
  display: flex;
  flex-direction: column;
  font-weight: 100;

  h1 {
    width: 100%;
    text-align: center;
    word-wrap: break-word;
    font-weight: 100;
    height: 50px;
  }

  h3 {
    font-size: 30px;
    width: 100%;
    text-align: center;
    word-wrap: break-word;
    margin-bottom: 1rem;
  }

  p {
    font-size: 20px;
    text-align: left;
  }
}

.text-container {
  display: flex;
  flex-direction: column;
}

.route-view {
  display: flex;
  margin: 0 50px;
  flex: 1;
  flex-direction: column;
  align-items: center;
  overflow: auto;
}

.tine-viewport-poweredby {
  text-align: right;
  margin: 10px 20px;
  position: static;
}

.footer {
  width: 100%;
  height: 150px; /* Fixed height */
  background-color: #000000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 50px;
}

.footer-content {
  display: flex;
  color: white;
  text-align: left;
  width: 100%;
  flex-direction: row;
  gap: 20%;
  justify-content: space-evenly;

  a {
    font-size: 14px;
  }
}

.logo {
  margin: 20px 50px;
  max-width: 150px;
}

</style>
