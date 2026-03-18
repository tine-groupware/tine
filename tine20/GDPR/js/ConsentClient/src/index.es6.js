/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import { createTineApp, useFormatMessage } from '../../../../Tinebase/js/SinglePageApplication';
import App from './App.vue';
import ManageConsentPage from './ManageConsentPage.vue'
import RegistrationView from './RegistrationView.vue'
import EmailPage from './EmailPage.vue'

const routes = [
  { path: '/register/for/:dipId?', name: 'email-page',component: EmailPage, props: true},
  { path: '/register/:token?', name: 'registration-view',component: RegistrationView, props: true},
  { path: '/manageConsent/:contactId?', name: 'manage-consent', component: ManageConsentPage, props: true},
]

const { mount } = createTineApp(App, {
  textdomain: 'GDPR',
  basePath: '/GDPR/view',
  routes,
});

// Export for route classes
export { useFormatMessage };

mount();
