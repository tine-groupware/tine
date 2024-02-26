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
        const panel = this;
        this.app = this.app || Tine.Tinebase.appMgr.get('Addressbook');
        this.title = this.title || this.app.i18n._('Mailing List');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.isMailinglistCheckbox = new Ext.form.Checkbox({
            disabled: true,
            boxLabel: this.app.i18n._('This group is a mailing list'),
            listeners: {scope: this, check: this.onMailinglistCheck}
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
        const checkboxItems = [this.isMailinglistCheckbox];

        _.forOwn(checkboxLabels, function(label, key) {
            panel.checkboxes[key] = new Ext.form.Checkbox({
                disabled: true,
                boxLabel: label,
                hideLabels: true,
            });
            checkboxItems.push(panel.checkboxes[key]);
        });
        
        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                frame: true,
                hideLabels: true,
                width: '100%',
                items: checkboxItems
            }, {
                layout: 'form',
                frame: true,
                width: '100%',
                items: [
                    this.replyToComboBox
                ]
            }]
        }];
        this.supr().initComponent.call(this);
    },
    
    onMailinglistCheck: function(cb, checked) {
        _.forOwn(this.checkboxes, function(checkbox, key) {
            checkbox.setReadOnly(!checked);
            checkbox.setDisabled(!checked);
        });
        this.replyToComboBox.setDisabled(!checked);
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        this.listRecord = record;
        const evalGrants = editDialog.evalGrants;
        const isMailinglist = _.get(record, 'data.xprops.useAsMailinglist', false);
        // TODO check right here, too
        const hasRight = Tine.Tinebase.common.hasRight('manage_list_email_options', 'Addressbook');
        const hasRequiredGrant = !evalGrants
            || (_.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant) && hasRight);
        const mailinglistDisabled = ! (_.get(record, 'data.account_grants.adminGrant', false) && hasRight);

        this.isMailinglistCheckbox.setDisabled(mailinglistDisabled);
        this.isMailinglistCheckbox.setValue(isMailinglist);
        _.forOwn(this.checkboxes, function(checkbox, key) {
            checkbox.setValue(_.get(record, 'data.xprops.' + key, false));
            checkbox.setDisabled(! isMailinglist);
        });
        
        const sieveReplyTo = record?.data?.xprops?.sieveReplyTo ?? 'sender';
        this.replyToComboBox.setValue(sieveReplyTo);
        this.setReadOnly(!hasRequiredGrant || !hasRight);
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.isMailinglistCheckbox.setDisabled(readOnly);
        _.forOwn(this.checkboxes, function(checkbox, key) {
            checkbox.setDisabled(readOnly);
        });
    },

    onRecordUpdate: function(editDialog, record) {
        // TODO set record xprops
        let xprops = record.get('xprops');
        const isMailingList = this.isMailinglistCheckbox.getValue();
        
        if (! xprops || Ext.isArray(xprops)) {
            xprops = {};
        }
        xprops.useAsMailinglist = isMailingList;
        _.forOwn(this.checkboxes, function(checkbox, key) {
            xprops[key] = isMailingList ? checkbox.getValue() : false;
        });
        xprops.sieveReplyTo = this.replyToComboBox.getValue();
        record.set('xprops', xprops);
        this.listRecord.set('xprops', xprops);
    }
});
