<template>
    <BModal v-model="modalTrigger" :title="record?.__record.name"
        :noCloseOnBackdrop="true"
        :noCloseOnEsc="true"
    >
        <!-- :footerClass="consentStatus === 'UNDECIDED' ? 'justify-content-end': 'justify-content-between'" -->
        <template #default v-if="record">
            <p v-if="record.__record.desc">{{ record.__record.desc }}</p>
            <div>
                <h5>{{ formatMessage('Status') }}:</h5>
                <BFormSelect
                    v-model="consentStatus"
                    :options="consentOptions"
                />
            </div>
            <div class="mt-3">
                <h5>{{ formatMessage('Comment') }}:</h5>
                <BFormTextarea
                    no-resize
                    :rows="5"
                    v-model="comment"
                />
            </div>
        </template>
        <template #footer>
            <!-- <BButton variant="primary" @click="addComment = !addComment" v-if="consentStatus !== 'UNDECIDED'">{{ addComment ? formatMessage("Don't add comment") : formatMessage("Add comment")}}</BButton> -->
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

const modalShow = ref(false);
const consentStatus = ref(null);
const consentOptions = computed(() => {
  const status = props.record?.__record.status === 'Manage' ? 'Agree' : props.record?.__record.status;
  return [
    {value: status, text: formatMessage(status)},
  ]
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
            break;
        case 'Agree':
            body.agreeComment = comment.value;
            break;
        default:
            // add nothing to the body
            break;
    }
    console.info("posting consent:", body)
    // todo
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
