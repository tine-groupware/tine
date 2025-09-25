<template>
  <SinglePage />
</template>

<script setup>
import SinglePage from './../../../../Tinebase/js/SinglePageApplication.vue';
</script>

<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

.main-container {
  height: 100vh;
  max-width: 600px;
  display: flex;
  flex: 1;
  flex-direction: column;
  font-weight: 100;
  text-align: left;
  box-sizing: border-box;
  justify-content: center;
  padding: 20px;

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

</style>

