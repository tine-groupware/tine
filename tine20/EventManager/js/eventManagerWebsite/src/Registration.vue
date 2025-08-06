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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.firstName"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Middle Name')"
          label-for="input-horizontal"
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
          label-for="input-horizontal"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.lastName"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Company')"
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.email"></b-form-input>
        </b-form-group>
        <b-form-group
          label-cols-sm="4"
          label-cols-lg="3"
          content-cols-sm
          content-cols-lg="7"
          :label="formatMessage('Mobile')"
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
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
          label-for="input-horizontal"
          class="mb-3"
        >
          <b-form-input v-model="contactDetails.country"></b-form-input>
        </b-form-group>
      </b-col>
    </b-row>
    <b-row>
      <b-col v-if="eventDetails.options">
        <h4 class="mb-4">Event Specific Information:</h4>
        <h5>{{eventDetails.name}}</h5>
        <div v-for="optionGroup in sortOptionsByGroup">
<!--          <h6 v-if="optionGroup.group" :style="{'margin-left' : (optionGroup.level-1) * 2 + 'em'}">{{optionGroup.group}}</h6>-->
          <div v-for="option in optionGroup.options" :style="{'margin-left' : (option.level-1) * 2 + 'em'}">
            <div v-if="option.option_config_class === 'EventManager_Model_TextOption'">
              <h6>{{option.name_option}}:</h6>
              <div class="mb-3">{{option.option_config.text_option}}</div>
            </div>
            <div v-if="option.option_config_class === 'EventManager_Model_TextInputOption'">
              <b-form-group
                label-cols-sm="4"
                label-cols-lg="3"
                content-cols-sm
                content-cols-lg="7"
                :label="option.option_config.text"
                label-for="input-horizontal"
                class="mb-3"
              >
                <b-form-input v-model="replies[option.id]"></b-form-input>
              </b-form-group>
            </div>
            <div class="mb-3" v-if="option.option_config_class === 'EventManager_Model_CheckboxOption'">
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
            <div v-if="option.option_config_class === 'EventManager_Model_FileOption'">
              <div class="mb-3">
                <h6>{{option.name_option}}:</h6>
                <b-button @click="downloadFile(option.option_config.node_id , option.option_config.file_name, option.option_config.file_type)">{{formatMessage('Download file')}}</b-button>
              </div>
              <div class="mb-3">
                <input
                  id="file-input"
                  type="file"
                  class="form-control"
                  @change="(event) => handleFileChange(event, option.id)"
                  :accept="acceptedTypes"
                  :multiple="allowMultiple"
                >
              </div>
            </div>
          </div>
        </div>
        <b-button @click="postRegistration">{{formatMessage('Complete Registration')}}</b-button>
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
const acceptedTypes = '.pdf, .doc, .docx, .png, .jpeg, .txt, .html, .htm, .jpg, .csv, .xlsx, .xls'
const allowMultiple = false;

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
})

const postRegistration = async () => {
  let eventId = route.params.id;
  let registration = {'eventId': eventId, 'contactDetails': contactDetails.value, 'replies': replies.value};
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
      })
  } catch (error) {
    console.error('Registration request failed:', error);
  }
  await uploadFiles(eventId, registrationId);
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

const maxBirthDate = computed(() => {
  return new Date().toISOString().split('T')[0]
});

// function to only select one of the checkboxes inside a group
function singleSelection(option) {
sortOptionsByGroup.value.forEach((group) => {
  if (option.group === group.group && group.group !== "") {
    group.options.forEach((o) => {
      if (o.id !== option.id && o.option_config_class === 'EventManager_Model_CheckboxOption') {
        replies.value[o.id] = "false";
      }
    })
  }
})
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
      eventDetails.value.options.forEach((option) => {
        replies.value[option.id] = '';
      });
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

</style>
