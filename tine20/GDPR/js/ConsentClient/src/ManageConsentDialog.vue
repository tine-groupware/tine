<template>
    <BModal v-model="modalTrigger" :title="dialogTitle"
        :noCloseOnBackdrop="true"
        :noCloseOnEsc="true"
            centered
    >
        <!-- :footerClass="consentStatus === 'UNDECIDED' ? 'justify-content-end': 'justify-content-between'" -->
        <template #default v-if="record">
          <p> {{ consentStatus !== 'Agree'
            ? formatMessage('I hereby withdraw the following intended purpose for data processing')
            : formatMessage('I hereby accept the following intended purpose for data processing')}}:
          </p>
          <div v-if="record.__record.name" class="fs-5 ps-1">
            <p class="fw-bold mb-1"> {{record?.__record.name}}</p>
          </div>
          <div v-if="record.__record.desc" class="fs-6 ps-1">
            <p>{{ record.__record.desc }}</p>
          </div>
            <div class="mt-3">
                <h5>{{ consentStatus !== 'Agree' ? formatMessage('Withdrawal comment') : formatMessage('Agreement comment') }}:</h5>
                <BFormTextarea
                    no-resize
                    :rows="5"
                    v-model="comment"
                />
            </div>
        </template>
        <template #footer>
            <!-- <BButton variant="primary" @click="addComment = !addComment" v-if="consentStatus !== 'UNDECIDED'">{{ addComment ? formatMessage("Don't add a comment") : formatMessage("Add a comment")}}</BButton> -->
            <BButton variant="primary" @click="emits('close')">{{ formatMessage('Cancel') }}</BButton>
            <BButton variant="primary" @click="postConsent">{{ formatMessage('Confirm') }}</BButton>
        </template>
    </BModal>
</template>

<script setup>
import {computed, defineProps, ref, watch,} from 'vue'
import {useFormatMessage} from './index.es6'

const { formatMessage } = useFormatMessage();
const props = defineProps({
    record: {type: Object, default: null},
    modelValue: { type: Boolean, default: false}
})

const emits = defineEmits(['confirm', 'update:modelValue', 'close']);
const modalTrigger = ref(false)

watch(() => props.modelValue, newVal => { modalTrigger.value = newVal })
watch(modalTrigger, newVal => { emits("update:modelValue", newVal) })

const consentStatus = ref(null);
const dialogTitle = computed(() => {
  return formatMessage(props.record?.__record.status || '') + ': ' + formatMessage(props.record?.__record.name || '')
})

const comment = ref();
watch(()=> props.modelValue, (newVal) => {
    if(newVal){
        // addComment.value = !!( props.record?.agreeComment || props.record?.withdrawComment )
      consentStatus.value = props.record?.__record.status === 'Manage' ? 'Agree' : props.record?.__record.status;
        const previousComment = props.record?.__record.status === 'Agree' ? props.record?.agreeComment : props.record?.withdrawComment;
        comment.value = previousComment || "";
    }
})
const postConsent = async () => {
    const body = JSON.parse(JSON.stringify(props.record))
    delete body.__record
    switch(consentStatus.value){
        case 'Withdraw':
            body.withdrawComment = comment.value;
            body.withdrawDate = new Date().toISOString().replace('T', ' ').split('.')[0];
            break;
        case 'Agree':
            body.agreeComment = comment.value;
            body.agreeDate = new Date().toISOString().replace('T', ' ').split('.')[0];
            break;
        default:
            // add nothing to the body
            break;
    }

    const contactId = window.location.href.split('/').pop();
    await fetch(`/GDPR/manageConsent/${contactId}`, {
        method: 'POST',
        body: JSON.stringify(body)
    }).then(resp => resp.json())
    .then(data => {
        console.debug(data);
    })
    modalTrigger.value = false
    emits('confirm');
}
</script>
