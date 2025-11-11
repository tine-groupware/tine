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
    <div class="title my-3">{{eventDetails.name}}</div>
    <div v-if="eventDetails.description" style="white-space: pre-wrap;">{{eventDetails.description}}</div>
    <h5 class="my-3">{{formatMessage('Information of the event:')}}</h5>
    <div class="row mb-3" v-if="eventDetails.start && eventDetails.start !== '1.1.1970'">
      <div class="col-4">{{formatMessage('When:')}}</div>
      <div class="col-8">{{eventDetails.start}}</div>
    </div>
    <div class="row mb-3" v-if="eventDetails.end && eventDetails.end !== '1.1.1970'">
      <div class="col-4">{{formatMessage('Until:')}}</div>
      <div class="col-8">{{eventDetails.end}}</div>
    </div>
    <div class="row mb-3" v-if="eventDetails.appointments && eventDetails.appointments.length > 0">
      <div class="col-4">{{formatMessage('Appointments:')}}</div>
      <div class="col-8">
        <div v-for="appointment in eventDetails.appointments" :key="appointment.id" class="mb-3">
          <div><strong>{{formatMessage('Session')}} {{appointment.session_number}}</strong></div>
          <div>{{appointment.formattedDate}} | {{appointment.formattedStartTime}} - {{appointment.formattedEndTime}}</div>
          <div v-if="appointment.description">{{appointment.description}}</div>
        </div>
      </div>
    </div>
    <div class="row mb-3" v-if="eventDetails.location && eventDetails.location.adr_one_postalcode">
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
          <p>{{ formatMessage('Please enter your details to register for this event:') }}</p>
          <div class="form-group mb-3">
            <label>{{ formatMessage('First Name:') }}</label>
            <input
              id="name-input"
              v-model="userFirstName"
              type="text"
              class="form-control"
              :placeholder="formatMessage('Enter your first name')"
              required
              @keyup.enter="handleEmailSubmit"
            />
            <div v-if="userFirstName && !isNameValid" class="text-danger mt-1">
              {{ formatMessage('Please enter your first name.') }}
            </div>
          </div>
          <div class="form-group mb-3">
            <label>{{ formatMessage('Last Name:') }}</label>
            <input
              id="name-input"
              v-model="userLastName"
              type="text"
              class="form-control"
              :placeholder="formatMessage('Enter your last name')"
              required
              @keyup.enter="handleEmailSubmit"
            />
            <div v-if="userLastName && !isNameValid" class="text-danger mt-1">
              {{ formatMessage('Please enter your last name.') }}
            </div>
          </div>
          <div class="form-group">
            <label>{{ formatMessage('Email Address:') }}</label>
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
              :disabled="!isFormValid || isSubmitting"
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
      <div class="text-end">
        <b-button @click="openEmailModal" variant="primary">
          {{ formatMessage('Manage my registration') }}
        </b-button>
        <b-button @click="openEmailModal" variant="primary" class="mx-3">
          {{ formatMessage('Registration') }}
        </b-button>
      </div>
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
const userFirstName = ref('');
const userLastName = ref('');
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

const isNameValid = computed(() => {
  return userFirstName.value.trim().length > 0 && userLastName.value.trim().length > 0;
});

const isEmailValid = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return userEmail.value.length > 0 && emailRegex.test(userEmail.value);
});

const isFormValid = computed(() => {
  return isNameValid.value && isEmailValid.value;
});

const openEmailModal = () => {
  showEmailModal.value = true;
};

const resetEmailForm = () => {
  userFirstName.value = '';
  userLastName.value = '';
  userEmail.value = '';
  isSubmitting.value = false;
};

const handleEmailSubmit = async () => {

  if (!isEmailValid.value || isSubmitting.value) {
    return;
  }

  isSubmitting.value = true;

  try {
    const eventId = route.params.id;
    const response = await fetch(`/EventManager/registration/doubleOptIn/${eventId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        eventId: route.params.id,
        n_given: userFirstName.value,
        n_family: userLastName.value,
        email: userEmail.value,
      })
    });

    if (response.ok) {
      const responseData = await response.json();

      if (responseData[0]) { //if contact is known
        const baseUrl = window.location.origin;
        window.location.href = `${baseUrl}${responseData[0]}`;
        return;
      }

      showEmailModal.value = false;
      modalTitle.value = 'Registration Request Sent';
      modalMessage.value = 'Please check your email and click the confirmation link to complete your registration.';
      showModal.value = true;
    } else {
      throw new Error('Registration request failed');
    }
  } catch (error) {
    console.error(error);
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
      const dateFormat = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      };

      const formatDate = (date, formatter) => {
        const d = new Date(date);
        if (isNaN(d.getTime()) || d.getTime() < 86400000) {
          return null;
        }
        return d.toLocaleString("de-DE", formatter);
      };

      data.start = formatDate(data.start, dateFormat);
      data.end = formatDate(data.end, dateFormat);
      data.registration_possible_until = formatDate(data.registration_possible_until, {day: '2-digit', month: '2-digit', year: 'numeric'});

      if (data.appointments && data.appointments.length > 0) {
        data.appointments = data.appointments.map(appointment => {
          const date = new Date(appointment.session_date).toLocaleDateString("de-DE");
          const startTime = appointment.start_time.substring(0, 5); // Remove seconds
          const endTime = appointment.end_time.substring(0, 5); // Remove seconds

          return {
            ...appointment,
            formattedDate: date,
            formattedStartTime: startTime,
            formattedEndTime: endTime
          };
        });
      }

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
