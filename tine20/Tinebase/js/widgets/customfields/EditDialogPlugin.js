/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Ext.ns('Tine.widgets.customfields');
require('./FieldWrapper');

Tine.widgets.customfields.EditDialogPlugin = function (config) {
    Ext.apply(this, config);
    this.tabPanelPosition = 20;
};

Tine.widgets.customfields.EditDialogPlugin._isModelInitialized = {};

Tine.widgets.customfields.EditDialogPlugin.prototype = {
    /**
     * @type Tine.Tinebase.Application app
     */
    app: null,
    
    /**
     * @type Tine.widgets.dialog.EditDialog editDialog
     */
    editDialog: null,

    // private
    isInitialised: false,

    init: function(editDialog) {
        if (this.isInitialised) {
            return;
        }
        this.editDialog = editDialog;
        
        // edit dialog without recordClass cannot have custom fields
        if(!this.editDialog.recordClass) {
            return;
        }
        
        this.app = Tine.Tinebase.appMgr.get(this.editDialog.app);
        this.customfieldsValue = [];
        
        // compute cf's for cf tab and add a cf tab on demand
        this.editDialog.on('beforerender', this.onBeforeRender, this);
        
        // fill/buffer all cf's with values
        this.editDialog.on('load', this.onRecordLoad, this);

        // load cf for mainTab in editDialog
        this.loadCfForm();

        // get all cf values
        this.editDialog.onRecordUpdate = this.editDialog.onRecordUpdate.createSequence(this.onRecordUpdate, this);
        this.isInitialised = true;
    },

    /**
     * dispatch values from customfield property
     */
    onRecordLoad: function() {
        var form = this.editDialog.getForm(),
            modelName = this.editDialog.recordClass.getMeta('appName') + '_Model_' + this.editDialog.recordClass.getMeta('modelName'),
            name,
            field,
            cfConfig;

        this.customfieldsValue = this.editDialog.record.get('customfields') || {};
        
        for (name in this.customfieldsValue) {
            field = form.findField('customfield_' + name);
            cfConfig = Tine.widgets.customfields.ConfigManager.getConfig(this.app, modelName, name);
            
            if (cfConfig) {
                // transform datetime values
                if (['date', 'datetime'].indexOf(Ext.util.Format.lowercase(cfConfig.get('definition').type)) != -1) {
                    this.customfieldsValue[name] = Date.parseDate(this.customfieldsValue[name], Date.patterns.ISO8601Long);
                }
                
                if (field) {
                    if(field.isXType('combo') && Ext.isObject(this.customfieldsValue[name])) {
                        var record = Tine.Tinebase.data.Record.setFromJson(this.customfieldsValue[name], field.recordClass);
                        field.setValue(record);
                    } else {
                        field.setValue(this.customfieldsValue[name]);
                    }
                }
            }
        }
    },
    
    /**
     * combile cf values in customfield property
     */
    onRecordUpdate: function() {
        var form = this.editDialog.getForm();
        
        form.items.each(function(f) {
            var name = f.getName();
            
            if (Ext.isString(name) && name.match(/^customfield_(.+)$/)) {
                name = name.match(/^customfield_(.+)$/)[1];
                
                this.customfieldsValue[name] = f.getValue();
            }
        }, this);
        
        this.customfieldsValue.toString = function() {
            return Ext.util.JSON.encode(this.customfieldsValue);
        };
        
        this.editDialog.record.set('customfields', this.customfieldsValue);
    },

    /**
     * match item with the key of customfield".
     */
    loadCfForm: function() {
        var _ = window.lodash,
            modelName = this.editDialog.recordClass.getMeta('appName') + '_Model_' + this.editDialog.recordClass.getMeta('modelName'),
            allCfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName);

        if (Tine.widgets.customfields.EditDialogPlugin._isModelInitialized[modelName]) return;

        _.each(allCfConfigs, _.bind(function (fields) {
            const key = _.get(fields, 'data.definition.uiconfig.key');
            if(key) {
                const pos = _.get(fields, 'data.definition.uiconfig.order');
                const config = {};
                if (key.match(/eastPanel$/)) {
                    Object.assign(config, {
                        labelSeparator: '',
                        hideLabel: true,
                        grow: false,
                        preventScrollbars:false,
                        anchor:'100% 100%',
                    });
                }
                const wrapperConfig = {
                    xtype: 'customfieldwrapper',
                    config: config,
                    fields: fields,
                    app:    this.app,
                };

                Ext.ux.ItemRegistry.registerItem(key, (key.match(/eastPanel$/) ?
                    {layout: 'form', items: wrapperConfig, title: _.get(fields, 'data.definition.label')} : wrapperConfig),pos ? pos : '0/0');
            }
        }, this));

        Tine.widgets.customfields.EditDialogPlugin._isModelInitialized[modelName] = true;
    },

    /**
     * create cf tab on demand
     */
    onBeforeRender: function() {
        var _ = window.lodash,
            modelName = this.editDialog.recordClass.getMeta('appName') + '_Model_' + this.editDialog.recordClass.getMeta('modelName'),
            allCfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, modelName),
            tabPanel = this.getTabPanel(),
            form = this.editDialog.getForm(),
            cfConfigs = [];

        // global sort so groups are sorted to
        allCfConfigs = _.sortBy(allCfConfigs, 'data.definition.uiconfig.order');

        // remove already applied cfs / fill the mixed collection
        Ext.each(allCfConfigs, function(cfConfig) {
            if (! form.findField('customfield_' + cfConfig.get('name'))
                && !_.get(cfConfig, 'data.definition.uiconfig.disabled')) {
                cfConfigs.push(cfConfig);
            }
        }, this);
        
        _.each(_.groupBy(cfConfigs, 'data.definition.uiconfig.tab'), _.bind(function(fields, tabId) {
            var tab = tabPanel.items.get(_.isNaN(+tabId) || ['', null].indexOf(tabId) >= 0? tabId : +tabId);
            if (! tab) {
                this.addCFTab(fields, tabId);
            } else {
                // evaluate group here?
                _.each(fields, _.bind(function(fieldConfig) {
                    var formField = Tine.widgets.customfields.Field.get(this.app, fieldConfig, {}, this.editDialog),
                        pos = _.get(fieldConfig, 'data.definition.uiconfig.order');

                    Ext.ux.ItemRegistry.registerItem(tab, formField, pos);
                }, this));

            }
        }, this));
    },

    getTabPanel: function () {
        if (! this.tabPanel) {
            // find the first tabPanel and add it there
            this.tabPanel = this.editDialog.items.find(function (item) {
                return Ext.isObject(item) && Ext.isFunction(item.getXType) && item.getXType() == 'tabpanel';
            });
        }

        return this.tabPanel;
    },

    /**
     * create a cf tab
     * 
     * @param {Collection} cfConfigs
     * @param {String} tabName
     */
    addCFTab: function(cfConfigs, tabName) {
        var _ = window.lodash,
            groups = _.groupBy(cfConfigs, 'data.definition.uiconfig.group'),
            allFields = [];
            items = [];

        _.each(groups, _.bind(function(fields, groupName) {
            groupName = !groupName && tabName == 'customfields' ? 'General' : groupName;
            fields = _.sortBy(fields, 'data.definition.uiconfig.order');

            var fieldObjects = _.map(fields, _.bind(Tine.widgets.customfields.Field.get, this, this.app, _, {anchor: '95%'}, this.editDialog));
            allFields = allFields.concat(fieldObjects);
            if (groupName) {
                items.push(new Ext.form.FieldSet({
                    title: i18n._hidden(groupName),
                    autoHeight:true,
                    autoWidth:true,
                    labelAlign: 'top',
                    labelWidth: '90%',
                    collapsible:true,
                    items: fieldObjects
                }));
            } else {
                items = items.concat(fieldObjects);
            }
        }, this));

        const panel = new Ext.Panel({
            title: (!tabName || tabName == 'customfields') ? i18n._('Custom Fields') : i18n._hidden(tabName),
            pos: this.tabPanelPosition,
            layout: 'form',
            border: true,
            frame: true,
            labelAlign: 'top',
            autoScroll: true,
            items: items,
            defaults: {
                anchor: '100%',
                labelSeparator: ''
            }
        });
        this.getTabPanel().add(panel);

        Promise.all(allFields.map((field) => { return field.afterIsRendered() })).then(() => {
            const itemsHeight = panel.items.items.reduce((height, item) => {return height + item.getHeight()}, 0);
            if (itemsHeight > panel.getHeight()) {
                panel.items.items.forEach((item) => { item.collapse() });
            }
        })
        this.tabPanelPosition++;
    }
};

Ext.preg('tinebase.widgets.customfield.editdialogplugin', Tine.widgets.customfields.EditDialogPlugin);
