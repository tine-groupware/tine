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

      <!-- Self Registrations Section -->
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

      <!-- No Registrations Message -->
      <div v-else class="text-center mt-5">
        <h6>{{ formatMessage('You don\'t have any registrations for yourself yet — get started by creating one!') }}</h6>
      </div>

      <!-- Other Participants Section -->
      <div v-if="otherParticipants.length">
        <h6 class="section-heading">
          {{ formatMessage('Users you registered') }}
        </h6>
      </div>
      <div
        v-if="otherParticipants.length"
        v-for="participant in otherParticipants"
        :key="participant.id"
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

      <!-- Create New Registration Button -->
      <div class="button-group">
        <b-button class="action-button" @click="openCreateNewProfile">
          {{ formatMessage('Create a new registration') }}
        </b-button>
      </div>

      <!-- Modal -->
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
import { ref, reactive, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useFormatMessage } from './index.es6';

const { formatMessage } = useFormatMessage();
const route = useRoute();

const isLoading = ref(true);
const events = ref([]);
const selectedParticipantId = ref(null);
const selectedEventId = ref(null);

// Data from backend
const accountOwner = ref(null);
const registrations = ref([]);
const dependantParticipants = ref([]);

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

const accountOwnerId = computed(() => {
  return accountOwner.value?.original_id || accountOwner.value?.id || null;
});

