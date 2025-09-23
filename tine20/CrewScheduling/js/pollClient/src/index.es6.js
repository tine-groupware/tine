/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import { createTineApp, useFormatMessage } from 'Tinebase/js/SinglePageApplication';
import App from './App.vue';
import PollClient from "./PollClient.vue";

const routes = [
  { path: '/:pollCode', name: 'poll-client',component: PollClient, props: true},
  { path: '/:pollCode/:userCode', name: 'poll-client-participant',component: PollClient, props: true},
];

const { mount } = createTineApp(App, {
  textdomain: 'CrewScheduling',
  basePath: '/CrewScheduling/view/Poll',
  routes
});

export { useFormatMessage };

mount();
