/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './loadVue'
import App from './App.vue';
import BootstrapVueNext from 'bootstrap-vue-next';
import _ from 'lodash'
import FormatMessage from 'format-message'
import { createRouter, createWebHashHistory, createWebHistory } from 'vue-router'

import ManageConsentPage from './ManageConsentPage.vue'
import RegistrationView from './RegistrationView.vue'
import EmailPage from './EmailPage.vue'

/* global Locale */
/* eslint no-undef: "error" */
require('Locale')
require('Locale/Gettext')

_.each(Tine.__translationData.msgs, function (msgs, category) {
  Locale.Gettext.prototype._msgs[category] = new Locale.Gettext.PO(msgs)
})


let gettext = new Locale.Gettext()
gettext.textdomain('GDPR')

// NOTE: we use gettext tooling for template selection
//       as there is almost no tooling for icu-messages out there
FormatMessage.setup({
  missingTranslation: 'ignore'
})
const app = vue.createApp(App);
app.config.globalProperties.formatMessage = function (template) {
  arguments[0] = gettext._hidden(template)
  return FormatMessage.apply(FormatMessage, arguments)
}

_.assign(app.config.globalProperties.formatMessage, FormatMessage)
app.config.globalProperties.fmHidden = app.config.globalProperties.formatMessage
app.config.globalProperties.window = window

const injectKey = Symbol('formatMessage');
app.provide(injectKey, {formatMessage: app.config.globalProperties.formatMessage, fmHidden: app.config.globalProperties.formatMessage});
export const useFormatMessage = () => window.vue.inject(injectKey);

const routes = [
  { path: '/register/for/:dipId?', name: 'email-page',component: EmailPage, props: true},
  { path: '/register/:token?', name: 'registration-view',component: RegistrationView, props: true},
  { path: '/manageConsent/:contactId?', name: 'manage-consent', component: ManageConsentPage, props: true},
]

const router = createRouter({
  history: createWebHistory('/GDPR/view'), // TODO: recommended option <according to docs>, requires `/GDPR/view/*` route config in /GDPR/Controller.php
  routes
})

app.use(BootstrapVueNext);
app.use(router)
app.mount('#tine-viewport-app');
