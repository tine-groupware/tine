/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.customfields');

/**
 * @namespace   Tine.widgets.customfields
 * @class       Tine.widgets.customfields.FilterModel
 * @extends     Tine.widgets.grid.FilterModel
 */
Tine.widgets.customfields.FilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,
    
    /**
     * @cfg {Record} cfConfig
     */
    cfConfig: null,
    
    valueType: 'customfield',
    
    /**
     * @private
     */
    initComponent: function() {
        this.field = 'customfield:' + this.cfConfig.id;
        this.cfDefinition = this.cfConfig.get('definition');
        this.label =  this.cfDefinition.label;

        switch (this.cfDefinition.type) {
            case 'record':
            case 'keyField':
                // Not used @see {Tine.widgets.customfields.FilterModel.prototype.getCustomfieldFilters(this.recordClass)}
                break;
            case 'integer':
            case 'int':
                this.valueType = 'number';
                break;
            case 'boolean':
                this.valueType = 'bool';
                break;
            default:
                this.valueType = this.cfDefinition.type;
                break;
        }
        
        Tine.widgets.customfields.FilterModel.superclass.initComponent.call(this);
    },
    
    /**
     * returns valueType of given filter
     * 
     * added for cf of type record and keyField
     * 
     * @param {Record} filter
     * @return {String}
     */
    getValueType: function (filter) {
        var operator  = filter.get('operator') ? filter.get('operator') : this.defaultOperator,
            valueType = 'selectionComboBox';

        return valueType;
    },
    
    /**
     * called on operator change of a filter row
     * 
     * modified for cf of type record and keyField
     * 
     * @private
     */
    onOperatorChange: function (filter, newOperator) {
        this.supr().onOperatorChange.call(this, filter, newOperator);
        
        if (['record', 'keyField'].indexOf(this.cfDefinition.type) !== -1) {
            var valueType = this.getValueType(filter);
            
            for (var valueField in filter.valueFields) {
                if (filter.valueFields.hasOwnProperty(valueField)) {
                    filter.valueFields[valueField][valueField === valueType ? 'show' : 'hide']();
                }
            }
            
            filter.formFields.value = filter.valueFields[valueType];
            if (valueType === 'selectionComboBox' && Ext.isArray(filter.formFields.value.value)) {
                filter.formFields.value.setValue(filter.formFields.value.value[0]);
            }
        }
    },


    /**
     * get custom field filter for filter toolbar
     *
     * @return {Array}
     */
    getCustomfieldFilters: function(recordClass) {
        var modelName = recordClass.getMeta('appName') + '_Model_' + recordClass.getMeta('modelName'),
            cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(recordClass.getMeta('appName'), modelName),
            result = [];
        Ext.each(cfConfigs, function(cfConfig) {
            try {
                var cfDefinition = cfConfig.get('definition');
                switch (cfDefinition.type) {
                    case 'record':
                        if (_.get(window, cfDefinition.recordConfig.value.records)) {
                            result.push({
                                filtertype: 'foreignrecord',
                                label: cfDefinition.label,
                                app: this.app,
                                ownRecordClass: this.recordClass,
                                foreignRecordClass: eval(cfDefinition.recordConfig.value.records),
                                linkType: 'foreignId',
                                ownField: 'customfield:' + cfConfig.id,
                                pickerConfig: cfDefinition.recordConfig.additionalFilterSpec ? {
                                    additionalFilterSpec: cfDefinition.recordConfig.additionalFilterSpec
                                } : null
                            });
                        }
                        break;
                    case 'keyField':
                        result.push({filtertype:'tine.widget.keyfield.filter',field:'customfield:' + cfConfig.id, app: this.app, keyfieldName: cfConfig.get('name') , label:cfDefinition.label});
                        break;
                    default:
                        result.push({filtertype: 'tinebase.customfield', app: this.app, cfConfig: cfConfig});
                        break;
                }

            } catch (e) {
                Tine.log.warn('CustomfieldFilters ' + cfDefinition.label + ' doesnt create');

            }
        }, this);


        return result;
    },
        
    /**
     * cf value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    cfValueRenderer: function(filter, el) {        
        var valueType   = this.getValueType(filter);
                    
        filter.valueFields = {};
        
        filter.valueFields.selectionComboBox = Tine.widgets.customfields.Field.get(this.app, this.cfConfig, {
            hidden: valueType !== 'selectionComboBox',
            minListWidth: 350,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            resizable: true,
            filter: filter,
            renderTo: el,
            listeners: {
                scope: this,
                'specialkey': function (field, e) {
                    if (e.getKey() === e.ENTER) {
                        this.onFiltertrigger();
                    }
                },
                'select': this.onFiltertrigger
            }
        });

        return filter.valueFields[valueType];
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.customfield'] = Tine.widgets.customfields.FilterModel;
