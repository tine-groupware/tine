<template>
  <div :class="[currentRouteClass]">
    <div class="header-section" v-html="initialData.header"></div>
    <div class="route-view">
      <RouterView/>
    </div>
    <div class="footer-section" v-html="initialData.footer"></div>
  </div>
</template>

<script setup>
/* eslint-disable */
import { computed, onMounted, ref, onBeforeMount, shallowRef } from 'vue';

const initialData = shallowRef({});
const templates = ref(null);
const responseData = ref(null);
import { useRoute } from 'vue-router';
const route = useRoute();

// Compute a class based on the current route
const currentRouteClass = computed(() => {
  return route.name ?? '';
});

const fetchData = async () => {
  const response = await fetch(window.location.pathname.replace('/view/', '/'))
  responseData.value = await response.json();
  if(!response.ok) console.error('Error fetching data:', responseData.value)

  if (window.initialData) {
    initialData.value = window.initialData;
  }
}

onBeforeMount(async () => {
  try{
    await fetchData();
  } catch(e) {
  }
})

onMounted(() => {
  const poweredBy = document.getElementsByClassName("tine-viewport-poweredby")[0] ?? null;
  if (poweredBy) {
    poweredBy.style.zIndex = 2000;
    poweredBy.style.position = 'fixed';
  }
})
</script>

<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

:root {
  --header-height: 80px;
  --footer-height: 150px;
}

.route-view {
  min-height: 100vh;
  display: flex;
  margin: 0 50px;
  padding-top: var(--header-height);
  padding-bottom: var(--footer-height);
  flex: 1;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow-x: auto;
}

.header-section {
  height: var(--header-height);
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  background-color: inherit;
  padding: 10px 20px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.footer-section {
  height: var(--footer-height);
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 20px;
  background-color: #f6f6f6;
  border-top: 1px solid #EDEDED;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
  box-sizing: border-box;
}

</style>
