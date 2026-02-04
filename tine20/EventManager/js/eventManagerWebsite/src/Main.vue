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
  <div class="page-wrap">
    <div>
      <b-navbar class="navbar-hr">
        <b-navbar-nav>
          <b-navbar-brand>Pastorale Dienststelle</b-navbar-brand>
          <b-nav-item href="EventManager/view/events">{{formatMessage('Events')}}</b-nav-item>
          <b-nav-item-dropdown :text="formatMessage('Topics')">
            <b-dropdown-item href="#">{{formatMessage('Church Service')}}</b-dropdown-item>
            <b-dropdown-item href="#">{{formatMessage('Voluntary Service')}}</b-dropdown-item>
            <b-dropdown-item href="#">{{formatMessage('Children')}}</b-dropdown-item>
            <b-dropdown-item href="#">{{formatMessage('Choir')}}</b-dropdown-item>
          </b-nav-item-dropdown>
          <b-nav-item @click.prevent="openEmailModal">{{formatMessage('My Registrations')}}</b-nav-item>
          <b-nav-item href="EventManager/view/contact">{{formatMessage('Contact')}}</b-nav-item>
        </b-navbar-nav>

        <b-navbar-nav class="ml-auto">
          <b-nav-form @submit.prevent="handleSearch">
            <b-form-input v-model="inputSearch" size="sm" class="mr-sm-2" :placeholder="formatMessage('Search')"></b-form-input>
            <b-button size="sm" class="search-button" type="submit">{{formatMessage('Search')}}</b-button>
          </b-nav-form>
        </b-navbar-nav>
      </b-navbar>
    </div>
    <main>
      <div>
        <SinglePage />
        <b-modal
          v-model="modal.show"
          :title="modal.title"
          :ok-only="modal.okOnly"
          :ok-title="modal.okText"
          :cancel-title="modal.cancelText"
          @ok="handleModalAction"
          @cancel="handleModalCancel"
        >
          <p v-html="modal.message"></p>

          <div v-if="modal.input" class="form-group">
            <b-form-input
              v-model="modal.inputValue"
              type="email"
              :placeholder="modal.inputPlaceholder"
              :state="modal.inputError ? false : null"
              required
            ></b-form-input>
            <b-form-invalid-feedback :state="!modal.inputError">
              {{ modal.inputError }}
            </b-form-invalid-feedback>
          </div>
        </b-modal>

      </div>
    </main>
    <footer class="mx-5 text-end">
      <a href="EventManager/contact">{{formatMessage('Imprint')}}</a>
      &#183
      <a href="EventManager/contact">{{formatMessage('Privacy Notice')}}</a>
    </footer>
  </div>
</template>

<script setup>

import {
  onBeforeMount,
  computed,
  ref,
  reactive
} from 'vue';

import SinglePage from './../../../../Tinebase/js/SinglePageApplication.vue';
import {useFormatMessage} from './index.es6';
const { formatMessage } = useFormatMessage();
import { navigateToEvents } from './searchUtils';

const inputSearch = ref("");

const handleSearch = () => {
  navigateToEvents(inputSearch.value);
};

const modal = reactive({
  show: false,
  title: '',
  message: '',
  type: 'info',
  okOnly: true,
  okText: 'OK',
  cancelText: 'Cancel',
  input: false,
  inputValue: '',
  inputPlaceholder: '',
  inputError: '',
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
  modal.input = config.input || false;
  modal.inputValue = config.inputValue || '';
  modal.inputPlaceholder = config.inputPlaceholder || '';
  modal.onConfirm = config.onConfirm || null;
  modal.onCancel = config.onCancel || null;
  modal.show = true;
};

const handleModalAction = (bvModalEvent) => {
  if (modal.input && !isEmailValid.value) {
    bvModalEvent.preventDefault();
    modal.inputError = formatMessage('Please enter a valid email address');
    return;
  }
  modal.inputError = '';

  if (modal.onConfirm) {
    modal.onConfirm(modal.input ? modal.inputValue : undefined);
  }

  modal.show = false;
  modal.inputValue = '';
  modal.inputError = '';
};

const handleModalCancel = () => {
  if (modal.onCancel) {
    modal.onCancel();
  }
  modal.show = false;
  modal.inputValue = '';
};

const isEmailValid = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return modal.inputValue.length > 0 && emailRegex.test(modal.inputValue);
});

const openEmailModal = () => {
  showModal({
    title: formatMessage('Email Address'),
    message: formatMessage('Please enter your email address to continue:'),
    type: 'confirm',
    okOnly: false,
    input: true,
    inputPlaceholder: formatMessage('Enter your email address'),
    onConfirm: handleEmailSubmit
  });
};

const handleEmailSubmit = async () => {
  try {
    const eventId = null;
    const response = await fetch(`/EventManager/registration/doubleOptIn/${eventId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: modal.inputValue,
      })
    });

    if (response.ok) {
      showModal({
        title: formatMessage('Confirmation E-Mail Sent'),
        message: formatMessage('Please check your email and click the confirmation link to continue.'),
        type: 'confirm',
        okOnly: true,
      });
    } else {
      throw new Error('Registration request failed');
    }
  } catch (error) {
    console.error(error);
    showModal({
      title: formatMessage('Registration Error'),
      message: formatMessage('There was an error sending your registration request. Please try again later.'),
      type: 'confirm',
      okOnly: true,
    });
  }
};

// websocket still pending, this only hide the loading mask...
onBeforeMount(async () => {
  document.getElementsByClassName('tine-viewport-waitcycle')[0].style.display = 'none';
})
</script>

<style lang="scss">
@import 'bootstrap/scss/bootstrap.scss';
@import 'bootstrap-vue-next/dist/bootstrap-vue-next.css';

.navbar-hr {
  border-bottom: 1px solid #333333;
}

main {
  flex-grow: 1;
}

.page-wrap{
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.search-button {
  font-weight: 500;
  border-radius: 8px;
  transition: all 0.3s ease;
  color: white !important;
  border: 2px solid #2c3e50 !important;
  background-color: #2c3e50 !important;

  &:hover {
    transform: scale(1.05);
    background-color: transparent !important;
    border-color: #2c3e50 !important;
    color: #2c3e50 !important;
    box-shadow: 0 2px 6px rgba(44, 62, 80, 0.3);
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

</style>
