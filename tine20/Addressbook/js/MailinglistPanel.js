/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Addressbook');

Tine.Addressbook.MailinglistPanel = Ext.extend(Ext.Panel, {

    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,

    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,

    /**
     * @property {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: 'Addressbook.Model.Contact',

    requiredGrant: 'editGrant',
    layout: 'fit',
    border: false,

    checkboxes: {},

    initComponent: function() {
        this.app = this.app || Tine.Tinebase.appMgr.get('Addressbook');
        this.title = this.title || this.app.i18n._('Mailing List');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.isMailinglistCheckbox = new Ext.form.Checkbox({
            hideLabels: true,
            boxLabel: this.app.i18n._('This group is a mailing list'),
            listeners: {scope: this, check: this.onMailinglistCheck}
        });
        
        this.emailField = new Ext.ux.form.MirrorTextField({
            width: 250,
            fieldLabel: this.app.i18n._('E-Mail'),
            xtype: 'mirrortextfield',
            name: 'email',
            maxLength: 128,
            allowBlank: true,
            checkState: function (editDialog, record) {
                this.validate();
            },
            validator: (value) => {
                const hasManageInternalDomainGrant = Tine.Tinebase.common.hasRight('manage_list_email_options', 'Addressbook');
                const domainValidation = Tine.Tinebase.common.checkEmailDomain(value);

                if (!hasManageInternalDomainGrant && domainValidation.isInternalDomain) {
                    return this.app.i18n._('You do not have the grant to enter internal domain');
                }

                const errorMessage = this.validateMailingList(value);
                if (errorMessage) {
                    domainValidation.isValid = false;
                    domainValidation.errorMessage = errorMessage;
                }

                return domainValidation.isValid || domainValidation.errorMessage;
            },
        });

        this.replyToComboBox = new Ext.form.ComboBox({
            hideLabel: false,
            disabled: true,
            width: 250,
            fieldLabel: this.app.i18n._('Reply to'),
            labelSeparator: '',
            name       : 'replyTo',
            store      : [
                ['mailingList', this.app.i18n._('Distribution list')],
                ['sender', this.app.i18n._('Sender')],
                ['both', this.app.i18n._('Sender and Distributor')]
            ],
            value      : 'sender',
        });
        
        const checkboxLabels = {
            'sieveKeepCopy': this.app.i18n._('Keep copy of group mails'),
            'sieveAllowExternal': this.app.i18n._('Forward external mails'),
            'sieveAllowOnlyMembers': this.app.i18n._('Only forward member mails'),
            'sieveForwardOnlySystem': this.app.i18n._('Only forward to system email accounts')
        };
        const checkboxItems = [];

        _.forOwn(checkboxLabels, (label, key) => {
            this.checkboxes[key] = new Ext.form.Checkbox({
                boxLabel: label,
                hideLabels: true,
            });
            checkboxItems.push(this.checkboxes[key]);
        });
        
        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [
                {
                layout: 'form',
                frame: true,
                hideLabels: true,
                width: '100%',
                items: [
                    this.isMailinglistCheckbox,
                    checkboxItems
                ]
            }, {
                layout: 'form',
                frame: true,
                width: '100%',
                items: [
                    this.emailField,
                    this.replyToComboBox
                ]
            }]
        }];
        this.supr().initComponent.call(this);
    },
    
    onMailinglistCheck: function(cb, checked) {
        Object.entries(this.checkboxes).forEach(([key, checkbox]) => {
            checkbox.setDisabled(!checked);
        });
        this.replyToComboBox.setVisible(checked);
        this.replyToComboBox.setDisabled(!checked);
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        this.listRecord = record;
        this.isMailingList = _.get(record, 'data.xprops.useAsMailinglist', false);
        this.isMailinglistCheckbox.checked = this.isMailingList;
        const sieveReplyTo = record?.data?.xprops?.sieveReplyTo ?? 'sender';
        const sieveReplyToEmail = record?.data?.email;

        this.replyToComboBox.setValue(sieveReplyTo);
        this.emailField.setValue(sieveReplyToEmail);
        this.emailField.value = sieveReplyToEmail;
        
        Object.entries(this.checkboxes).forEach(([key, checkbox]) => {
            checkbox.checked = _.get(record, 'data.xprops.' + key, false);
        });
        
        this.afterIsRendered().then(() => {
            const hasRight = Tine.Tinebase.common.hasRight('manage_list_email_options', 'Addressbook');
            const containerData = _.get(record, record.constructor.getMeta('grantsPath'));
            const containerGrant =  !containerData ? false : containerData[this.requiredGrant];
            const hasRequiredGrant = !editDialog.evalGrants || containerGrant;
            this.onMailinglistCheck(null, this.isMailingList);
            this.emailField.validate();
            this.setReadOnly(!hasRequiredGrant || !hasRight);
        });
    },
    
    setReadOnly(readOnly, includeMain = true) {
        if (includeMain) this.isMailinglistCheckbox.setReadOnly(readOnly);
        this.onMailinglistCheck(null, !readOnly && this.isMailinglistCheckbox?.checked);
    },
    
    onRecordUpdate: function(editDialog, record) {
        // TODO set record xprops
        let xprops = record.get('xprops');
        const isMailingList = this.isMailinglistCheckbox.getValue();
        
        if (! xprops || Ext.isArray(xprops)) {
            xprops = {};
        }
        xprops.useAsMailinglist = isMailingList;
        Object.entries(this.checkboxes).forEach(([key, checkbox]) => {
            xprops[key] = isMailingList ? checkbox.getValue() : false;
        });
        xprops.sieveReplyTo = this.replyToComboBox.getValue();
        record.set('xprops', xprops);
        this.listRecord.set('xprops', xprops);
    },

    validateMailingList: function (value) {
        const domainValidation = Tine.Tinebase.common.checkEmailDomain(value);
        this.isMailinglistCheckbox.setDisabled(domainValidation.isValid && !domainValidation.isInternalDomain);

        if (!this.isMailinglistCheckbox.checked) {
            if (domainValidation.isInternalDomain) {
                if (!this.alertedDomains) {
                    this.alertedDomains = [];
                }

                const title = this.app.i18n._('Email distribution list function required');
                const text = this.app.i18n._('E-Mail accounts for the domain of the specified email address are automatically managed by this installation. ' +
                    'You can only use addresses from this domain here if the “Mailing list” support is enabled for this group.');

                if(!this.alertedDomains.includes(value)) {
                    this.alertedDomains.push(value);
                    Ext.MessageBox.alert(title,
                        text + '<br><br>' +
                        this.app.i18n._('Would you like to enable this function now?'),
                    (btn) => {
                        if (btn === 'ok') {
                            this.isMailinglistCheckbox.setValue(true);
                        }
                    });
                }
                return title + '<br><br>' + text;
            }
        } else {
            if (!value) {
                return this.app.i18n._('E-Mail can not be empty when mailing list is enabled');
            }
            if (!domainValidation.isInternalDomain) {
                this.isMailinglistCheckbox.setValue(false);
            }
        }

        return null;
    }
});
