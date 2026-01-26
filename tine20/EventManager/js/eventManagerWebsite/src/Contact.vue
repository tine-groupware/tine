<!--
/*
 * Tine 2.0
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */
-->

<template>
  <div class="contact-container">
    <b-container class="contact-wrapper">
      <b-row class="title text-center">
        <b-col>
          <h1>{{formatMessage('Contact')}}</h1>
        </b-col>
      </b-row>

    </b-container>
  </div>
</template>

<script setup>
import {useFormatMessage} from './index.es6';
const { formatMessage } = useFormatMessage();
</script>

<style scoped lang="scss">
.contact-container {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 3rem 0;
  width: 100%;
}

.contact-wrapper {
  background: white;
  border-radius: 12px;
  padding: 2.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-width: 900px;
  margin: 0 auto;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  border-bottom: 3px solid #2c3e50;
  padding-bottom: 1rem;
  margin-bottom: 0.5rem;

  h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
  }

  h5 {
    color: #6c757d;
    font-weight: 400;
    margin-top: 0.5rem;
    margin-bottom: 0;
  }
}
</style>
