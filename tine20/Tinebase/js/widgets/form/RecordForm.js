/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

/**
 * Generic 'Edit Record' form
 *
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.RecordForm
 * @extends     Ext.ux.form.ColumnFormPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config The configuration options.
 */

Tine.widgets.form.RecordForm = Ext.extend(Ext.ux.form.ColumnFormPanel, {
    /**
     * record definition class  (required)
     * @cfg {Ext.data.Record} recordClass
     */
    recordClass: null,

    /**
     * {Tine.widgets.dialog.EditDialog} editDialog
     */
    editDialog: null,

    initComponent: function() {
        var appName = this.recordClass.getMeta('appName'),
            app = Tine.Tinebase.appMgr.get(appName),
            fieldDefinitions = Tine.widgets.form.RecordForm.getFieldDefinitions(this.recordClass);

        this.items = [];
        this.plugins = this.plugins || [];
        this.plugins.push({
            ptype: 'ux.itemregistry',
            key:   [app.appName, this.recordClass.getMeta('modelName'), 'RecordForm'].join('-')
        })

        // sometimes we need the instances from registry (e.g. printing)
        this.editDialog.recordForm = this;

        Ext.each(fieldDefinitions, function(fieldDefinition) {
            const fieldsToExclude = _.get(this, 'editDialog.fieldsToExclude', this.fieldsToExclude);
            if (_.isArray(fieldsToExclude) && _.indexOf(fieldsToExclude, fieldDefinition.fieldName) >=0) return;

            var field = Tine.widgets.form.FieldManager.get(app, this.recordClass, fieldDefinition.fieldName, 'editDialog');
            if (field) {
                // apply basic layout
                field.columnWidth = 1;
                // add edit dialog
                if (this.editDialog) {
                    field.editDialog = this.editDialog;
                }

                this.items.push([field]);
            }
        }, this);

        Tine.widgets.form.RecordForm.superclass.initComponent.call(this);
    }
});

/**
 * get fieldDefinitions of all fields which should be present in recordForm
 *
 * @param recordClass
 * @return []
 */
Tine.widgets.form.RecordForm.getFieldDefinitions = function(recordClass) {
    var fieldNames = recordClass.getFieldNames(),
        modelConfig = recordClass.getModelConfiguration(),
        fieldsToExclude = ['description', 'tags', 'notes', 'attachments', 'relations', 'customfields', 'account_grants', 'grants'];

    Ext.each(Tine.Tinebase.Model.genericFields, function(field) {fieldsToExclude.push(field.name)});
    fieldsToExclude.push(recordClass.getMeta('idProperty'));

    let fieldDefs = _.sortBy(_.reduce(fieldNames, function(fieldDefinitions, fieldName) {
        var fieldDefinition = _.cloneDeep(modelConfig.fields[fieldName]);
        if (fieldsToExclude.indexOf(fieldDefinition.fieldName) < 0) {
            _.set(fieldDefinition, 'uiconfig.sorting', _.get(fieldDefinition, 'uiconfig.sorting', fieldDefinitions.length * 10));
            fieldDefinitions.push(fieldDefinition);
        }
        return fieldDefinitions;
    }, []), (field) => {return _.get(field, 'uiconfig.sorting')});


    return fieldDefs;
};

Tine.widgets.form.RecordForm.getFormFields = function(recordClass, configInterceptor) {
    const fieldDefinitions = Tine.widgets.form.RecordForm.getFieldDefinitions(recordClass);
    const fieldManager = _.bind(Tine.widgets.form.FieldManager.get,
        Tine.widgets.form.FieldManager, recordClass.getMeta('appName'), recordClass.getMeta('modelName'), _,
        Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

    return _.reduce(fieldDefinitions, function(formFields, fieldDefinition) {
        const fieldName = fieldDefinition.fieldName;
        const config = {};
        if (configInterceptor) {
            configInterceptor(fieldName, config, fieldDefinition)
        }
        const formFieldDefinition = fieldManager(fieldName, config);
        if (formFieldDefinition) {
            formFields[fieldDefinition.fieldName] = Ext.create(formFieldDefinition);
        }
        return formFields;
    }, {});
};

Tine.widgets.form.RecordForm.getFormHeight = function(recordClass) {
    var dlgConstructor = Tine.widgets.dialog.EditDialog.getConstructor(recordClass),
        fieldDefinitions = Tine.widgets.form.RecordForm.getFieldDefinitions(recordClass),
        formHeight = 38+23+30; // btnfooter + tabpanel + paddings

    if (dlgConstructor) {
        // toolbar
        if (dlgConstructor.prototype.hasOwnProperty('initButtons')) {
            formHeight += 30;
        }
    }

    Ext.each(fieldDefinitions, function(fieldDefinition) {
        var app = Tine.Tinebase.appMgr.get(recordClass.getMeta('appName')),
            field = Tine.widgets.form.FieldManager.get(app, recordClass, fieldDefinition.fieldName, 'editDialog'),
            height = field ? (field.height+30 || 42) : 0;

        formHeight += height;
    });

    return formHeight;
};

Ext.reg('recordform', Tine.widgets.form.RecordForm);
