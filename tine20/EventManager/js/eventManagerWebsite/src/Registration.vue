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
  <div class="registration-container">
    <b-container class="registration-wrapper">
      <b-row class="title text-center">
        <b-col>
          <h1>{{registrationTitle}}</h1>
          <h5>{{eventDate}}</h5>
        </b-col>
      </b-row>

      <div>
        <b-alert v-model="knownContact" dismissible>
          {{formatMessage('Welcome back! We’ve preloaded your saved information. Please confirm or update your details to keep your account current.')}}
        </b-alert>
        <b-form-group v-if="knownContact" class="mb-3 section-heading" :label="formatMessage('Select a Participant')">
          <b-form-select v-model="selectedParticipantId" :options="participantsDropdownOptions" @change="handleParticipantSelection" />
        </b-form-group>
      </div>

      <b-row>
        <b-col>
          <h4
            v-b-toggle.collapse-1
            @click="isCollapsedParticipant = !isCollapsedParticipant"
            class="mb-4 collapsible-header section-heading"
          >
            {{formatMessage('Participant Information:')}} <span class="chevron" :class="{ 'rotated': !isCollapsedParticipant }">▼</span>
          </h4>
          <b-collapse visible id="collapse-1">
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Salutation')"
              class="mb-3"
            >
              <b-form-select v-model="contactDetails.salutation" :options="salutations"></b-form-select>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Title')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.title"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('First Name') + '*'"
              class="mb-3"
            >
              <b-form-input
                v-model="contactDetails.n_given"
                :class="{ 'required-field-error': validationErrors.includes('n_given') }"
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Middle Name')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.n_middle"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Last Name') + '*'"
              class="mb-3"
            >
              <b-form-input
                v-model="contactDetails.n_family"
                :class="{ 'required-field-error': validationErrors.includes('n_family') }"
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Company')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.org_name"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Day of Birth')"
              class="mb-3"
            >
              <b-form-input
                id="birthday-input"
                type="date"
                class="form-registration"
                v-model="contactDetails.bday"
                :max="maxBirthDate"
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('E-mail') + '*'"
              class="mb-3"
            >
              <b-form-input
                v-model="contactDetails.email"
                :class="{ 'required-field-error': validationErrors.includes('email') }"
              ></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Mobile')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.tel_cell"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Telephone')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.tel_home"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Street')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.adr_one_street"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('House Nr.')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.adr_one_street2"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Postal Code')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.adr_one_postalcode"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('City')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.adr_one_locality"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Region')"
              class="mb-3"
            >
              <b-form-input v-model="contactDetails.adr_one_region"></b-form-input>
            </b-form-group>
            <b-form-group
              label-cols-sm="4"
              label-cols-lg="3"
              content-cols-sm
              content-cols-lg="7"
              :label="formatMessage('Country')"
              class="mb-3"
            >
              <b-form-select v-model="contactDetails.adr_one_countryname" :options="countries"></b-form-select>
            </b-form-group>
          </b-collapse>
        </b-col>
      </b-row>
      <b-row>
        <b-col v-if="eventDetails.options?.length">
          <h4
            v-b-toggle.collapse-2
            @click="isCollapsedEvent = !isCollapsedEvent"
            class="mb-4 collapsible-header section-heading"
          >
            {{formatMessage('Event Information:')}}<span class="chevron" :class="{ 'rotated': !isCollapsedEvent }">▼</span>
          </h4>
          <b-collapse visible id="collapse-2">
            <h5 class="event-title text-center">{{eventDetails.name}}</h5>
            <div v-for="optionGroup in visibleOptionsByGroup" :key="optionGroup.group">
              <h6 class="option-group">{{optionGroup.group}}</h6>
              <div :class="{
              'required-field-error-container': optionGroup.group && optionGroup.group.trim() !== '' && hasGroupValidationError(optionGroup.group)
              }">
                <div v-for="option in optionGroup.options" :key="option.id" :style="{'margin-left' : (option.level-1) * 2 + 'em'}">
                  <div v-if="option.option_config_class === 'EventManager_Model_TextOption'">
                    <h6 class="option-group">{{option.name_option}}</h6>
                    <MarkdownRenderer :content="option.option_config.text_option" />
                  </div>
                  <div v-if="option.option_config_class === 'EventManager_Model_TextInputOption'">
                    <b-form-group
                      label-cols-sm="4"
                      label-cols-lg="3"
                      content-cols-sm
                      content-cols-lg="7"
                      :label="`${option.name_option}${option.option_config?.text ? ' : ' + option.option_config.text : ''}`"
                      class="option-group"
                    >
                      <b-form-textarea
                        v-if="option.option_config.multiple_lines"
                        v-model="replies[option.id]"
                        :maxlength="option.option_config.max_characters || undefined"
                        :class="{'required-field-error': (!option.group || option.group.trim() === '') && validationErrors.includes(option.id)}"
                        rows="4"
                      ></b-form-textarea>
                      <b-form-input
                        v-else
                        v-model="replies[option.id]"
                        :type="option.option_config.only_numbers ? 'number' : 'text'"
                        :maxlength="!option.option_config.only_numbers && option.option_config.max_characters ? option.option_config.max_characters : undefined"
                        :class="{'required-field-error': (!option.group || option.group.trim() === '') && validationErrors.includes(option.id)}"
                        @input="handleTextInputChange(option, $event)"
                      ></b-form-input>
                      <small v-if="option.option_config.multiple_lines && option.option_config.max_characters" class="text-muted">
                        {{getCharacterCount(option.id)}} / {{option.option_config.max_characters}} {{formatMessage('characters')}}
                      </small>
                      <small v-else-if="!option.option_config.only_numbers && option.option_config.max_characters" class="text-muted">
                        {{getCharacterCount(option.id)}} / {{option.option_config.max_characters}} {{formatMessage('characters')}}
                      </small>
                    </b-form-group>
                  </div>
                  <div class="mb-3" v-if="option.option_config_class === 'EventManager_Model_CheckboxOption'"
                       :class="{ 'required-field-error-container': (!option.group || option.group.trim() === '') && validationErrors.includes(option.id)}">
                    <b-form-checkbox
                      v-model="replies[option.id]"
                      value="true"
                      unchecked-value="false"
                      @click="singleSelection(option)"
                    >
                      <h6 class="option-group">{{option.name_option}}</h6>
                      <div v-if="option.option_config">
                        <MarkdownRenderer v-if="option.option_config.description" :content="option.option_config.description" />
                        <div v-if="option.option_config.price">Price: {{option.option_config.price}}</div>
                      </div>
                    </b-form-checkbox>
                  </div>
                  <div v-if="option.option_config_class === 'EventManager_Model_FileOption'"
                       :class="{ 'required-field-error-container': (!option.group || option.group.trim() === '') && validationErrors.includes(option.id)}">
                    <div class="m-3">
                      <h6 class="option-group">{{option.name_option}}</h6>
                      <div v-if="option.option_config && option.option_config.node_id !== ''">
                        <b-button class="action-button" @click="downloadFile(option.option_config.node_id , option.option_config.file_name, option.option_config.file_type)">{{formatMessage('Download file')}}</b-button>
                      </div>
                      <div class="m-3" v-if="option.option_config && option.option_config.file_acknowledgement && option.option_config.node_id !== ''">
                        <b-form-checkbox
                          v-model="replies[option.id]"
                          value="true"
                          unchecked-value="false"
                          @click="singleSelection(option)"
                        >{{formatMessage('I have read the document and accept the terms and conditions')}}</b-form-checkbox>
                      </div>
                      <div v-else-if="option.option_config && option.option_config.file_upload" class="m-3">
                        <input
                          id="file-input"
                          type="file"
                          class="form-control"
                          @change="(event) => handleFileChange(event, option.id)"
                          :accept="acceptedTypes"
                          :multiple=false
                        >
                        <div v-if="uploadedFiles[option.id] && uploadedFiles[option.id].length > 0" class="uploaded-file-info">
                          <div class="file-label">
                            {{formatMessage('Uploaded file')}}:
                          </div>
                          <div class="file-actions">
                            <span
                              class="file-download"
                              @click="downloadFile(uploadedFiles[option.id][0].node_id, uploadedFiles[option.id][0].name, uploadedFiles[option.id][0].file_type)"
                            >
                              {{uploadedFiles[option.id][0].name}}
                            </span>
                            <span
                              class="file-delete"
                              @click="deleteFile(option.id)"
                            >
                              {{formatMessage('Delete file')}}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </b-collapse>
        </b-col>

        <div class="registrant-section">
          <b-form-checkbox v-if="shouldShowRegistrantCheckbox" v-model="isRegistrant">
            {{ formatMessage('I am completing the registration form for another person') }}
          </b-form-checkbox>
        </div>

        <div v-if="isRegistrant">
          <b-row>
            <b-col>
              <h4
                v-b-toggle.collapse-3
                @click="isCollapsedRegistrant = !isCollapsedRegistrant"
                class="mb-4 collapsible-header section-heading"
              >
                {{formatMessage('Registrant Information:')}} <span class="chevron" :class="{ 'rotated': !isCollapsedRegistrant }">▼</span>
              </h4>
              <b-collapse visible id="collapse-3">
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Salutation')"
                  class="mb-3"
                >
                  <b-form-select v-model="registrantDetails.salutation" :options="salutations"></b-form-select>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Title')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.title"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('First Name') + '*'"
                  class="mb-3"
                >
                  <b-form-input
                    v-model="registrantDetails.n_given"
                    :class="{ 'required-field-error': validationErrors.includes('n_given') }"
                  ></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Middle Name')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.n_middle"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Last Name') + '*'"
                  class="mb-3"
                >
                  <b-form-input
                    v-model="registrantDetails.n_family"
                    :class="{ 'required-field-error': validationErrors.includes('n_family') }"
                  ></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Company')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.org_name"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Day of Birth')"
                  class="mb-3"
                >
                  <b-form-input
                    id="birthday-input2"
                    type="date"
                    class="form-registration"
                    v-model="registrantDetails.bday"
                    :max="maxBirthDate"
                  ></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('E-mail') + '*'"
                  class="mb-3"
                >
                  <b-form-input
                    v-model="registrantEmail"
                    :class="{ 'required-field-error': validationErrors.includes('email') }"
                    :readonly="isVerifyEmailRegistrant"
                  ></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Mobile')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.tel_cell"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Telephone')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.tel_home"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Street')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.adr_one_street"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('House Nr.')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.adr_one_street2"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Postal Code')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.adr_one_postalcode"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('City')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.adr_one_locality"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Region')"
                  class="mb-3"
                >
                  <b-form-input v-model="registrantDetails.adr_one_region"></b-form-input>
                </b-form-group>
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="formatMessage('Country')"
                  class="mb-3"
                >
                  <b-form-select v-model="registrantDetails.adr_one_countryname" :options="countries"></b-form-select>
                </b-form-group>
              </b-collapse>
            </b-col>
          </b-row>
        </div>

        <div v-if="isAlreadyRegistered">
          <div class="button-group">
            <b-button class="action-button" @click="() => handlePostRegistration(true)">{{formatMessage('Update Registration')}}</b-button>
            <b-button class="action-button" @click="openCancelConfirmation">{{formatMessage('Cancel Registration')}}</b-button>
          </div>
        </div>

        <div v-else class="button-group">
          <b-button class="action-button" @click="checkWaitingList">{{formatMessage('Register')}}</b-button>
        </div>
      </b-row>

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
      </b-modal>
    </b-container>
  </div>
