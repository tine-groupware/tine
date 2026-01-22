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
  <div class="account-container">
  <b-container class="registration-wrapper">
    <b-row class="title text-center">
      <b-col>
        <h1>{{ formatMessage('Manage Your Registrations') }}</h1>
      </b-col>
    </b-row>

    <div v-if="selfRegisteredEvents.length">
      <h5 class="section-heading">{{ formatMessage('My Events') }}</h5>
      <b-table
        bordered
        hover
        responsive
        :items="selfRegisteredEvents"
        :fields="fields"
        small
      >

        <template #cell(name)="{ item }">
          <a
            :href="getEventLink(item.event_id)"
            target="_blank"
            class="event-link"
          >
            {{ getEventName(item.event_id) }}
          </a>
        </template>

        <template #cell(date)="{ item }">
          <span>{{ getEventDate(item.event_id) }}</span>
        </template>

        <template #cell(status)="{ item }">
          <span>{{ formatMessage(item.status) }}</span>
        </template>

        <template #cell(option)="{ item }">
          <b-button
            v-if="item.status === 'Cancelled'"
            class="action-button-table"
            size="sm"
            @click="registerAgain(item, true)"
          >
            {{ formatMessage('Register Again') }}
          </b-button>
          <div v-else>
            <b-button
              class="action-button-table"
              size="sm"
              @click="registerAgain(item, false)"
            >
              {{ formatMessage('Update') }}
            </b-button>
            <b-button
              class="action-button-table"
              size="sm"
              @click="openCancelConfirmation(item)"
            >
              {{ formatMessage('Cancel Registration') }}
            </b-button>
          </div>
        </template>
      </b-table>
    </div>

    <div v-else class="text-center mt-5">
      <h6>{{formatMessage('You don’t have any registrations for yourself yet — get started by creating one!')}}</h6>
    </div>

    <div v-if="otherParticipants.length">
      <h6 class="section-heading">
        {{formatMessage('Users you registered')}}
      </h6>
    </div>
    <div
      v-if="otherParticipants.length"
      v-for="participant in otherParticipants"
      :key="participant.original_id"
    >
      <h6 class="user-heading">{{ participant.name }}</h6>
      <b-table
        bordered
        hover
        responsive
        :items="participant.events"
        :fields="fields"
        small
      >
        <template #cell(name)="{ item }">
          <a
          :href="getEventLink(item.event_id)"
          target="_blank"
          class="event-link"
          >
          {{ getEventName(item.event_id) }}
          </a>
        </template>

        <template #cell(date)="{ item }">
          <span>{{ getEventDate(item.event_id) }}</span>
        </template>

        <template #cell(status)="{ item }">
          <span>{{ formatMessage(item.status) }}</span>
        </template>

        <template #cell(option)="{ item }">
          <b-button
            v-if="item.status === 'Cancelled'"
            class="action-button-table"
            size="sm"
            @click="registerAgain(item, true)"
          >
            {{ formatMessage('Register Again') }}
          </b-button>
          <div v-else>
            <b-button
              class="action-button-table"
              size="sm"
              @click="registerAgain(item, false)"
            >
              {{ formatMessage('Update') }}
            </b-button>
            <b-button
              class="action-button-table"
              size="sm"
              @click="openCancelConfirmation(item)"
            >
              {{ formatMessage('Cancel Registration') }}
            </b-button>
          </div>
        </template>
      </b-table>
    </div>

    <div class="button-group">
      <b-button class="action-button" @click="openCreateNewProfile">{{formatMessage('Create a new registration')}}</b-button>
    </div>

    <b-modal
      v-model="modal.show"
      :title="modal.title"
      :ok-only="modal.okOnly"
      :ok-title="modal.okText"
      :ok-variant="modal.dangerButton ? 'danger' : 'primary'"
      :cancel-title="modal.cancelText"
      @ok="handleModalAction"
      @cancel="handleModalCancel"
    >
      <p v-html="modal.message"></p>

      <div v-if="modal.type === 'new-profile'">
        <b-form-group :label="formatMessage('Select an Event')">
          <b-form-select v-model="selectedEventId" :options="eventDropdownOptions" />
        </b-form-group>
        <b-form-group :label="formatMessage('Select a Person')">
          <b-form-select v-model="selectedParticipantId" :options="participantsDropdownOptions" />
        </b-form-group>
      </div>
    </b-modal>
  </b-container>
  </div>
