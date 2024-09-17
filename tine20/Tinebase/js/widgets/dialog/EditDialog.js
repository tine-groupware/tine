/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.dialog');

/**
 * Generic 'Edit Record' dialog
 * Base class for all 'Edit Record' dialogs
 *
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.EditDialog
 * @extends     Ext.FormPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */

import { getLocalizedLangPicker } from '../form/LocalizedLangPicker'

Tine.widgets.dialog.EditDialog = Ext.extend(Ext.FormPanel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {String} mode (remote|local|load(remote):save(local)
     * Set to 'local' if the EditDialog only operates on this.record (defaults to 'remote' which loads and saves using the recordProxy)
     */
    mode: 'remote',
    /**
     * @cfg {Array} tbarItems
     * additional toolbar items (defaults to false)
     */
    tbarItems: false,
    /**
     * internal/untranslated app name (required)
     *
     * @cfg {String} appName
     */
    appName: null,
    /**
     * the modelName (filled by application starter)
     *
     * @type {String} modelName
     */
    modelName: null,

    /**
     * record definition class  (required if no modelconfig! don't declare for modelconfig!!)
     *
     * @cfg {Ext.data.Record} recordClass
     */
    recordClass: null,
    /**
     * @cfg {Ext.data.DataProxy} recordProxy
     */
    recordProxy: null,
    /**
     * @cfg {Bool} showContainerSelector
     * show container selector in bottom area
     */
    showContainerSelector: null,
    /**
     * @cfg {Bool} evalGrants
     * should grants of a grant-aware records be evaluated (defaults to true)
     */
    evalGrants: true,
    /**
     * @cfg {String} requiredSaveGrant
     * required grant for apply/save
     */
    requiredSaveGrant: 'editGrant',

    /**
     * @cfg {Ext.data.Record} record
     * record in edit process.
     */
    record: null,

    /**
     * holds the modelConfig for the handled record (json-encoded object)
     * will be decoded in initComponent
     *
     * @type
     */
    modelConfig: null,

    /**
     * @cfg {String} saveAndCloseButtonText
     * text of save and close button
     */
    saveAndCloseButtonText: '',
    /**
     * @cfg {Function} saveAndCloseActionUpdater
     * overwritable action updater
     */
    saveAndCloseActionUpdater: null,
    /**
     * @cfg {String} cancelButtonText
     * text of cancel button
     */
    cancelButtonText: '',

    /**
     * @cfg {Boolean} copyRecord
     * copy record
     */
    copyRecord: false,

    /**
     * @cfg {Boolean} doDuplicateCheck
     * do duplicate check when saving record (mode remote only)
     */
    doDuplicateCheck: true,

    /**
     * when a record has the relations-property the relations-panel can be disabled here
     * @cfg {Boolean} hideRelationsPanel
     */
    hideRelationsPanel: false,

    /**
     * when a record has the attachments-property the attachments-panel can be disabled here
     * @cfg {Boolean} hideAttachmentsPanel
     */
    hideAttachmentsPanel: false,

    /**
     * Registry for other relationgridpanels than the generic one,
     * handling special types of relations the generic one will not.
     * Panels registered here must have a store with the relation records.
     *
     * @type {Array}
     */
    relationPanelRegistry: null,

    /**
     * ignore relations to given php-class names in the relation grid
     * @type {Array}
     */
    ignoreRelatedModels: null,

    /**
     * dialog is currently saving data
     * @type Boolean
     */
    saving: false,

    /**
     * Disable adding cf tab even if model has support for customfields
     * @type Boolean
     */
    disableCfs: false,

    /**
     * check for unsaved changes before closing
     * @type Boolean
     */
    checkUnsavedChanges: true,

    /**
     * @property window {Ext.Window|Ext.ux.PopupWindow|Ext.Air.Window}
     */
    /**
     * @property {Number} loadRequest
     * transaction id of loadData request
     */
    /**
     * @property loadMask {Ext.LoadMask}
     */

    /**
     * @property containerSelectCombo {Tine.widgets.container.SelectionComboBox}
     */
    containerSelectCombo: null,

    /**
     * If set, these fields are readOnly (when called dependent to related record)
     *
     * @type {Ext.util.MixedCollection}
     */
    fixedFields: null,

    /**
     * Plain Object with additional configuration (JSON-encoded)
     *
     * @type {Object}
     */
    additionalConfig: null,

    /**
     * Assuming mode is not local, but the dialog is supposed to treat this.record as a json string and keep those data
     */
    recordFromJson: false,

    /**
     * canonical name
     * @cfg {String} canonicalName
     */
    canonicalName: 'EditDialog',

    descriptionFieldName: 'description',

    // private
    bodyStyle:'padding:5px',
    layout: 'fit',
    border: false,
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    deferredRender: false,
    buttonAlign: null,
    bufferResize: 500,

    /**
     * relations panel
     *
     * @type Tine.widgets.relation.GenericPickerGridPanel
     */
    relationsPanel: null,

    // Array of Relation Pickers
    relationPickers: null,

    /**
     * attachments panel
     *
     * @type Tine.widgets.dialog.AttachmentsGridPanel
     */
    attachmentsPanel: null,

    /**
     * holds the loadMask
     * set this to false, if no loadMask should be shown
     *
     * @type {Ext.LoadMask}
     */
    loadMask: null,

    /**
     * hook notes panel into dialog
     */
    displayNotes: false,

    useMultiple: false,

    inheritableStatics: {
        /**
         * register checkState provider
         * @param {String} field
         * @param {Funciton} fn check state function
         */
        registerCheckStateProvider: function(field, fn) {
            this.statics.checkStateProviders = this.statics.checkStateProviders || {}
            this.statics.checkStateProviders[field] = this.statics.checkStateProviders[field] || []
            this.statics.checkStateProviders[field].push(fn)
        },
        getCheckStateProviders(field) {
            return _.get(this, `statics.checkStateProviders.${field}`, []);
        }
    },

    //private
    initComponent: function() {
        this.relationPanelRegistry = this.relationPanelRegistry ? this.relationPanelRegistry : [];
        this.addEvents(
            /**
             * @event cancel
             * Fired when user pressed cancel button
             */
            'cancel',
            /**
             * @event saveAndClose
             * Fired when user pressed OK button
             */
            'saveAndClose',
            /**
             * @event update
             * @desc  Fired when the record got updated
             * @param {Json String} data data of the entry
             * @pram  {String} this.mode
             */
            'update',
            /**
             * @event apply
             * Fired when user pressed apply button
             */
            'apply',
            /**
             * @event load
             * @param {Tine.widgets.dialog.EditDialog} this
             * @param {Tine.data.Record} record which got loaded
             * @param {Function} ticket function for async defer
             * Fired when record is loaded
             */
            'load',
            /**
             * @event save
             * @param {Tine.widgets.dialog.EditDialog} this
             * @param {Tine.data.Record} record which got loaded
             * @param {Function} ticket function for async defer
             * Fired when remote record is saving
             */
            'save',
            /**
             * @event updateDependent
             * Fired when a subpanel updates the record locally
             */
            'updateDependent',
            /**
             * @event change
             * Fires just before the field blurs if the field value has changed.
             * @param {Ext.form.Field} this
             * @param {Mixed} newValue The new value
             * @param {Mixed} oldValue The original value
             */
            'change'
        );

        if (Ext.isString(this.modelConfig)) {
            this.modelConfig = Ext.decode(this.modelConfig);
        }

        if (Ext.isString(this.additionalConfig)) {
            Ext.apply(this, Ext.decode(this.additionalConfig));
        }

        var fixedFieldsData = Ext.isString(this.fixedFields) ? Ext.decode(this.fixedFields) : this.fixedFields || {};
        this.fixedFields = new Ext.util.MixedCollection();
        this.fixedFields.addAll(fixedFieldsData);

        if (! this.recordClass && this.modelName) {
            this.recordClass = Tine[this.appName].Model[this.modelName];
        }

        if (this.recordClass) {
            this.appName    = this.appName    ? this.appName    : this.recordClass.getMeta('appName');
            this.modelName  = this.modelName  ? this.modelName  : this.recordClass.getMeta('modelName');
        }

        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.appName);
        }

        if (! this.windowNamePrefix) {
            this.windowNamePrefix = this.modelName + 'EditWindow_';
        }

        Tine.log.debug('initComponent: appName: ', this.appName);
        Tine.log.debug('initComponent: modelName: ', this.modelName);
        Tine.log.debug('initComponent: app: ', this.app);

        // init some translations
        if (this.app.i18n && this.recordClass !== null) {
            this.i18nRecordName = this.app.i18n.n_hidden(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1);
            this.i18nRecordsName = this.app.i18n._hidden(this.recordClass.getMeta('recordsName'));
        }

        // auto record proxy
        if (! this.recordProxy && this.recordClass) {
            Tine.log.debug('no record proxy given, creating a new one...');
            this.recordProxy = new Tine.Tinebase.data.RecordProxy({
                recordClass: this.recordClass
            });
        }

        // auto eval grants
        if (this.recordClass) {
            var grantsField = this.recordClass.getMeta('grantsPath')
                .replace(/^data\./, '')
                .replace(/\..+/g, '');

            this.evalGrants = this.evalGrants && (grantsField == 'data' || this.recordClass.hasField(grantsField));
        }

        // init plugins
        this.plugins = Ext.isString(this.plugins) ? Ext.decode(this.plugins) : Ext.isArray(this.plugins) ? this.plugins.concat(Ext.decode(this.initialConfig.plugins)) : [];

        this.plugins.push(this.tokenModePlugin = new Tine.widgets.dialog.TokenModeEditDialogPlugin({}));
        // added possibility to disable using customfield plugin
        if (this.disableCfs !== true) {
            const cfPlugin = new Tine.widgets.customfields.EditDialogPlugin({});
            this.plugins.push(cfPlugin);
            this.initPlugin(cfPlugin);

        }
        Ext.ux.pluginRegistry.addRegisteredPlugins(this);

        // multiEditPlugin need to load before record is initialised
        const multiEditPlugin = _.find(this.plugins, { ptype: "multiple_edit_dialog"});
        if (multiEditPlugin) {
            this.initPlugin(multiEditPlugin);
        }

        if (this.recordClass) {
            this.fieldManager = _.bind(Tine.widgets.form.FieldManager.get,
                Tine.widgets.form.FieldManager, this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), _,
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
        }

        // init actions
        this.initActions();
        // init buttons and tbar
        this.initButtons();
        // init record
        this.initRecord();
        // init container selector
        this.initContainerSelector();
        // get items for this dialog
        this.items = this.getFormItems();

        // init relations panel if relations are defined
        this.initRelationsPanel();
        // init attachments panel
        this.initAttachmentsPanel();
        // init notes panel
        this.initNotesPanel();
        this.initGrantsPanel();


        // apply generic tab sorting
        if (this.items.xtype == 'tabpanel') {
            this.items.plugins = this.items.plugins || [];
            this.items.plugins.push({
                ptype : 'ux.tabpanelsortplugin',
                enableDD: false
            });
            this.items.items[0].pos = 10;
        }

        Tine.widgets.dialog.EditDialog.superclass.initComponent.call(this);

        // set fields readOnly if set
        this.fixFields();

        // firefox fix: blur each item before tab changes, so no field  will be focused afterwards
        if (Ext.isGecko) {
            this.items.items[0].addListener('beforetabchange', function(tabpanel, newtab, oldtab) {
                if (! oldtab) {
                    return;
                }
                var form = this.getForm();

                if (form && form.hasOwnProperty('items'))
                    form.items.each(function(item, index) {
                        item.blur();
                    });
            }, this);
        }

        if (Ext.isFunction(this.window.relayEvents) && Tine.Tinebase.featureEnabled('featureRememberPopupSize')) {
            this.window.relayEvents(this, ['resize']);
        }

        if (this.readOnly) {
            this.setReadOnly(this.readOnly);
        }
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        if (this.recordClass) {
            return ['',
                this.recordClass.getMeta('appName'),
                this.canonicalName,
                this.recordClass.getMeta('modelName'),
            ].join(Tine.Tinebase.CanonicalPath.separator);
        }
    },

    /**
     * generic form layout
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }, {
                ptype: 'ux.itemregistry',
                key:   [this.app.appName, this.recordClass.getMeta('modelName'), 'EditDialog-TabPanel'].join('-')
            }],
            items:_.concat([
                {
                    title: this.i18nRecordName,
                    autoScroll: true,
                    border: false,
                    frame: true,
                    layout: 'border',
                    defaults: { autoScroll: true },
                    items: [Ext.applyIf(this.getRecordFormItems(), {
                        region: 'center',
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype:'textfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: 1/2
                        },
                    })].concat(this.getEastPanel())
                }, _.get(this.recordClass.getModelConfiguration(), 'modlogActive') ? this.activitiesTabPanel = new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    getRecordId: () => {return this.record.id },
                    record_model: this.modelName
                }) : []
            ])
        };
    },

    getEastPanel: function() {
        var items = [];
        if (this.recordClass.hasField(this.descriptionFieldName)) {
            const field = Tine.widgets.form.FieldManager.get(this.app, this.recordClass, this.descriptionFieldName, 'editDialog');

            items.push(new Ext.Panel({
                title: field.fieldLabel, //i18n._('Description'),
                iconCls: 'descriptionIcon',
                layout: 'form',
                labelAlign: 'top',
                border: false,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-editDialog-eastPanel'
                }],
                items: [Object.assign({
                    style: 'margin-top: -4px; border 0px;',
                    labelSeparator: '',
                    xtype: 'textarea',
                    name: this.descriptionFieldName,
                    hideLabel: true,
                    grow: false,
                    preventScrollbars: false,
                    anchor: '100% 100%',
                    emptyText: i18n._('Enter description'),
                    requiredGrant: 'editGrant'
                }, field)]
            }));
        }

        if (this.recordClass.hasField('tags')) {
            items.push(new Tine.widgets.tags.TagPanel({
                app: this.appName,
                border: false,
                bodyStyle: 'border:1px solid #B5B8C8;'
            }));
        }

        return items.length ? {
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
            items: items
        } : [];
    },

    getRecordFormItems: function() {
        return new Tine.widgets.form.RecordForm({
            recordClass: this.recordClass,
            editDialog: this
        });
    },

    /**
     * allows to fetch record form fields
     *
     * @param name
     * @returns {*}
     */
    getRecordFormField: function(name) {
        return this.recordForm['formfield_' + name];
    },

    /**
     * fix fields (used for preselecting form fields when called in dependency to another record)
     * @return {Boolean}
     */
    fixFields: function() {
        if (this.fixedFields && this.fixedFields.getCount() > 0) {
            if (! this.rendered) {
                this.fixFields.defer(100, this);
                return false;
            }

            this.fixedFields.each(function(value, index) {
                var key = this.fixedFields.keys[index];

                var field = this.getForm().findField(key);

                if (field) {
                    if (value !== '###CURRENT###') {
                        if (Ext.isFunction(this.recordClass.getField(key).type)) {
                            var foreignRecordClass = this.recordClass.getField(key).type;
                            var record = new foreignRecordClass(value);
                            field.selectedRecord = record;
                            field.setValue(value, this.record);
                            field.fireEvent('select');
                        } else {
                            field.setValue(value, this.record);
                        }
                    }
                    field.disable();
                }
            }, this);
        }
    },

    /**
     * call checkState for every field
     */
    checkStates: function() {
        if (this.isDestroyed || !this.record) return;

        if(this.loadRequest){
            return _.delay(_.bind(this.checkStates, this), 250);
        }
        this.onRecordUpdate();
        this.getForm().items.each(function (item) {
            if (Ext.isFunction(item.checkState)) {
                item.checkState(this, this.record);
            }
            if (item.name) {
                _.each(this.constructor.getCheckStateProviders(item.name), (fn) => {
                    fn.call(item, this, this.record);
                });
            }
        }, this)
    },

    /**
     * Get available model for given application
     *
     *  @param {Mixed} application
     *  @param {Boolean} customFieldModel
     */
    getApplicationModels: function (application, customFieldModel) {
        var models      = [],
            useModel,
            appName     = Ext.isString(application) ? application : application.get('name'),
            app         = Tine.Tinebase.appMgr.get(appName),
            trans       = app && app.i18n ? app.i18n : i18n,
            appModels   = Tine[appName].Model;

        if (appModels) {
            for (var model in appModels) {
                if (appModels.hasOwnProperty(model) && typeof appModels[model].getMeta === 'function') {
                    if (customFieldModel && appModels[model].getField('customfields')) {
                        useModel = appModels[model].getMeta('appName') + '_Model_' + appModels[model].getMeta('modelName');

                        Tine.log.info('Found model with customfields property: ' + useModel);
                        models.push([useModel, trans.n_(appModels[model].getMeta('recordName'), appModels[model].getMeta('recordsName'), 1)]);
                    } else if (! customFieldModel) {
                        useModel = 'Tine.' + appModels[model].getMeta('appName') + '.Model.' + appModels[model].getMeta('modelName');

                        Tine.log.info('Found model: ' + useModel);
                        models.push([useModel, trans.n_(appModels[model].getMeta('recordName'), appModels[model].getMeta('recordsName'), 1)]);
                    }
                }
            }
        }
        return models;
    },

    /**
     * init actions
     */
    initActions: function() {
        this.action_saveAndClose = new Ext.Action({
            hidden: this.readOnly,
            requiredGrant: this.requiredSaveGrant,
            text: (this.saveAndCloseButtonText != '') ? this.app.i18n._(this.saveAndCloseButtonText) : i18n._('Ok'),
            minWidth: 70,
            ref: '../btnSaveAndClose',
            scope: this,
            actionUpdater: this.saveAndCloseActionUpdater,
            // TODO: remove the defer when all subpanels use the deferByTicket mechanism
            handler: function() { this.onSaveAndClose.defer(500, this); },
            iconCls: 'action_saveAndClose'
        });

        this.action_applyChanges = new Ext.Action({
            hidden: this.readOnly,
            requiredGrant: this.requiredSaveGrant,
            text: i18n._('Apply'),
            minWidth: 70,
            ref: '../btnApplyChanges',
            scope: this,
            handler: this.onApplyChanges,
            iconCls: 'action_applyChanges'
        });

        this.action_cancel = new Ext.Action({
            text: (this.cancelButtonText != '') ? this.app.i18n._(this.cancelButtonText) :
                (this.readOnly ? i18n._('Close') : i18n._('Cancel')),
            minWidth: 70,
            scope: this,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        });

        this.action_delete = new Ext.Action({
            requiredGrant: 'deleteGrant',
            text: i18n._('delete'),
            minWidth: 70,
            scope: this,
            handler: this.onDelete,
            iconCls: 'action_delete',
            disabled: true
        });

        this.action_export = Tine.widgets.exportAction.getExportButton(this.recordClass, {
            getExportOptions: this.getExportOptions.createDelegate(this)
        }, Tine.widgets.exportAction.SCOPE_SINGLE);

        if (this.enablePrinting) {
            this.action_print = new Ext.Action({
                requiredGrant: 'readGrant',
                text: String.format(i18n._('Print {0}'), this.recordClass.getRecordName()),
                handler: this.onPrint,
                iconCls: 'action_print',
                disabled: false,
                scope: this
            });
        }

        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: this.recordClass,
            evalGrants: this.evalGrants
        });

        this.actionUpdater.addActions([
            this.action_saveAndClose,
            this.action_applyChanges,
            this.action_cancel,
            this.action_delete
        ]);

        if (this.recordClass && !this.localizedLangPicker) {
            this.localizedLangPicker = getLocalizedLangPicker(this.recordClass);
            if (this.localizedLangPicker) {
                this.tbarItems = this.tbarItems || [];
                if (this.tbarItems.indexOf('->') <= 0) {
                    this.tbarItems.push('->');
                }
                this.tbarItems.push(this.localizedLangPicker);
            }
        }
    },

    /**
     * get export options/data
     */
    getExportOptions: function() {
        this.onRecordUpdate();
        return {
            recordData: this.record.data
        };
    },

    /**
     * init buttons
     *
     * use button order from preference
     */
    initButtons: function () {
        this.fbar = [
            '->'
        ];

        this.fbar.push(this.action_cancel, this.action_saveAndClose);

        this.tbar = new Ext.Toolbar({
            items: this.tbarItems || [],
            plugins: [].concat(this.recordClass ? [{
                ptype: 'ux.itemregistry',
                key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-editDialog-Toolbar'
            }] : [])
        });

        const btnStyle = _.find(this.tbar.items.items, {scale: 'medium'}) ? { scale: 'medium', rowspan: 2, iconAlign: 'top'} : {}
        if (this.action_print) {
            this.tbar.add(Ext.apply(new Ext.Button(this.action_print), btnStyle));
        }

        if (this.action_export) {
            this.tbar.add(Ext.apply(new Ext.Button(this.action_export), btnStyle));
        }

        this.actionUpdater.addActions(this.tbar.items);
    },

    /**
     * init container selector
     */
    initContainerSelector: function() {
        if (this.showContainerSelector) {
            this.containerSelectCombo = new Tine.widgets.container.SelectionComboBox({
                id: this.app.appName + 'EditDialogContainerSelector-' + Ext.id(),
                fieldLabel: i18n._('Saved in'),
                width: 300,
                listWidth: 300,
                name: this.recordClass.getMeta('containerProperty'),
                recordClass: this.recordClass,
                containerName: this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1),
                containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
                appName: this.app.appName,
                // required grant to change container
                requiredGrant: this.evalGrants ? 'deleteGrant' : false,
                // required grants for the container to choose
                requiredGrants: [this.evalGrants ? 'addGrant' : false],
                disabled: this.isContainerSelectorDisabled(),
                listeners: {
                    scope: this,
                    select: function() {
                        if ( this.saveAndCloseActionUpdater ) return;
                        // enable or disable save button dependent to containers account grants
                        // on edit: check editGrant, on add: check addGrant
                        var grants = this.containerSelectCombo.selectedContainer
                            ? this.containerSelectCombo.selectedContainer.account_grants : {},
                            grantToCheck = (this.record.data.id) ? 'editGrant' : 'addGrant',
                            enabled =  grants.hasOwnProperty(grantToCheck) && grants[grantToCheck]
                                    || grants.hasOwnProperty('adminGrant') && grants.adminGrant ? true : false;

                        this.action_saveAndClose.setDisabled(! enabled);
                    }
                }
            });
            this.on('render', function() { this.getForm().add(this.containerSelectCombo); }, this);

            this.fbar = [
                i18n._('Saved in'),
                this.containerSelectCombo
            ].concat(this.fbar);
        }
    },

    /**
     * checks if the container selector should be disabled (dependent on account grants of the container itself)
     * @return {}
     */
    isContainerSelectorDisabled: function() {
        if (this.record) {
            var cp = this.recordClass.getMeta('containerProperty'),
                container = this.record.data[cp],
                grants = (container && container.hasOwnProperty('account_grants')) ? container.account_grants : null,
                cond = false;

            // check grants if record already exists and grants should be evaluated
            if(this.evalGrants && this.record.data.id && grants) {
                cond = ! (grants.hasOwnProperty('editGrant') && grants.editGrant);
            }

            return cond;
        } else {
            return false;
        }
    },

    /**
     * init record to edit
     */
    initRecord: function() {
        Tine.log.debug('init record with mode: ' + this.mode);
        if (! this.record) {
            Tine.log.debug('creating new default data record');
            this.record = new this.recordClass(this.recordClass.getDefaultData(), 0);
        }

        if (!Ext.isFunction(this.record.beginEdit)) {
            this.record = this.recordProxy.recordReader({responseText: this.record});
        }

        // Mode local means, that the record is not supposed to be loaded from server and saved to server.
        // So if mode !== local, the record would be always loaded from the server and discard all data passed as record before
        // To bypass this you can set recordFromJson === true, then the dialog wouldn't load the record from server!
        // But to make this work you need to pass a json encoded record to the editdialog as string!
        // NOTE: we only load records with id from remote, new records (id === 0/null/undefined) are local
        if (!this.record.phantom && (this.mode !== 'local' || this.mode.match(/load\(remote\)/)) && this.recordFromJson !== true && [null, undefined, 0, '0'].indexOf(this.record?.id) < 0) {
            this.initialRecord = this.record;
            this.loadRemoteRecord();
        } else {
            this.onRecordLoad.defer(10, this);
        }
    },

    /**
     * load record via record proxy
     */
    loadRemoteRecord: function() {
        Tine.log.info('initiating record load via proxy');
        this.loadRequest = this.recordProxy.loadRecord(this.record, {
            scope: this,
            success: function(record) {
                this.record = record;
                // apply modifications from initial Record
                _.forEach(this.initialRecord?.modified, (val, field) => {
                    this.record.set(field, this.initialRecord.get(field));
                });
                this.onRecordLoad();
            }
        });
    },

    /**
     * copy this.record record
     */
    doCopyRecord: function() {
        this.record = this.doCopyRecordToReturn(this.record);

        var _ = window.lodash,
            hasRequiredGrant = _.get(this.record, this.recordClass.getMeta('grantsPath') + '.addGrant');

        // unset container if user is not allowed to add record in original container
        if(this.evalGrants && ! hasRequiredGrant) {
            _.unset(this.record, 'data.' + this.recordClass.getMeta('containerProperty'));
            _.set(this.record, this.recordClass.getMeta('grantsPath') + '.deleteGrant', true);
            _.set(this.record, this.recordClass.getMeta('grantsPath') + '.addGrant', true);
            _.set(this.record, this.recordClass.getMeta('grantsPath') + '.editGrant', true);
        }
    },

    /**
     * Copy record and returns "new record with same settings"
     *
     * @param {Tine.Tinebase.data.Record} record
     */
    doCopyRecordToReturn: function(record) {
        var recordData = this.getCopyRecordData(record, this.recordClass, this.omitCopyTitle);
        return new this.recordClass(recordData, Tine.Tinebase.data.Record.generateUID());
    },

    /**
     * get data to copy record
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param recordClass
     * @param {Boolean} omitCopyTitle
     */
    getCopyRecordData: function (record, recordClass, omitCopyTitle) {
        var _ = window.lodash,
            titleProperty = recordClass.getMeta('titleProperty'),
            omitFields = _.concat(recordClass.getMeta('copyOmitFields') || [],
                // 2017-12-21 - cweiss - why where attachments omitted? if you know please document here!
                ['id', 'notes' /*, 'attachments'*/]),
            fields = recordClass.getFieldNames(),
            fieldsToCopy = fields.diff(omitFields),
            recordData = Ext.copyTo({__meta: { phantom: true }}, record.data, fieldsToCopy),
            resetProperties = {
                alarms:    ['id', 'record_id', 'sent_time', 'sent_message'],
                relations: ['id', 'own_id', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time']
            },
            setProperties = {alarms: {sent_status: 'pending'}};

        Ext.iterate(resetProperties, function(property, properties) {
            if (recordData.hasOwnProperty(property)) {
                var r = recordData[property];
                if (!r) return;
                for (var index = 0; index < r.length; index++) {
                    Ext.each(properties,
                        function(prop) {
                            r[index][prop] = prop == 'id' ?  Tine.Tinebase.data.Record.generateUID() : null;
                        }
                    );
                }
            }
        });

        Ext.iterate(setProperties, function(property, properties) {
            if (recordData.hasOwnProperty(property)) {
                var r = recordData[property];
                if (!r) return;
                for (var index = 0; index < r.length; index++) {
                    Ext.iterate(properties,
                        function(prop, value) {
                            r[index][prop] = value;
                        }
                    );
                }
            }
        });
        if (! omitCopyTitle && !(recordClass.getMeta('copyNoAppendTitle'))) {
            recordData[titleProperty] = String.format(i18n._('{0} (copy)'), recordData[titleProperty]);
        }

        return recordData;
    },

    /**
     * helper to load record (from extern)
     *
     * @param record
     * @return {Promise<unknown>}
     */
    async loadRecord(record, supressMessageBus) {
        return new Promise((resolve) => {
            this.on('load', resolve, this, {single: true, buffer: 200});
            if (record === 'remote') {
                return this.loadRemoteRecord()
            }
            this.record = record;
            if (!supressMessageBus) {
                window.postal.publish({
                    channel: "recordchange",
                    topic: [this.app.appName, this.recordClass, 'update'].join('.'),
                    data: {... this.record.data}
                });
            }
            this.onRecordLoad();
        });
    },
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        var _ = window.lodash;

        Tine.log.debug('Tine.widgets.dialog.EditDialog::onRecordLoad() - Loading of the following record completed:');
        Tine.log.debug(this.record);


        if (!this.record.id || this.recordClass.getMeta('containerProperty') && !_.get(this.record, 'data.' + this.recordClass.getMeta('containerProperty'), false)) {
            _.set(this.record, this.recordClass.getMeta('grantsPath') + '.deleteGrant', true);
            _.set(this.record, this.recordClass.getMeta('grantsPath') + '.addGrant', true);
            _.set(this.record, this.recordClass.getMeta('grantsPath') + '.editGrant', true);
        }

        this.fixedFields.eachKey(function(field, value) {
            if (value !== '###CURRENT###') {
                this.record.set(field, this.recordClass.getField(field) ? this.recordClass.getField(field).convert(value) : value);
            }
        }, this);

        if (this.copyRecord) {
            this.doCopyRecord();
            this.window.setTitle(String.format(i18n._('Copy {0}'), this.i18nRecordName));
        } else {
            if (this.isNewRecord()) {
                this.window.setTitle(formatMessage('{gender, select, male {Add New {recordName}} female {Add New {recordName}} other {Add New {recordName}}}', {
                    gender: this.recordClass.getRecordGender(),
                    recordName: this.i18nRecordName
                }));
            } else {
                (async () => {
                    let title = this.record.getTitle();
                    if (title && title.asString) {
                        title = await title.asString();
                    }
                    this.window.setTitle(String.format(i18n._('Edit {0} "{1}"'), this.i18nRecordName, title));
                })();

                if (! this.el.findParent('.x-window')) {
                    if (_.get(Tine.Tinebase.router.routes, `${this.appName}.${this.recordClass.getMeta('recordName')}`)) {
                        Tine.Tinebase.router.setRoute(`${this.appName}/${this.recordClass.getMeta('recordName')}/${this.record.get(this.recordClass.getMeta('idProperty'))}`);
                    }
                }
            }
        }

        var ticketFn = this.onAfterRecordLoad.deferByTickets(this),
            wrapTicket = ticketFn();

        this.fireEvent('load', this, this.record, ticketFn);
        wrapTicket();
    },

    /**
     * overwrite if record id is already set on new records
     * @returns {boolean}
     */
    isNewRecord: function () {
        return !this.record || !(this.record.get && this.record.id )
    },

    setReadOnly: function(readOnly) {
        this.readOnly = true;
        if (! this.saveAndCloseActionUpdater) {
            this.action_saveAndClose.setHidden(readOnly);
        }
        if (! this.cancelButtonText) {
            this.action_cancel.setText(readOnly ? i18n._('Close') : i18n._('Cancel'));
        }
        this.onAfterRecordLoad();
    },

    // finally load the record into the form
    onAfterRecordLoad: function() {
        var _ = window.lodash,
            form = this.getForm();

        if (form) {
            form.loadRecord(this.record);
            form.isValid();
        }

        if (this.record && this.record.hasOwnProperty('data') && Ext.isObject(this.record.data[this.recordClass.getMeta('containerProperty')])) {
            this.updateToolbars(this.record, this.recordClass.getMeta('containerProperty'));
        }

        this.actionUpdater.updateActions([this.record]);

        // add current timestamp as id, if this is a dependent record
        if (this.modelConfig && this.modelConfig.isDependent == true && this.record.id == 0) {
            this.record.setId(Tine.Tinebase.data.Record.generateUID());
            // this.record.set('id', (new Date()).getTime());
        }

        // apply grants to fields with requiredGrant prop
        if (this.evalGrants || this.readOnly) {
            this.getForm().items.each(function (f) {
                if (f.initialConfig.disabled) return;
                const recordGrants = _.get(this.record, this.recordClass.getMeta('grantsPath'));
                let hasRequiredGrants = !this.readOnly && true;

                const requiredGrants = _.get(this.modelConfig, `fields[${f.fieldName}].requiredGrants`);
                if (requiredGrants) {
                    hasRequiredGrants = hasRequiredGrants && ( recordGrants.adminGrant || requiredGrants?.some((requiredGrant) => { return recordGrants[requiredGrant] }));
                }

                // NOTE: requiredGrant is UI only property
                if (f.isFormField && f.requiredGrant !== undefined) {
                    const grantsPath = this.recordClass.getMeta('grantsPath');
                    hasRequiredGrants = hasRequiredGrants &&
                        (_.get(this.record, `${grantsPath}.${f.requiredGrant}`) || _.get(this.record, `${grantsPath}.adminGrant`));
                }

                f.setDisabled(!hasRequiredGrants);
            }, this);
        }

        this.loadRequest = null;

        (function() {
            this.checkStates();
            // otherwise we would get modifications in a normal roundtrip record->form->record
            this.record.commit();
        }).defer(100, this);

        this.hideLoadMask();
    },

    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function(callback, scope) {
        var form = this.getForm();

        // merge changes from form into record
        form.updateRecord(this.record);

        this.actionUpdater.updateActions([this.record]);

        //TODO Use Promises instead of Tickets if async is needed
        this.fireEvent('recordUpdate', this, this.record);
    },

    /**
     * @private
     */
    onRender : function(ct, position){
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);

        // generalized keybord map for edit dlgs
        new Ext.KeyMap(this.el, [
            {
                key: [10,13], // ctrl + return
                ctrl: true,
                scope: this,
                fn: function() {
                    if (this.getForm().hasOwnProperty('items')) {
                        // force set last selected field
                        this.getForm().items.each(function(item) {
                            if (item.hasFocus) {
                                item.onBlur();
                            }
                        }, this);
                    }
                    this.action_saveAndClose.execute();
                }
            }
        ]);

        if (this.focusField) {
            const focusField = this.getForm().findField(this.focusField);
            if (focusField) {
                focusField.focus(true, 500);
            }
        }
        this.showLoadMask();

        // init change event
        this.getForm().items.each(function(item) {
            this.relayEvents(item, ['change', 'select']);
        }, this);
        this.on('change', this.checkStates, this, {buffer: 100});
        this.on('select', this.checkStates, this, {buffer: 100});
        this.window.on('beforeclose', this.onBeforeClose, this);
    },

    /**
     * update (action updateer) top and bottom toolbars
     */
    updateToolbars: function(record, containerField) {
        if (! this.evalGrants) {
            return;
        }
        this.actionUpdater.updateActions(record);
    },

    /**
     * get top toolbar
     */
    getToolbar: function() {
        return this.getTopToolbar();
    },

    /**
     * is form valid?
     *
     * @return {Boolean}
     */
    isValid: function() {
        var me = this;
        return new Promise(function (fulfill, reject) {
            if (me.getForm().isValid()) {
                if ( me?.attachmentsPanel?.isUploading()) {
                    reject(i18n._('Files are still uploading.'));
                } else {
                    fulfill(true);
                }
            } else {
                reject(me.getValidationErrorMessage())
            }
        });
    },

    /**
     * vaidates on multiple edit
     *
     * @return {Boolean}
     */
    isMultipleValid: function() {
        return true;
    },

    onBeforeClose: function() {
        if (this.checkUnsavedChanges && this.window.confirmLeavSite && this.record) {
            this.checkStates();
            if (_.keys(this.record.getChanges()).length) {
                console.warn('this changes would be lost:');
                console.warn(this.record.getChanges());
                return false;
            }
        }
        this.purgeListeners();
    },
    /**
     * @private
     */
    onCancel : function(force){
        if(force===true || this.fireEvent('beforecancel', this) !== false) {
            this.fireEvent('cancel', this);
            this.window.close(force);
        }
    },

    /**
     * @private
     */
    onSaveAndClose: function() {
        this.fireEvent('saveAndClose');
        this.onApplyChanges(true);
    },

    /**
     * helper function to async force save
     *
     * @return {Promise<record>}
     */
    async applyChanges() {
        return new Promise((resolve, reject) => {
            this.on('update', resolve, this, {single: true, buffer: 10});
            this.on('requestException', (exception) => {
                _.defer(() => {reject(exception)});
                return false;
            }, this, {single: true, buffer: 10});
            this.onApplyChanges(false);
        });
    },

    /**
     * generic apply changes handler
     * @param {Boolean} closeWindow
     */
    onApplyChanges: function(closeWindow) {
        if (this.saving) {
            return;
        }
        this.saving = true;

        this.showLoadMask();

        var ticketFn = this.doApplyChanges.deferByTickets(this, [closeWindow]),
            wrapTicket = ticketFn();

        this.fireEvent('save', this, this.record, ticketFn);
        wrapTicket();
    },

    /**
     * is called from onApplyChanges
     * @param {Boolean} closeWindow
     */
    doApplyChanges: function(closeWindow) {
        // we need to sync record before validating to let (sub) panels have
        // current data of other panels
        this.onRecordUpdate();

        // quit copy mode
        this.copyRecord = false;

        var isValid = this.isValid(),
            vBool = !! isValid,
            me = this;

        if (Ext.isDefined(isValid) && ! Ext.isFunction(isValid.then)) {
            // convert legacy isValid into promise
            isValid = new Promise(function (fulfill, reject) {
                return vBool ? fulfill(true) : reject(me.getValidationErrorMessage());
            });
        }

        isValid.then(function () {
            if (me.mode !== 'local' && !me.mode.match(/save\(local\)/)) {
                me.recordProxy.saveRecord(me.record, {
                    scope: me,
                    success: function (record) {
                        // override record with returned data
                        me.record = record;
                        me.afterIsRendered()
                            .then(me.onRecordLoad.bind(me))
                            .then(async () => {
                                let ticketFn = me.onAfterApplyChanges.deferByTickets(me, [closeWindow]);
                                let wrapTicket = ticketFn();

                                try {
                                    me.fireEvent('update', Ext.util.JSON.encode(me.record.getData()), me.mode, me, ticketFn);
                                } catch(e) {
                                    this.forceClose = true;
                                } finally {
                                    if (me.loadMask) {
                                        await me.hideLoadMask();
                                    }
                                }

                                wrapTicket();
                            });

                    },
                    failure: me.onRequestFailed,
                    timeout: 300000 // 5 minutes
                }, me.getAdditionalSaveParams(me));
            } else {
                me.afterIsRendered().then(function() {
                    const ticketFn = me.onAfterApplyChanges.deferByTickets(me, [closeWindow]);
                    const wrapTicket = ticketFn();
                    const recordData = {...me.record.getData()};

                    // NOTE: update event in local mode should have resolved data (like in remote mode)
                    this.getForm().items.items.forEach((field) => {
                        const cfName = _.get(String(field.name).match(/^customfield_(.+)$/), '[1]');
                        if (me.recordClass.hasField(field.name) || cfName) {
                            const path = cfName ? `customfields[${cfName}]` : field.name;

                            // recordPickers
                            if (field?.selectedRecord?.data) {
                                _.set(recordData, path, field.selectedRecord.data);
                            }
                            // @TODO recordsPickers & pickerGrids?
                        }
                    });
                    me.onRecordLoad();
                    // skip unnecessary server updates
                    if(me.needsUpdateEvent || Object.keys(me.record.getChanges()).length || !me.record.getId()
                        || (me.record.constructor.hasField('creation_time') && !me.record.creation_time)) {
                        me.fireEvent('update', Ext.util.JSON.encode(recordData), me.mode, me, ticketFn);
                    }
                    wrapTicket();
                }.bind(me));

            }
        }, function (message) {
            me.saving = false;
            me.hideLoadMask();
            Ext.MessageBox.alert(i18n._('Errors'), message);
        });
    },

    /**
     * returns additional save params
     *
     * @param {EditDialog} me
     * @returns {{duplicateCheck: boolean}}
     */
    getAdditionalSaveParams: function(me) {
        return {
            duplicateCheck: me.doDuplicateCheck
        };
    },

    onAfterApplyChanges: async function (closeWindow) {
        await this.hideLoadMask();
        this.saving = false;
        
        if (this.window.popup) this.window.rename(this.windowNamePrefix + this.record.id);
        
        if (closeWindow) {
            this.window.fireEvent('saveAndClose');
            if (this.window?.popup && (this.forceClose || !this.window.popup.opener)) {
                this.window.popup.close();
            }
            this.window.close(true);
        }
    },

    /**
     * get validation error message
     *
     * @return {String}
     */
    getValidationErrorMessage: function() {
        return i18n._('Please review the fields marked red. They contain invalid values.');
    },

    /**
     * generic delete handler
     */
    onDelete: function(btn, e) {
        Ext.MessageBox.confirm(i18n._('Confirm'), String.format(i18n._('Do you really want to delete this {0}?'), this.i18nRecordName), function(_button) {
            if(btn == 'yes') {
                var deleteMask = new Ext.LoadMask(this.getEl(), {msg: String.format(i18n._('Deleting {0}'), this.i18nRecordName)});
                deleteMask.show();

                this.recordProxy.deleteRecords(this.record, {
                    scope: this,
                    success: function() {
                        this.window.close();
                    },
                    failure: function () {
                        Ext.MessageBox.alert(i18n._('Failed'), String.format(i18n._('Could not delete {0}.'), this.i18nRecordName));
                        Ext.MessageBox.hide();
                    }
                });
            }
        });
    },

    onPrint: function (printMode) {
        this.onRecordUpdate();
        var renderer = new (this.printer || Ext.ux.Printer.EditDialogRenderer)();
        renderer.print(this);
    },

    /**
     * duplicate(s) found exception handler
     *
     * @param {Object} exception
     */
    onDuplicateException: function(exception) {
        var resolveGridPanel = new Tine.widgets.dialog.DuplicateResolveGridPanel({
            app: this.app,
            store: new Tine.widgets.dialog.DuplicateResolveStore({
                app: this.app,
                recordClass: this.recordClass,
                recordProxy: this.recordProxy,
                data: {
                    clientRecord: exception.clientRecord,
                    duplicates: exception.duplicates
                }
            }),
            fbar: [
                '->',
                this.action_cancel,
                this.action_saveAndClose
            ]
        });

        // intercept save handler
        resolveGridPanel.btnSaveAndClose.setHandler(function(btn, e) {
            var resolveStrategy = resolveGridPanel.store.resolveStrategy;

            // action discard -> close window
            if (resolveStrategy == 'discard') {
                return this.onCancel();
            }

            this.record = resolveGridPanel.store.getResolvedRecord();

            // quit copy mode before populating form with resolved data
            this.copyRecord = false;
            this.onRecordLoad();

            mainCardPanel.layout.setActiveItem(this.id);
            resolveGridPanel.doLayout();

            this.doDuplicateCheck = false;
            this.onSaveAndClose();
        }, this);

        // place in viewport
        this.window.setTitle(String.format(i18n._('Resolve Duplicate {0} Suspicion'), this.i18nRecordName));
        var mainCardPanel = this.findParentBy(function(p) {return p.isWindowMainCardPanel });
        mainCardPanel.add(resolveGridPanel);
        mainCardPanel.layout.setActiveItem(resolveGridPanel.id);
        resolveGridPanel.doLayout();
    },

    /**
     * generic request exception handler
     *
     * @param {Object} exception
     */
    onRequestFailed: function(exception) {
        this.saving = false;
        if(this.fireEvent('requestException', exception) !== false) {

            if (this.exceptionHandlingMap && this.exceptionHandlingMap[exception.code] && typeof this.exceptionHandlingMap[exception.code] === 'function') {
                this.exceptionHandlingMap[exception.code](exception);

            } else if (exception.code == 629) {
                this.onDuplicateException.apply(this, arguments);

            } else if (exception.code === 650) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception, function () {
                    this.onAfterApplyChanges(true);
                }, this);

            } else {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }
        }

        this.hideLoadMask();
    },

    /**
     * creates the relations panel, if relations are defined
     */
    initRelationsPanel: function() {
        if (! this.hideRelationsPanel && this.recordClass && this.recordClass.hasField('relations')) {
            // init relations panel before onRecordLoad
            if (! this.relationsPanel) {
                this.relationsPanel = new Tine.widgets.relation.GenericPickerGridPanel({ anchor: '100% 100%', editDialog: this });
                this.items.items.push(this.relationsPanel);
            }
        }
    },

    /**
     * create notes panel
     */
    initNotesPanel: function() {
        // This dialog is pretty generic but for some cases it's used in a differend way
        if(this.displayNotes == true) {
            this.items.items.push(this.notesGridPanel = new Tine.widgets.activities.ActivitiesGridPanel({
                anchor: '100% 100%',
                editDialog: this
            }));
        }
    },

    initGrantsPanel() {
        if (this.recordClass?.getModelConfiguration()?.extendsContainer) {
            this.grantsGrid = new Tine.widgets.container.GrantsGrid({
                title:  i18n._('Permissions'),
                alwaysShowAdminGrant: true,
                hasAccountPrefix: true,
                recordClass: Tine.Tinebase.data.RecordMgr.get(this.recordClass.getModelConfiguration().grantsModel)
            });
            this.items.items.push(this.grantsGrid);
            this.on('load', (me, record) => {
                const grants = Tine.Tinebase.common.assertComparable(record.get('grants') || []);
                this.grantsGrid.getStore().loadData({results: grants});
            });
            this.on('recordUpdate', (me, record) => {
                const grants = Tine.Tinebase.common.assertComparable(_.map(this.grantsGrid.getStore().data.items, 'data'));
                record.set('grants', grants);
            });
        }
    },

    /**
     * creates attachments panel
     */
    initAttachmentsPanel: function() {
        if (! this.attachmentsPanel && ! this.hideAttachmentsPanel && this.recordClass && this.recordClass.hasField('attachments') && Tine.Tinebase.registry.get('filesystemAvailable')) {
            this.attachmentsPanel = new Tine.widgets.dialog.AttachmentsGridPanel({ anchor: '100% 100%', editDialog: this });
            this.items.items.push(this.attachmentsPanel);
        }
    },

    showLoadMask: async function() {
        return this.afterIsRendered().then(() => {
            if (this.loadMask !== false && this.i18nRecordName) {
                if (!this.loadMask) {
                    this.loadMask = new Ext.LoadMask(this.getEl(), {msg: String.format(i18n._('Transferring {0}...'), this.i18nRecordName)});
                }
                this.loadMask.show();
            }
        });
    },

    hideLoadMask: async function() {
        let me = this;
        return this.afterIsRendered().then(() => {
            if (this.loadMask) {
                return new Promise((resolve) => {
                    _.defer(() => {
                        me.loadMask.hide();
                        resolve();
                    });
                })
            }
            return Promise.resolve();
        });
    }
});

Tine.widgets.dialog.EditDialog.getConstructor = function(recordClass) {
    if (_.isString(recordClass)) recordClass = Tine.Tinebase.data.RecordMgr.get(recordClass);
    if (!recordClass || (recordClass && !recordClass.getMeta)) return null;
    var appName = recordClass.getMeta('appName'),
        modelName = recordClass.getMeta('modelName'),
        editDialogClass = _.get(Tine, `${appName}.${modelName}EditDialog`);

    return editDialogClass;
};