</template>

<script setup>
import {computed, ref, reactive, watch} from 'vue';
import {useFormatMessage} from './index.es6';
const { formatMessage } = useFormatMessage();
import _ from 'lodash';
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import MarkdownRenderer from './../../../../Tinebase/js/MarkdownRenderer.vue';

const router = useRouter();
const route = useRoute();
const isCollapsedParticipant = ref(false);
const isCollapsedEvent = ref(false);
const isCollapsedRegistrant = ref(false);
const knownContact = ref(false);
const showRegisteredContactAlert = ref(false);
const replies = ref({});
const uploadedFiles = ref({});
const validationErrors = ref([]);
const isVerifyEmail = ref(false);
const isVerifyEmailRegistrant = ref(false);
const isAlreadyRegistered = ref(false);
const acceptedTypes = '.pdf, .doc, .docx, .png, .jpeg, .txt, .html, .htm, .jpg, .csv, .xlsx, .xls';
const isExpired = ref(false);
const isUpdate = ref(false);
const hasFileChanged = ref(false);
const isRegistrant = ref(false);
const registrantEmail = ref();
const registrationIdRef = ref();
const selectedParticipantId = ref(null);
const dependantParticipants = ref(null);
const registrantEvents = ref(null);
const participants = ref(null);
const accountOwner = ref(null);
const shouldShowRegistrantCheckbox = ref(true);

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

