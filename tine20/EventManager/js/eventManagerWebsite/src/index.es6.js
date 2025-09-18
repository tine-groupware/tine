/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import "./loadVue"
import App from './App.vue'
import BootstrapVueNext from 'bootstrap-vue-next'
import _ from 'lodash'
import { translationHelper } from "./keys"

import FormatMessage from 'format-message'
require('Locale')
require('Locale/Gettext')

import {createRouter, createWebHashHistory} from 'vue-router';
import Events from "./Events.vue";
import Contact from "./Contact.vue";
import EventDetail from "./EventDetail.vue";
import Registration from "./Registration.vue";

_.each(Tine.__translationData.msgs, function (msgs, category) {
    Locale.Gettext.prototype._msgs[category] = new Locale.Gettext.PO(msgs)
})

let gettext = new Locale.Gettext()
gettext.textdomain('EventManager')

// NOTE: we use gettext tooling for template selection
//       as there is almost no tooling for icu-messages out there
FormatMessage.setup({
    missingTranslation: 'ignore'
})

const { createApp } = window.vue
const app = createApp(App);
app.config.globalProperties.formatMessage = function (template) {
      arguments[0] = gettext._hidden(template)
      return FormatMessage.apply(FormatMessage, arguments)
}

_.assign(app.config.globalProperties.formatMessage, FormatMessage)
app.provide(translationHelper, app.config.globalProperties.formatMessage)

const routes = [
  { path: '/event', component: Events},
  { path: '/event/:id', component: EventDetail},
  { path: '/contact', component: Contact},
  { path: '/event/:id/registration/:token?', component: Registration},
]

const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

app.use(router);
app.use(BootstrapVueNext);
app.mount('#tine-viewport-app')
