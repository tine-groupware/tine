import "./loadVue"

import App from "./App.vue"
import BootstrapVueNext from "bootstrap-vue-next"
// until the floorplans work quite well in dark mode
import './styles.scss'
import _ from "lodash";
import { translationHelper } from "./keys"

import FormatMessage from 'format-message'
/* global Locale */
/* eslint no-undef: "error" */
require('Locale')
require('Locale/Gettext')

_.each(Tine.__translationData.msgs, function (msgs, category) {
  Locale.Gettext.prototype._msgs[category] = new Locale.Gettext.PO(msgs)
})

let gettext = new Locale.Gettext()
gettext.textdomain('Calendar')

// NOTE: we use gettext tooling for template selection
//       as there is almost no tooling for icu-messages out there
FormatMessage.setup({
  missingTranslation: 'ignore'
})

const { createApp } = window.vue
const app = createApp(App)
app.config.globalProperties.formatMessage = function (template) {
  arguments[0] = gettext._hidden(template)
  return FormatMessage.apply(FormatMessage, arguments)
}

_.assign(app.config.globalProperties.formatMessage, FormatMessage)
app.provide(translationHelper, app.config.globalProperties.formatMessage)

app.use(BootstrapVueNext)
app.mount("#tine-viewport-app")