const registrationTitle = computed(() =>
  `${formatMessage('Registration for')} ${eventDetails.value.name}`
);

const eventDate = computed(() => {
  const dateFormat = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  };

  const formatDate = (date, formatter) => {
    const d = new Date(date);
    if (isNaN(d.getTime()) || d.getTime() < 86400000) {
      return null;
    }
    return d.toLocaleString("de-DE", formatter);
  };

  const formattedDate = formatDate(eventDetails.value.start, dateFormat);
  return `${formatMessage('on the')} ${formattedDate || formatMessage('TBD')}`
}
)

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

const contactDetails = ref({
  id: "",
  salutation : "",
  n_prefix : "",
  n_given : "",
  n_middle : "",
  n_family : "",
  org_name : "",
  bday : "",
  email : "",
  tel_cell: "",
  tel_home : "",
  adr_one_street : "",
  adr_one_street2: "",
  adr_one_postalcode : "",
  adr_one_locality : "",
  adr_one_region : "",
  adr_one_countryname : "",
});

const registrantDetails = ref({
  id: "",
  salutation : "",
  n_prefix : "",
  n_given : "",
  n_middle : "",
  n_family : "",
  org_name : "",
  bday : "",
  email : "",
  tel_cell: "",
  tel_home : "",
  adr_one_street : "",
  adr_one_street2: "",
  adr_one_postalcode : "",
  adr_one_locality : "",
  adr_one_region : "",
  adr_one_countryname : "",
});

const salutations = ref([
  { value: 'MR', text: formatMessage('Mr') },
  { value: 'MS', text: formatMessage('Ms') },
  { value: 'COMPANY', text: formatMessage('Company') },
  { value: 'PERSON', text: formatMessage('Person') },
]);

const countries = ref([
  { value: 'DE', text: formatMessage('Deutschland') },
]);

const createNewProfile = () => {
  registrantDetails.value = { ...contactDetails.value };

  contactDetails.value = {
    id: "",
    salutation: "",
    n_prefix: "",
    n_given: "",
    n_middle: "",
    n_family: "",
    org_name: "",
    bday: "",
    email: "",
    tel_cell: "",
    tel_home: "",
    adr_one_street: "",
    adr_one_street2: "",
    adr_one_postalcode: "",
    adr_one_locality: "",
    adr_one_region: "",
    adr_one_countryname: "",
  };

  eventDetails.value.options.forEach((option) => {
    switch (option.option_config_class) {
      case 'EventManager_Model_CheckboxOption':
        replies.value[option.id] = 'false';
        break;
      case 'EventManager_Model_TextInputOption':
        replies.value[option.id] = '';
        break;
      case 'EventManager_Model_FileOption':
        if (option.option_config && option.option_config.file_acknowledgement) {
          replies.value[option.id] = 'false';
        } else {
          uploadedFiles.value[option.id] = [];
        }
        break;
    }
  });

  knownContact.value = false;
  showRegisteredContactAlert.value = false;
  isRegistrant.value = true;
  isAlreadyRegistered.value = false;
};

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
  const registerOthers = eventDetails.value.register_others;
  const options = [];

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
        if (p.original_id && p.n_fileas && !seen.has(p.original_id)) {
          options.push({
            value: p.original_id,
            text: p.n_fileas
          });
          seen.add(p.original_id);
        }
      });
    }
  }

  return options;
});

