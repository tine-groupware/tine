/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import {Personas} from "../../../../ux/vue/PersonaContainer";

Ext.ns('Tine.Tinebase.widgets.dialog');

Tine.Tinebase.widgets.dialog.PasswordDialog = Ext.extend(Tine.widgets.dialog.ModalDialog, {
    /**
     * @cfg {Boolean} allowEmptyPassword
     * Allow to proceed with an empty password
     */
    allowEmptyPassword: false,

    /**
     * @cfg {Boolean} hasPwGen
     * dialog provides password generation action
     */
    hasPwGen: true,

    /**
     * @cfg {Boolean} locked
     * password field is locked (****) per default
     */
    locked: true,

    /**
     * @cfg {String} windowTitle
     * title text when openWindow is used
     */
    windowTitle: '',

    /**
     * @cfg {String} questionText
     * question label for user prompt
     */
    questionText: '',

    /**
     * @cfg {String} passwordFieldLabel
     * label of password field
     */
    passwordFieldLabel: '',

    additionalFields: [],

    policyConfig: null,


    /**
     * Constructor.
     */
    initComponent: async function () {
        this.title = this.windowTitle || i18n._('Set password');
        this.persona = Personas.QUESTION_INPUT
        this.policyConfig = this.policyConfig || Tine.Tinebase.configManager.get('downloadPwPolicy')

        this.supr().initComponent.call(this)
        this.contentProps = window.vue.reactive({
            passwordLabel: this.passwordFieldLabel || i18n._('Password'),
            questionText: this.questionText,
            hasPwGen: this.hasPwGen,
            allowBlank: this.allowEmptyPassword,
            locked: this.locked,
            clipboard: this.hasPwGen,
            pwMandatoryByPolicy: this.policyConfig?.pwIsMandatory || false,

            injectKey: this.injectKey,
            additionalFields: this.additionalFields
        })

        this.injected['genPW'] = Tine.Tinebase.widgets.form.PasswordTriggerField.prototype.genPW.bind(this)

        const { default: PasswordDialog } = await import(/* webpackChunkName: "Tinebase/vue/PasswordDialog"*/'./PasswordDialog.vue')

        this.dlgContentComponent = PasswordDialog
        this.postInit()
    },

    openWindow: function () {
        this.showModal()
        return this.windowProxy
    }
});

const jsb2 =
    {
        "text": "PasswordDialog.js",
        "path": "js/widgets/dialog/"
    }

    const _jsb2 =
        {
            "text": "index.js",
            "path": "js/widgets/dialog/vue/PasswordDialog/"
    }