</template>

<script setup>
import {ref, reactive, computed} from 'vue';
import {useRoute} from 'vue-router';
import "./AccountManagement.vue";
import {useFormatMessage} from './index.es6';
import participantId from "../../../../library/ExtJS/src/core/Ext-more";
import SelectedEventId from "../../../../library/ExtJS/src/core/Ext-more";
const { formatMessage } = useFormatMessage();

const route = useRoute();

const modal = reactive({
  show: false,
  title: '',
  message: '',
  type: 'info',
  okOnly: true,
  okText: 'OK',
  cancelText: 'Cancel',
  dangerButton: false,
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
  modal.dangerButton = config.dangerButton || false;
  modal.onConfirm = config.onConfirm || null;
  modal.onCancel = config.onCancel || null;
  modal.show = true;
};

const handleModalAction = () => {
  if (modal.onConfirm) {
    modal.onConfirm();
  }
  modal.show = false;
};

const handleModalCancel = () => {
  if (modal.onCancel) {
    modal.onCancel();
  }
  modal.show = false;
};

const isLoading = ref(true);
const registrantEvents = ref(null);
const participants = ref(null);
const dependantParticipants = ref(null);
const events = ref([]);
const selectedParticipantId = ref(null);
const selectedEventId = ref(null);

const selectedEventRegisterOthers = computed(() => {
  if (!selectedEventId.value || !events.value) return null;
  const event = events.value.find(e => e.id === selectedEventId.value);
  return event ? event.register_others : null;
});

const registrantId = computed(() => {
  if (!participants.value) return null;

  if (participants.value.original_id) {
    return participants.value.original_id;
  }

  if (participants.value.length > 0 && participants.value[0].registrant) {
    return participants.value[0].registrant.original_id;
  }

  return null;
});

const participantsDropdownOptions = computed(() => {
  const registerOthers = selectedEventRegisterOthers.value;
  const options = [];

  if (!registerOthers) {
    return [{ value: null, text: formatMessage('Please select an event first'), disabled: true }];
  }

  const registerOthersNum = Number(registerOthers);

  if (registerOthersNum === 1) {
    options.push({ value: null, text: formatMessage('New participant') });
  }

  const seen = new Set();

  if (participants.value) {
    if (participants.value.n_fileas) {
      if (!seen.has(participants.value.original_id)) {
        options.push({
          value: participants.value.original_id,
          text: participants.value.n_fileas
        });
        seen.add(participants.value.original_id);
      }
    } else if (participants.value.length > 0) {
      const registrantParticipant = participants.value.find(p =>
        p.participant.original_id === registrantId.value
      );

      if (registrantParticipant && !seen.has(registrantParticipant.participant.original_id)) {
        options.push({
          value: registrantParticipant.participant.original_id,
          text: registrantParticipant.participant.n_fileas
        });
        seen.add(registrantParticipant.participant.original_id);
      }
    }
  }

  if (registerOthersNum === 1 || registerOthersNum === 3) {

    if (participants.value && Array.isArray(participants.value)) {
      participants.value.forEach(registration => {
        const participantId = registration.participant?.original_id;
        const participantName = registration.participant?.n_fileas;
        const isNotSelf = participantId !== registrantId.value;

        if (isNotSelf && participantId && participantName && !seen.has(participantId)) {
          options.push({
            value: participantId,
            text: participantName
          });
          seen.add(participantId);
        }
      });
    }

    if (dependantParticipants.value && dependantParticipants.value.length > 0) {
      dependantParticipants.value.forEach(p => {
        if (p.id && p.n_fileas && !seen.has(p.id)) {
          options.push({
            value: p.id,
            text: p.n_fileas
          });
          seen.add(p.id);
        }
      });
    }
  }

  return options;
});

const eventDropdownOptions = computed(() => {
  return [
    { value: null, text: formatMessage('Please select an event'), disabled: true },

    ...events.value.map(e => ({
      value: e.id,
      text: e.name
    }))
  ];
});

const fields = [
  { key: 'name', label: formatMessage('Event'), thStyle: { width: '30%' } },
  { key: 'date', label: formatMessage('Date'), thStyle: { width: '30%' } },
  { key: 'status', label: formatMessage('Status'), thStyle: { width: '20%' } },
  { key: 'option', label: formatMessage('Action'), thStyle: { width: '20%' } }
]

function getEventLink(eventId) {
  const baseUrl = window.location.origin
  return `${baseUrl}/EventManager/view/event/${eventId}`
}

function getEventName(eventId) {
  if (!events.value || events.value.length === 0) {
    return 'Loading...';
  }
  const event = events.value.find(e => e.id === eventId);
  return event ? event.name : `${eventId}`;
}

function getEventDate(eventId) {
  if (!events.value || events.value.length === 0) {
    return 'Loading...';
  }
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

  const event = events.value.find(e => e.id === eventId);
  if (!event) return `${eventId}`;
  const formattedDate = formatDate(event.start, dateFormat);
  return formattedDate || formatMessage('TBD');
}

function sortRegistrationsByEventDate(registrations) {
  return registrations.sort((a, b) => {
    const isCancelledA = a.status === 'Cancelled';
    const isCancelledB = b.status === 'Cancelled';

    if (isCancelledA && !isCancelledB) return 1;
    if (!isCancelledA && isCancelledB) return -1;

    const eventA = events.value.find(e => e.id === a.event_id);
    const eventB = events.value.find(e => e.id === b.event_id);

    if (!eventA || !eventB) return 0;

    const dateA = new Date(eventA.start);
    const dateB = new Date(eventB.start);

    // Invalid dates go to the end (but before cancelled)
    const isValidDateA = !isNaN(dateA.getTime()) && dateA.getTime() >= 86400000;
    const isValidDateB = !isNaN(dateB.getTime()) && dateB.getTime() >= 86400000;

    if (!isValidDateA && isValidDateB) return 1;
    if (isValidDateA && !isValidDateB) return -1;
    if (!isValidDateA && !isValidDateB) return 0;

    return dateA - dateB;
  });
}

const selfRegisteredEvents = computed(() => {
  if (!participants.value || !participants.value.length) return [];
  const filtered = participants.value.filter(registration => {
    const participantId = registration.participant?.original_id;
    const registrantId = registration.registrant?.original_id;
    return participantId === registrantId;
  });
  return sortRegistrationsByEventDate(filtered);
});

const otherParticipants = computed(() => {
  if (!participants.value || !participants.value.length) return [];
  const participantsMap = new Map();
  participants.value.forEach(registration => {
    const participantId = registration.participant?.original_id;
    const registrantId = registration.registrant?.original_id;

    if (participantId !== registrantId) {
      const participantName =  registration.participant?.n_fn;

      if (!participantsMap.has(participantId)) {
        participantsMap.set(participantId, {
          id: participantId,
          name: participantName,
          events: []
        });
      }

      participantsMap.get(participantId).events.push(registration);
    }
  });

  participantsMap.forEach(participant => {
    sortRegistrationsByEventDate(participant.events);
  });

  return Array.from(participantsMap.values());
});

async function registerAgain(registration, isReregistered) {
  const baseUrl = window.location.origin;
  const eventId = registration.event_id;
  const token = route.params.token;
  const participantId = registration.participant?.original_id;
  window.location.href = `${baseUrl}/EventManager/view/event/${eventId}/registration/${token}?participantId=${participantId}&isReregistered=${isReregistered}`;
}

function openCreateNewProfile() {
  selectedEventId.value = null;
  selectedParticipantId.value = participants.value.n_fileas? participants.value.original_id : participants.value[0].registrant.original_id;

  showModal({
    title: formatMessage('New Registration'),
    message: formatMessage('Please select an event to register under this email address:'),
    type: 'new-profile',
    okOnly: false,
    okText: formatMessage('Continue'),
    cancelText: formatMessage('Cancel'),
    onConfirm: handleProfileSubmit
  });
}

const handleProfileSubmit = async () => {
  if (!selectedEventId.value) {
    alert(formatMessage('Please select an event.'));
    return;
  }
  const isSelfRegistration = selfRegisteredEvents.value.length > 0 &&
    selfRegisteredEvents.value[0].participant?.original_id === selectedParticipantId.value;
  const baseUrl = window.location.origin;
  const token = route.params.token;
  const newProfileParam = isSelfRegistration ? 'false' : 'true';
  window.location.href = `${baseUrl}/EventManager/view/event/${selectedEventId.value}/registration/${token}?newProfile=${newProfileParam}&participantId=${selectedParticipantId.value}`;
};

function openCancelConfirmation(registration) {
  const eventId = registration.event_id;
  showModal({
    title: formatMessage('Cancel Registration'),
    message: `${formatMessage('Do you really want to cancel your registration for')} "<strong>${getEventName(eventId)}</strong>"`,
    type: 'confirm',
    okOnly: false,
    okText: formatMessage('Yes'),
    cancelText: formatMessage('No'),
    onConfirm: () => confirmCancel(registration)
  });
}

async function confirmCancel(registration) {
  const eventId = registration.event_id;
  const token = route.params.token;
  const registrationId = registration.id;
  try {
    const response = await fetch(`/EventManager/deregistration/${eventId}/${token}/${registrationId}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      method: 'POST',
    });

    if (!response.ok) {
      throw new Error('Cancellation failed');
    }

    showModal({
      title: formatMessage('Registration Cancelled'),
      message: formatMessage('Your registration has been cancelled successfully.'),
      type: 'success',
  });
    setTimeout(function () {
      location.reload();
    }, 2000);
  } catch (error) {
    console.error(error);
    showModal({
      title: formatMessage('Error'),
      message: formatMessage('Could not cancel your registration. Please try again later.'),
      type: 'error'
    });
  }
}

async function getRegistrantEvents() {
  let token = route.params.token;
  try {
    const resp = await fetch(`/EventManager/account/${token}`, {
      method: 'GET'
    });
    registrantEvents.value = await resp.json();
    participants.value = registrantEvents.value[0];
    dependantParticipants.value = registrantEvents.value[1];
  } catch (error) {
    console.error('Error fetching contact details: ', error);
  }
}

async function fetchData() {
  try {
    const resp = await fetch(`/EventManager/events`, {
      method: 'GET'
    });
    events.value = await resp.json();
  } catch (error) {
    console.error('Error fetching events:', error);
  }
}

async function initializeData() {
  await Promise.all([
    getRegistrantEvents(),
    fetchData()
  ]);
  isLoading.value = false;
}
initializeData();

</script>

<style lang="scss">

.account-container {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
  padding: 3rem 0;
  width: 100%;
}

.registration-wrapper {
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

.section-heading {
  color: #2c3e50;
  font-weight: 600;
  font-size: 1.3rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e9ecef;
}

.user-heading {
  color: #2c3e50;
  font-weight: 600;
  font-size: 1rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.event-link {
  color: black;
  text-decoration: none;
}
.event-link:hover {
  text-decoration: underline;
}

b-table {
  margin-top: 1.5rem;
}

.button-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px solid #e9ecef;
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

  &:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
  }

  &:active {
    transform: scale(0.98);
  }
}

.action-button-table {
  font-weight: 500;
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

  &:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
  }

  &:active {
    transform: scale(0.98);
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
  .container {
    padding: 1.5rem 0;
  }

  .registration-wrapper {
    padding: 1.5rem;
    border-radius: 8px;
  }

  .title {
    font-size: 2rem;

    h1 {
      font-size: 2rem;
    }

    h5 {
      font-size: 1rem;
    }
  }

  .section-heading {
    font-size: 1.1rem;
  }

  .form-group {
    .col-form-label {
      margin-bottom: 0.5rem;
    }
  }

  .button-group {
    flex-direction: column;

    .action-button {
      width: 100%;
    }
  }
}

</style>
