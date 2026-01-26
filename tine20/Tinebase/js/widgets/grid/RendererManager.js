/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

require('./AttachmentRenderer');
require('./ImageRenderer');
require('./jsonRenderer');

import {supportedTypes as supportedACETypes} from "../form/AceField";
import getACERenderer from './ACERenderer';

/**
 * central renderer manager
 * - get renderer for a given field
 * - register renderer for a given field
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.RendererManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.widgets.grid.RendererManager = function() {
    var renderers = {};

    return {
        /**
         * const for category gridPanel
         */
        CATEGORY_GRIDPANEL: 'gridPanel',

        /**
         * const for category displayPanel
         */
        CATEGORY_DISPLAYPANEL: 'displayPanel',

        /**
         * default renderer - quote content
         */
        defaultRenderer: function(value) {
            return [null, undefined].indexOf(value) < 0 ? `<span ext:qtip="${Tine.Tinebase.common.doubleEncode(value)}">${Ext.util.Format.htmlEncode(value)}</span>` : '';
        },

        /**
         * get renderer of well known field names
         *
         * @param {String} fieldName
         * @return Function/null
         */
        getByFieldname: function(fieldName) {
            var renderer = null;

            if (fieldName == 'tags') {
                renderer = Tine.Tinebase.common.tagsRenderer;
            } else if (fieldName == 'notes') {
                // @TODO
                renderer = function(value) {return value ? i18n._('has notes') : '';};
            } else if (fieldName == 'relations') {
                renderer = Tine.Tinebase.common.relationsRenderer;
            } else if (fieldName == 'customfields') {
                // @TODO
                // we should not come here!
            } else if (fieldName == 'container_id') {
                renderer = Tine.Tinebase.common.containerRenderer;
            } else if (fieldName == 'attachments') {
                renderer = Tine.widgets.grid.attachmentRenderer;
            } else if (fieldName == 'color') {
                renderer = Tine.Tinebase.common.colorRenderer;
            } else if (fieldName === 'url') {
                renderer = Tine.Tinebase.common.urlRenderer;
            }

            return renderer;
        },

        /**
         * get renderer by data type
         *
         * @param {String} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {boolean} cf
         * @return {Function}
         */
        getByDataType: function (appName, modelName, fieldName, cf = false) {
            if(!cf){
                var renderer = null,
                    recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName),
                    field = recordClass ? recordClass.getField(fieldName) : null,
                    fieldDefinition = Object.assign({}, field, _.get(field, 'fieldDefinition', {}), _.get(field, 'fieldDefinition.conifg', {}), _.get(field, 'fieldDefinition.uiconfig', {})),
                    fieldType = _.get(fieldDefinition, 'fieldDefinition.type', _.get(fieldDefinition, 'type', 'auto'));
                if (_.get(fieldDefinition, 'config.specialType') === 'localizedString') {
                    fieldType = 'localizedString';
                }
            }
            switch (fieldType) {
                case 'record':
                case 'records':
                    if (Tine.Tinebase.common.hasRight('view', fieldDefinition.config.appName, fieldDefinition.config.modelName.toLowerCase())) {
                        renderer = function (value, row, record) {
                            var foreignRecordClass = Tine[fieldDefinition.config.appName].Model[fieldDefinition.config.modelName];

                            if (foreignRecordClass && value) {
                                if (typeof value !== 'string') {
                                    const record = Tine.Tinebase.data.Record.setFromJson(value, foreignRecordClass);
                                    const titleProperty = foreignRecordClass.getMeta('titleProperty');
                                    value = Ext.util.Format.htmlEncode(_.isFunction(_.get(record, 'getTitle')) ? record.getTitle() : _.get(record, titleProperty, ''));
                                    if (!!+_.get(record, 'data.is_deleted')) {
                                        value = '<span style="text-decoration: line-through;">' + value + '</span>';
                                    }
                                }
                            }
                            return value;
                        };
                        if (fieldType === 'records') {
                            // @TODO cope with cross and metadata records
                            const rr = renderer;
                            renderer = (rs) => {
                                return _.join(_.map(rs, rr), ', ' );
                            }
                        }
                    } else {
                        renderer = null;
                    }
                    break;
                case 'bigint':
                case 'integer':
                case 'float':
                    if (fieldDefinition.hasOwnProperty('specialType')) {
                        switch (fieldDefinition.specialType) {
                            case 'bytes1000':
                                renderer = function (value, cell, record) {
                                    const forceUnit = _.get(fieldDefinition, 'uiconfig.forceUnit', null);
                                    return Tine.Tinebase.common.byteFormatter(parseInt(value), forceUnit, 2, true);
                                };
                                break;
                            case 'bytes':
                                renderer = function (value, cell, record) {
                                    return Tine.Tinebase.common.byteRenderer(value, cell, record, 2, false);
                                };
                                break;
                            case 'minutes':
                                renderer = Tine.Tinebase.common.minutesRenderer;
                                break;
                            case 'seconds':
                                renderer = Tine.Tinebase.common.secondsRenderer;
                                break;
                            case 'percent':
                                renderer = function (value, cell, record) {
                                    return Tine.Tinebase.common.percentRenderer(value, fieldDefinition.type, fieldDefinition.nullable);
                                };
                                break;
                            case 'discount':
                                if (fieldDefinition.singleField) {
                                    renderer = function (value, metaData, record) {
                                        const type = record.get(fieldName.replace(/_sum$/, '_type'));
                                        if (type === 'PERCENTAGE') {
                                            value = record.get(fieldDefinition.fieldName.replace(/_sum$/, '_percentage'));
                                            return !value && value !== 0 ? '' : Tine.Tinebase.common.percentRenderer(value, 'float');
                                        } else {
                                            return !value && value !== 0 ? '' : Ext.util.Format.money(value, metaData);
                                        }
                                    }
                                } else {
                                    renderer = function (value, metaData, record) {
                                        return !value ? '' : Ext.util.Format.money(value, metaData);
                                    }
                                }
                                break;

                            case 'durationSec':
                                renderer = function (value, cell, record) {
                                    return Ext.ux.form.DurationSpinner.durationRenderer(value, {
                                        baseUnit: 'seconds'
                                    });
                                };
                                break;
                            default:
                                renderer = Ext.util.Format.htmlEncode;
                        }

                        renderer = renderer.createSequence(function (value, metadata, record) {
                            if (metadata) {
                                metadata.css += ' tine-gird-cell-number';
                            }
                        });

                    }
                    break;
                case 'string':
                    renderer = this.defaultRenderer;
                    if (fieldDefinition.hasOwnProperty('specialType')) {
                        switch (fieldDefinition.specialType) {
                            case 'country':
                                renderer = Tine.Tinebase.common.countryRenderer;
                                break;
                            case 'currency':
                                renderer = Tine.Tinebase.common.currencyRenderer;
                                break;
                            case 'application':
                                renderer = Tine.Tinebase.common.applicationRenderer;
                                break;
                            default:
                                renderer = this.defaultRenderer;
                        }
                    }
                    break;
                case 'text':
                case 'fulltext':
                    renderer = this.defaultRenderer;
                    if (fieldDefinition.hasOwnProperty('specialType')) {
                        if (supportedACETypes.indexOf(fieldDefinition.specialType) >= 0) {
                            renderer = getACERenderer(fieldDefinition.specialType);
                        } else if (fieldDefinition.specialType === 'markdown') {
                            renderer = Tine.Tinebase.common.markdownRenderer;
                        }
                    }
                    break;
                case 'user':
                    renderer = Tine.Tinebase.common.usernameRenderer;
                    break;
                case 'keyfield':
                case 'keyField':
                    renderer = Tine.Tinebase.widgets.keyfield.Renderer.get(fieldDefinition?.config?.application || appName, _.get(fieldDefinition,
                        'keyFieldConfigName', fieldDefinition.name));
                    break;
                case 'datetime_separated_date':
                case 'date': {
                        const format = this.getDateFormat(field);
                        renderer = _.bind(Tine.Tinebase.common.dateRenderer, {format});
                    }
                    break;
                case 'datetime': {
                        const format = this.getDateTimeFormat(field);
                        renderer = _.bind(Tine.Tinebase.common.dateTimeRenderer, {format});
                    }
                    break;
                case 'time':
                    renderer = Tine.Tinebase.common.timeRenderer;
                    break;
                case 'tag':
                    renderer = Tine.Tinebase.common.tagsRenderer;
                    break;
                case 'container':
                    renderer = Tine.Tinebase.common.containerRenderer;
                    break;
                case 'boolean':
                    renderer = Tine.Tinebase.common.booleanRenderer;
                    break;
                case 'money':
                    renderer = function (value, metaData, record) {
                        return Ext.util.Format.money(value, Object.assign({zeroMoney: fieldDefinition?.specialType === 'zeroMoney'}, metaData), fieldDefinition.nullable);
                    };
                    break;
                case 'attachments':
                    renderer = Tine.widgets.grid.attachmentRenderer;
                    break;
                case 'image':
                    renderer = Tine.widgets.grid.imageRenderer;
                    break;
                case 'json':
                    renderer = Tine.widgets.grid.jsonRenderer;
                    break;
                case 'relation':
                case 'relations':
                    let cc = fieldDefinition.config;

                    if (cc && cc.type && cc.appName && cc.modelName) {
                        let rendererObj = new Tine.widgets.relation.GridRenderer({
                            appName: appName,
                            type: cc.type,
                            foreignApp: cc.appName,
                            foreignModel: cc.modelName
                        });
                        renderer = _.bind(rendererObj.render, rendererObj);
                        break;
                    }
                    break;
                case 'hexcolor':
                    renderer = Tine.Tinebase.common.colorRenderer;
                    break;
                case 'model':
                    renderer = (classname, metaData, record) => {
                        const recordClass = Tine.Tinebase.data.RecordMgr.get(_.get(classname, 'className', classname));
                        return recordClass ? recordClass.getRecordName() : classname;
                    };
                    break;
                case 'dynamicRecord':
                    const foreignFieldDefinition = _.get(recordClass?.getModelConfiguration(), `fields.${fieldName}.config`, {});
                    const isJSONStorage = _.toUpper(_.get(foreignFieldDefinition, `config.storage`, '')) === 'JSON';
                    const dependentRecords = _.get(foreignFieldDefinition, `config.dependentRecords`, false);

                    const classNameField = fieldDefinition.config.refModelField;
                    renderer = (configRecord, metaData, record) => {
                        const configRecordClass = Tine.Tinebase.data.RecordMgr.get(record.get(classNameField));
                        if (! configRecordClass) return '';

                        const hasNoAPI = !_.get(Tine, `${configRecordClass.getMeta('appName')}.search${_.upperFirst(configRecordClass.getMeta('modelName'))}s`);
                        const isDependent = configRecordClass.getModelConfiguration()?.isDependent;

                        const titleHTML = `<span class="tine-recordclass-gridicon ${configRecordClass.getIconCls()}">&nbsp;</span>${Ext.util.Format.htmlEncode(Tine.Tinebase.data.Record.setFromJson(configRecord, configRecordClass).getTitle() || '')} (${configRecordClass.getRecordName()})`;
                        return isJSONStorage || dependentRecords || hasNoAPI || isDependent ? titleHTML : `<a href="#" data-record-class="${configRecordClass.getPhpClassName()}" data-record-id="${configRecord.id}">${titleHTML}</a>`;
                    };
                    break;
                case 'language':
                    const allLanguages = Locale.getTranslationList('Language');
                    renderer = (value, metaData, record) => {
                        return allLanguages[value];
                    };
                    break;
                case 'localizedString':
                    const type = _.get(fieldDefinition, 'config.type')
                    const languagesAvailableDef = _.get(recordClass.getModelConfiguration(), 'languagesAvailable')
                    const keyFieldDef = Tine.Tinebase.widgets.keyfield.getDefinition(_.get(languagesAvailableDef, 'config.appName', appName), languagesAvailableDef.name)
                    const translationList = Locale.getTranslationList('Language')
                    
                    renderer = function renderer(value, metaData, record, rowIndex, colIndex, store) {
                        const lang = store?.localizedLang || keyFieldDef.default
                        const localized = _.find(value, { language: lang })
                        const text = Ext.util.Format.htmlEncode(_.get(localized, 'text', ''));
                        const langCode = lang.toUpperCase();
                        const qtip = i18n._('This is a multilingual field:') + '<br />' + _.reduce(value, (text, localized) => {
                            return text + '<br />' + translationList[localized.language] + ': ' + Ext.util.Format.htmlEncode(localized.text)
                        }, '')
                        const row =  document.createElement('div');
                        row.className = 'tine-grid-cell-action-fit';
                        
                        const row1Left = document.createElement('div');
                        row1Left.innerHTML = text;
                        
                        const row1Right =  document.createElement('div');
                        row1Right.innerHTML = langCode;
                        row1Right.className = 'tine-grid-cell-action tine-grid-cell-localized';
                        row1Right.setAttribute('ext:qtip',  qtip);
                        
                        row.appendChild(row1Left);
                        row.appendChild(row1Right);
                        
                        metaData.css = 'tine-grid-cell-action-wrap'
                        return  row.outerHTML;
                    }
                    break;
            }

            if (renderer && _.get(fieldDefinition, 'uiconfig.translate')) {
                renderer = _.wrap(renderer, function(func, v) {
                    const app = Tine.Tinebase.appMgr.get(fieldDefinition.owningApp || fieldDefinition.appName || appName);
                    const [, ...args] = arguments;
                    args[0] = v ? app.i18n._hidden(v) : '';
                    return func.apply(this, args);
                });
            }

            return renderer;
        },

        /**
         * returns renderer for given field
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {gridPanel|displayPanel} optional.
         * @return {Function}
         */
        get: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            var renderer = renderers[categoryKey] ? renderers[categoryKey] : renderers[genericKey];

            // check for common names
            if (! renderer) {
                renderer = this.getByFieldname(fieldName);
            }

            // check for known datatypes
            if (! renderer) {
                renderer = this.getByDataType(appName, modelName, fieldName, String(fieldName).match(/^#.+/));
            }

            if (!renderer && String(fieldName).match(/^#.+/)) {
                var cfConfig = Tine.widgets.customfields.ConfigManager.getConfig(appName, modelName, fieldName.replace(/^#/,''));
                renderer = Tine.widgets.customfields.Renderer.get(appName, cfConfig);
            }

            // finally apply generic wrap for dynamic metadata provider
            const wrap = _.wrap(renderer ? renderer : this.defaultRenderer, function(func, value, metaData, record, rowIndex, colIndex, store) {
                if (_.isFunction(wrap.transformMetaData)) {
                    metaData = wrap.transformMetaData(value, metaData, record, rowIndex, colIndex, store);
                }
                return func(value, metaData, record, rowIndex, colIndex, store);
            });

            return wrap
        },

        /**
         * register renderer for given field
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {Function} renderer
         * @param {String} category {gridPanel|displayPanel} optional.
         * @param {Object} scope to call renderer in, optional.
         */
        register: function(appName, modelName, fieldName, renderer, category, scope) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            renderers[category ? categoryKey : genericKey] = scope ? renderer.createDelegate(scope) : renderer;
        },

        /**
         * check if a renderer is explicitly registered
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {gridPanel|displayPanel} optional.
         * @return {Boolean}
         */
        has: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            return (renderers[categoryKey] ? renderers[categoryKey] : renderers[genericKey]) ? true : false;
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
        },

        getDateFormat: function(field) {
            return _.get(field, 'fieldDefinition.uiconfig.format', ['wkday', 'medium']);
        },

        getDateTimeFormat(field) {
            return _.get(field, 'fieldDefinition.uiconfig.format', {
                Date: ['wkday', 'medium'],
                Time: ['medium']
            });
        }
    };
}();