const handleParticipantSelection = (participantId) => {
  if (!participantId) { // new participant
    contactDetails.value = {
      id: "",
      salutation: "",
      n_prefix: "",
      n_given: "",
      n_middle: "",
      n_family: "",
      org_name: "",
      bday: "",
      email: "",
      tel_cell: "",
      tel_home: "",
      adr_one_street: "",
      adr_one_street2: "",
      adr_one_postalcode: "",
      adr_one_locality: "",
      adr_one_region: "",
      adr_one_countryname: "",
    };

    if (accountOwner.value) {
      registrantDetails.value = { ...accountOwner.value };
      registrantEmail.value = accountOwner.value.email;
    }

    isRegistrant.value = true;
    shouldShowRegistrantCheckbox.value = true;
    showRegisteredContactAlert.value = false;
    isAlreadyRegistered.value = false;
  } else if (accountOwner.value && String(participantId) === String(accountOwner.value.id)) { // accountOwner
    contactDetails.value = { ...accountOwner.value };
    registrantDetails.value = { ...accountOwner.value };
    isRegistrant.value = false;
    shouldShowRegistrantCheckbox.value = false;

    const registration = eventDetails.value.registrations?.find(
      reg => String(reg.participant?.original_id) === String(participantId)
    );

    if (registration) {
      showRegisteredContactAlert.value = registration.status !== '3';
      isAlreadyRegistered.value = registration.status !== '3';
      getBookedOptions(registration);
      registrationIdRef.value = registration.id;
    } else {
      showRegisteredContactAlert.value = false;
      isAlreadyRegistered.value = false;
    }
  } else { // dependantParticipant
    let participant = dependantParticipants.value?.find(p => String(p.original_id) === String(participantId));

    if (!participant){ // from account owner register participants
      let registration = participants.value?.find(p => String(p.participant.original_id) === String(participantId));
      participant = registration.participant;
    }

    if (participant) {
      contactDetails.value = { ...participant };

      if (accountOwner.value) {
        registrantDetails.value = { ...accountOwner.value };
        registrantEmail.value = accountOwner.value.email;
      }

      isRegistrant.value = true;
      shouldShowRegistrantCheckbox.value = true;

      const registration = eventDetails.value.registrations?.find(
        reg => String(reg.participant?.original_id) === String(participantId)
      );

      if (registration) {
        showRegisteredContactAlert.value = registration.status !== '3';
        isAlreadyRegistered.value = registration.status !== '3';
        getBookedOptions(registration);
        registrationIdRef.value = registration.original_id;
      } else {
        showRegisteredContactAlert.value = false;
        isAlreadyRegistered.value = false;
      }
    }
  }
};


function deleteFile(optionId) {
  hasFileChanged.value = true;
  delete uploadedFiles.value[optionId];
  const fileInput = document.getElementById('file-input');
  if (fileInput) {
    fileInput.value = '';
  }
}

function openCancelConfirmation() {
  showModal({
    title: formatMessage('Cancel Registration'),
    message: `${formatMessage('Do you really want to cancel your registration for')} "<strong>${eventDetails.value.name}</strong>"`,
    type: 'confirm',
    okOnly: false,
    okText: formatMessage('Yes'),
    cancelText: formatMessage('No'),
    onConfirm: confirmCancel
  });
}

async function confirmCancel() {
  const token = window.location.href.split('/').pop();
  let eventId = route.params.id;

  try {
    const response = await fetch(`/EventManager/deregistration/${eventId}/${token}/${registrationIdRef.value}`, {
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
      onConfirm: () => {
        const baseUrl = window.location.origin;
        window.location.href = `${baseUrl}/EventManager/view/#/event`;
      }
    });
  } catch (error) {
    console.error(error);
    showModal({
      title: formatMessage('Error'),
      message: formatMessage('Could not cancel your registration. Please try again later.'),
      type: 'error'
    });
  }
}

const getCharacterCount = (optionId) => {
  return replies.value[optionId] ? replies.value[optionId].length : 0;
};

const handleTextInputChange = (option, value) => {
  if (option.option_config.only_numbers) {
    replies.value[option.id] = value;
  } else {
    if (option.option_config.max_characters && value.length > option.option_config.max_characters) {
      replies.value[option.id] = value.substring(0, option.option_config.max_characters);
    } else {
      replies.value[option.id] = value;
    }
  }
};

const sortOptionsByGroup = computed(() => {
  const options = eventDetails.value.options;

  return _.chain(options)
    .groupBy(option => option.group || '')
    .map((groupOptions, groupName) => ({
      group: groupName,
      options: _.sortBy(groupOptions, option => option.sorting ?? -1),
      sorting: groupName === '' ? -1 : _.min(groupOptions.map(opt => opt.sorting ?? Infinity)),
      level: _.min(groupOptions.map(opt => opt.level))
    }))
    .sortBy('sorting')
    .value();
});

const visibleOptionsByGroup = computed(() => {
  const sortedGroups = sortOptionsByGroup.value;
  return sortedGroups.map(group => ({
    ...group,
    options: group.options.filter(option => isOptionVisible(option))
  }));
});

const getGroupedAndUngroupedOptions = () => {
  const optionsByGroup = new Map();
  const ungroupedOptions = [];

  visibleOptionsByGroup.value.forEach(group => {
    group.options.forEach(option => {
      if (option.group && option.group.trim() !== '') {
        if (!optionsByGroup.has(option.group)) {
          optionsByGroup.set(option.group, []);
        }
        optionsByGroup.get(option.group).push(option);
      } else {
        ungroupedOptions.push(option);
      }
    });
  });

  return { optionsByGroup, ungroupedOptions };
};

