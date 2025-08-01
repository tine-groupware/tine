/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

import OrganizerCombo from "./OrganizerCombo";

/**
 * Simple Import Dialog
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.ImportDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * @constructor
 * @param       {Object} config The configuration options.
 * 
 * TODO add app grid to show results when dry run is selected
 */
Tine.Calendar.ImportDialog = Ext.extend(Tine.widgets.dialog.ImportDialog, {
    
    appName: 'Calendar',
    modelName: 'Event',
    
    /**
     * init import wizard
     */
    initComponent: function() {
        Tine.log.debug('Tine.Calendar.ImportDialog::initComponent');
        Tine.log.debug(this);

        Tine.Calendar.ImportDialog.superclass.initComponent.call(this);
    },

    getItems: function() {
        return [
            this.getPanel(),
            this.getEventOptionsPanel()
        ];
    },
    
    /**
     * do import request
     * 
     * @param {Function} callback
     * @param {Object}   importOptions
     */
    doImport: function(callback, importOptions, clientRecordData) {
        var targetContainer = this.containerCombo.getValue();
        var type = this.typeCombo.getValue();
        
        var params = {
            importOptions: Ext.apply({
                container_id: targetContainer,
                sourceType: this.typeCombo.getValue(),
                importFileByScheduler: (this.typeCombo.getValue() == 'remote_caldav') ? true : false
            }, importOptions || {})
        };

        if (this.typeCombo.getValue() == 'remote_caldav') {
            params.importOptions = Ext.apply({
                password: this.remotePassword.getValue(),
                username: this.remoteUsername.getValue()
            }, params.importOptions);
        }

        if (type == 'upload') {
            params = Ext.apply(params, {
                clientRecordData: clientRecordData,
                method: this.appName + '.import' + this.recordClass.getMeta('modelName')  + 's',
                tempFileId: this.uploadButton.getTempFileId(),
                definitionId: this.definitionCombo.getValue()
            });
        } else {
            params = Ext.apply(params, {
                method: this.appName + '.importRemote' + this.recordClass.getMeta('modelName')  + 's',
                remoteUrl: this.remoteLocation.getValue(),
                interval: this.ttlCombo.getValue()
            });
        }

        // finally apend generic options from Calendar_Import_Abstract
        Object.assign(params.importOptions, this.eventOptionsForm.getForm().getFieldValues())

        const attendeePanel = _.find(this.eventOptionsForm.items.items, { name: 'attendee' });
        attendeePanel.onRecordUpdate(attendeePanel.record);
        params.importOptions.attendee = attendeePanel.record.get('attendee');

        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            callback: this.onImportResponse.createDelegate(this, [callback], true),
            params: params
        });
    },
    
    /**
     * called when import request sends response
     * 
     * @param {Object}   request
     * @param {Boolean}  success
     * @param {Object}   response
     * @param {Function} callback
     */
    onImportResponse: function(request, success, response, callback) {
        var decoded = Ext.util.JSON.decode(response.responseText);
        
        Tine.log.debug('Tine.widgets.dialog.SimpleImportDialog::onImportResponse server response');
        Tine.log.debug(decoded);
        
        this.lastImportResponse = decoded;

        var that = this;

        if (success) {
            const type = this.typeCombo.getValue()

            if (type !== 'upload' && this.ttlCombo.getValue() !== 'once') {
                Ext.MessageBox.show({
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.INFO,
                    fn: callback,
                    scope: that,
                    title: that.app.i18n._('Import Definition Success!'),
                    msg: that.app.i18n._('The iCal import definition has been created successfully! Please wait a few minutes for the events to be synced by the cron job.')
                });
            } else {
                _.defer(_.bind(callback, that))
            }
            
            var wp = this.app.mainScreen.getWestPanel(),
                tp = wp.getContainerTreePanel(),
                state = wp.getState();
                
            tp.getLoader().load(tp.getRootNode());
            wp.applyState(state);
            
        } else {
            Tine.Tinebase.ExceptionHandler.handleRequestException(response, callback, that);
        }
    },
    
    /**
     * Returns a panel with a upload field and descriptions
     * 
     * @returns {Object}
     */
    getUploadPanel: function () {
        return {
            xtype: 'panel',
            baseCls: 'ux-subformpanel',
            id: 'uploadPanel',
            hidden: true,
            title: i18n._('Choose Import File'),
            height: 100,
            items: [{
                xtype: 'label',
                html: '<p>' + i18n._('Please choose the file that contains the records you want to add to Tine 2.0') + '</p><br />'
            }, {
                xtype: 'tw.uploadbutton',
                ref: '../../uploadButton',
                text: String.format(i18n._('Select file containing your {0}'), this.recordClass.getRecordsName()),
                handler: this.onFileReady,
                allowedTypes: this.allowedFileExtensions,
                scope: this
            }]
        };
    },
    
    /**
     * Returns a panel with a text field for a remote location and a description
     * 
     * @returns {Object}
     */    
    getRemotePanel: function () {
        var ttl = [
            ['once', this.app.i18n._('once')],
            ['hourly', this.app.i18n._('hourly')],
            ['daily', this.app.i18n._('daily')],
            ['weekly', this.app.i18n._('weekly')]
        ];

        var ttlStore = new Ext.data.ArrayStore({
            fields: ['ttl_id', 'ttl'],
            data: ttl
        });
        
        return {
            xtype: 'form',
            labelAlign: 'top',
            baseCls: 'ux-subformpanel',
            id: 'remotePanel',
            hidden: false,
            title: this.app.i18n._('Choose Remote Location'),
            //height: 230,
            items: [{
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Please choose a remote location you want to add to Tine 2.0') + '</p><br />'
            }, {
                ref: '../../remoteLocation',
                xtype: 'textfield',
                scope: this,
                enableKeyEvents: true,
                width: 400,
                listeners: {
                    scope: this,
                    keyup: function() {
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'label',
                ref: '../../remoteUsernameLabel',
                html: '<p><br />' + this.app.i18n._('Username') + '</p><br />'
            }, {
                ref: '../../remoteUsername',
                xtype: 'textfield',
                scope: this,
                disabled: true,
                enableKeyEvents: true,
                width: 400,
                listeners: {
                    scope: this,
                    keyup: function() {
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'label',
                ref: '../../remotePasswordLabel',
                html: '<p><br />' + this.app.i18n._('Password') + '</p><br />'
            }, {
                ref: '../../remotePassword',
                xtype: 'tw-passwordTriggerField',
                clipboard: false,
                scope: this,
                disabled: true,
                enableKeyEvents: true,
                width: 400,
                listeners: {
                    scope: this,
                    keyup: function() {
                        this.manageButtons();
                    }
                }
            }, {
                xtype: 'label',
                html: '<p><br />' + this.app.i18n._('Refresh time') + '</p><br />'
            }, {
                xtype: 'combo',
                mode: 'local',
                ref: '../../ttlCombo',
                value: 'once',
                scope: this,
                width: 400,
                listeners: {
                    scope: this,
                    'select': function() {
                        this.manageButtons();
                    }
                },
                editable: false,
                allowblank: false,
                valueField: 'ttl_id',
                displayField: 'ttl',
                store: ttlStore
            }]
        };
    },
    
    getImportOptionsPanel: function () {
        if (this.importOptionsPanel) {
            return this.importOptionsPanel;
        }
        
        return {
            xtype: 'panel',
            ref: '../../importOptionsPanel',
            baseCls: 'ux-subformpanel',
            title: this.app.i18n._('General Settings'),
            height: 100,
            width: 400,
            items: [{
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Calendar name (you need permissions to add events)') + '<br /><br /></p>'
            }, {
                xtype: 'panel',
                heigth: 150,
                layout: 'hbox',
                border: false,
                items: [{
                    xtype: 'panel',
                    border: false,
                    flex: 1,
                    height: 20,
                    items: [new Tine.widgets.container.SelectionComboBox({
                        id: this.app.appName + 'EditDialogContainerSelector',
                        ref: '../../../../containerCombo',
                        stateful: false,
                        containerName: this.recordClass.getContainerName(),
                        containersName: this.recordClass.getContainersName(),
                        appName: this.appName,
                        value: this.defaultImportContainer,
                        requiredGrant: false,
                        recordClass: this.recordClass,
                        width: 400
                    })]
                }]
            }]
        };
    },
    
    getDefinitionPanel: function () {
        if (this.definitionPanel) {
            return this.definitionPanel;
        }
        
        var def = this.selectedDefinition,
            description = def ? def.get('description') : '',
            options = def ? def.get('plugin_options_json') : null,
            example = options && options.example ? options.example : '';
    
        return {
            xtype: 'panel',
            ref: '../../definitionPanel',
            id: 'definitionPanel',
            hidden: true,
            baseCls: 'ux-subformpanel',
            title: this.app.i18n._('What should the file you upload look like?'),
            flex: 1,
            items: [
            {
                xtype: 'label',
                html: '<p>' + this.app.i18n._('tine (Groupware) does not support all types of files you may want to upload. You will need to manually adjust your file so that tine (Groupware) can process it.') + '</p><br />'
            }, {
//                xtype: 'label',
//                html: '<p>' + this.app.i18n._('Below is a list of all supported import formats, along with a sample file showing how Tine 2.0 expects your file to be structured.') + '</p><br />'
//            }, {
                xtype: 'label',
                html: '<p>' + this.app.i18n._('Please select the import format of the file you want to upload') + '<br /><br /></p>'
            }, {
                xtype: 'combo',
                ref: '../../definitionCombo',
                store: this.definitionsStore,
                displayField:'label',
                valueField:'id',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                allowBlank: false,
                forceSelection: true,
                width: 400,
                value: this.selectedDefinition ? this.selectedDefinition.id : null,
                listeners: {
                    scope: this,
                    'select': this.onDefinitionSelect
                }
            }, {
                xtype: 'label',
                ref: '../../exampleLink',
                html: example ? ('<p><a href="' + example + '">' + this.app.i18n._('Download example file') + '</a></p>') : '<p>&nbsp;</p>'
            }, {
                xtype: 'displayfield',
                ref: '../../definitionDescription',
                height: 70,
                value: description,
                cls: 'x-ux-display-background-border',
                style: 'padding-left: 5px;'
            }]
        };
    },
    
    /**
     * returns the file panel of this wizard (step 1)
     */
    getPanel: function() {
        var def = this.selectedDefinition,
            description = def ? def.get('description') : '',
            options = def ? def.get('plugin_options_json') : null,
            example = options && options.example ? options.example : '';
        
        var types = [
            ['remote_ics', this.app.i18n._('Remote ICS File')],
            ['remote_caldav', i18n._('Remote CalDAV Server')],
            ['upload', this.app.i18n._('Upload Local File')]
        ]
        
        var typeStore = new Ext.data.ArrayStore({
            fields: [
                'type_id',
                'type_value'
            ],
            data: types,
            disabled: false
        });

        return {
            // title: this.app.i18n._('Choose File and Format'),
            // baseCls: 'ux-subformpanel',
            layout: 'vbox',
            border: false,
            xtype: 'ux.displaypanel',
            // frame: true,
            ref: '../filePanel',
            items: [{
                xtype: 'panel',
                baseCls: 'ux-subformpanel',
                title: this.app.i18n._('Select type of source'),
                height: 60,
                items: [{
                    xtype: 'combo',
                    mode: 'local',
                    ref: '../../typeCombo',
                    width: 400,
                    listeners:{
                        scope: this,
                        'select': function (combo) {
                            if (combo.getValue() == 'upload') {
                                Ext.getCmp('uploadPanel').show();
                                Ext.getCmp('definitionPanel').show();
                                Ext.getCmp('remotePanel').hide();
                            } else if (combo.getValue() == 'remote_ics' || combo.getValue() == 'remote_caldav') {
                                Ext.getCmp('uploadPanel').hide();
                                Ext.getCmp('definitionPanel').hide();
                                Ext.getCmp('remotePanel').show();
                                if (combo.getValue() == 'remote_caldav') {
                                    this.remoteLocation.emptyText = 'http://example/calendars';
                                    this.remoteUsername.enable();
                                    this.remotePassword.enable();
                                    this.remoteUsername.show();
                                    this.remotePassword.show();
                                    this.remoteUsernameLabel.show();
                                    this.remotePasswordLabel.show();
                                } else {
                                    this.remoteLocation.emptyText = 'http://example.ics';
                                    this.remoteUsername.disable();
                                    this.remotePassword.disable();
                                    this.remoteUsername.hide();
                                    this.remotePassword.hide();
                                    this.remoteUsernameLabel.hide();
                                    this.remotePasswordLabel.hide();
                                }
                                this.remoteLocation.applyEmptyText();
                                this.remoteLocation.reset();
                            }
                            
                            this.doLayout();
                            this.manageButtons();
                        },
                        'render': function (combo) {
                            combo.setValue('upload');
                            Ext.getCmp('uploadPanel').show();
                            Ext.getCmp('definitionPanel').show();
                            Ext.getCmp('remotePanel').hide();
                        }
                    },
                    scope: this,
                    valueField: 'type_id',
                    displayField: 'type_value',
                    store: typeStore
                }]
            },
            this.getUploadPanel(),
            this.getRemotePanel(),
            this.getImportOptionsPanel(),
            this.getDefinitionPanel()],

            nextIsAllowed: (function() {
                var credentialsCheck = false;

                if (this.typeCombo.getValue() == 'remote_caldav') {
                    if (this.remoteUsername.getValue() != '' && this.remotePassword.getValue() != '') {
                        credentialsCheck = true;
                    }
                } else if (this.typeCombo.getValue() == 'remote_ics') {
                    credentialsCheck = true;
                }

                return (
                    ((this.typeCombo && (this.typeCombo.getValue() == 'remote_ics' || this.typeCombo.getValue() == 'remote_caldav'))
                    && (this.remoteLocation && this.remoteLocation.getValue())
                    && (this.ttlCombo && (this.ttlCombo.getValue() || this.ttlCombo.getValue() === 0)))
                    && credentialsCheck
                    || ((this.typeCombo && (this.typeCombo.getValue() == 'upload'))
                    && (this.definitionCombo && this.definitionCombo.getValue())
                    && (this.uploadButton && this.uploadButton.upload))
                    && (this.containerCombo && this.containerCombo.getValue())
                );

            }).createDelegate(this)
        };
    },

    getEventOptionsPanel: function () {
        return {
            title: this.app.i18n._('Event Import Options'),
            baseCls: 'ux-subformpanel',
            border: false,
            layout: 'fit',
            items: [{
                xtype: 'form',
                // frame: true,
                border: false,
                ref: '../eventOptionsForm',
                labelAlign: 'top',
                bodyStyle: 'padding: 5px;',
                defaults: { anchor: '100%' },
                items: [{
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Update Existing Events'),
                    boxLabel: this.app.i18n._('Update already existing events'),
                    name: 'updateExisting'
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Force Update for Existing Events'),
                    boxLabel: this.app.i18n._("Update exiting events even if imported sequence number isn't higher"),
                    name: 'forceUpdateExisting'
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Delete Missing Events'),
                    boxLabel: this.app.i18n._('Delete events missing from the import data (future events only).'),
                    name: 'deleteMissing'
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Match Attendee'),
                    boxLabel: this.app.i18n._('Match attendee with existing users or contacts.'),
                    name: 'matchAttendees',
                    checked: true
                }/*, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Basic Data Only'),
                    boxLabel: this.app.i18n._('Import only basic data (i.e., without attendees, alarms, UID, ...).'),
                    name: 'onlyBasicData'
                }*/, new Tine.Calendar.AttendeeGridPanel({
                    height: 200,
                    name: 'attendee',
                    initComponent: function() {
                        Tine.Calendar.AttendeeGridPanel.prototype.initComponent.call(this);
                        this.onRecordLoad(new Tine.Calendar.Model.Event({
                            editGrant: true
                        }));
                    },
                }), {
                    xtype: 'radiogroup',
                    name: 'attendeeStrategy',
                    columns: 2,
                    items: [{
                        inputValue: 'add',
                        name: 'attendeeStrategy',
                        boxLabel: this.app.i18n._('Add Attendee to Import Data'),
                        checked: true
                    }, {
                        inputValue: 'replace',
                        name: 'attendeeStrategy',
                        boxLabel: this.app.i18n._('Ignore Import Data and Replace Attendee'),
                    }]
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Keep Existing Attendee'),
                    boxLabel: this.app.i18n._('Do not remove attendees from existing events that are not in the import data.'),
                    name: 'keepExistingAttendee'
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Match Organizer'),
                    boxLabel: this.app.i18n._('Match organizer with existing user or contact.'),
                    name: 'matchOrganizer',
                    checked: true
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Skip from other internal organizers') +
                        Tine.widgets.form.FieldManager.getDescriptionHTML(this.app.i18n._('Use this when events of other internal organizers are already present in this installation or get imported with an other job.')),
                    boxLabel: this.app.i18n._('Skip Events if organizer matched an other internal user than the import user.'),
                    name: 'skipInternalOtherOrganizer',
                    checked: false
                }, {
                    xtype: 'checkbox',
                    fieldLabel: this.app.i18n._('Disable external organizer calendar'),
                    boxLabel: this.app.i18n._('Import events of external organizers directly into the import calendar.'),
                    name: 'disableExternalOrganizerCalendar',
                    checked: false
                }, {
                    xtype: 'calendar-event-organizer-combo',
                    fieldLabel: this.app.i18n._('Force Organizer for new Events'),
                    name: 'overwriteOrganizer',
                    onOrganizerSelect: function(data) {
                        this.organizerData = data;
                    },
                    getValue: function() {
                        return this.organizerData;
                    }
                }]
            }],
            onFinishButton: (function() {
                if (! this.importMask) {
                    this.importMask = new Ext.LoadMask(this.getEl(), {msg: String.format(i18n._('Importing {0}'), this.recordClass.getRecordsName())});
                }
                this.importMask.show();

                // collect client data
                var clientRecordData = [];
                var importOptions = {};

                this.doImport(function(request, success, response) {
                    this.importMask.hide();

                    this.fireEvent('finish', this, this.layout.activeItem);

                    if (Ext.isArray(response?.exceptions) && response.exceptions.length > 0) {
                        this.backButton.setDisabled(true);
                        this.finishButton.setHandler(function() {this.window.close()}, this);
                    } else {
                        this.window.close();
                    }
                }, importOptions, clientRecordData);
            }).createDelegate(this)
        }
    }
});

/**
 * Create new import window
 */
Tine.Calendar.ImportDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 600,
        name: Tine.Calendar.ImportDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Calendar.ImportDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