const selectedEventRegisterOthers = computed(() => {
  if (!selectedEventId.value || !events.value) return null;
  const event = events.value.find(e => e.id === selectedEventId.value);
  return event ? event.register_others : null;
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

  if (accountOwner.value && accountOwner.value.n_fileas) {
    const ownerId = accountOwnerId.value;
    if (ownerId && !seen.has(ownerId)) {
      options.push({
        value: ownerId,
        text: accountOwner.value.n_fileas
      });
      seen.add(ownerId);
    }
  }

  if (registerOthersNum === 1 || registerOthersNum === 3) {
    // Add participants from registrations
    if (registrations.value && Array.isArray(registrations.value)) {
      registrations.value.forEach(registration => {
        const participantId = registration.participant?.original_id || registration.participant?.id;
        const participantName = registration.participant?.n_fileas;
        const isNotSelf = participantId !== accountOwnerId.value;

        if (isNotSelf && participantId && participantName && !seen.has(participantId)) {
          options.push({
            value: participantId,
            text: participantName
          });
          seen.add(participantId);
        }
      });
    }

    // Add dependant participants (relations)
    if (dependantParticipants.value && dependantParticipants.value.length > 0) {
      dependantParticipants.value.forEach(p => {
        const participantId = p.original_id || p.id;
        if (participantId && p.n_fileas && !seen.has(participantId)) {
          options.push({
            value: participantId,
            text: p.n_fileas
          });
          seen.add(participantId);
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
];

const selfRegisteredEvents = computed(() => {
  if (!registrations.value || !Array.isArray(registrations.value)) {
    return [];
  }

  const ownerId = accountOwnerId.value;

  if (!ownerId) {
    return [];
  }

  const filtered = registrations.value.filter(registration => {
    const participantId = registration.participant?.original_id || registration.participant?.id;
    const registrantId = registration.registrant?.original_id || registration.registrant?.id;
    return String(participantId) === String(ownerId) &&
      String(participantId) === String(registrantId);
  });
  return sortRegistrationsByEventDate(filtered);
});

const otherParticipants = computed(() => {
  console.log('=== otherParticipants computation ===');

  if (!registrations.value || !Array.isArray(registrations.value)) {
    console.log('No registrations array, returning empty');
    return [];
  }

  const ownerId = accountOwnerId.value;
  console.log('accountOwnerId:', ownerId);

  if (!ownerId) {
    console.log('No accountOwnerId, returning empty');
    return [];
  }

  const participantsMap = new Map();

  registrations.value.forEach(registration => {
    const participantId = registration.participant?.original_id || registration.participant?.id;
    const registrantId = registration.registrant?.original_id || registration.registrant?.id;

    console.log('Checking registration for otherParticipants:', {
      event_id: registration.event_id,
      participantId,
      registrantId,
      ownerId,
      participantIsOwner: String(participantId) === String(ownerId),
      registrantIsOwner: String(registrantId) === String(ownerId),
      participantEqualsRegistrant: String(participantId) === String(registrantId)
    });

    const isSelfRegistration = String(participantId) === String(ownerId) &&
      String(participantId) === String(registrantId);

    if (!isSelfRegistration) {
      const participantName = registration.participant?.n_fn || registration.participant?.n_fileas;

      console.log('Including in otherParticipants:', participantName);

      if (!participantsMap.has(participantId)) {
        participantsMap.set(participantId, {
          id: participantId,
          name: participantName,
          events: []
        });
      }

      participantsMap.get(participantId).events.push(registration);
    } else {
      console.log('Excluding (self-registration)');
    }
  });

  // Sort events for each participant
  participantsMap.forEach(participant => {
    participant.events = sortRegistrationsByEventDate(participant.events);
  });

  console.log('otherParticipants result:', Array.from(participantsMap.values()));
  return Array.from(participantsMap.values());
});

function sortRegistrationsByEventDate(registrations) {
  return [...registrations].sort((a, b) => {
    const isCancelledA = a.status === 'Cancelled';
    const isCancelledB = b.status === 'Cancelled';

    // Cancelled events go to the end
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

function getEventLink(eventId) {
  const baseUrl = window.location.origin;
  return `${baseUrl}/EventManager/view/event/${eventId}`;
}

function getEventName(eventId) {
  if (!events.value || events.value.length === 0) {
    return 'Loading...';
  }
  const event = events.value.find(e => e.id === eventId);
  return event ? event.name : `Event ${eventId}`;
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
  if (!event) return `Event ${eventId}`;

  const formattedDate = formatDate(event.start, dateFormat);
  return formattedDate || formatMessage('TBD');
}

async function registerAgain(registration, isReregistered) {
  const baseUrl = window.location.origin;
  const eventId = registration.event_id;
  const token = route.params.token;
  const participantId = registration.participant?.original_id || registration.participant?.id;

  window.location.href = `${baseUrl}/EventManager/view/event/${eventId}/registration/${token}?participantId=${participantId}&isReregistered=${isReregistered}`;
}

function openCreateNewProfile() {
  selectedEventId.value = null;
  selectedParticipantId.value = accountOwnerId.value;

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

  const baseUrl = window.location.origin;
  const token = route.params.token;

  // Determine if this is a new profile or existing participant
  const isSelfRegistration = String(selectedParticipantId.value) === String(accountOwnerId.value);
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
    dangerButton: true,
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

    setTimeout(() => {
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

async function fetchAccountData() {
  const token = route.params.token;

  try {
    const resp = await fetch(`/EventManager/account/${token}`, {
      method: 'GET'
    });

    const data = await resp.json();
    console.log('Raw account data from backend:', data);
    const [firstElement, dependants] = data;

    if (Array.isArray(firstElement)) {
      registrations.value = firstElement;

      if (firstElement.length > 0) {
        if (firstElement[0].registrant) {
          accountOwner.value = firstElement[0].registrant;
        } else if (firstElement[0].participant) {
          accountOwner.value = firstElement[0].participant;
        }
      }
    } else if (firstElement && typeof firstElement === 'object') {
      accountOwner.value = firstElement;
      registrations.value = [];
    } else {
      accountOwner.value = null;
      registrations.value = [];
    }

    dependantParticipants.value = Array.isArray(dependants) ? dependants : [];

    console.log('Processed accountOwner:', accountOwner.value);
    console.log('Processed registrations:', registrations.value);
    console.log('Processed dependants:', dependantParticipants.value);

  } catch (error) {
    console.error('Error fetching account details:', error);
  }
}

async function fetchEvents() {
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
    fetchAccountData(),
    fetchEvents()
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
    color: #2c3e50 !important;
    opacity: 0.7 !important;
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