const hasOptionValue = (option) => {
  switch (option.option_config_class) {
    case 'EventManager_Model_TextInputOption':
      return replies.value[option.id] && replies.value[option.id].trim() !== '';
    case 'EventManager_Model_CheckboxOption':
      return replies.value[option.id] === 'true';
    case 'EventManager_Model_FileOption':
      if (option.option_config && option.option_config.file_acknowledgement) {
        return replies.value[option.id] === 'true';
      } else if (option.option_config && option.option_config.file_upload) {
        return uploadedFiles.value[option.id] && uploadedFiles.value[option.id].length > 0;
      }
      return true;
    case 'EventManager_Model_TextOption':
      return true;
    default:
      return false;
  }
};

const isOptionVisible = (option) => {
  if (!option.option_rule || option.option_rule.length === 0) {
    return true;
  }

  const ruleType = option.rule_type !== undefined ? Number(option.rule_type) : 1;

  if (ruleType === 1) {
    return option.option_rule.some(rule => evaluateRule(rule));
  } else {
    return option.option_rule.every(rule => evaluateRule(rule));
  }
};

const isOptionRequired = (option) => {
  if (!option.option_required) {
    return false;
  }

  const requiredType = Number(option.option_required);

  switch (requiredType) {
    case 1: // yes
      return true;
    case 2: // no
      return false;
    case 3: // if
      return isOptionVisible(option) && option.option_rule && option.option_rule.length > 0;
    default:
      return false;
  }
};

const hasGroupValidationError = (groupName) => {
  if (!groupName || groupName.trim() === '') return false;

  const { optionsByGroup } = getGroupedAndUngroupedOptions();
  const groupOptions = optionsByGroup.get(groupName);

  if (!groupOptions) return false;

  return groupOptions.some(option => validationErrors.value.includes(option.id));
};

const isValidEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

watch(() => contactDetails.value.email, (newEmail) => {
  if (newEmail && newEmail.trim()) {
    if (isValidEmail(newEmail.trim())) {
      validationErrors.value = validationErrors.value.filter(err => err !== 'email');
    } else {
      if (!validationErrors.value.includes('email')) {
        validationErrors.value.push('email');
      }
    }
  }
});
const validateRequiredFields = () => {
  validationErrors.value = [];
  const errors = [];

  const requiredFields = ['n_given', 'n_family', 'email'];
  const missingFields = _.filter(requiredFields, field =>
    _.isEmpty(_.get(contactDetails.value, field, '').trim())
  );
  errors.push(...missingFields);

  const email = _.get(contactDetails.value, 'email', '').trim();
  if (email && !isValidEmail(email)) {
    if (!errors.includes('email')) {
      errors.push('email');
    }
  }

  // check grouped options (only one needs to be filled per group if option is required)
  const { optionsByGroup, ungroupedOptions } = getGroupedAndUngroupedOptions();
  optionsByGroup.forEach((groupOptions, groupName) => {
    const requiredOptions = groupOptions.filter(option => isOptionRequired(option));

    if (requiredOptions.length > 0) {
      // Check if ANY option in the group has a value
      const hasAnyValue = groupOptions.some(option => hasOptionValue(option));

      // If no option in the group has a value, mark all required options in the group as errors
      if (!hasAnyValue) {
        requiredOptions.forEach(option => {
          errors.push(option.id);
        });
      }
    }
  });

    // Validate ungrouped options (each must be filled individually)
    ungroupedOptions.forEach(option => {
      if (isOptionRequired(option) && !hasOptionValue(option)) {
        errors.push(option.id);
      }
    });

    validationErrors.value = errors;
    return errors.length === 0;
};


const evaluateRule = (rule) => {
  const refOptionField = rule.ref_option_field;
  const referencedOption = eventDetails.value.options.find(opt => opt.id === refOptionField);

  if (referencedOption?.option_config_class === 'EventManager_Model_FileOption') {
    const hasFiles = uploadedFiles.value[refOptionField] &&
      uploadedFiles.value[refOptionField].length > 0;

    switch (rule.criteria) {
      case 1:
        return hasFiles;
      case 2:
        return !hasFiles;
      default:
        return false;
    }
  } else {
    const requiredValue = rule.value;
    const criteria = rule.criteria !== undefined ? Number(rule.criteria) : 1;
    const currentValue = replies.value[refOptionField];
    const referencedOptionType = referencedOption?.option_config_class;
    let result;

    switch (criteria) {
      case 1: // yes
        if (referencedOptionType === 'EventManager_Model_CheckboxOption') {
          result = currentValue === 'true';
        } else {
          result = currentValue && currentValue.trim() !== '';
        }
        break;

      case 2: // no
        if (referencedOptionType === 'EventManager_Model_CheckboxOption') {
          result = currentValue === 'false' || !currentValue;
        } else {
          result = !currentValue || currentValue.trim() === '';
        }
        break;

      case 3: // is
        result = currentValue && currentValue === requiredValue;
        break;

      case 4: // is not
        result = !currentValue || currentValue !== requiredValue ;
        break;

      case 5: // greater or equal to
        const currentNum = parseFloat(String(currentValue).trim());
        const requiredNum = parseFloat(String(requiredValue).trim());
        result = !isNaN(currentNum) && !isNaN(requiredNum) && currentNum >= requiredNum;
        break;

      default:
        return currentValue === requiredValue;
    }
    return result;
  }
};


