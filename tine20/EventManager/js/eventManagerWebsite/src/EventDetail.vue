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

      <b-modal
        v-model="modal.show"
        :title="modal.title"
        :ok-only="modal.okOnly"
        :ok-title="modal.okText"
        :cancel-title="modal.cancelText"
        @ok="handleModalAction"
        @cancel="handleModalCancel"
      >
        <p v-html="modal.message"></p>

        <div v-if="modal.input" class="form-group">
          <b-form-input
            v-model="modal.inputValue"
            type="email"
            :placeholder="modal.inputPlaceholder"
            :state="modal.inputError ? false : null"
            required
          ></b-form-input>
          <b-form-invalid-feedback :state="!modal.inputError">
            {{ modal.inputError }}
          </b-form-invalid-feedback>
        </div>
      </b-modal>

      <div>
        <b-button @click="openEmailModal" variant="primary">
          {{ formatMessage('Manage my registration') }}
        </b-button>
        <b-button @click="openEmailModal" variant="primary" class="mx-3">
          {{ formatMessage('Register now') }}
        </b-button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {inject, ref, computed, reactive} from 'vue';
import {translationHelper} from "./keys";
import {useRoute} from 'vue-router';
import _ from 'lodash';

const formatMessage = inject(translationHelper);
const route = useRoute();
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
const modal = reactive({
  show: false,
  title: '',
  message: '',
  type: 'info',
  okOnly: true,
  okText: 'OK',
  cancelText: 'Cancel',
  input: false,
  inputValue: '',
  inputPlaceholder: '',
  inputError: '',
  onConfirm: null,
  onCancel: null
});

const showModal = (config) => {
  modal.title = config.title || '';
  modal.message = config.message || '';
  modal.type = config.type || 'info';
  modal.okOnly = config.okOnly !== false;
  modal.okText = config.okText || 'OK';
  modal.cancelText = config.cancelText || formatMessage('Cancel');
  modal.input = config.input || false;
  modal.inputValue = config.inputValue || '';
  modal.inputPlaceholder = config.inputPlaceholder || '';
  modal.onConfirm = config.onConfirm || null;
  modal.onCancel = config.onCancel || null;
  modal.show = true;
};

const handleModalAction = (bvModalEvent) => {
  if (modal.input && !isEmailValid.value) {
    bvModalEvent.preventDefault();
    modal.inputError = formatMessage('Please enter a valid email address');
    return;
  }
  modal.inputError = '';

  if (modal.onConfirm) {
    modal.onConfirm(modal.input ? modal.inputValue : undefined);
  }

  modal.show = false;
  modal.inputValue = '';
  modal.inputError = '';
};

const handleModalCancel = () => {
  if (modal.onCancel) {
    modal.onCancel();
  }
  modal.show = false;
  modal.inputValue = '';
};

const isEmailValid = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return modal.inputValue.length > 0 && emailRegex.test(modal.inputValue);
});

const openEmailModal = () => {
  showModal({
    title: formatMessage('Email Address'),
    message: formatMessage('Please enter your email address to continue:'),
    type: 'confirm',
    okOnly: false,
    input: true,
    inputPlaceholder: formatMessage('Enter your email address'),
    onConfirm: handleEmailSubmit
  });
};

const handleEmailSubmit = async () => {
  try {
    const eventId = route.params.id;
    const response = await fetch(`/EventManager/registration/doubleOptIn/${eventId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        eventId: route.params.id,
        email: modal.inputValue,
      })
    });

    if (response.ok) {
      const responseData = await response.json();

      if (responseData[0]) { //if contact is known
        const baseUrl = window.location.origin;
        window.location.href = `${baseUrl}${responseData[0]}`;
        return;
      }

      showModal({
        title: formatMessage('Registration Request Sent'),
        message: formatMessage('Please check your email and click the confirmation link to continue.'),
        type: 'confirm',
        okOnly: true,
      });
    } else {
      throw new Error('Registration request failed');
    }
  } catch (error) {
    console.error(error);
    showModal({
      title: formatMessage('Registration Error'),
      message: formatMessage('There was an error sending your registration request. Please try again later.'),
      type: 'confirm',
      okOnly: true,
    });
  }
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
