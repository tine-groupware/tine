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
    <h4>{{formatMessage('Information of the event:')}}</h4>
    <div>
      <b-button :to="{ path: '/event/'+ route.params.id + '/registration' }" variant="primary">{{formatMessage('Registration')}}</b-button>
    </div>
  </div>
</template>

<script setup>
import {inject, ref} from 'vue';
import {translationHelper} from "./keys";
import {useRoute} from 'vue-router';

const formatMessage = inject(translationHelper);
const eventDetails = ref({
  name: "",
  start : "",
  end: "",
  location: "",
  type: "",
  status: "",
  fee: "",
  totalPlaces: "",
  bookedPlaces: "",
  availablePlaces: "",
  doubleOptIn: "",
  options: [],
  registrations: [],
  appointments: [],
  description: "",
  isLive: "",
  registrationPossibleUntil: "",
});

const route = useRoute();

async function getEvent() {
  let eventId = route.params.id
  await fetch(`/EventManager/view/event/${eventId}`, {
    method: 'GET'
  }).then(resp => resp.json())
    .then(data => {
      eventDetails.value = data;
      console.log(data);
    })

  /*await fetch(`/EventManager/search/event`, {
    method: 'GET'
  }).then(resp => resp.json())
    .then(data => {
      console.debug(data);
      let index = data.findIndex((t) => t.id === taskId)
      if (index > 0) {
        previousTask.value = data[index - 1].id;
      }
      if (index < data.length -1){
        nextTask.value = data[index + 1].id;
      }
    })*/
}
getEvent();
</script>

<script>
export default {
  name: "EventDetail"
}
</script>

<style scoped lang="scss">

</style>
