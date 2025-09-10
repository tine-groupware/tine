<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <div class="container">
    <div class="title mb-3">{{eventDetails.name}}</div>
    <div v-if="eventDetails.description" style="white-space: pre-wrap;">{{eventDetails.description}}</div>
    <h5 class="mb-3">{{formatMessage('Information of the event:')}}</h5>
    <div class="row mb-3" v-if="eventDetails.start && eventDetails.start !== '1.1.1970'">
      <div class="col-4">{{formatMessage('When:')}}</div>
      <div class="col-8">{{eventDetails.start}}</div>
    </div>
    <div class="row mb-3" v-if="eventDetails.end && eventDetails.end !== '1.1.1970'">
      <div class="col-4">{{formatMessage('Until:')}}</div>
      <div class="col-8">{{eventDetails.end}}</div>
    </div>
    <div class="row mb-3" v-if="eventDetails.appointments">
      <div class="col-4">{{formatMessage('Appointments:')}}</div>
      <div class="col-8">{{eventDetails.appointments}}</div>
    </div>
    <div class="row mb-3" v-if="eventDetails.location">
      <div class="col-4">{{formatMessage('Address:')}}</div>
      <div class="col-8">
        <p class="mb-0">{{_.get(eventDetails, 'location.adr_one_street')}}</p>
        <p class="mb-0">{{_.get(eventDetails, 'location.adr_one_postalcode')}}</p>
        <p class="mb-0">{{_.get(eventDetails, 'location.adr_one_locality')}}</p>
      </div>
    </div>
    <div class="row mb-3" v-if="eventDetails.fee">
      <div class="col-4">{{formatMessage('Fee:')}}</div>
      <div class="col-8">{{eventDetails.fee}} Euros</div>
    </div>
    <div class="row mb-3" v-if="eventDetails.registration_possible_until && eventDetails.registration_possible_until !== '1.1.1970'">
      <div class="col-4">{{formatMessage('Registration possible until:')}}</div>
      <div class="col-8">{{eventDetails.registration_possible_until}}</div>
    </div>
    <div class="mb-3">
      <b-button :to="{ path: '/event/'+ route.params.id + '/registration' }" variant="primary">{{formatMessage('Registration')}}</b-button>
    </div>
  </div>
</template>

<script setup>
import {inject, ref} from 'vue';
import {translationHelper} from "./keys";
import {useRoute} from 'vue-router';
import _ from 'lodash';

const formatMessage = inject(translationHelper);
const eventDetails = ref({
  name: "",
  start : "",
  end: "",
  location: "",
  type: "",
  status: "",
  fee: "",
  total_places: "",
  booked_places: "",
  available_places: "",
  doubleOptIn: "",
  options: [],
  registrations: [],
  appointments: [],
  description: "",
  isLive: "",
  registration_possible_until: "",
});

const route = useRoute();

async function getEvent() {
  let eventId = route.params.id
  await fetch(`/EventManager/view/event/${eventId}`, {
    method: 'GET'
  }).then(resp => resp.json())
    .then(data => {
      data.start = new Date(data.start).toLocaleDateString("de").replaceAll(", 00:00:00", "").replaceAll(", 01:00:00", "");
      data.end = new Date(data.end).toLocaleDateString("de").replaceAll(", 00:00:00", "").replaceAll(", 01:00:00", "");
      data.registration_possible_until = new Date(data.registration_possible_until).toLocaleDateString("de").replaceAll(", 00:00:00", "").replaceAll(", 01:00:00", "");
      eventDetails.value = data;
      console.log(data);
    })
}


getEvent();
</script>

<script>
export default {
  name: "EventDetail"
}
</script>

<style scoped lang="scss">

.title {
  font-size: xx-large;
  font-weight: bold;
}
</style>
