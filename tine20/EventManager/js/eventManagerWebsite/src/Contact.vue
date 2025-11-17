<!--
/*
 * Tine 2.0
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <div class="container">
    <h1>CONTACT</h1>
  </div>
</template>

<script setup>
import {inject} from 'vue';
import {translationHelper} from "./keys";
const formatMessage = inject(translationHelper);
</script>

<script>
export default {
  name: "Contact"
}
</script>

<style scoped lang="scss">

</style>
