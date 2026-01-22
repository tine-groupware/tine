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
  <div class="event-detail-container">
    <div class="event-detail-card">
      <div class="title my-4">{{eventDetails.name}}</div>

      <div v-if="eventDetails.description" class="description-section mb-4">
        <MarkdownRenderer :content="eventDetails.description" />
      </div>

      <h5 class="section-heading mb-4">{{formatMessage('Information of the event:')}}</h5>

      <div class="info-grid">
        <div class="info-row" v-if="eventDetails.start && eventDetails.start !== '1.1.1970'">
          <div class="info-label">{{formatMessage('When:')}}</div>
          <div class="info-value">{{eventDetails.start}}</div>
        </div>

        <div class="info-row" v-if="eventDetails.end && eventDetails.end !== '1.1.1970'">
          <div class="info-label">{{formatMessage('Until:')}}</div>
          <div class="info-value">{{eventDetails.end}}</div>
        </div>

        <div class="info-row appointments-row" v-if="eventDetails.appointments && eventDetails.appointments.length > 0">
          <div class="info-label">{{formatMessage('Appointments:')}}</div>
          <div class="info-value">
            <div v-for="appointment in eventDetails.appointments" :key="appointment.id" class="appointment-card">
              <div class="appointment-session"><strong>{{formatMessage('Session')}} {{appointment.session_number}}</strong></div>
              <div class="appointment-time">{{appointment.formattedDate}} | {{appointment.formattedStartTime}} - {{appointment.formattedEndTime}}</div>
              <MarkdownRenderer v-if="appointment.description" :content="appointment.description" class="appointment-description" />
            </div>
          </div>
        </div>

        <div class="info-row" v-if="eventDetails.location && eventDetails.location.adr_one_postalcode">
          <div class="info-label">{{formatMessage('Address:')}}</div>
          <div class="info-value">
            <p class="mb-0">{{_.get(eventDetails, 'location.adr_one_street')}}</p>
            <p class="mb-0">{{_.get(eventDetails, 'location.adr_one_postalcode')}}</p>
            <p class="mb-0">{{_.get(eventDetails, 'location.adr_one_locality')}}</p>
          </div>
        </div>

        <div class="info-row" v-if="eventDetails.fee">
          <div class="info-label">{{formatMessage('Fee:')}}</div>
          <div class="info-value">{{eventDetails.fee}} Euros</div>
        </div>

        <div class="info-row" v-if="eventDetails.registration_possible_until && eventDetails.registration_possible_until !== '1.1.1970'">
          <div class="info-label">{{formatMessage('Registration possible until:')}}</div>
          <div class="info-value">{{eventDetails.registration_possible_until}}</div>
        </div>
      </div>

      <div>
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

        <div class="button-group">
          <b-button @click="openEmailModal" class="action-button">
            {{ formatMessage('Manage my registration') }}
          </b-button>
          <b-button @click="openEmailModal" class="action-button">
            {{ formatMessage('Register now') }}
          </b-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref, computed, reactive} from 'vue';
import {useRoute} from 'vue-router';
import _ from 'lodash';
import MarkdownRenderer from './../../../../Tinebase/js/MarkdownRenderer.vue';
import {useFormatMessage} from './index.es6';
const { formatMessage } = useFormatMessage();

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
        title: formatMessage('Confirmation E-Mail Sent'),
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
  await fetch(`/EventManager/event/${eventId}`, {
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

<style lang="scss">
.event-detail-container {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 3rem 0;
  width: 100%;
}

.event-detail-card {
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
  margin-bottom: 1.5rem;
}

.description-section {
  color: #6c757d;
  line-height: 1.8;
  font-size: 1.05rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #2c3e50;
}

.section-heading {
  color: #2c3e50;
  font-weight: 600;
  font-size: 1.3rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e9ecef;
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.info-row {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 1.5rem;
  padding: 1rem 0;
  border-bottom: 1px solid #e9ecef;

  &:last-child {
    border-bottom: none;
  }
}

.appointments-row {
  grid-template-columns: 200px 1fr;
  align-items: start;
}

.info-label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 1rem;
}

.info-value {
  color: #495057;
  font-size: 1rem;
  line-height: 1.6;
}

.appointment-card {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  border-left: 3px solid #2c3e50;

  &:last-child {
    margin-bottom: 0;
  }
}

.appointment-session {
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.appointment-time {
  color: #6c757d;
  font-size: 0.95rem;
  margin-bottom: 0.5rem;
}

.appointment-description {
  color: #495057;
  font-size: 0.9rem;
  font-style: italic;
}

.button-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.action-button {
  font-weight: 500;
  padding: 0.75rem 1.75rem;
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

.modal-content {
  border-radius: 12px !important;
  border: none !important;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
}

.modal-header {
  background-color: #f8f9fa !important;
  border-bottom: 2px solid #e9ecef !important;
  border-radius: 12px 12px 0 0 !important;
  padding: 1.5rem !important;

  .modal-title {
    color: #2c3e50 !important;
    font-weight: 600 !important;
  }

  .close {
    color: #2c3e50 !important; opacity: 0.7 !important;
    transition: opacity 0.3s ease !important;
    &:hover {
      opacity: 1 !important;
    }
  }
}

.modal-body {
  padding: 1.5rem !important;
  color: #495057 !important;
}

.modal-footer {
  border-top: 2px solid #e9ecef !important;
  padding: 1rem 1.5rem !important;
  .btn {
    padding: 0.5rem 1.5rem !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    transition: all 0.3s ease !important;
  }

  .btn-primary {
    background-color: #2c3e50 !important;
    border-color: #2c3e50 !important;

    &:hover {
      background-color: #1a252f !important;
      border-color: #1a252f !important;
      transform: scale(1.05);
    }
  }

  .btn-secondary {
    background-color: #6c757d !important;
    border-color: #6c757d !important;

    &:hover {
      background-color: #5a6268 !important;
      border-color: #545b62 !important;
    }
  }

  .btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    &:hover {
      background-color: #c82333 !important;
      border-color: #bd2130 !important;
      transform: scale(1.05);
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .event-detail-card {
    padding: 1.5rem;
  }

  .title {
    font-size: 2rem;
  }

  .info-row {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }

  .appointments-row {
    grid-template-columns: 1fr;
  }

  .button-group {
    flex-direction: column;

    .action-button {
      width: 100%;
    }
  }
}
</style>
