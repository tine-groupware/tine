<template>
    <BModal v-model="props.otherConfigs.visible"
        :title="props.opt.title"
        :title-class="'title'"
        :modal-class="'bootstrap-scope'"
        :hide-header-close="!props.opt.closable"
        :hide-footer="!props.opt.buttons"
        :centered="true"
        @close="closeBox"
    >
        <template #default>
            <div class="container">
                <div class="row">
                    <div class="col-3" v-if="props.opt.icon">
                        <img :src="imgSrc" class="dark-reverse">
                    </div>
                    <div class="col">
                        <div class="pb-1 mb-1">
                            <span v-html="props.opt.msg"></span>
                        </div>
                        <div class="pb-1 mb-1 ext-mb-textarea" v-if="textAreaElVisibllity">
                            <BFormTextarea v-model="textElValue" :rows="textAreaHeight"/>
                        </div>
                        <div class="pb-1 mb-1" v-if="textBoxElVisibility">
                            <BFormInput v-model="textElValue"/>
                        </div>
                        <div v-if="progressBarVisibility">
                            <BProgress :max="1" height="2em">
                                <BProgressBar
                                    :animated="props.opt.wait"
                                    :value="props.opt.wait ? 1 : props.opt.progressValue"
                                    variant="primary">
                                    <span v-html="props.opt.progressText"></span>
                                </BProgressBar>
                            </BProgress>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <div>
                <BButton class="mx-1 x-tool-close vue-button" v-for="button in buttonToShow" @click="button.clickHandler" :key="button.name" :class="button.class">{{ button.name }}</BButton>
            </div>
        </template>
    </BModal>
</template>

<script setup>
// TODO: change the progressBar according to `props.opt.waitConfig` if available
// NOTE: Ext.MessageBox.wait is currently not used with any waitConfig, so 
// the implementation is not given top priority
import {
    onBeforeMount,
    defineProps,
    watch,
    computed,
    ref,
    onBeforeUnmount
} from "vue"

import getIconPath from "./helpers";

import { useExtEventBus } from ".";

import {
    BModal,
    BFormTextarea,
    BProgressBar,
    BProgress,
    BButton,
    BFormInput
} from "bootstrap-vue-next"

const ExtEventBus = useExtEventBus();
const textBoxElVisibility = ref(false);
const textAreaElVisibllity = ref(false);
const progressBarVisibility = ref(false);
const textAreaHeight = ref(0);
const textElValue = ref("");
const imgSrc = ref()

const init = async function() {
    if (props.opt.icon){
        const {default: img} = await import(/* webpackChunkName: "Tinebase/js/[request]"*/`images/dialog-personas/${getIconPath(props.opt.icon)}.svg`)
        imgSrc.value = img
    }
    if(props.opt.prompt){
        if(props.opt.multiline){
            textBoxElVisibility.value = false;
            textAreaElVisibllity.value = true;
            textAreaHeight.value = (typeof props.opt.multiline === "number") ? props.opt.multiline / 25 : props.opt.defaultTextAreaHeight;
        }else{
            textBoxElVisibility.value = true;
            textAreaElVisibllity.value = false;
        }
    }else{
        textBoxElVisibility.value = false;
        textAreaElVisibllity.value = false;
    } 
    textElValue.value = props.opt.value;
    progressBarVisibility.value = props.opt.progress === true || props.opt.wait === true;
    
}

const props = defineProps({
    opt: Object,
    visible: Boolean,
    otherConfigs: Object
})

const closeBox = () => {
    if(props.opt.closable){
        ExtEventBus.emit("close");
    }
}

const buttonToShow = computed(() => {
    if(props.opt.buttons){ 
        const keys = Object.keys(props.opt.buttons).map(buttonName => {
            return {
                clickHandler: () => {
                    ExtEventBus.emit("buttonClicked", {
                        buttonName,
                        textElValue: textElValue.value,
                    })
                },
                name: props.otherConfigs.buttonText[buttonName],
                class: `${buttonName}-button`
            }
        })
        return keys;
    } else{
        return []
    }
})

watch(() => props.otherConfigs.visible, newVal =>{
    if(newVal) init();
})

onBeforeMount(async () => {
    await import(/* webpackChunkName: "Tinebase/js/CustomBootstrapVueStyles" */"library/ExtJS/src/widgets/VueMessageBox/styles.scss")
    init();
})
</script>

<style lang="scss">

.title{
    font-weight: bold;
}

.vue-button.x-tool-close{
    background-image: none !important;
}
</style>