<template>
    <BModal v-model="modalTrigger" @hide="hideDialog"
        :noCloseOnBackdrop="true"
        :noCloseOnEsc="true"
        :title="`${formatMessage('I am not {email}', {email: notThis})}. ${formatMessage('I am')}:`"
    >
        <template #default>
            <label for="email-addr" class="mb-2">{{ formatMessage("Enter your email address")}}:</label>
            <BFormInput
                id="email-addr"
                v-model="email"
                :placeholder="formatMessage('Enter your email')"
                type="email"
            ></BFormInput>
        </template>
        <template #footer>
            <BButton @click="onButtonClicked" variant="primary"> {{ formatMessage("Get my link")}}</BButton>
        </template>
    </BModal>
</template>

<script setup>

import {
    defineProps,
    watch,
    ref
} from "vue"

const props = defineProps({
    notThis: { type: String, required: true},
    modelValue: {type: Boolean, default: false},
    closable: {type: Boolean, default: true}
})

const emits = defineEmits(["update:modelValue"])

const modalTrigger = ref(false)
watch(() => props.modelValue, newVal => { modalTrigger.value = newVal })
watch(modalTrigger, newVal => { emits("update:modelValue", newVal) })

const hideDialog = ($e) => { if (!props.closable) $e.preventDefault() }

const email = ref("")
const onButtonClicked = () => {
    // todo check if email valid
    console.log("Send new link to: ", email.value)
}

</script>