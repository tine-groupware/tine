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
        <h1>{{formatMessage('Registration')}}</h1>
      </b-col>
    </b-row>
    <b-row>
      <b-col>
        <h4 class="mb-4">Personal Information:</h4>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Salutation')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.salutation"></b-form-input>
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
            v-model="contactDetails.firstName"
            :class="{ 'required-field-error': validationErrors.includes('firstName') }"
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
          <b-form-input v-model="contactDetails.middleName"></b-form-input>
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
            v-model="contactDetails.lastName"
            :class="{ 'required-field-error': validationErrors.includes('lastName') }"
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
          <b-form-input v-model="contactDetails.company"></b-form-input>
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
            v-model="contactDetails.birthday"
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
          <b-form-input v-model="contactDetails.mobile"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Telephone')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.telephone"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Street')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.street"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('House Nr.')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.houseNumber"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Postal Code')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.postalCode"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('City')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.city"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Region')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.region"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Country')"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.country"></b-form-input>
        </b-form-group>
      </b-col>
    </b-row>
    <b-row>
      <b-col v-if="eventDetails.options">
        <h4 class="mb-4">{{formatMessage('Event Specific Information:')}}</h4>
        <h5>{{eventDetails.name}}</h5>
        <div v-for="optionGroup in visibleOptionsByGroup" :key="optionGroup.group">
          <h6>{{optionGroup.group}}</h6>
          <div :class="{
          'required-field-error-container': optionGroup.group && optionGroup.group.trim() !== '' && hasGroupValidationError(optionGroup.group)
          }">
            <div v-for="option in optionGroup.options" :key="option.id" :style="{'margin-left' : (option.level-1) * 2 + 'em'}">
              <div v-if="option.option_config_class === 'EventManager_Model_TextOption'">
                <h6>{{option.name_option}}</h6>
                <div class="mb-3">{{option.option_config.text_option}}</div>
              </div>
              <div v-if="option.option_config_class === 'EventManager_Model_TextInputOption'">
                <b-form-group
                  label-cols-sm="4"
                  label-cols-lg="3"
                  content-cols-sm
                  content-cols-lg="7"
                  :label="option.option_config.text"
                  class="mb-3"
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
                  <h6>{{option.name_option}}</h6>
                  <div v-if="option.option_config">
                    <div v-if="option.option_config.description">{{option.option_config.description}}</div>
                    <div v-if="option.option_config.price">Price: {{option.option_config.price}}</div>
                  </div>
                </b-form-checkbox>
              </div>
              <div v-if="option.option_config_class === 'EventManager_Model_FileOption'"
                   :class="{ 'required-field-error-container': (!option.group || option.group.trim() === '') && validationErrors.includes(option.id)}">
                <div class="mb-3">
                  <h6>{{option.name_option}}</h6>
                  <div v-if="option.option_config && option.option_config.node_id !== ''">
                    <b-button class="mb-3" @click="downloadFile(option.option_config.node_id , option.option_config.file_name, option.option_config.file_type)">{{formatMessage('Download file')}}</b-button>
                  </div>
                  <div class="mb-3" v-if="option.option_config && option.option_config.file_acknowledgement && option.option_config.node_id !== ''">
                    <b-form-checkbox
                      v-model="replies[option.id]"
                      value="true"
                      unchecked-value="false"
                      @click="singleSelection(option)"
                    >{{formatMessage('I have read and accept the terms and conditions')}}</b-form-checkbox>
                  </div>
                  <div v-else-if="option.option_config && option.option_config.file_upload" class="mb-3">
                    <input
                      id="file-input"
                      type="file"
                      class="form-control"
                      @change="(event) => handleFileChange(event, option.id)"
                      :accept="acceptedTypes"
                      :multiple=false
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <b-modal v-model="showModal" :title="formatMessage(modalTitle)" hide-footer>
          <p>{{ formatMessage(modalMessage) }}</p>
          <b-button @click="handleModalClose" variant="primary">OK</b-button>
        </b-modal>
        <b-button class="mt-3" @click="postRegistration">{{formatMessage('Complete Registration')}}</b-button>
      </b-col>
    </b-row>
  </b-container>
</template>

<script setup>
import {inject, ref, computed} from 'vue';
import {translationHelper} from "./keys";
import {useRoute} from 'vue-router';

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
  salutation : "",
  title : "",
  firstName : "",
  middleName : "",
  lastName : "",
  company : "",
  birthday : "",
  email : "",
  mobile: "",
  telephone : "",
  street : "",
  houseNumber: "",
  postalCode : "",
  city : "",
  region : "",
  country : "",
});

const replies = ref({});
const uploadedFiles = ref({});
const showModal = ref(false);
const modalTitle = ref('');
const modalMessage = ref('');
const validationErrors = ref([]);
const acceptedTypes = '.pdf, .doc, .docx, .png, .jpeg, .txt, .html, .htm, .jpg, .csv, .xlsx, .xls'

const getCharacterCount = (optionId) => {
  return replies.value[optionId] ? replies.value[optionId].length : 0;
};

