/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Timetracker');

/**
 * Timetracker Edit Dialog
 */
Tine.Timetracker.TimesheetEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'TimesheetEditWindow_',
    appName: 'Timetracker',
    modelName: 'Timesheet',
    recordClass: 'Tine.Timetracker.Model.Timesheet',
    // recordProxy: Tine.Timetracker.timesheetBackend,
    tbarItems: null,
    evalGrants: false,
    useInvoice: false,
    displayNotes: true,
    context: { 'skipClosedCheck': false },

    windowWidth: 800,
    windowHeight: 530,
    
    factor: '0',
    factorChanged: false,

    
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     */
    updateToolbars: function(record) {
        this.onTimeaccountUpdate();
        Tine.Timetracker.TimesheetEditDialog.superclass.updateToolbars.call(this, record, 'timeaccount_id');
        if(this.useMultiple && !this.getForm().findField('is_billable').checked) {
            this.disableBillableFields(true)
        }
    },

    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        // TODO get this.timeAccount from filter if set - should be done in grid (onCreateNewRecord)
        if (this.record.get('timeaccount_id')) {
            this.timeAccount = new Tine.Timetracker.Model.Timeaccount(this.record.get('timeaccount_id'));
            this.onTimeaccountUpdate();
        }

        Tine.Timetracker.TimesheetEditDialog.superclass.onRecordLoad.call(this);
    },
    
    onTimeaccountSelect: function(field, timeaccount) {
        this.timeAccount = timeaccount;
        this.onTimeaccountUpdate();
        // set factor from this.timeAccount except it was manually changed before
        if (this.timeAccount) {
            if (!this.factorChanged) {
                this.factor = this.timeAccount.data.accounting_time_factor;
            }
            this.getForm().findField('accounting_time_factor').setValue(this.factor);

            this.calculateAccountingTime();
        }
    },

    /**
     * this gets called when initializing and if a new this.timeAccount is chosen
     *
     */
    onTimeaccountUpdate: function() {
        if (!this.timeAccount) return;
        // check for manage_timeaccounts right
        var manageRight = Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts');
        var notBillable = false;
        var notClearable = false;

        // TODO this.timeAccount.get('account_grants') contains [Object object] -> why is that so? this should be fixed
        var grants = this.record.get('timeaccount_id')
            ? this.record.get('timeaccount_id').account_grants
            : (this.timeAccount.get('container_id') && this.timeAccount.get('container_id').account_grants
                ? this.timeAccount.get('container_id').account_grants
                :  {});
        
        if (grants) {
            var setDisabled = !(grants.bookAllGrant || grants.adminGrant || manageRight);
            var accountField = this.getForm().findField('account_id');
            accountField.setDisabled(setDisabled);
            // set account id to the current user, if he doesn't have the right to edit other users timesheets
            if (setDisabled) {
                if (this.copyRecord && (this.record.get('account_id') !== Tine.Tinebase.registry.get('currentAccount').accountId)) {
                    accountField.setValue(Tine.Tinebase.registry.get('currentAccount'));
                }
            }
            notBillable = !(grants.manageBillableGrant || grants.adminGrant || manageRight);
            notClearable = !(grants.adminGrant || manageRight);
            // only if useInvoice = false
            if(!this.useInvoice) {
                this.getForm().findField('billed_in').setDisabled(!(grants.adminGrant || manageRight));
            }
        }

        if (this.timeAccount.data) {
            notBillable = notBillable || this.timeAccount.data.is_billable == "0" || this.timeAccount.get('is_billable') == "0";
            
            // clearable depends on this.timeAccount is_billable as well (changed by ps / 2009-09-01, behaviour was inconsistent)
            notClearable = notClearable || this.timeAccount.data.is_billable == "0" || this.timeAccount.get('is_billable') == "0";

            if (this.timeAccount.data.is_billable == "0" || this.timeAccount.get('is_billable') == "0") {
                this.getForm().findField('is_billable').setValue(false);
            }
            
            //Always reset is_billable to true on copy timesheet (only if Timaccount is billable of course)
            if (this.copyRecord && (this.timeAccount.data.is_billable == "1" || this.timeAccount.get('is_billable') == "1")) {
                this.getForm().findField('is_billable').setValue(true);
            }

            this.getForm().findField('timeaccount_description').setValue(this.timeAccount.data.description);

            this.getForm().findField('is_billable').setDisabled(notBillable);
            this.disableBillableFields(!this.getForm().findField('is_billable').checked);
            this.getForm().findField('is_cleared').setDisabled(notClearable);
            this.disableClearedFields(notClearable);
        }
        
        if (this.record.id === 0) {
            // set is_billable for new records according to the this.timeAccount setting
            this.getForm().findField('is_billable').setValue(this.timeAccount.data.is_billable);
        }
    },
    
    /**
     * Always set is_billable if this.timeAccount is billable. This is needed for copied sheets where the
     * original is set to not billable
     */
    onAfterRecordLoad: function() {
        Tine.Timetracker.TimesheetEditDialog.superclass.onAfterRecordLoad.call(this);
        if (this.record.id == 0 && this.record.get('timeaccount_id') && this.record.get('timeaccount_id').is_billable) {
            this.getForm().findField('is_billable').setValue(this.record.get('timeaccount_id').is_billable);
        }

        this.factor = this.timeAccount ? this.timeAccount.get('accounting_time_factor') : this.getForm().findField('accounting_time_factor').getValue();

        if (this.record.get('accounting_time_factor') !== this.factor) {
            this.factor = this.record.get('accounting_time_factor');
            this.factorChanged = true;
        }

        this.calculateAccountingTime();
        var focusFieldName = this.record.get('timeaccount_id') ? 'duration' : 'timeaccount_id',
            focusField = this.getForm().findField(focusFieldName);

        focusField.focus(true, 250);
    },

    /**
     * this gets called when initializing and if cleared checkbox is changed
     *
     * @param {} field
     * @param {} newValue
     *
     * @todo    add prompt later?
     */
    onClearedUpdate: function(field, checked) {
        if (!this.useMultiple) {
            this.getForm().findField(this.useInvoice ? 'invoice_id': 'billed_in').setDisabled(! checked);
        }
    },

    initComponent: function() {
        var salesApp = Tine.Tinebase.appMgr.get('Sales');
        this.useInvoice = Tine.Tinebase.appMgr.get('Sales')
            && salesApp.featureEnabled('invoicesModule')
            && Tine.Tinebase.common.hasRight('manage', 'Sales', 'invoices')
            && Tine.Sales.Model.Invoice;

        this.on('beforeapplychanges', this.onBeforeApplyChanges, this);
        Tine.Timetracker.TimesheetEditDialog.superclass.initComponent.call(this);
    },

    /**
     * overwrites the isValid method on multipleEdit
     */
    isMultipleValid: function() {
        var valid = true;
        var keys = ['timeaccount_id', 'description', 'account_id'];
        Ext.each(keys, function(key) {
            var field = this.getForm().findField(key);
            if(field.edited && ! field.validate()) {
                field.markInvalid();
                valid = false;
            }
        }, this);
        return valid;
    },

    calculateAccountingTime: function() {
        const roundingMinutes = Tine.Tinebase.configManager.get('accountingTimeRoundingMinutes', 'Timetracker') || 15;
        const roundingMethod = Tine.Tinebase.configManager.get('accountingTimeRoundingMethod', 'Timetracker') || 'round';

        if (!this.useMultiple) {
            const factor = this.getForm().findField('accounting_time_factor').getValue();
            const duration = this.getForm().findField('duration').getValue();
            const accountingTime = Math[roundingMethod](factor * duration / roundingMinutes) * roundingMinutes;

            if (factor !== this.factor) {
                this.factor = factor;
                this.factorChanged = true;
            }
            this.getForm().findField('accounting_time').setValue(accountingTime);
        }
    },

    onCheckBillable: function(field, checked) {
        if (!this.useMultiple) {
            if (!checked) {
                this.disableClearedFields(true);
                this.getForm().findField('is_cleared').setDisabled(true);
            } else {
                if (!this.factorChanged) {
                    this.factor = this.timeAccount.data.accounting_time_factor;
                }
                this.getForm().findField('accounting_time_factor').setValue(this.factor);
                this.calculateAccountingTime();
                this.disableClearedFields(false);
                this.getForm().findField('is_cleared').setDisabled(false);
            }
        }
        this.disableBillableFields(!checked)
    },
    
    disableBillableFields: function(disable) {
        this.getForm().findField('accounting_time_factor').setDisabled(disable);
    },

    disableClearedFields: function(disable) {
        this.getForm().findField(this.useInvoice ? 'invoice_id': 'billed_in')?.setDisabled(disable);
    },
    
    onEndTimeChange: function() {
        let endTime = this.record.get('end_date') ?
            Date.parseDate(`${this.record.get('end_date').format('Y-m-d')} ${this.getForm().findField('end_time').getValue().format('H:i:s')}`, Date.patterns.ISO8601Long)
            : this.getForm().findField('end_time').getValue();
        if (! endTime) return;

        let startTime = this.getForm().findField('start_time').getValue() ? Date.parseDate(`${this.getForm().findField('start_date').getValue().format('Y-m-d')} ${this.getForm().findField('start_time').getValue().format('H:i:s')}`, Date.patterns.ISO8601Long) : null;
        if (! startTime) {
            startTime = endTime.clone().add(Date.MINUTE, -1 * this.getForm().findField('duration').getValue());
            this.getForm().findField('start_time').setValue(startTime.format(this.getForm().findField('start_time').format));
        } else {
            if (endTime < startTime) {
                endTime = endTime.add(Date.DAY, 1);
                this.record.set('end_date', endTime.clone().clearTime());
            }
            const duration = (+endTime - +startTime)/60000
            this.getForm().findField('duration').setValue(duration);
        }
    },

    checkStates() {
        Tine.Timetracker.TimesheetEditDialog.superclass.checkStates.apply(this, arguments);

        const accountId = this.getForm().findField('account_id').getValue();
        const timeAccount = this.getForm().findField('timeaccount_id').selectedRecord;
        const isNewRecord = !this.record.get('creation_time');
        const grants = _.get(timeAccount, 'data.account_grants', {});
        const isOwn = Tine.Tinebase.registry.get('currentAccount').accountId === accountId;
        const processStatusPicker = this.getForm().findField('process_status')
        const processStatus = processStatusPicker.getValue();
        const allowUpdate = grants.adminGrant || grants.manageBillableGrant || grants.bookAllGrant || (isOwn && grants.bookOwnGrant)
            || (processStatus === 'REQUESTED' && (isNewRecord || (isOwn && grants.requestOwnGrant)))
            || Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts')
            || Tine.Tinebase.common.hasRight('admin', 'Timetracker');

        this.getForm().findField('account_id').setDisabled(! (grants.bookAllGrant ||grants.adminGrant));
        processStatusPicker.setDisabled(!(grants.manageBillableGrant || grants.bookAllGrant || (isOwn && grants.bookOwnGrant) || grants.adminGrant));
        if (isNewRecord && timeAccount !== processStatusPicker.timeAccount) {
            processStatusPicker.timeAccount = timeAccount;
            processStatusPicker.setValue((grants.manageBillableGrant || grants.bookAllGrant || (isOwn && grants.bookOwnGrant)) ? 'ACCEPTED' : 'REQUESTED');
        }

        [this.attachmentsPanel, this.action_saveAndClose].forEach((item) => {
            item[item.setReadOnly ? 'setReadOnly' : 'setDisabled'](!allowUpdate);
        });

        let startTime = this.getForm().findField('start_time').getValue();
        const duration = this.getForm().findField('duration').getValue();

        if (!startTime && duration > 24*60) {
            startTime = new Date().clearTime()
            this.getForm().findField('start_time').setValue(startTime);
        }

        if (startTime) {
            const startDate = this.getForm().findField('start_date').getValue();
            const endTimeField = this.getForm().findField('end_time');
            let endTime = Date.parseDate(`${startDate.format('Y-m-d')} ${startTime.format('H:i:s')}`, Date.patterns.ISO8601Long).add(Date.MINUTE, duration);
            this.record.set('end_date', endTime.clone().clearTime())
            endTimeField.setValue(endTime.format(endTimeField.format));
        } else {
            this.record.set('end_date', null);
            this.getForm().findField('end_time').setValue(null);
        }

        this.multiDayHint.setVisible(!!this.record.get('correlation_id'));

        this.calculateAccountingTime();
    },

    onBeforeApplyChanges: async function() {
        if (this.record.get('correlation_id')) {
            const changes = this.record.getChanges();
            const multiApplicableFields = _.difference(Object.keys(changes), ['start_date', 'start_time', 'end_time', 'duration', 'accounting_time']);
            if (multiApplicableFields.length) {
                if (await Ext.MessageBox.show({
                    title: this.app.formatMessage('Change all Correlated Timesheets'),
                    msg: this.app.formatMessage('Apply these changes to all correlated Timesheets?') + '<br />' + _.map(multiApplicableFields, fieldName => {
                        return `<b>${this.getForm().findField(fieldName)?.fieldLabel || fieldName}:</b> ${Tine.widgets.grid.RendererManager.get('Timetracker', 'Timesheet', fieldName, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL)(changes[fieldName])}`;
                    }).join('<br />'),
                    buttons: Ext.MessageBox.YESNO,
                    icon: Ext.MessageBox.QUESTION
                }) === 'yes') {
                    // @TODO add handling for relations (rewrite MultipleEditDialogPlugin and use it)
                    const { results: timesheets } = await Tine.Timetracker.searchTimesheets([{field: 'correlation_id', operator: 'equals', value: this.record.get('correlation_id')}], {sort: 'start_date', dir: 'ASC'});
                    await Tine.Tinebase.updateMultipleRecords('Timetracker', 'Timesheet', _.reduce(changes, (a, v, k) => { return _.concat(a, multiApplicableFields.indexOf(k) >= 0 ? {name: k, value: v} : []) }, []), [{field: 'id', operator: 'in', value: _.map(timesheets, 'id')}]);

                    await this.loadRecord('remote')
                    _.forEach(changes, (v, k) => {
                        if ( multiApplicableFields.indexOf(k) < 0) {
                            this.record.set(k, v);
                        }
                    })
                }
            }

        }
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initialization is done.
     */
    getFormItems: function() {
        var _ = window.lodash,
            me = this,
            fieldManager = _.bind(
                Tine.widgets.form.FieldManager.get, 
                Tine.widgets.form.FieldManager, 
                this.appName, 
                this.modelName, 
                _, 
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
        
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items:[{
                title: this.app.i18n.ngettext('Timesheet', 'Timesheets', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                layoutConfig: {
                    enableResponsive: true,
                    responsiveBreakpointOverrides: [{level: 2, width: 600}]
                },
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    autoScroll: true,
                    border: false,
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        plugins: [{
                            ptype: 'ux.itemregistry',
                            key: 'Timetracker.Timesheet.CustomField',
                        }],
                        formDefaults: {
                            xtype: 'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: .25
                        },
                        items: [[{
                            xtype: 'v-alert',
                            variant: 'info',
                            columnWidth: 1,
                            ref: '../../../../../../multiDayHint',
                            hidden: true,
                            label: '<a href="#">' + this.app.i18n._('This Item is part of a multi day series of correlated Timesheets.') + '</a>',
                            listeners: {
                                render: function (){
                                    this.getEl().on('click', async e => {
                                        e.stopEvent();
                                        const { results: timesheets } = await Tine.Timetracker.searchTimesheets([{field: 'correlation_id', operator: 'equals', value: me.record.get('correlation_id')}], {sort: 'start_date', dir: 'ASC'});
                                        await Ext.MessageBox.show({
                                            buttons: Ext.Msg.OK,
                                            icon: Ext.MessageBox.INFO,
                                            title: me.app.formatMessage('Ccorrelated Timesheets:'),
                                            msg: timesheets.reduce((accu, tsData) => {
                                                const timesheet = Tine.Timetracker.Model.Timesheet.setFromJson(tsData)
                                                const dateFormat = me.getForm().findField('start_date').format
                                                return accu.concat(timesheet.id === me.record.id ? [] :
                                                    `<a href="#" data-record-class="Timetracker_Model_Timesheet" data-record-id="${timesheet.id}">${timesheet.get('start_date').format(dateFormat)}: ${timesheet.getTitle()}</a>`)
                                            }, []).join('<br />')
                                        });
                                    })
                                }
                            }
                        }], [
                            fieldManager('timeaccount_id', {
                                disabled: !!this.record.get('workingtime_is_cleared'),
                                columnWidth: 0.75,
                                listeners: {
                                    scope: this,
                                    select: this.onTimeaccountSelect
                                },
                                lazyInit: false
                            }),
                            fieldManager('process_status', {
                                columnWidth: 0.25,
                            })
                        ], [
                            fieldManager('duration', {
                                disabled: !!this.record.get('workingtime_is_cleared'),
                                selectOnFocus: true,
                                allowBlank: false,
                                allowNegative: false,
                                enableKeyEvents: true,
                            }),
                            fieldManager('start_date', {disabled: !!this.record.get('workingtime_is_cleared')}), 
                            fieldManager('start_time', {
                                disabled: !!this.record.get('workingtime_is_cleared'),
                            }),
                            fieldManager('end_time', {
                                disabled: !!this.record.get('workingtime_is_cleared'),
                                listeners: {
                                    scope: this,
                                    blur: this.onEndTimeChange,
                                    select: this.onEndTimeChange,
                                },
                                setValue: function(value, field) {
                                    Ext.form.TimeField.prototype.setValue.apply(this, arguments);
                                    const startDateField = me.getForm().findField('start_date');
                                    const dateFormat = startDateField.format;
                                    const endDate = me.record.get('end_date')
                                    const endDateString = endDate ? endDate.format(dateFormat) : '';
                                    if (endDateString && endDateString !== startDateField.getValue().format(dateFormat)) {
                                        const warn = `<span ext:qtip="${me.app.i18n._('Time period overlaps day boundary. When saved, multiple timesheets will be created.')}" class="x-dialog-warn tine-grid-row-action-icon"}></span>`
                                        this.setFieldLabel(`${warn} ${this.initialConfig.fieldLabel} (${endDateString})`);
                                    } else {
                                        this.setFieldLabel(this.initialConfig.fieldLabel);
                                    }

                                },
                                validateValue : function(preventMark) {
                                    let isValid = this.supr().validateValue();
                                    const duration = this.findParentBy((c) => {return c.getForm }).getForm().findField('duration').getValue();
                                    if (duration < 0) {
                                        this.markInvalid(me.app.i18n._('End must not be before start.'));
                                        return false;
                                    }
                                    return isValid;
                                },

                            }),
                        ], [
                            fieldManager('description', {
                                disabled: !!this.record.get('workingtime_is_cleared'),
                                columnWidth: 1,
                                allowBlank: false,
                                validator: (v) => {
                                    return v !== '';
                                },
                                height: 150}),
                            ]
                        ]
                    }, {
                        layout: 'hbox',
                        height: 160,
                        // autoHeight: true,
                        layoutConfig: {
                            align: 'stretch',
                            pack: 'start'
                        },
                        items: [{
                            flex: 1,
                            xtype: 'fieldset',
                            layout: 'hfit',
                            margins: '0 5 10 5',
                            title: this.app.i18n._('Accounting'),
                            items: [{
                                xtype: 'columnform',
                                labelAlign: 'top',
                                // autoHeight: true,
                                columnLayoutConfig: {
                                    responsiveBreakpointOverrides: [{level: 2, width: 420}],
                                },
                                formDefaults: {
                                    xtype: 'textfield',
                                    anchor: '100%',
                                    labelSeparator: '',
                                    columnWidth: .25
                                },
                                items: [[
                                    fieldManager('is_billable', {
                                        columnWidth: .4,
                                        listeners: {
                                            scope: this,
                                            check: this.onCheckBillable
                                        }}),
                                    fieldManager('accounting_time_factor', {
                                        disabled: false,
                                        columnWidth: .1,
                                        decimalSeparator: ',',
                                        fieldLabel: this.app.i18n._('Factor'),
                                        listeners: {
                                            scope: this,
                                            change: this.calculateAccountingTime
                                    }}),
                                    fieldManager('accounting_time', {
                                        disabled: true,
                                        fieldLabel: this.app.i18n._('Accounting time'),
                                        listeners: {
                                            scope: this
                                        }}),
                                    fieldManager('need_for_clarification'),
                                ], [
                                    fieldManager('is_cleared', {columnWidth: .4, listeners: {
                                        scope: this,
                                        check: this.onClearedUpdate
                                    }}),
                                    fieldManager(this.useInvoice ? 'invoice_id' : 'billed_in', {columnWidth: .5 }),
                                ], [
                                    fieldManager('workingtime_is_cleared', {columnWidth: .4, disabled: true}),
                                    fieldManager('workingtime_cleared_in', {disabled: true, columnWidth: .5}),
                                ]]
                            }]
                        }]
                    }, {
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype: 'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: 1
                        },
                        items: [[
                            fieldManager('account_id')
                        ]]
                    }]
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.tags.TagPanel({
                            app: 'Timetracker',
                            border: false,
                            // bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, {
                    title: this.app.i18n._('Time Account'),
                    autoScroll: true,
                    border: false,
                    frame: true,
                    layout: 'border',
                    items: [{
                        region: 'center',
                        layout: 'fit',
                        height: 400,
                        flex: 1,
                        border: false,
                        style: 'padding-bottom: 5px;',
                        items: [{
                            xtype: 'textarea',
                            name: 'timeaccount_description',
                            grow: false,
                            preventScrollbars: false,
                            fieldLabel: this.app.i18n._('Description'),
                            readOnly: true
                        }]
                    }]
                }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (! this.copyRecord) ? this.record.id : null,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
    
    /**
     * returns additional save params
     *
     * @returns {{checkBusyConflicts: boolean}}
     */
    getAdditionalSaveParams: function() {
        return {
            context: this.context
        };
    },
    
    /**
     * show error if request fails
     * 
     * @param response
     * @param request
     * @private
     */
    onRequestFailed: function(response, request) {
        this.saving = false;
        
        if (response.code && response.code === 902) {
            // deadline exception
            Ext.MessageBox.alert(
                this.app.i18n._('Failed'), 
                String.format(this.app.i18n._('Could not save {0}.'), this.i18nRecordName) 
                    + ' ( ' + this.app.i18n._('Booking deadline for this time account has been exceeded.') /* + ' ' + response.message  */ + ')'
            );
        } else if (response.code && response.code == 444) {
            //Time Account is closed
            if(Tine.Tinebase.common.hasRight('manage', 'Timetracker', 'timeaccounts')) {
                this.onClosedWarning.apply(this, arguments);
            } else {
                Ext.MessageBox.alert(
                    this.app.i18n._('Closed time account warning!'),
                    String.format(this.app.i18n._('The selected time account is already closed.'))
                );
            }
        } if (response.code === 650) {
            Tine.Tinebase.ExceptionHandler.handleRequestException(response, function () {
                this.onAfterApplyChanges(true);
            }, this);
        } else {
            // call default exception handler
            Tine.Tinebase.ExceptionHandler.handleRequestException(response);
        }
        this.hideLoadMask();
    },
    
    onClosedWarning: function() {
        Ext.Msg.confirm(this.app.i18n._('Closed time account warning!'),
            this.app.i18n._('The selected time account is already closed. Do you wish to continue anyway?'),
            function(btn) {
                if (btn == 'yes') {
                    this.context = { 'skipClosedCheck': true };
                    this.onApplyChanges(true);
                }
            }, this);
    },

    /**
     * disabled exportbutton in TimesheetEditDialog
     */
    initActions: function () {
        Tine.Timetracker.TimesheetEditDialog.superclass.initActions.call(this);
        this.action_export = null;
    },

    doCopyRecord: function() {
        Tine.Timetracker.TimeaccountEditDialog.superclass.doCopyRecord.call(this);
        const factor = this.record.data?.timeaccount_id?.accounting_time_factor;
        this.record.set('accounting_time_factor', factor ?? 1);
    }
});
