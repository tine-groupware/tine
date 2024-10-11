/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.widgets.customfields');

/**
 * manages key field renderers
 * 
 * @namespace   Tine.widgets.customfields
 * @class       Tine.widgets.customfields.Renderer
 * @singleton
 */
Tine.widgets.customfields.Renderer = function(){
    var renderers = {};
    
    return {

        /**
         * returns key field record renderer
         * 
         * @param {String/Application}  app
         * @param {Record}              cfConfig 
         * @param {String}              what pipe seperated field with text|icon
         * @return Ext.data.Store
         */
        get: function(app, cfConfig, what) {
            var appName = Ext.isString(app) ? app : app.appName,
                app = Tine.Tinebase.appMgr.get(appName),
                cfDefinition = cfConfig.get('definition'),
                cfName = cfConfig.get('name'),
                what = what ? what : 'text|icon',
                whatParts = what.split('|'),
                key = appName + cfConfig.id + what;
                
            if (! renderers[key]) {
                if (['keyfield' /*, 'bool', 'boolean'*/].indexOf(Ext.util.Format.lowercase(cfDefinition.type)) > -1) {
                    // NOTE existingkeyfields might come from an other app!
                    var app = cfDefinition.options && Ext.isString(cfDefinition.options.app) ? cfDefinition.options.app : app;
                    var keyFieldName = cfDefinition.options && Ext.isString(cfDefinition.options.keyFieldName) ? cfDefinition.options.keyFieldName : cfName;
                    renderers[key] = function(value, metaData, record) {
                        var customfields = _.get(record, 'data.customfields', {});
                        return Tine.Tinebase.widgets.keyfield.Renderer.render(app, keyFieldName, customfields[cfName]);
                    };
                    
                } else if (['record'].indexOf(Ext.util.Format.lowercase(cfDefinition.type)) > -1 &&
                    _.get(window, cfDefinition.recordConfig.value.records)) {
                    renderers[key] = function(value, metaData, record) {
                        var customfields = _.get(record, 'data.customfields', {}),
                            recordClass = eval(cfDefinition.recordConfig.value.records),
                            cfData = _.get(customfields, cfName, customfields),
                            record = Tine.Tinebase.data.Record.setFromJson(cfData, recordClass);

                        return record.getTitle();
                    };
                } else {
                    renderers[key] = function(value, metaData, record) {
                        var customfields = _.get(record, 'data.customfields', {});
                        switch (cfDefinition.type)
                        {
                            case 'date':
                                return Tine.Tinebase.common.dateRenderer(customfields[cfName]);
                            case 'datetime':
                                return Tine.Tinebase.common.dateTimeRenderer(customfields[cfName]);
                            case 'time':
                                return Tine.Tinebase.common.timeRenderer(customfields[cfName]);
                            case 'boolean':
                                return Tine.Tinebase.common.booleanRenderer(customfields[cfName]);
                            case 'recordList':
                                return Tine.widgets.customfields.Renderer.recordListRenderer(cfDefinition, customfields[cfName]);
                            default:
                                return Ext.util.Format.htmlEncode(customfields[cfName] || "");
                        }
                    };
                }
            }
            
            return renderers[key];
        },

        recordListRenderer: function(cfDefinition, value) {
            var title = '';
            Ext.each(value, function (value) {
                var recordClass = eval(cfDefinition.recordListConfig.value.records);
                title += Ext.isObject(value) && value.hasOwnProperty(recordClass.getMeta('titleProperty'))
                    ? value[recordClass.getMeta('titleProperty')] + ' '
                    : value + '';
            })
            return title;
        },

        
        /**
         * render a given value
         * 
         * @param {String/Application}  app
         * @param {Record}              cfConfig 
         * @return Ext.data.Store
         */
        render: function(app, cfConfig, id) {
            var renderer = this.get(app, cfConfig);
            
            return renderer(id);
        },

        renderAll: function(app, model, data) {
            var cfDefs = Tine.widgets.customfields.ConfigManager.getConfigs(app, model),
                html = '';

            Ext.each(cfDefs, function(cfDef) {
                var renderedValue = this.render(app, cfDef, data);

                html += '<div class="customfield-rendered-row print-single-details-row">' +
                    '<span class="customfield-rendered-label">'+ cfDef.get('definition').label + '</span>' +
                    '<span class="customfield-rendered-value">'+ renderedValue + '</span>' +
                '</div>';
            }, this);

            return html;
        },

        /**
         * register a custom renderer
         * 
         * @param {String/Application}  app
         * @param {Record}              cfConfig 
         * @param {Function}            renderer
         */
        register: function(app, cfConfig, renderer) {
            var appName = Ext.isString(app) ? app : app.appName,
                cfDefinition = cfConfig.get('definition'),
                cfName = cfConfig.get('name'),
                key = appName + cfConfig.id;
                
            renderers[key] = renderer;
        }
    }
}();
