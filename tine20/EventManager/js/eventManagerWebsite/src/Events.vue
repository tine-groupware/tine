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
  <b-container fluid class="events-container">

    <b-row class="text-center my-5">
      <b-col>
        <h1 class="page-title">{{formatMessage('Events')}}</h1>
      </b-col>
    </b-row>

    <b-row class="justify-content-center" v-if="filteredEvents.length > 0">
      <b-col lg="10" xl="8">
        <div class="events-list">
          <BCard
            v-for="event in filteredEvents"
            :key="event.id"
            :title="event.name"
            tag="article"
            class="event-card mb-4"
          >
            <BCardText>
              <div v-if="event.tags && event.tags.length > 0" class="mb-3">
                <b-badge
                  v-for="(tag, index) in event.tags"
                  :key="index"
                  variant="primary"
                  class="tags me-2 mb-1"
                >
                  {{ tag.name }}
                </b-badge>
              </div>
              <MarkdownRenderer
                v-if="event.description && event.description.length < 255"
                :content="event.description"
              />
              <MarkdownRenderer
                v-else-if="event.description"
                :content="event.description.substring(0, 255) + '...'"
              />
            </BCardText>
            <b-button :to="{ path: '/event/'+ event.id }" class="info-button">
              {{formatMessage('More Information')}}
            </b-button>
          </BCard>
        </div>
      </b-col>
    </b-row>

    <b-row v-else class="justify-content-center my-5">
      <b-col lg="10" xl="8" class="text-center">
        <p>{{formatMessage('No events found matching your search.')}}</p>
      </b-col>
    </b-row>
  </b-container>
</template>

<script setup>

import {ref, onBeforeMount, computed} from 'vue';
import MarkdownRenderer from "../../../../Tinebase/js/MarkdownRenderer.vue";
import {getSearchFromUrl} from './searchUtils';
import {useFormatMessage} from './index.es6';

const { formatMessage } = useFormatMessage();

const searchQuery = ref(getSearchFromUrl());

const events = ref([]);

const filteredEvents = computed(() => {
  if (!searchQuery.value || searchQuery.value.trim() === '') {
    return events.value;
  }

  const query = searchQuery.value.toLowerCase();

  return events.value.filter(event => {
    const nameMatches = event.name.toLowerCase().includes(query);
    const tagMatches = event.tags && event.tags.some(tag =>
      tag.name && tag.name.toLowerCase().includes(query)
    );

    return nameMatches || tagMatches;
  });
});

async function fetchData() {
  try {
    const resp = await fetch(`/EventManager/events`, {
      method: 'GET'
    });
    events.value = await resp.json();
    console.log(events.value);
  } catch (error) {
    console.error('Error fetching events:', error);
  }
}

const loading = ref(true);
onBeforeMount(async () => {
  loading.value = true;
  try {
    await fetchData();
  } catch (e) {}
  document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
  loading.value = false;
})
</script>

<style scoped lang="scss">
.events-container {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 3rem 0;
  width: 100%;
}

.page-title {
  font-weight: 700;
  color: #2c3e50;
  font-size: 2.5rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 0;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.event-card {
  width: 100%;
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  background: white;

  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
  }

  :deep .card-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.75rem;
  }

  :deep .card-body {
    padding: 1.5rem;
  }

  :deep .card-text {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 1.25rem;
    font-size: 1rem;
  }
}

:deep .info-button {
  font-weight: 500;
  padding: 0.6rem 1.5rem;
  border-radius: 8px;
  transition: all 0.3s ease;
  color: #2c3e50 !important;
  border: 2px solid #2c3e50 !important;
  background-color: transparent !important;

  &:hover {
    transform: scale(1.05);
    background-color: #2c3e50 !important;
    border-color: #2c3e50 !important;
    color: white !important;
    box-shadow: 0 2px 6px rgba(44, 62, 80, 0.3);
  }
}

.tags {
  background-color: #2c3e50 !important;
  border-color: #2c3e50 !important;
  color: white !important;
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }

  .event-card {
    :deep .card-title {
      font-size: 1.25rem;
    }
  }
}
</style>
