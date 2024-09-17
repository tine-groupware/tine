/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

import 'widgets/form/AceField';
import 'widgets/form/ModelPicker';
import 'widgets/form/LanguagePicker';
import 'widgets/form/RecordEditField';
import 'widgets/form/LocalizedField';
import 'widgets/form/UrlField';

/**
 * central form field manager
 * - get form field for a given field
 * - register form field for a given field
 *
 * @namespace   Tine.widgets.form
 * @class       Tine.widgets.form.FieldManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.widgets.form.FieldManager = function() {
    var fields = {};

    return {
        /**
         * const for category editDialog
         */
        CATEGORY_EDITDIALOG: 'editDialog',

        /**
         * const for category propertyGrid
         */
        CATEGORY_PROPERTYGRID: 'propertyGrid',

        specialTypeMap: {
            password : 'tw-passwordTriggerField'
        },

        /**
         * get form field of well known field names
         *
         * @param {String} fieldName
         * @return {Object}
         */
        getByFieldname: function(fieldName) {
            var field = null;

            return field;
        },

        /**
         * get form field by data type
         *
         * @param {String} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|propertyGrid} optional.
         * @param {Object} config
         * @return {Object}
         */
        getByModelConfig: function(appName, modelName, fieldName, category, config) {
            var recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                modelConfig = recordClass ? recordClass.getModelConfiguration() : null,
                fieldDefinition = _.get(modelConfig, 'fields.' + fieldName, {});

            // have self contained fieldDefinition
            fieldDefinition.appName = appName;
            fieldDefinition.fieldName = fieldName;

            if (_.get(fieldDefinition, 'disabled') || _.get(fieldDefinition, 'uiconfig.disabled')) {
                return null;
            }

            return this.getByFieldDefinition(fieldDefinition, category, config);
        },

        getByFieldDefinition: function(fieldDefinition, category, config) {
            category = category || Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG;
            config = config || {};

            var field = {},
                fieldType = fieldDefinition.type || 'textfield',
                app = Tine.Tinebase.appMgr.get(fieldDefinition.owningApp || fieldDefinition.appName),
                i18n = fieldDefinition.useGlobalTranslation ? window.i18n : app.i18n;

            Object.assign(field, fieldDefinition, fieldDefinition.config || {},  fieldDefinition.uiconfig || {});

            if (fieldType === 'virtual' && fieldDefinition.config) {
                fieldType = fieldDefinition.config.type || 'textfield';
                fieldDefinition = _.merge({}, fieldDefinition, fieldDefinition.config);
            }
            if (fieldType === 'text' && fieldDefinition.length && fieldDefinition.length <= 256) {
                fieldType = 'textfield';
            }
            if (_.get(fieldDefinition, 'config.specialType') === 'localizedString') {
                fieldType = fieldDefinition.type = 'localizedString';
            }

            field.fieldLabel = i18n._hidden(fieldDefinition.label || fieldDefinition.fieldName);
            field.name = fieldDefinition.fieldName || fieldDefinition.name;
            field.readOnly = !! fieldDefinition.readOnly || !! _.get(fieldDefinition, 'uiconfig.readOnly');
            field.allowBlank = !! (fieldDefinition.validators && fieldDefinition.validators.allowEmpty);
            // make field available via recordForm.formfield_NAME
            field.ref = '../../formfield_' + field.name;

            if (fieldDefinition['default']) {
                field['default'] = i18n._hidden(fieldDefinition['default']);
            }

            switch (fieldType) {
                case 'money':
                    field.xtype = 'extuxmoneyfield';
                    if (fieldDefinition.hasOwnProperty('allowNegative')) {
                        field.allowNegative = fieldDefinition.allowNegative;
                    }
                    break;
                case 'date':
                case 'datetime_separated_date':
                    field.xtype = 'datefield';
                    if (fieldDefinition.dateFormat) {
                        field.dateFormat = fieldDefinition.dateFormat;
                    }
                    if (fieldDefinition.format) {
                        field.format = fieldDefinition.format;
                    }
                    if (fieldDefinition.nullable) {
                        field.xtype = 'extuxclearabledatefield';
                    }
                    break;
                case 'time':
                    field.xtype = 'timefield';
                    break;
                case 'datetime':
                    field.xtype = 'datetimefield'; // form ux.datetimefield
                    break;
                case 'bool':
                case 'boolean':
                    if (category === 'editDialog') {
                        field.xtype = 'checkbox';
                        field.boxLabel = i18n._hidden(_.get(fieldDefinition, 'boxLabel', field.fieldLabel));
                        field.checked = field['default'];
                        field.hideLabel = !_.get(fieldDefinition, 'boxLabel');
                        field.blurOnChange = true;
                    } else {
                        field.xtype = 'booleancombo';
                        field.blurOnSelect = true;
                    }
                    break;
                case 'integer':
                    field.xtype = 'numberfield';
                    field.allowDecimals = false;

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'percent') {
                        field.xtype = 'extuxnumberfield';
                        field.useThousandSeparator = false;
                        field.suffix = ' %';
                    }

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'durationSec') {
                        field.xtype = 'durationspinner';
                        field.baseUnit = 'seconds';
                    }

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'minutes') {
                        field.xtype = 'durationspinner';
                        field.baseUnit = 'minutes';
                    }

                    if (fieldDefinition.max) {
                        field.maxValue = fieldDefinition.max;
                    }

                    if (fieldDefinition.min) {
                        field.minValue = fieldDefinition.min;
                    }
                    break;
                case 'float':
                    field.xtype = 'numberfield';
                    field.decimalPrecision = 2;

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'percent') {
                        field.xtype = 'extuxnumberfield';
                        field.suffix = ' %';
                    }

                    if (fieldDefinition.specialType && fieldDefinition.specialType === 'discount') {
                        field.xtype = 'discountfield';
                    }

                    if (fieldDefinition.max) {
                        field.maxValue = fieldDefinition.max;
                    }

                    if (fieldDefinition.min) {
                        field.minValue = fieldDefinition.min;
                    }
                    break;
                case 'user':
                    field.xtype = 'addressbookcontactpicker';
                    field.userOnly = true;
                    field.useAccountRecord = true;
                    break;
                case 'relation':
                case 'relations':
                    if (fieldDefinition.config && fieldDefinition.config.appName && fieldDefinition.config.modelName) {
                        field.xtype = 'tinerelationpickercombo';
                        field.recordClass = Tine[fieldDefinition.config.appName].Model[fieldDefinition.config.modelName];
                        field.app = fieldDefinition.config.appName;
                        field.relationType = fieldDefinition.config.type;
                        field.modelUnique = true;
                        if (fieldDefinition.config.additionalFilterSpec) {
                            field.additionalFilterSpec = fieldDefinition.config.additionalFilterSpec;
                        }
                        // TODO pass degree and other options in config?
                        field.relationDegree = 'sibling';

                        if (fieldType === 'relations') {
                            _.assign(field, {
                                title: i18n._(fieldDefinition.label),
                                xtype: 'tinerelationpickergridpanel',
                                isFormField: true,
                                fieldName: fieldDefinition.fieldName,
                                hideHeaders: true,
                                height: 80 /* 4 records */ + 2 * 26 /* 2 toolbars */
                            });
                        }
                    }
                    break;
                case 'record':
                    if (fieldDefinition.config && fieldDefinition.config.appName && fieldDefinition.config.modelName) {
                        if (fieldDefinition.config.additionalFilterSpec) {
                            field.additionalFilterSpec = fieldDefinition.config.additionalFilterSpec;
                        }
                        field.fieldLabel = field.fieldLabel ? field.fieldLabel :
                            Tine.Tinebase.data.RecordMgr.get(fieldDefinition.config.appName, fieldDefinition.config.modelName)?.getRecordName();

                        // @TODO: denormalizationOf should have an edit plugin
                        if (category === 'editDialog' && fieldDefinition.config.dependentRecords && !fieldDefinition.config.denormalizationOf) {
                            field.xtype = 'tw-recordEditField';
                            field.enableDelete = true;
                            field.appName = fieldDefinition.config.appName;
                            field.modelName = fieldDefinition.config.modelName;
                            break;
                        }
                        var picker = Tine.widgets.form.RecordPickerManager.get(
                            fieldDefinition.config.appName,
                            fieldDefinition.config.modelName,
                            Ext.apply(field, config)
                        );

                        field = picker;
                    }
                    break;
                case 'records':
                    if (category === 'editDialog') {
                        Ext.apply(field, config);
                        field.xtype = field.xtype || 'wdgt.pickergrid';
                        field.fieldLabel = field.fieldLabel ? field.fieldLabel :
                            Tine.Tinebase.data.RecordMgr.get(fieldDefinition.config.appName, fieldDefinition.config.modelName)?.getRecordsName();
                        field.recordClass = Tine[fieldDefinition.config.appName].Model[fieldDefinition.config.modelName];
                        field.isFormField = true;
                        field.fieldName = fieldDefinition.fieldName;
                        // NOTE: it's hard to compute height here as enableTbar, hideHeaders is calculated on runtime
                        field.height = 80 /* 4 records */ + (field.enableTbar || 0) * 26  +  26 /* 2 toolbars */
                        if (_.get(fieldDefinition, 'config.dependentRecords', false)) {
                            // @TODO use different widget here (e.g. quickadd gird)
                            var modelConf = field.recordClass.getModelConfiguration() || {};
                            if (modelConf.isMetadataModelFor) {
                                field.isMetadataModelFor = modelConf.isMetadataModelFor;
                                field.height = field.height + 26; // picker
                            }
                            field.allowCreateNew = true;
                            field.refIdField = _.get(fieldDefinition, 'config.refIdField', undefined);
                            _.set(field, 'editDialogConfig.mode', 'local');
                        }
                        if (fieldDefinition.config.additionalFilterSpec) {
                            field.additionalFilterSpec = fieldDefinition.config.additionalFilterSpec;
                        }
                    } else {
                        config.allowMultiple = true;
                        var picker = Tine.widgets.form.RecordPickerManager.get(
                            fieldDefinition.config.appName,
                            fieldDefinition.config.modelName,
                            Ext.apply(field, config)
                        );
                        field = picker;
                    }
                    break;
                case 'localizedString':
                    field.xtype = field.type === 'string' ?
                        'tw-localized-string-text-field' :
                        'tw-localized-string-text-area';
                    break;
                case 'model':
                    field.xtype = 'tw-modelpicker';
                    break
                case 'dynamicRecord':
                    // NOTE: this editor depends and the className _data_ and therefore can't be assigned statically
                    //       as the editor api (get/setValue) does not know about the record the value comes from
                    //       it's not possible to auto create a editor here
                    field.xtype = 'tw-recordEditField';
                    field.appName = fieldDefinition.config.appName;
                    field.modelName = fieldDefinition.config.refModelField;
                    field.fieldName = fieldDefinition.fieldName;
                    break
                case 'keyfield':
                    field.xtype = 'widget-keyfieldcombo';
                    field.app = app;
                    field.keyFieldName = fieldDefinition.name;
                    break;
                case 'text':
                case 'fulltext':
                    field.xtype = 'textarea';
                    field.height = 70; // 5 lines
                    break;
                case 'stringAutocomplete':
                    field.xtype = 'tine.widget.field.AutoCompleteField';
                    field.appName = fieldDefinition.config.appName;
                    field.modelName = fieldDefinition.config.modelName;
                    break;
                case 'numberableStr':
                case 'numberableInt':
                    field.xtype = 'textfield';
                    field.disabled = ! _.get(field, 'config.editable');
                    field.emptyText = window.i18n._('Gets assigned automatically');
                    break;
                case 'json':
                    field.xtype = field.xtype || 'tw-acefield';
                    field.mode = 'json';
                    field.height = 150; // 12 lines
                    break;
                case 'xml':
                    field.xtype = 'tw-acefield';
                    field.mode = 'xml';
                    field.height = 150; // 12 lines
                    break;
                case 'container':
                    field.xtype = 'tinewidgetscontainerselectcombo';
                    break;
                case 'containers':
                    field.xtype = 'containerspicker';
                    field.appName = fieldDefinition.config.appName;
                    field.modelName = fieldDefinition.config.modelName;
                    break;
                case 'filelocation':
                    field.xtype = 'fileselectionfield';
                    field.mode = fieldDefinition.config.mode;
                    field.locationTypesEnabled = fieldDefinition.config.locationTypesEnabled;
                    field.allowMultiple = fieldDefinition.config.allowMultiple;
                    field.constraint = fieldDefinition.config.constraint;
                    field.initialPath = fieldDefinition.config.initialPath;
                    field.fileName = fieldDefinition.config.fileName;
                    break;
                case 'hexcolor':
                    field.xtype = 'colorfield';
                    field.width = 80;
                    break;
                case 'language':
                    field.xtype = 'tw-languagepicker';
                    break;
                case 'label':
                    field.xtype = 'label';
                    break;
                case 'url':
                    field.xtype = 'urlfield';
                    break;
                case 'password':
                    field.xtype = field.xtype || 'tw-passwordTriggerField';
                    break;
                default:
                    field.xtype = field.xtype || this.specialTypeMap[fieldDefinition.specialType] || 'textfield';
                    field.emptyValue = field.emptyValue || (fieldDefinition.nullable ? null : '');

                    if (fieldDefinition.length) {
                        field.maxLength = fieldDefinition.length;
                    }
                    break;
            }

            Ext.apply(field, config);

            return field;
        },

        /**
         * returns form field for given field
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|propertyGrid} optional.
         * @param {Object} config
         * @return {Object}
         */
        get: function(appName, modelName, fieldName, category, config) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]),
                config = config || {};

            // check for registered renderer
            var field = fields[categoryKey] ? fields[categoryKey] : fields[genericKey];

            // check for common names
            if (! field) {
                field = this.getByFieldname(fieldName);
            }

            // check for known datatypes
            if (! field) {
                field = this.getByModelConfig(appName, modelName, fieldName, category, config);
            }

            return field;
        },

        /**
         * register renderer for given field
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {Object} field
         * @param {String} category {editDialog|propertyGrid} optional.
         */
        register: function(appName, modelName, fieldName, field, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            field.name = fieldName;
            field.fieldName = fieldName;
            fields[category ? categoryKey : genericKey] = field;
        },

        /**
         * check if a field is explicitly registered
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|propertyGrid} optional.
         * @return {Boolean}
         */
        has: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            return (fields[categoryKey] ? fields[categoryKey] : fields[genericKey]) ? true : false;
        },

        /**
         * returns the modelName by modelName or record
         *
         * @param {Record/String} modelName
         * @return {String}
         */
        getModelName: function(modelName) {
            return Ext.isFunction(modelName) ? modelName.getMeta('modelName') : modelName;
        },

        /**
         * returns the modelName by appName or application instance
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @return {String}
         */
        getAppName: function(appName) {
            return Ext.isString(appName) ? appName : appName.appName;
        },

        /**
         * returns a key by joining the array values
         *
         * @param {Array} params
         * @return {String}
         */
        getKey: function(params) {
            return params.join('_');
        }
    };
}();
