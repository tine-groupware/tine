<template>
    <div class="container" v-if="!loading || !consentConfig">
        <BNavbar class="my-3">
            <BNavbarBrand>
                <TineLogo class="logo"/> 
            </BNavbarBrand>
            <!-- <BButton variant="primary" @click="handleNotSomeone">{{ formatMessage('I am not {email}', {email: email}) }}</BButton> -->
        </BNavbar>
        <div>
            <div class="ps-3 my-4">
                <h3>{{ formatMessage('Manage consent')}}: <span>{{email}}</span></h3>
                <p>{{ consentPageExplaination }}</p>
            </div>
            <div class="mt-2 container px-auto table-container">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ formatMessage('Purpose') }}</th>
                            <th class="status table-light text-center">{{ formatMessage('Status') }}</th>
                            <th class="button table-light"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="purpose in extendedDataIntendedPurposes" :key="purpose.__record.key">
                            <td class="highlight"><strong>{{ purpose.__record.name }}</strong></td>
                            <td class="status text-center" :class=purpose.__record.cellClass>{{ purpose.__record.statusText}}</td>
                            <td class="table-light text-center"><BButton variant="primary py-0 px-5" @click="openDialog(purpose)" class="mt-auto">{{ purpose.__record.status }}</BButton></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- <EmailDialog :notThis="email" v-model="emailDialogVis" :closable="validContact"> </EmailDialog> -->
    <ManageConsentDialog  @confirm="fetchData" @close="closeConsentDialog" :record="consentRecord" v-model="consentDialogVis"/>
</template>

<script setup>
import {
    onBeforeMount,
    ref,
    reactive,
    watch,
    computed
} from 'vue';
import TineLogo from './icon-components/TineLogo.vue';
import ManageConsentDialog from './ManageConsentDialog.vue';
import EmailDialog from './EmailDialog.vue'
import { useFormatMessage } from './index.es6';

const { formatMessage } = useFormatMessage();
const consentConfig = ref(null);
const validContact = ref(true);
const loading = ref(true);
let fetchURL = ""

const _CONTACT_EMAIL = "current_contact.email"
const email = computed(() => _.get(consentConfig.value, _CONTACT_EMAIL) || "__")

const _CONSENT_PAGE_EXPALIN_TEXT = "manageConsentPageExplainText"
const consentPageExplaination = computed(() => {
    return '' ; // _.get( _.get(consentConfig.value, _CONSENT_PAGE_EXPALIN_TEXT), "en", null)
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
    const val = _.get(consentConfig.value, _ALL_DATA_INTENDED_PURPOSES)?.map(purpose => {
        // const purposeRecord = _.find(consentConfig.value.dataIntendedPurposeRecords, (record) => {
        //     return record.id === purpose.id
        // })
        let _purposeRecord = _.find(_.get(consentConfig.value, _CONTACT_INTENDED_PURPOSE_RECORD), (record) => record.intendedPurpose.id === purpose.id)
        if (!_purposeRecord) {
            _purposeRecord = {
                intendedPurpose: purpose,
                ..._DEF_VAL
            }
        }
        const getStatus = function( record ) {
            if(!(record.withdrawDate || record.agreeDate)) return {
                status: 'Manage',
                statusText: formatMessage('Not decided'),
                cellClass: 'table-warning',
            };
            if(record.withdrawDate && new Date(record.withdrawDate).getTime() < new Date().getTime()) {
                return {
                    status: 'Agree',
                    statusText: formatMessage('Withdrawn on {date}', {date: new Date(record.withdrawDate).toLocaleString("de")}),
                    cellClass: 'table-danger'
                };
            } else {
                return {
                    status: 'Withdraw',
                    statusText: formatMessage('Agreed on {date}', {date: new Date(record.agreeDate).toLocaleString("de")}),
                    cellClass: 'table-success'
                };
            }
        };
        const _GDPR_DEFAULT_LANG = "GDPR_default_lang"
        const def_lang = _.get(consentConfig.value, _GDPR_DEFAULT_LANG)
        const getItem = (array, locale) => _.find(array, (item) => item.language === locale) || null
        _purposeRecord.__record = {
            name: getItem(_purposeRecord.intendedPurpose.name, def_lang)?.text,
            desc: getItem(_purposeRecord.intendedPurpose.description, def_lang)?.text,
            key: `${_purposeRecord.id}_${_purposeRecord.last_modified_time}`,
            ...getStatus(_purposeRecord),
        };
        return _purposeRecord;
    }) || []
    return val
})

const handleNotSomeone = () => {
    openEmailDialog()
}

const emailDialogVis = ref(false)
const openEmailDialog = () => { emailDialogVis.value = true }
const closeEmailDialog = () => { emailDialogVis.value = false }

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
    const _t = window.location.href.split('/').pop();
    if(_t == "manageConsent") throw new Error("Invalid Link");
    fetchURL = `/GDPR/manageConsent/${_t}` 
    const response = await fetch(fetchURL)
    if(!response.ok) throw new Error(response)
    consentConfig.value = await response.json();
}

onBeforeMount(async () => {
    loading.value = true;
    try{
        await fetchData();
    } catch(e) {
        validContact.value = false
        emailDialogVis.value = true
    }
    document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
    loading.value = false;
}) 
</script>

<style lang="scss">
$primary: #0062a7;
$secondary: #8cb8d7;
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

.logo{
    height: 3em;
}

/* div.content{
    width: 90%;
    margin: 0 auto;
    margin-top: 1em;
} */
div.title-bar{
    margin-top: 1em;
}

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
    width: 50%;
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
</style>