function checkValidationFields() {
  validationErrors.value = [];

  if (!validateRequiredFields()) {
    const email = _.get(contactDetails.value, 'email', '').trim();
    const hasInvalidEmail = email && !isValidEmail(email);

    showModal({
      title: formatMessage('Validation Error'),
      message: hasInvalidEmail
        ? formatMessage('Please enter a valid email address.')
        : formatMessage('Please fill all required fields.'),
      type: 'error'
    });
    return false;
  }
  return true;
}

function checkWaitingList() {
  if (!checkValidationFields()) {
    return;
  }
  const registration_deadline = eventDetails.value.registration_possible_until;
  const available_places = eventDetails.value.available_places;
  if ( registration_deadline && new Date(registration_deadline).getTime() < new Date().getTime()) {
    isExpired.value = true;
  }
  if (available_places && (available_places <= 0 || isExpired.value)) {
    const expiredMessage = isExpired.value
      ? `${formatMessage('The registration date for')} "<strong>${eventDetails.value.name}</strong>" ${formatMessage('has expired. If you register you will be on our waiting list. Do you still want to register?')}`
      : `${formatMessage('The event')} "<strong>${eventDetails.value.name}</strong>" ${formatMessage('is full. If you register you will be on our waiting list. Do you still want to register?')}`;

    showModal({
      title: formatMessage('Waiting list'),
      message: expiredMessage,
      type: 'waiting-list',
      okOnly: false,
      okText: formatMessage('Yes'),
      cancelText: formatMessage('No'),
      onConfirm: postRegistration
    });
  } else {
    postRegistration();
  }
}

const handlePostRegistration = (update = false) => {
  if (!checkValidationFields()) {
    return;
  }
  isUpdate.value = update;
  postRegistration();
};

const postRegistration = async () => {
  const filteredReplies = {};
  const { optionsByGroup, ungroupedOptions } = getGroupedAndUngroupedOptions();

  optionsByGroup.forEach((groupOptions, groupName) => {
    groupOptions.forEach(option => {
      if (option.option_config_class === 'EventManager_Model_FileOption') {
        if (option.option_config && option.option_config.file_acknowledgement) {
          if (hasOptionValue(option)) {
            filteredReplies[option.id] = replies.value[option.id];
          }
        }
        // skip file upload options (handle in uploadFiles)
        return;
      }

      if (option.option_config_class === 'EventManager_Model_TextOption') {
        return;
      }

      if (hasOptionValue(option)) {
        filteredReplies[option.id] = replies.value[option.id];
      }
    });
  });

  ungroupedOptions.forEach(option => {
    if (option.option_config_class === 'EventManager_Model_FileOption') {
      if (option.option_config && option.option_config.file_acknowledgement) {
        if (hasOptionValue(option)) {
          filteredReplies[option.id] = replies.value[option.id];
        }
      }
      // skip file upload options (handle in uploadFiles)
      return;
    }

    if (option.option_config_class === 'EventManager_Model_TextOption') {
      return;
    }

    if (hasOptionValue(option)) {
      filteredReplies[option.id] = replies.value[option.id];
    }
  });

  const eventId = route.params.id;
  registrantDetails.value.email = registrantEmail.value;
  const registration = {'eventId': eventId, 'contactDetails': contactDetails.value, 'replies': filteredReplies, 'registrantDetails': registrantDetails.value};
  const body = JSON.parse(JSON.stringify(registration));
  let registrationId = '';

  try {
    const response = await fetch(`/EventManager/register/${eventId}`, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      method: 'POST',
      body: JSON.stringify(body)
    }).then(resp => resp.json())
      .then(data => {
        registrationId = data.id;
        console.debug(data);
      });

    if (hasFileChanged.value) {
      await uploadFiles(eventId, registrationId);
    }

    if (isUpdate.value) {
      isUpdate.value = false;
      showModal({
        title: formatMessage('Update Registration'),
        message: formatMessage('Your registration was updated successfully.'),
        type: 'success',
        onConfirm: () => {
          clearForm();
          const baseUrl = window.location.origin;
          window.location.href = `${baseUrl}/EventManager/view/events`;
        }
      });
    } else {
      showModal({
        title: formatMessage('Success'),
        message: formatMessage('Registration completed successfully! You will receive a confirmation e-mail.'),
        type: 'success',
        onConfirm: () => {
          clearForm();
          const baseUrl = window.location.origin;
          window.location.href = `${baseUrl}/EventManager/view/events`;
        }
      });
    }

  } catch (error) {
    console.error('Registration request failed:', error);
    showModal({
      title: formatMessage('Error'),
      message: formatMessage('Registration failed. Please try again.'),
      type: 'error'
    });
  }
};

const uploadFileForOption = async (eventId, registrationId, optionId, files) => {
  const formData = new FormData();
  formData.append('eventId', eventId);

  Array.from(files).forEach(file => {
    formData.append('files[]', file);
  });

  const response = await fetch(`/EventManager/files/${eventId}/${optionId}/${registrationId}`, {
    method: 'POST',
    body: formData
  });

  if (!response.ok) {
    throw new Error(`Failed to upload files for option ${optionId}`);
  }
};

const uploadFiles = async (eventId, registrationId) => {
  const uploadPromises = Object.entries(uploadedFiles.value)
    .filter(([optionId, files]) => files && files.length > 0)
    .map(([optionId, files]) => uploadFileForOption(eventId, registrationId, optionId, files));

  try {
    await Promise.all(uploadPromises);
  } catch (error) {
    console.error('File upload failed:', error);
  }
};

function handleFileChange(event, optionId) {
  hasFileChanged.value = true;
  const files = event.target.files;
  if (files.length > 0) {
    uploadedFiles.value[optionId] = files;
  } else {
    delete uploadedFiles.value[optionId];
  }
}

