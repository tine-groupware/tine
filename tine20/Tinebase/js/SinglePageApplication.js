import * as vue from "tine-vue";
window.vue = vue;
import BootstrapVueNext from 'bootstrap-vue-next';
import { createRouter, createWebHistory } from 'vue-router';
import _ from 'lodash';
import FormatMessage from 'format-message';
import { getCurrentInstance } from 'vue';

/* global Locale */
/* eslint no-undef: "error" */
require('Locale');
require('Locale/Gettext');

// Define injection key at module level
const FORMAT_MESSAGE_KEY = Symbol.for('formatMessage');
const TINE_TEXT_DOMAIN_KEY = Symbol.for('TineDomainKey');

// This helper is used in the index.js file
export function initComponent(comp, domainName) {
    comp[TINE_TEXT_DOMAIN_KEY] = domainName;
    return comp;
}

function setupTranslations(textdomain) {
    _.each(Tine.__translationData.msgs, function (msgs, category) {
        Locale.Gettext.prototype._msgs[category] = new Locale.Gettext.PO(msgs);
    });

    let gettext = new Locale.Gettext();
    gettext.textdomain(textdomain);

    FormatMessage.setup({
        missingTranslation: 'ignore'
    });

    return gettext;
}

function configureApp(app, textdomain) {
    const gettext = setupTranslations(textdomain);

    app.config.globalProperties.formatMessage = function (template) {
        const instance = getCurrentInstance();
        const domainToUse = instance.type?.[TINE_TEXT_DOMAIN_KEY] || textdomain;
        gettext.textdomain(domainToUse);
        let msg = gettext.getmsg(domainToUse, gettext.category);
        let translatedTemplate = null;
        if (msg) {
            translatedTemplate = msg.get(template);
        }
        if (!translatedTemplate) {
            gettext.textdomain('Tinebase');
            translatedTemplate = gettext._hidden(template);
        }
        arguments[0] = translatedTemplate;

        return FormatMessage.apply(FormatMessage, arguments);
    };

    _.assign(app.config.globalProperties.formatMessage, FormatMessage);
    app.config.globalProperties.fmHidden = app.config.globalProperties.formatMessage;
    app.config.globalProperties.window = window;

    // Use the passed injection key
    app.provide(FORMAT_MESSAGE_KEY, {
        formatMessage: app.config.globalProperties.formatMessage,
        fmHidden: app.config.globalProperties.formatMessage
    });

    return FORMAT_MESSAGE_KEY;
}

export function createTineApp(AppComponent, options = {}) {
    const {
        textdomain = 'Tinebase', // Default value, can be overridden
        routes = [],
        basePath = '/Tinebase/view',
        mountElement = '#tine-viewport-app'
    } = options;

    const app = vue.createApp(AppComponent);

    configureApp(app, textdomain, FORMAT_MESSAGE_KEY);

    if (routes.length > 0) {
        const router = createRouter({
            history: createWebHistory(basePath),
            routes
        });
        app.use(router);
    }
    app.use(BootstrapVueNext);

    return {
        app,
        mount: () => app.mount(mountElement)
    };
}

export const useFormatMessage = () => {
    const context = window.vue.inject(FORMAT_MESSAGE_KEY);
    if (!context) {
        console.warn('formatMessage not found. Make sure the app is properly configured.');
        return {
            formatMessage: (template, ...args) => template,
            fmHidden: (template, ...args) => template
        };
    }
    return context;
};