<template>
  <div class="mt-2 table-container" v-if="visible">
    <table class="table table-bordered">
      <thead>
      <tr>
        <th class="table-light">{{ formatMessage('Purpose of processing') }}</th>
        <th v-if="!props.consentConfig.current_contact" class="table-light">{{ formatMessage('Description') }}</th>
        <th v-if="props.consentConfig.current_contact" class="status table-light">{{ formatMessage('Status') }}</th>
        <th v-if="props.consentConfig.current_contact" class="table-light"></th>
      </tr>
      </thead>
      <tbody>
      <tr v-for="purpose in extendedDataIntendedPurposes" :key="purpose.__record.key">
        <td class="highlight"><strong>{{ purpose.__record.name }}</strong></td>
        <td v-if="!props.consentConfig.current_contact" class="highlight" :class=purpose.__record.cellClass><strong>{{ purpose.__record.desc }}</strong></td>
        <td v-if="props.consentConfig.current_contact" class="status" :class=purpose.__record.cellClass>{{ purpose.__record.statusText}}</td>
        <td v-if="props.consentConfig.current_contact" class="action-button text-center"><BButton variant="primary py-0" @click="openDialog(purpose)">{{ formatMessage(purpose.__record.status) }}</BButton></td>
      </tr>
      </tbody>
    </table>
  </div>
  <ManageConsentDialog  @confirm="fetchData" @close="closeConsentDialog" :record="consentRecord" v-model="consentDialogVis"/>
</template>

<script setup>

import {computed, defineEmits, ref, watch} from 'vue';

import {useFormatMessage} from './index.es6';
import ManageConsentDialog from "./ManageConsentDialog.vue";

const props = defineProps({
  consentConfig: {type: Object, required: true},
})

const emit = defineEmits(['update:consentConfig'])
const consentConfig = ref(props.consentConfig);
const { formatMessage } = useFormatMessage();
const visible = ref(true);

watch(consentConfig, (newVal) => {
  emit('update:consentConfig', newVal)
})

const _ALL_DATA_INTENDED_PURPOSES = "allDataIntendedPurposes"
const _CONTACT_INTENDED_PURPOSE_RECORD = "current_contact.GDPR_DataIntendedPurposeRecord"
const _DEF_VAL = Object.freeze({
  agreeComment: null,
  agreeDate: null,
  created_by: null,
  creation_time: null,
  deleted_by: null,
  deleted_time: null,
  id: null,
  is_deleted: "0",
  last_modified_by: null,
  last_modified_time: null,
  record: null,
  seq: "1",
  withdrawComment: null,
  withdrawDate: null
})
const extendedDataIntendedPurposes = computed(() => {
  const dips = _.get(props.consentConfig, _ALL_DATA_INTENDED_PURPOSES);
  const isExpired = function (withdrawDate) {
    return withdrawDate && new Date(withdrawDate).getTime() < new Date().getTime();
  }

  let val = dips?.map(purpose => {
    let _purposeRecord = _.find(_.get(props.consentConfig, _CONTACT_INTENDED_PURPOSE_RECORD), (record) => record.intendedPurpose.id === purpose.id)
    if (!_purposeRecord) {
      _purposeRecord = {
        intendedPurpose: purpose,
        ..._DEF_VAL
      }
    }
    const getStatus = function (record) {
      if (!(record.withdrawDate || record.agreeDate)) return {
        status: 'Agree',
        localizedStatus: formatMessage('Agree'),
        statusText: formatMessage('Not decided'),
        cellClass: 'table-warning',
      };
      if (isExpired(record.withdrawDate)) {
        return {
          status: 'Agree',
          localizedStatus: formatMessage('Agree'),
          statusText: formatMessage('Withdrawal date') + ' ' + new Date(record.withdrawDate).toLocaleString("de"),
          cellClass: 'table-danger'
        };
      } else {
        return {
          status: 'Withdraw',
          localizedStatus: formatMessage('Withdraw'),
          statusText: formatMessage('Agreement date') + ' ' + new Date(record.agreeDate).toLocaleString("de"),
          cellClass: 'table-success'
        };
      }
    };

    const def_lang = props.consentConfig?.locale.locale;
    const getItem = (array, locale) => {
      return _.find(array, (item) => item.language === locale)
        || _.find(array, (item) => item.language === 'en')
        || null;
    }

    _purposeRecord.__record = {
      name: getItem(_purposeRecord.intendedPurpose.name, def_lang)?.text,
      desc: getItem(_purposeRecord.intendedPurpose.description, def_lang)?.text,
      key: `${_purposeRecord.id}_${_purposeRecord.last_modified_time}`,
      ...getStatus(_purposeRecord),
    };
    return _purposeRecord;
  }) || []

  return val.filter((purpose) => {
    return purpose.intendedPurpose.is_self_registration ? !isExpired(purpose.withdrawDate) : true
  })
})

const consentDialogVis= ref(false);
const consentRecord = ref(null);

const openDialog = function (record){
  consentRecord.value = record
  consentDialogVis.value = true;
}

const closeConsentDialog = async () => {
  consentDialogVis.value = false;
}

const fetchData = async () => {
  const response = await fetch(window.location.pathname.replace('/view/', '/'))
  if(!response.ok) throw new Error(response)
  consentConfig.value = await response.json();
}

</script>


<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

button {
  background-color: #DCE8F5;
  color: #222;
  border: 1px solid #008CC9;
}

button:hover {
  background-color: #FFF;
  color: #222;
  border: 1px solid #008CC9;
}

button:active {
  background-color: #DCE8F5;
  color: #222;
  border: 1px solid #008CC9;
}
td.highlight{
  background-color: #DCE8F5;
}
td.UNDECIDED{
  background-color: yellow;
}
td.WITHDRAWN{
  background-color: red;
}
td.AGREED{
  background-color: green;
}
td.button, th.button{
  width: 20%;
}

td.status, th.status{
  width: 320px;
}

td.action-button, td.action-button{
  min-width: 130px;
}

.button{
  width: 100%;
  padding: 0;
}

.consent-button-group{
  margin: 1em;
}

.consent-button-group > button{
  padding: 0 1em;
  margin: 0 1em;
}

.table-container {
  table {
    vertical-align: middle;
  }
}

</style>