const clearForm = () => {
  contactDetails.value = _.mapValues(contactDetails.value, () => '');
  replies.value = {};

  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach(input => {
    input.value = '';
  });
};

const maxBirthDate = computed(() => {
  return new Date().toISOString().split('T')[0];
});

// function to only select one of the checkboxes inside a group
function singleSelection(option) {
  if (option.group && option.group.trim() !== "") {
    if (replies.value[option.id] !== 'true') {
      visibleOptionsByGroup.value.forEach((group) => {
        if (group.group === option.group) {
          group.options.forEach((o) => {
            if (o.id !== option.id && o.option_config_class === 'EventManager_Model_CheckboxOption') {
              replies.value[o.id] = "false";
            }
          });
        }
      });
    }
  }
}

const download = (data, name, type) => {
  const url = window.URL.createObjectURL(new Blob([data], { type }));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', name);
  document.body.appendChild(link);
  link.click();
  setTimeout(() => {
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  }, 200);
};

async function downloadFile(nodeId, name, type) {
  await fetch(`/EventManager/getFile/${nodeId}`, {
    method: 'GET'
  }).then(res => res.blob()).then(data => {
    download(data, name, type);
  });
}

async function getEvent(skipContactDetails = false, participantId, isReregistered = false) {
  let eventId = route.params.id;
  await fetch(`/EventManager/event/${eventId}`, {
    method: 'GET'
  }).then(resp => resp.json())
    .then(data => {
      eventDetails.value = data;
      if (eventDetails.value.options) {
        if (eventDetails.value.options.length > 1) {
          eventDetails.value.options.forEach((option) => {
            switch (option.option_config_class) {
              case 'EventManager_Model_CheckOption':
                replies.value[option.id] = 'false';
                break;
              case 'EventManager_Model_TextInputOption':
                replies.value[option.id] = '';
                break;
              case 'EventManager_Model_FileOption':
                if (option.option_config && option.option_config.file_acknowledgement) {
                  replies.value[option.id] = 'false';
                } else {
                  uploadedFiles.value[option.id] = null;
                }
                break;
              case 'EventManager_Model_TextOption':
                // Display-only, no input needed
                break;
            }
          });
        } else {
          if (eventDetails.value.options.option_config_class === 'EventManager_Model_CheckOption') {
            replies.value[eventDetails.value.options.id] = 'false';
          } else {
            replies.value[eventDetails.value.options.id] = '';
          }
        }
      }
    });
  if (participantId) {
    const registration = eventDetails.value.registrations?.find(
      reg => String(reg.participant?.original_id) === String(participantId)
    );

    if (registration) {
      await getEventRegistrantDetails();
      contactDetails.value = registration.participant;
      if (!contactDetails.value.email){
        contactDetails.value.email = registration.registrant.email
      }

      if (!isReregistered) {
        await getBookedOptions(registration);
      } else {
        knownContact.value = true;
        isAlreadyRegistered.value = false;
      }

      if (registration.participant.original_id !== registration.registrant.original_id) {
        showRegisteredContactAlert.value = false;
      }
    }
  } else if (route.params.token && !skipContactDetails) {
    await getEventContactDetails();
  }
}

async function getEventContactDetails() {
  let eventId = route.params.id;
  let token = route.params.token;
  try {
    const resp = await fetch(`/EventManager/contact/${token}/${eventId}`, {
      method: 'GET'
    });
    const data = await resp.json();
    const [contactData, registration, relatedContacts] = data;
    //dependantParticipants.value = relatedContacts;
    accountOwner.value = contactData;

    const contactFields = Object.keys(contactDetails.value);
    const filteredContactData = {};

    contactFields.forEach(field => {
      filteredContactData[field] = contactData[field] || "";
    });

    contactDetails.value = filteredContactData;

    const { email, ...otherDetails } = contactData; //exclude email
    knownContact.value = Object.values(otherDetails).some(v => v && v.trim() !== "");

    if (contactData.email && contactData.email.trim() !== '') {
      registrantEmail.value = contactData.email;
      isVerifyEmail.value = true;
    }

    if (registration && registration !== '') {
      await getBookedOptions(registration)
      registrationIdRef.value = registration.id;
    }

  } catch (error) {
    console.error('Error fetching contact details: ', error);
  }
}

async function getBookedOptions(registration) {
  showRegisteredContactAlert.value = registration.status !== '3';
  isAlreadyRegistered.value = registration.status !== '3';
  const bookedOptions = registration.booked_options || [];
  for(const bookedOption of bookedOptions) {
    const optionId = bookedOption.option?.id || bookedOption.option;
    if (optionId) {
      const sc = bookedOption.selection_config;
      switch (bookedOption.selection_config_class) {
        case 'EventManager_Model_Selections_Checkbox':
          replies.value[optionId] = sc.booked === true || optionId === 'true' ? 'true' : 'false';
          break;

        case 'EventManager_Model_Selections_TextInput':
          replies.value[optionId] = sc.response || '';
          break;

        case 'EventManager_Model_Selections_File':
          if (sc.node_id) {
            try {
              await loadPreviouslyUploadedFile(optionId, sc.node_id, sc.file_name);
            } catch (error) {
              console.error(`Failed to load file for option ${optionId}:`, error);
            }
          } else {
            replies.value[optionId] = sc.file_acknowledgement === true || sc.file_acknowledgement === 'true' ? 'true' : 'false';
          }
          break;
      }
    }
  }
  console.log('Final replies:', replies.value);
}

