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
      <b-modal ref="emailModal" v-model="showEmailModal" :title="formatMessage('Event Registration')" @hidden="resetEmailForm" hide-footer>
        <div class="mb-3">
          <p>{{ formatMessage('Please enter your email address to register for this event:') }}</p>
          <div class="form-group">
            <label for="email-input">{{ formatMessage('Email Address:') }}</label>
            <input
              id="email-input"
              v-model="userEmail"
              type="email"
              class="form-control"
              :placeholder="formatMessage('Enter your email address')"
              required
              @keyup.enter="handleEmailSubmit"
            />
            <div v-if="userEmail && !isEmailValid" class="text-danger mt-1">
              {{ formatMessage('Please enter a valid email address.') }}
            </div>
          </div>

          <div class="mt-3 d-flex justify-content-end gap-2">
            <button class="btn btn-secondary" @click="showEmailModal = false">
              {{ formatMessage('Cancel') }}
            </button>
            <button
              class="btn btn-primary"
              @click="handleEmailSubmit"
              :disabled="!isEmailValid || isSubmitting"
            >
              <span v-if="isSubmitting">{{ formatMessage('Sending...') }}</span>
              <span v-else>{{ formatMessage('Send Registration Request') }}</span>
            </button>
          </div>
        </div>
      </b-modal>

      <b-modal v-model="showModal" :title="formatMessage(modalTitle)" hide-footer>
        <p>{{ formatMessage(modalMessage) }}</p>
        <b-button @click="handleModalClose" variant="primary">OK</b-button>
      </b-modal>

      <b-button @click="openEmailModal" variant="primary">
        {{ formatMessage('Registration') }}
      </b-button>
    </div>
  </div>
</template>

<script setup>
import {inject, ref, computed} from 'vue';
import {translationHelper} from "./keys";
import {useRoute} from 'vue-router';
import _ from 'lodash';

const formatMessage = inject(translationHelper);
const route = useRoute();
const showModal = ref(false);
const showEmailModal = ref(false);
const modalTitle = ref('');
const modalMessage = ref('');
const userEmail = ref('');
const isSubmitting = ref(false);
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

// Email validation computed property
const isEmailValid = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return userEmail.value.length > 0 && emailRegex.test(userEmail.value);
});

const emailValidation = computed(() => {
  if (userEmail.value.length === 0) return null;
  return isEmailValid.value;
});

const openEmailModal = () => {
  console.log('Opening email modal');
  showEmailModal.value = true;
};

const resetEmailForm = () => {
  console.log('Resetting email form');
  userEmail.value = '';
  isSubmitting.value = false;
};

// THIS IS THE PROBLEMATIC FUNCTION
const handleEmailSubmit = async () => {

  if (!isEmailValid.value || isSubmitting.value) {
    return;
  }

  isSubmitting.value = true;

  try {
    const response = await fetch(`/EventManager/registration/doubleOptIn`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        eventId: route.params.id,
        email: userEmail.value
      })
    });

    if (response.ok) {
      console.log('Registration request successful');
      showEmailModal.value = false;
      modalTitle.value = 'Registration Request Sent';
      modalMessage.value = 'Please check your email and click the confirmation link to complete your registration.';
      showModal.value = true;
    } else {
      throw new Error('Registration request failed');
    }
  } catch (error) {
    console.error('Registration error:', error);
    modalTitle.value = 'Registration Error';
    modalMessage.value = 'There was an error sending your registration request. Please try again later.';
    showModal.value = true;
  } finally {
    isSubmitting.value = false;
  }
};

const handleModalClose = () => {
  showModal.value = false;
};

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
