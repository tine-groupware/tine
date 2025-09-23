/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * central column manager
 * - get column for a given column
 * - register column for a given column
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ColumnManager
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @singleton
 */
Tine.widgets.grid.ColumnManager = function() {
    var columns = {};

    return {
        /**
         * const for category editDialog
         */
        CATEGORY_EDITDIALOG: 'editDialog',

        /**
         * const for category mainScreen
         */
        CATEGORY_PROPERTYGRID: 'mainScreen',

        /**
         * get column of well known column names
         *
         * @param {String} columnName
         * @return {Object}
         */
        getByFieldname: function(columnName) {
            var column = null;

            return column;
        },

        /**
         * get column by data type
         *
         * @param {String} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|mainScreen} optional.
         * @param {Object} config
         * @return {Object}
         */
        getByModelConfig: function(appName, modelName, fieldName, category, config) {
            const column = {};
            config.dataIndex  = config?.dataIndex ?? fieldName;
            fieldName = config.dataIndex;
            
            const recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName);
            const field = recordClass?.getField?.(fieldName) ?? { name: fieldName , type: 'auto'};
            const modelConfig = recordClass ? recordClass.getModelConfiguration() : null;
            let fieldDefinition = _.get(modelConfig, 'fields.' + fieldName , null);
            const app = Tine.Tinebase.appMgr.get(fieldDefinition?.owningApp || appName);
            
            // e.g. no access to owningApp
            if (! app) return null;
            
            if (fieldDefinition) {
                if (_.get(fieldDefinition, 'disabled') || _.get(fieldDefinition, 'uiconfig.disabled')) {
                    return null;
                }

                if (! Tine.Tinebase.fieldUiFeatureEnabled(fieldDefinition)) {
                    return null;
                }

                if (['text', 'fulltext'].indexOf(fieldDefinition.type) >= 0) {
                    config.hidden = true;
                }
                if (fieldDefinition.type === 'records') {
                    if (_.get(fieldDefinition, 'config.specialType') === 'localizedString') {
                        fieldDefinition.type = 'localizedString';
                    } else if (_.get(fieldDefinition, 'uiconfig.hasGridColumn', false)) {
                        config.width = 250;
                    } else {
                        // don't show multiple record fields unless explicitly configured (backward compability)
                        return null;
                    }
                }
                
                if (fieldDefinition.type === 'virtual') {
                    fieldDefinition = fieldDefinition.config || {};
                }
                // If no label exists, don't use in grid
                if (fieldDefinition?.disabled || !fieldDefinition?.label) return null;
                // don't show parent property in dependency of an editDialog
                if (this.editDialog && fieldDefinition.hasOwnProperty('config') && fieldDefinition.config.isParent) return null;
                // don't show record field if the user doesn't have the right on the application
                if (fieldDefinition.type === 'record' && !(fieldDefinition.config && fieldDefinition.config.doNotCheckModuleRight) 
                    && (! Tine.Tinebase.common.hasRight('view', fieldDefinition.config.appName, fieldDefinition.config.modelName.toLowerCase() + 's'))) {
                    return null;
                }
            }
            
            const uiConfig = this.getColumnUIConfig(field, fieldDefinition, null, app);
            Ext.applyIf(uiConfig, {
                minWidth: 50,
                defaultWidth: 80,
                maxWidth: 1000,
                hidden: false, // defaults to false
                sortable: true, // defaults to true
            });
            const resolvedConfig = this.resolveUIConfigWidth(config, uiConfig);
            
            Ext.apply(column, config);
            Ext.applyIf(column, resolvedConfig);
            
            // NOTE: app might be from owningApp whereas appName ist the models app
            const renderer = Tine.widgets.grid.RendererManager.get(appName, modelName, fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
            if (renderer) {
                column.renderer = renderer;
            }
            
            return column;
        },

        getColumnUIConfig(field = null, fieldDefinition = null,  refConfig = null, app = null) {
            const i18n = (fieldDefinition?.useGlobalTranslation || !app) ? window.i18n : app?.i18n;
            let type = fieldDefinition?.type ?? field.type ?? 'auto';
            const config = {
                id: refConfig?.id ?? field.name,
                dataIndex: refConfig?.dataIndex ?? field.name,
            };
            
            if (type === 'auto' && refConfig?.renderer && (!refConfig?.width || refConfig.width === 100)) {
                type = this.getTypeByRenderer(refConfig?.renderer);
            }
            
            if (!fieldDefinition) {
                const resolvedType = this.getTypeByFieldName(field.name);
                if (type === 'auto' || resolvedType !== field.name.toLowerCase()) {
                    type = resolvedType;
                }
            }
            
            if (['string', 'localizedString', 'text', 'numberablestr', 'fulltext'].includes(type)) {
                if (fieldDefinition) {
                    const length = fieldDefinition?.length ?? 40;
                    config.minWidth = 100;
                    config.defaultWidth = 100;
                    
                    if (length === 64) {
                        config.maxWidth = 200;
                    }
                    if (length === 86) {
                        config.maxWidth = 250;
                    }
                    if (length === 255) {
                        config.minWidth = 120;
                        config.defaultWidth = 150;
                        config.maxWidth = 1000;
                    }
                    if (fieldDefinition?.label) {
                        type = fieldDefinition.label;
                    }

                    if (fieldDefinition.specialType === 'currency') {
                        config.minWidth = 50;
                        config.maxWidth = 100;
                    }
                }
            }
            type = type.toLowerCase();
            
            if (['color'].includes(type)) {
                config.minWidth = 25;
                config.defaultWidth = 25;
                config.maxWidth = 40;
            }
            
            if (type === 'attachments') {
                config.minWidth = 25;
                config.defaultWidth = 25;
                config.maxWidth = 25;
                config.header = '<div class="action_attach tine-grid-row-action-icon"></div>';
                config.renderer = Tine.widgets.grid.attachmentRenderer;
                config.tooltip = window.i18n._('Attachments');
                config.resizeable = false;
            }

            if (type === 'image') {
                config.minWidth = 20;
                config.defaultWidth = 25;
                config.maxWidth = 50;
                config.header = '<div class="action_image tine-grid-row-action-icon"></div>';
                
                if (fieldDefinition?.label) {
                    const label = fieldDefinition.label;
                    config.header = label === 'Image' ? window.i18n._('Image') : window.i18n._(label);
                    config.tooltip = config.header;
                }
            }
            
            if (['hexcolor', 'bool', 'boolean', 'flags'].includes(type)) {
                config.minWidth = 40;
                config.defaultWidth = 50;
                config.maxWidth = 60;
            }
            
            if (['date', 'time', 'datetime_separated_time', 'datetime_separated_date'
                ,'data', 'datetime', 'datetime_separated_tz' 
            ].includes(type)) {
                let width = 120;
                let wkdayWidth = 0;
                if (field) {
                    const format = Tine.widgets.grid.RendererManager.getDateTimeFormat(field);
                    if ( _.indexOf(format?.Date ?? format, 'wkday') >= 0) wkdayWidth = 15;
                }
                 config.minWidth = 75 + wkdayWidth;
                 config.defaultWidth = width += wkdayWidth;
                 config.maxWidth = 160;
            }
            
            if (type === 'tag') {
                config.minWidth = 35;
                config.defaultWidth = 40;
                config.maxWidth = 100;
            }

            if (['integer', 'bigint', 'float', 'money', 'size'].includes(type)) {
                config.minWidth = 60;
                config.defaultWidth = 60;
                config.maxWidth = 100;
                
                if (fieldDefinition?.specialType === 'minutes') {
                    config.defaultWidth = config.maxWidth = 130;
                    config.minWidth = 40;
                }
            }
            
            if (type === 'id') {
                config.minWidth = 50;
                config.defaultWidth = 100;
                config.maxWidth = 300;
            }
            
            if (['description', 'name', 'title', 'email', 'url'].some(key => type === key)) {
                config.minWidth = 150;
                config.defaultWidth = 200;
                config.maxWidth = 1000;
            }
            
            if (type === 'number') {
                config.defaultWidth = 80;
                config.maxWidth = 200;
            }
            
            if (type === 'application') {
                config.minWidth = 100;
                config.defaultWidth = 150;
                config.maxWidth = 200;
            }
            
            if (type === 'type') {
                config.minWidth = 50;
                config.defaultWidth = 100;
                config.maxWidth = 200;
            }

            if (['user', 'record', 'relation', 'virtual', 'custom', 'foo', 'dynamicrecord', 'model', 'records'].includes(type)) {
                config.minWidth = 100;
                config.defaultWidth = 150;
                config.maxWidth = 1000;
                
                if (type === 'model') {
                    config.defaultWidth = 200;
                }
            }
            
            if (type === 'keyfield') {
                config.minWidth = 50;
                config.defaultWidth = 80;
                config.maxWidth = 1000;
                
                if (app && fieldDefinition?.name) {
                    try {
                        const store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(fieldDefinition.config?.application || app, fieldDefinition.name);
                        const data = store.getData();
                        const maxText = data.map((f) => f.i18nValue).reduce((longest, current) => {
                            return current.length > longest.length ? current : longest;
                        }, "");
                        const maxTextLength = Math.ceil(this.getTextWidth(maxText) / 10) * 10;
                        
                        const minText = data.map((f) => f.i18nValue).reduce((shortest, current) => {
                            return current.length < shortest.length ? current : shortest;
                        }, data[0].i18nValue);
                        const minTextLength = Math.ceil(this.getTextWidth(minText) / 10) * 10;
                        
                        config.minWidth = minTextLength + 50;
                        config.maxWidth = maxTextLength + 50;
                        config.defaultWidth = config.minWidth;
                    } catch (e) {
                        Tine.log.error(e);
                    }
                    config.minWidth = Math.max(config.defaultWidth, config.minWidth);
                }
            }
            
            if (fieldDefinition) {
                if(_.indexOf(['attachments', 'image'], fieldDefinition.type) >= 0) {
                    config.resizable = false;
                }
                if(_.indexOf([
                    'data', 'datetime_separated_date', 'datetime_separated_time', 'datetime_separated_tz', 
                    'money', 'integer', 'bool', 'boolean', 'float',
                ], fieldDefinition.type) >= 0) {
                    config.align = 'right';
                }
                if (fieldDefinition.hasOwnProperty('summaryType')) {
                    config.summaryType = fieldDefinition.summaryType;
                }
                if (fieldDefinition?.uiconfig?.responsiveLevel) {
                    config.responsiveLevel = fieldDefinition.uiconfig.responsiveLevel;
                }
                if (fieldDefinition.hasOwnProperty('shy')) {
                    if (fieldDefinition.shy) config.responsiveLevel = 'large';
                    config.hidden = !!fieldDefinition.shy;
                }
                if (fieldDefinition?.tooltip) {
                    config.tooltip = fieldDefinition.tooltip;
                }
                if (fieldDefinition?.label) {
                    config.label = fieldDefinition.label;
                }
                if (fieldDefinition.hasOwnProperty('sortable') && fieldDefinition.sortable === false) {
                    config.sortable = false;
                }
            }
            
            if (!config?.header && config?.label){
                config.header = i18n._(config.label);
            }
            
            if (!config?.tooltip && config?.header){
                config.tooltip = i18n._( config.tooltip ?? config.header);
            }
            
            if (!config?.width && config?.defaultWidth){
                config.width = config.defaultWidth;
            }

            Object.assign(config, fieldDefinition?.uiconfig?.columnConfig || {})
            return config;
        },
        
        getTypeByRenderer(renderer) {
            if (renderer?.name) {
                switch (renderer.name) {
                    case 'booleanRenderer':
                        return 'boolean';
                    case 'statusRenderer':
                        return 'type';
                    case 'dateTimeRenderer':
                        return 'datetime';
                    case 'dateRenderer':
                        return 'datetime_separated_date';
                    case 'usernameRenderer':
                    case 'accountRenderer':
                        return 'name';
                    case 'accountTypeRenderer':
                        return 'image';
                    case 'money':
                        return 'money';
                    default:
                        return 'auto';
                }
            }
            return 'auto';
        },
        
        getTypeByFieldName(fieldName) {
            const type = fieldName.toLowerCase();
            if (type === 'billed_in') return 'datetime';
            if (type === 'has_attachment') return 'attachments';
            if (type === 'id') return 'id';
            if ('tags' === type) return 'tag';
            if (['_by', 'record', '_id', 'id'].some(key => type.includes(key))) return 'record';
            if (['description', 'name', 'title'].some(key => type.includes(key))) return 'title';
            if (type.includes('model')) return 'model';
            if (type.includes('time')) return 'time';
            if (['email', 'url'].some(key => type.includes(key))) return 'email';
            if (type.includes('number')) return 'number';
            if (type.includes('application')) return 'application';
            if (type.includes('type')) return 'type';
            if (type.includes('image')) return 'image';
            
            return type;
        },
        
        resolveUIConfigWidth(defaultConfig, resolvedConfig) {
            // force width overwrite if width is set
            if (defaultConfig?.width && defaultConfig?.width !== 100) {
                const width = defaultConfig.width;
                const extendRange = width >= 100 ? 500 : 30;
                let defaultWidth = defaultConfig.defaultWidth ?? resolvedConfig.defaultWidth ?? width;
                let minWidth = defaultConfig.minWidth ?? resolvedConfig.minWidth ?? Math.max(25 , width - 30);
                let maxWidth = defaultConfig.maxWidth ?? resolvedConfig?.maxWidth ?? Math.min(1000 , width + extendRange);
                
                if (width > maxWidth) defaultWidth = width;
                if (width < minWidth) minWidth = width;
                
                resolvedConfig.minWidth = Math.min(minWidth, width);
                resolvedConfig.defaultWidth = defaultWidth;
                resolvedConfig.maxWidth = Math.max(maxWidth, width);
                resolvedConfig.width = width;
            }
            return resolvedConfig;
        },
        
        getTextWidth(text) {
            // Create a canvas element (this can be an offscreen canvas)
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            // The context will use the default font if none is set
            // Measure the text width
            const metrics = context.measureText(text);
            return metrics.width;
        },

        /**
         * returns column for given column
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|mainScreen} optional.
         * @param {Object} config
         * @return {Object}
         */
        get: function(appName, modelName, fieldName, category, config = {}) {
            appName = this.getAppName(appName);
            modelName = this.getModelName(modelName);
            const categoryKey = this.getKey([appName, modelName, fieldName, category]);
            const genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            let column = columns[categoryKey] ? columns[categoryKey] : columns[genericKey];
            
            // check for common names
            if (! column) {
                column = this.getByFieldname(fieldName);
            }

            // check for known datatypes
            if (! column) {
                column = this.getByModelConfig(appName, modelName, fieldName, category, config);
            }
            return column;
        },
        
        getResolvedColumnsConfig(configs, appName, modelName) {
            configs.forEach((config) => {
                const resolveConfig = this.get(appName, modelName, config.id, Tine.widgets.grid.ColumnManager.CATEGORY_PROPERTYGRID, config);
                Ext.applyIf(config, resolveConfig);
            });
            return configs;
        },

        /**
         * register renderer for given column
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {Object} column
         * @param {String} category {editDialog|mainScreen} optional.
         */
        register: function(appName, modelName, fieldName, column, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            columns[category ? categoryKey : genericKey] = column;
        },

        /**
         * check if a column is explicitly registered
         *
         * @param {String/Tine.Tinebase.Application} appName
         * @param {Record/String} modelName
         * @param {String} fieldName
         * @param {String} category {editDialog|mainScreen} optional.
         * @return {Boolean}
         */
        has: function(appName, modelName, fieldName, category) {
            var appName = this.getAppName(appName),
                modelName = this.getModelName(modelName),
                categoryKey = this.getKey([appName, modelName, fieldName, category]),
                genericKey = this.getKey([appName, modelName, fieldName]);

            // check for registered renderer
            return (columns[categoryKey] ? columns[categoryKey] : columns[genericKey]) ? true : false;
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
