/*
 * Tine 2.0
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */

import { createTineApp, useFormatMessage } from '../../../../Tinebase/js/SinglePageApplication';
import App from './App.vue';
import AccountManagement from "./AccountManagement.vue";
import Contact from "./Contact.vue";
import EventDetail from "./EventDetail.vue";
import Events from "./Events.vue";
import Registration from "./Registration.vue";

const routes = [
  {path: '/account/:token?', name: 'account-management', component: AccountManagement, props: true},
  {path: '/contact', name: 'contact', component: Contact, props: true},
  {path: '/event/:id', name: 'event-detail', component: EventDetail, props: true},
  {path: '/events', name: 'events', component: Events, props: true},
  {path: '/event/:id/registration/:token?', name: 'registration', component: Registration, props: true},
]

const { mount } = createTineApp(App, {
  textdomain: 'EventManager',
  basePath: '/EventManager/view',
  routes,
});

// Export for route classes
export { useFormatMessage };

mount();