// Handle text input changes with validation
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
  let options = eventDetails.value.options;
  let optionsByGroup = [];
  options.forEach((option) => {
    if (option.group) {
      let existingGroup = optionsByGroup.find((groupOption) => {
        return groupOption.group === option.group
      });
      if (existingGroup) {
        existingGroup.options.push(option);
        if (existingGroup.sorting > option.sorting) {
          existingGroup.sorting = option.sorting; // make sure option collection has the smallest sorting of all contain options
        }
        if (existingGroup.level > option.level) {
          existingGroup.level = option.level; // make sure group title has the smallest level of all contain options
        }
      } else {
        optionsByGroup.push({
          "group": option.group,
          "options": [option],
          "sorting": option.sorting,
          "level": option.level,
        });
      }
    } else { // options without group have their own empty group and are sort at the end
      optionsByGroup.push({
        "group": "",
        "options": [option],
        "sorting": option.sorting,
        "level": option.level,
      })
    }
  });
  // inner sorting of the options of a group
  optionsByGroup.forEach((group) => {
    group.options.sort((option1 , option2) => {
      let o1 = option1.sorting ? option1.sorting : 1000;
      let o2 = option2.sorting ? option2.sorting : 1000;
      return o1 - o2;
    });
  })
  // sorting of the groups
  return optionsByGroup.sort((group1, group2) => {
    let g1 = group1.sorting ? group1.sorting : 1000;
    let g2 = group2.sorting ? group2.sorting : 1000;
      return g1 - g2;
  });
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
      return true; // For other file options (display only)
    case 'EventManager_Model_TextOption':
      return true; // Text options are display-only
    default:
      return false;
  }
};

const isOptionVisible = (option) => {
  if (!option.option_rule || option.option_rule.length === 0) {
    return true;
  }

  const ruleType = option.rule_type !== undefined ? Number(option.rule_type) : 1;

  if (ruleType === 1) { // at least one rule must be satisfied
    return option.option_rule.some(rule => evaluateRule(rule));
  } else { // all rules must be satisfied
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

const validateRequiredFields = () => {
  validationErrors.value = [];
  const errors = [];

  const requiredPersonalFields = [
    { key: 'firstName', value: contactDetails.value.firstName },
    { key: 'lastName', value: contactDetails.value.lastName },
    { key: 'email', value: contactDetails.value.email }
  ];

  requiredPersonalFields.forEach(field => {
    if (!field.value || field.value.trim() === '') {
      errors.push(field.key);
    }
  });

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

const postRegistration = async () => {
  validationErrors.value = [];

  if (!validateRequiredFields()) {
    modalTitle.value = 'Validation Error';
    modalMessage.value = 'Please fill all required fields.';
    showModal.value = true;
    return;
  }

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
  const registration = {'eventId': eventId, 'contactDetails': contactDetails.value, 'replies': filteredReplies};
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
        registrationId = data.id
        console.debug(data);
      });

  await uploadFiles(eventId, registrationId);

    modalTitle.value = 'Success';
    modalMessage.value = 'Registration completed successfully!';
    showModal.value = true;

  } catch (error) {
    console.error('Registration request failed:', error);
    modalTitle.value = 'Error';
    modalMessage.value = 'Registration failed. Please try again.';
    showModal.value = true;
  }
}

const uploadFiles = async (eventId, registrationId) => {
  const hasFiles = Object.values(uploadedFiles.value).some(files => files && files.length > 0);

  try {
    for (const [optionId, files] of Object.entries(uploadedFiles.value)) {
      if (files && files.length > 0) {
        const formData = new FormData();
        formData.append('eventId', eventId);

        // Add files for this specific option
        Array.from(files).forEach((file) => {
          formData.append('files[]', file);
        });

        const fileResponse = await fetch(`/EventManager/files/${eventId}/${optionId}/${registrationId}`, {
          method: 'POST',
          body: formData
        });

        const fileData = await fileResponse.json();
        console.debug('File upload response:', fileData);

      }
    }
  } catch (error) {
    console.error('File upload failed:', error);
  }
}

const clearForm = () => {
  contactDetails.value = {
    salutation: '',
    title: '',
    firstName: '',
    middleName: '',
    lastName: '',
    company: '',
    birthday: '',
    email: '',
    mobile: '',
    telephone: '',
    street: '',
    houseNumber: '',
    postalCode: '',
    city: '',
    region: '',
    country: ''
  };

  replies.value = {};

  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach(input => {
    input.value = '';
  });
}

const handleModalClose = () => {
  showModal.value = false;
  if (modalTitle.value === 'Success') {
    clearForm();
  }
}

const maxBirthDate = computed(() => {
  return new Date().toISOString().split('T')[0]
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
    download(data, name, type)
  })
}

async function getEvent() {
  let eventId = route.params.id;
  await fetch(`/EventManager/view/event/${eventId}`, {
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
      console.log(data);
    })
}

function handleFileChange(event, optionId) {
  const files = event.target.files;
  if (files.length > 0) {
    console.log(`Selected files for option ${optionId}:`, files);
    uploadedFiles.value[optionId] = files;
  } else {
    // Clear files if none selected
    delete uploadedFiles.value[optionId];
  }
}

getEvent();

</script>

<script>
export default {
  name: "Registration"
}
</script>

<style scoped lang="scss">

.required-field-error {
  border: 2px solid #dc3545 !important;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}
.required-field-error-container {
  border: 2px solid #dc3545;
  border-radius: 0.375rem;
  padding: 0.5rem;
  background-color: rgba(220, 53, 69, 0.05);
}
</style>
