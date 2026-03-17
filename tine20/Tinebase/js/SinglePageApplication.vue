<template>
  <div :class="[currentRouteClass]">
    <!-- Show normal content if no error -->
      <div class="header-section" v-html="initialData.header"></div>
        <div class="route-view">
          <div v-if="errorMessage" class="text-center">
            <h4>{{ errorMessage }}</h4>
          </div>
          <RouterView v-else/>
        </div>
      <div class="footer-section" v-html="initialData.footer"></div>
  </div>
</template>

<script setup>
/* eslint-disable */
import { computed, ref, onBeforeMount, shallowRef, defineProps } from 'vue';

defineProps({
  autoFetch: {type: Boolean, default: true}
})

const initialData = shallowRef({});
const responseData = ref(null);
const errorMessage = ref(null);
import { useRoute } from 'vue-router';
const route = useRoute();
const loading = ref(true);

// Compute a class based on the current route
const currentRouteClass = computed(() => {
  return route.name ?? '';
});

const fetchData = async () => {
  loading.value = true
  try{
    if (!vue.getCurrentInstance().props.autoFetch) {
      return
    }
    const response = await fetch(window.location.pathname.replace('/view/', '/'))
    responseData.value = await response.json();

    if (window.initialData) {
      initialData.value = window.initialData;
    }

    if(!response.ok) {
      errorMessage.value = 'Content not found';
      console.error('Error fetching data:', responseData.value)
    }
  } catch(e) {
    errorMessage.value = e?.message ?? 'Content not found';
  } finally {
    document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
    loading.value = false
  }
}

onBeforeMount(fetchData)

</script>

<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

:root {
  --header-height: 100px;
  --footer-height: 90px;
  --spacing: 20px;
}

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

.route-view {
  min-height: 100vh;
  display: flex;
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
  background-color: white;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  z-index: 10000;
}

.footer-section {
  height: var(--footer-height);
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  padding: 10px 20px;
  background-color: #f6f6f6;
  border-top: 1px solid #EDEDED;
  display: flex;
  flex-direction: column;
  justify-content: center;
  overflow: hidden;
  box-sizing: border-box;
  z-index: 10000;
}

/* Generic fix - all modals respect fixed header/footer */
.modal {
  top: var(--header-height);
  bottom: var(--footer-height);
  height: auto;
}

.modal-content {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-height: calc(100vh - var(--header-height) - var(--footer-height) - var(--spacing) * 2);
}

.modal-body {
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}
</style>
