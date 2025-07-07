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
  <b-container>

    <b-row class="text-center my-5">
      <b-col>
        <h1>{{formatMessage('Events')}}</h1>
      </b-col>
    </b-row>

    <b-row>
      <b-card-group deck>
        <BCard
          v-for="event in events"
          :title= event.name
          tag="article"
          style="max-width: 20rem; min-width: 18rem"
        >
          <BCardText>
            <div v-if="event.description && event.description.length<127">{{event.description}}</div>
            <div v-else>{{event.description && event.description.substring(0,127)+"..."}}</div>
          </BCardText>
          <b-button :to="{ path: '/event/'+ event.id }" variant="primary">{{formatMessage('More Information')}}</b-button>
        </BCard>
      </b-card-group>
    </b-row>
  </b-container>
</template>

<script setup>
import {inject} from 'vue';
import {translationHelper} from "./keys";
const formatMessage = inject(translationHelper);
</script>

<script>
export default {
  name: "Events"
}

import {
  ref,
  computed
} from 'vue';

const events = ref(null);
async function fetchData() {
  await fetch(`/EventManager/view/search/event`, {
    method: 'GET'
  }).then(resp => resp.json())
    .then(data => {
      console.log(data);
      events.value = data;
    })
}

/*const filteredEvents = computed(() => {
  return events.value.filter((event) => {
    return event.name.toLowerCase().indexOf(input.value.toLowerCase()) !== -1;
  });
})*/

fetchData();

</script>

<style scoped lang="scss">

</style>