async function loadPreviouslyUploadedFile(optionId, nodeId, fileName) {
  try {
    const response = await fetch(`/EventManager/getFile/${nodeId}`, {
      method: 'GET'
    });
    if (!response.ok) {
      throw new Error(`Failed to fetch file: ${response.statusText}`);
    }

    const blob = await response.blob();
    const file = new File([blob], fileName, {
      type: blob.type
    });

    if (!uploadedFiles.value[optionId]) {
      uploadedFiles.value[optionId] = [];
    }

    uploadedFiles.value[optionId] = [file];

  } catch (error) {
    console.error(`Error loading file for option ${optionId}:`, error);
    throw error;
  }
}

async function getEventRegistrantDetails() {
  let eventId = route.params.id;
  let token = route.params.token;
  try {
    const resp = await fetch(`/EventManager/contact/${token}/${eventId}`, {
      method: 'GET'
    });
    const data = await resp.json();
    const [registrantData, registration] = data;

    registrantDetails.value = registrantData;
    if (registrantData.email && registrantData.email.trim() !== '') {
      registrantEmail.value = registrantData.email;
      isVerifyEmailRegistrant.value = true;
    }
  } catch (error) {
  console.error('Error fetching contact details: ', error);
  }
}

async function getFromAccountOwnerRegisterParticipants() {
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

onMounted(async () => {
  const shouldCreateNewProfile = route.query.newProfile === 'true';
  const participantId = route.query.participantId || '';
  const isReregistered = route.query.isReregistered === 'true';

  await getFromAccountOwnerRegisterParticipants();
  await getEvent(shouldCreateNewProfile, participantId, isReregistered);

  if (shouldCreateNewProfile) {
    if (!participantId) {
      createNewProfile();
    }
    isRegistrant.value = true;
    await getEventRegistrantDetails();
    router.replace({
      params: route.params,
      query: {}
    });
  } else if (accountOwner.value) {
    selectedParticipantId.value = accountOwner.value.id;
    shouldShowRegistrantCheckbox.value = false;
    handleParticipantSelection(accountOwner.value.id);
  }
});

</script>

<style lang="scss">
.registration-container {
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

.collapsible-header {
  cursor: pointer;
  user-select: none;
  transition: all 0.3s ease;
  display: flex;
  justify-content: space-between;
  align-items: center;

  &:hover {
    color: #1a252f;
  }
}

.chevron {
  display: inline-block;
  transition: transform 0.3s ease;
  font-size: 0.9rem;
  margin-left: 0.5rem;

  &.rotated {
    transform: rotate(-90deg);
  }
}

.event-title {
  color: #2c3e50;
  font-weight: 600;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
}

:deep(.alert) {
  border-radius: 8px;
  border-left: 4px solid #2c3e50;
  margin-bottom: 1.5rem;

  &.alert-success {
    background-color: #d4edda;
    border-left-color: #28a745;
    color: #155724;
  }

  a {
    color: inherit;
    text-decoration: underline;
    font-weight: 600;

    &:hover {
      text-decoration: none;
    }
  }
}

:deep(.form-group) {
  margin-bottom: 1.25rem;

  label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
  }
}

:deep(.form-control),
:deep(.custom-select) {
  border: 2px solid #e9ecef;
  border-radius: 8px;
  padding: 0.75rem;
  transition: all 0.3s ease;
  font-size: 1rem;

  &:focus {
    border-color: #2c3e50;
    box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.15);
  }
}

:deep(textarea.form-control) {
  min-height: 100px;
}

.form-control[readonly] {
  background-color: #f8f9fa;
  opacity: 1;
  cursor: not-allowed;
}

.required-field-error {
  border: 2px solid #dc3545 !important;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
  background-color: #fff5f5;

  &:focus {
    border-color: #dc3545 !important;
  }
}

.required-field-error-container {
  border: 2px solid #dc3545;
  border-radius: 8px;
  padding: 1rem;
  background-color: rgba(220, 53, 69, 0.05);
  margin-bottom: 1rem;
}

input[type="file"] {
  border: 2px dashed #e9ecef;
  border-radius: 8px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;

  &:hover {
    border-color: #2c3e50;
    background-color: #f8f9fa;
  }
}

.text-muted {
  font-size: 0.875rem;
  color: #6c757d;
  display: block;
  margin-top: 0.25rem;
}

input[type="date"] {
  &::-webkit-calendar-picker-indicator {
    cursor: pointer;
    filter: invert(35%) sepia(10%) saturate(1000%) hue-rotate(169deg);
  }
}

:deep(.collapse) {
  padding-top: 1rem;
}

.registrant-section {
  margin: 1.5rem 0 !important;

  :deep(.custom-control-label) {
    font-weight: 500;
    color: #2c3e50;
  }
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

.option-group {
  color: #2c3e50;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.uploaded-file-info {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background-color: #f8f9fa;
  border-radius: 8px;
  border-left: 3px solid #2c3e50;

  .file-label {
    color: #6c757d;
    font-size: 0.875rem;
    display: block;
    margin-bottom: 0.5rem;
  }

  .file-actions {
    display: flex;
    gap: 1.5rem;
    align-items: center;
  }

  .file-download {
    color: #2c3e50;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;

    &:hover {
      color: #1a252f;
      text-decoration: underline;
    }
  }

  .file-delete {
    color: #6c757d;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.875rem;

    &:hover {
      color: #dc3545;
      text-decoration: underline;
    }
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
  .registration-container {
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
