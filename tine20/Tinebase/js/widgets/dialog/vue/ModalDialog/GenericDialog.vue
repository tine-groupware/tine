<!--
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <sdeshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
-->

<template>
  <BModal v-model="showModal"
          :title="modalProps.title"
          :title-class="'title'"
          :modal-class="'bootstrap-scope'"
          :hide-header-close="!modalProps.closable"
          :hide-footer="!modalProps.buttons"
          :centered="true"
          :no-fade="true"
          :lazy="true"
          :noCloseOnBackdrop="true"
          v-bind:data-bs-theme="darkmode"
          :noCloseOnEsc="true"
          class="dark-reverse"
          @close="handleModalClose" :style="{'z-index': modalProps.zIndex}"
          :id="modalProps.injectKey"
  >
    <template #default>
      <BOverlay :show="_maskModal">
        <div class="row">
          <div class="col-3" v-if="modalProps.persona">
            <PersonaContainer :icon-name="modalProps.persona" :skin-color="modalProps.skinColor"/>
          </div>
          <div class="col">
            <component :is="dlgContentComponent" ref="contentCompRef"/>
          </div>
        </div>
        <template #overlay>
          <div class="text-center">
            {{ modalProps.maskMessage }}
          </div>
        </template>
      </BOverlay>
    </template>
    <template #footer>
      <div>
        <BButton class="mx-1 x-tool-close vue-button" v-for="button in buttonToShow" @click="button.clickHandler"
                 ref="buttonRefs"
                 :key="button.name" :class="button.class" :hidden="button.hidden" :disabled="_maskModal || button.disabled" variant="secondary">
<!--          <img :src="button.iconSrc" class="buttonIcon" :alt="button.class"/>-->
          {{ button.text }}
        </BButton>
      </div>
    </template>
  </BModal>
</template>

<script setup>
import { computed, inject, ref, onBeforeUnmount, watch, nextTick } from 'vue'
import PersonaContainer from '../../../../ux/vue/PersonaContainer/PersonaContainer.vue'
import { createFocusTrap } from 'focus-trap'

const props = defineProps({
  modalProps: {
    title: String,
    persona: String,
    buttons: Object,
    closable: Boolean,
    injectKey: String,
    visible: Boolean,
    zIndex: Number,
    skinColor: String,

    maskModal: { type: Boolean, default: false },
    maskMessage: { type: String, default: 'Loading' },

    focusTrapStack: { type: Object, default: null }
  },
  dlgContentComponent: Object
})

const _maskModal = ref(false)
watch(() => props.modalProps.maskModal, (newVal) => {
  _maskModal.value = newVal
})

const contentCompRef = ref()

const darkmode = document.getElementsByTagName('body')[0].classList.contains('dark-mode') ? ref('dark') : ref('light')

const { eventBus: EventBus } = inject(props.modalProps.injectKey)

const showModal = ref(false)
const buttonRefs = ref()
let ft = null
watch(() => props.modalProps.visible, (newVal) => {
  if (newVal) {
    showModal.value = newVal
    const isKeyForward = (event) => {
      return !(['text', 'password'].includes(event.target?.type)) && (event.key === 'ArrowDown' || event.key === 'ArrowRight' || (event.key === 'Tab' && !event.shiftKey))
    }
    const isKeyBackward = (event) => {
      return !(['text', 'password'].includes(event.target?.type)) && (event.key === 'ArrowUp' || event.key === 'ArrowLeft' || (event.key === 'Tab' && event.shiftKey))
    }
    nextTick(() => {
      const focusEl = contentCompRef.value?.initialFocus || buttonRefs?.value[buttonRefs.value.length - 1].$el
      ft = createFocusTrap(
        `#${props.modalProps.injectKey} .modal-content`,
        {
          trapStack: props.modalProps.focusTrapStack,
          initialFocus: focusEl,
          isKeyForward,
          isKeyBackward,
          escapeDeactivates: false
        }
      )
      ft.activate()
    })
  } else {
    ft.deactivate()
    ft = null
    showModal.value = newVal
  }
})

const buttonToShow = computed(() => {
  if (props.modalProps.buttons) {
    return props.modalProps.buttons.map(button => {
      // let iconSrc;
      // switch(button.name){
      //   case 'ok':
      //     iconSrc = "images/icon-set/icon_ok.svg"
      //     break
      //   default:
      //     iconSrc = "images/icon-set/icon_ok.svg"
      //     break
      // }
      return {
        clickHandler: () => {
          handleButtonClick(button.eventName)
        },
        name: button.name,
        class: `${button.name}-button ${button?.iconCls || ''}`,
        text: button.text,
        hidden: button?.hidden || false,
        disabled: contentCompRef.value?.[`disable${button.name}`]
      }
    })
  } else {
    return []
  }
})

const handleButtonClick = (eventName) => {
  const val = contentCompRef.value.getValue()
  if (val || val === 0) EventBus.emit(eventName, val)
  else EventBus.emit(eventName)
}

const handleModalClose = () => {
  ft.deactivate()
  showModal.value = false
  EventBus.emit('close')
}

onBeforeUnmount(() => {
  ft?.deactivate()
})
</script>

<style lang="scss">
.container {
  --skin-color: #FFFFFF;
}
.vue-button.x-tool-close {
  background-image: none !important;
}

.vue-msg-box-svg-container {
  .skin {
    fill: var(--skin-color) !important;
  }
}

.vue-button{
  min-width: 70px;

  //.btn-content{
  //  display: flex;
  //  flex-direction: row;
  //  justify-content: space-around;
  //  .buttonIcon{
  //    height: 1.5em;
  //  }
  //}

}
</style>
