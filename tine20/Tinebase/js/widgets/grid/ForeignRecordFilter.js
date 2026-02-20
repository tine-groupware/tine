/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * Foreign Record Filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.ForeignRecordFilter
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * <p>Filter for foreign records</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 */
Tine.widgets.grid.ForeignRecordFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    
    /**
     * @cfg {Application} app (required)
     */
    app: null,
    
    /**
     * @cfg {Record} ownRecordClass own record class for generic filter row
     */
    ownRecordClass: null,
    
    /**
     * @cfg {Record} foreignRecordClass needed for explicit defined filters
     */
    foreignRecordClass : null,

    /**
     * @cfg {String} foreignRefIdField optional, defaults to idProperty
     * might differ for foreignRecords (many to many relation) filter
     *  -> foreignRefId is not yet part of modelConfiguration :-(
     */
    foreignRefIdField: null,

    /**
     * @cfg {String} linkType {relation|foreignId} needed for explicit defined filters
     */
    linkType: 'relation',
    
    /**
     * @cfg {String} filterName server side filterGroup Name, needed for explicit defined filters
     */
    filterName: null,
    
    /**
     * @cfg {String} ownField for explicit filterRow
     */
    ownField: null,
    
    /**
     * @cfg {String} editDefinitionText untranslated edit definition button text
     */
    editDefinitionText: 'Edit definition', // i18n._('Edit definition')
    
    /**
     * @cfg {Object} optional picker config
     */
    pickerConfig: null,
    
    /**
     * @cfg {String} startDefinitionText untranslated start definition button text
     */
    startDefinitionText: 'Start definition', // i18n._('Start definition')
    
    /**
     * @property this filterModel is the generic 'related to' filterRow
     * @type Boolean
     */
    isGeneric: false,
    
    field: 'foreignRecord',
    
    /**
     * ignore this php models (filter is not shown)
     * @cfg {Array}
     */
    ignoreRelatedModels: null,
    
    /**
     * @private
     */
    initComponent: function() {
        if (this.ownRecordClass) {
            this.ownRecordClass = Tine.Tinebase.data.RecordMgr.get(this.ownRecordClass);
        }
        if (!this.ownRecordClass) {
            this.ownRecordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName)
                || this.ftb.recordClass;
        }

        if (!this.foreignRecordClass) {
            // NOTE: def is equal to mc, but we can fake it in legacy models
            const def = this.ownRecordClass.getField(this.field)?.fieldDefinition;
            if (def && def.config) {
                this.foreignRecordClass = `${def.config.appName}.${def.config.modelName}`
            }
        }
        if (this.foreignRecordClass) {
            this.foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(this.foreignRecordClass);
        }
        
        // TODO: remove this when files can be searched
        this.ignoreRelatedModels = this.ignoreRelatedModels ? this.ignoreRelatedModels.push('Filemanager_Model_Node') : ['Filemanager_Model_Node'];
        // TODO: remove this when ProductAggregates can be searched (or move this exception to Sales app)
        this.ignoreRelatedModels.push('Sales_Model_ProductAggregate');
        
        if (this.ownField) {
            this.field = this.ownField;
        }

        if (_.get(this.foreignRecordClass?.getModelConfiguration(), 'denormalizationOf')) {
            this.foreignRefIdField = 'original_id';
        }

        this['init' + (this.isGeneric ? 'Generic' : 'Explicit')]();
        Tine.widgets.grid.ForeignRecordFilter.superclass.initComponent.call(this);
    },
    
    /**
     * init the generic foreign filter row
     */
    initGeneric: function() {
            
        this.label = i18n._('Related to');
        
        var operators = [];
        
        // linkType relations automatic list
        if (this.ownRecordClass.hasField('relations')) {
            var operators = [];
            Ext.each(Tine.widgets.relation.Manager.get(this.app, this.ownRecordClass, this.ignoreRelatedModels), function(relation) {
                if (Tine.Tinebase.common.hasRight('run', relation.relatedApp)) {
                    // TODO: leave label as it is?
                    var label = relation.text.replace(/ \(.+\)/,'');
                    operators.push({operator: {linkType: 'relation', foreignRecordClass: Tine.Tinebase.data.RecordMgr.get(relation.relatedApp, relation.relatedModel)}, label: label});
                }
            }, this);
        }
        // get operators from registry
        Ext.each(Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.get(this.ownRecordClass), function(def) {
            // translate label
            var foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(def.foreignRecordClass),
                appName = foreignRecordClass.getMeta('appName'),
                app = Tine.Tinebase.appMgr.get(appName),
                label = app ? app.i18n._hidden(def.label) : def.label;

            if(def.generic !== false) {
                operators.push({
                    operator: {
                        isRegisteredOperator: true,
                        linkType: def.linkType,
                        foreignRecordClass: foreignRecordClass,
                        filterName: def.filterName
                    }, label: label
                });
            }
        }, this);

        // we need this to detect operator changes
        Ext.each(operators, function(o) {o.toString = this.objectToString}, this);

        this.operatorStore = new Ext.data.JsonStore({
            fields: ['operator', 'label'],
            data: operators
        });

        if ( this.operatorStore.getCount() > 0) {
            this.defaultOperator = this.operatorStore.getAt(0).get('operator');
        }
    },

    /**
     * init an explicit filter row
     */
    initExplicit: function() {
        this.foreignField = this.foreignRefIdField;
        let i18n = new Locale.Gettext();
        i18n.textdomain('Tinebase');
        
        if (this.foreignRecordClass) {
            this.foreignField = this.foreignRecordClass.getMeta('idPropert<y');
            const foreignApp = Tine.Tinebase.appMgr.get(this.foreignRecordClass.getMeta('appName'));
            if (foreignApp) i18n = foreignApp.i18n;
            this.itemName = this.foreignRecordClass.getMeta('recordName');
        }

        if (! this.label) {
            if (this.foreignRecordClass) this.label = i18n.n_(this.foreignRecordClass.getMeta('recordName'), this.foreignRecordClass.getMeta('recordsName'), 1);
        } else {
            this.label = i18n._(this.label);
        }

        if (! this.operators) {
            this.operators = ['equals', 'not', 'in', 'notin', 'definedBy'];
            
            // allOf operator for records fields
            if (this.multipleForeignRecords || this.ownRecordClass && _.get(this.ownRecordClass.getModelConfiguration(), 'fields.'+ this.ownField + '.type') === 'records') {
                this.operators.push('allOf');
            }
        }


        if (this.ownRecordClass && this.foreignRecordClass) {
            if (!this.independentRecords && _.get(this.ownRecordClass.getField(this.field), 'fieldDefinition.config.dependentRecords')) {
                const dataFields = _.difference(this.foreignRecordClass.getDataFields(), [_.get(this.ownRecordClass.getField(this.field), 'fieldDefinition.config.refIdField')]);
                if (dataFields.length === 1 && _.get(this.foreignRecordClass.getField(dataFields[0]), 'fieldDefinition.type') === 'record') {
                    // cross-records: skip cross-record level completely, have all operators
                    this.crossRecordClass = this.foreignRecordClass;
                    this.crossRecordForeignField = dataFields[0];
                    const foreignRecordConfig = _.get(this.foreignRecordClass.getField(dataFields[0]), 'fieldDefinition.config');
                    this.foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(foreignRecordConfig.appName, foreignRecordConfig.modelName);
                } else if (this.foreignRecordClass.getModelConfiguration()?.isMetadataModelFor) {
                    // metadata-records: equals should work on metadata-field-record, definedBy should work on metadata-record (as is)
                    this.metaDataRecordClass = this.foreignRecordClass;
                    this.metaDataForField = this.metaDataRecordClass.getModelConfiguration().isMetadataModelFor;
                    const metaDataForRecordConfig = _.get(this.metaDataRecordClass.getField(this.metaDataForField), 'fieldDefinition.config');
                    this.foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(metaDataForRecordConfig.appName, metaDataForRecordConfig.modelName);
                } else if (this.foreignRecordClass.getModelConfiguration()?.denormalizationOf) {
                    // denormalization -> filter for original model
                    this.denormalizationRecordClass = this.foreignRecordClass;
                    this.foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(this.foreignRecordClass.getModelConfiguration().denormalizationOf);
                } else {
                    // we have no api's to pick foreign records - and it makes no sense
                    this.operators = ['definedBy'];
                    this.defaultOperator = 'definedBy';
                }
            }

            // get operators from registry
            Ext.each(Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.get(this.ownRecordClass), function (def) {
                // translate label
                const foreignRecordClass = Tine.Tinebase.data.RecordMgr.get(def.foreignRecordClass);
                const appName = foreignRecordClass.getMeta('appName');
                const app = Tine.Tinebase.appMgr.get(appName);
                const label = app ? app.i18n._hidden(def.label) : def.label;

                if (foreignRecordClass === this.foreignRecordClass) {
                    this.operators.push({
                        operator: Ext.apply(def, {
                            foreignRecordClass: foreignRecordClass
                        }),
                        label: label
                    });
                }
            }, this);
        }


        if (! this.defaultOperator) {
            this.defaultOperator = 'equals';
        }
    },
    
    /**
     * onDefineRelatedRecord
     * 
     * @param {} filter
     */
    onDefineRelatedRecord: function(filter) {
        Tine.log.debug('Tine.widgets.grid.ForeignRecordFilter::onDefineRelatedRecord() - filter:');
        Tine.log.debug(filter);
        
        if (! filter.toolbar) {
            this.createRelatedRecordToolbar(filter);
        }
        
        this.ftb.setActiveSheet(filter.toolbar);
        filter.formFields.value.setText(window.i18n._(this.editDefinitionText));
    },
    
    /**
     * get related record value data
     * 
     * NOTE: generic filters have their foreign record definition in the values
     */
    getRelatedRecordValue: function(filter) {
        var _ = window.lodash,
            me = this,
            filters = filter.toolbar ? filter.toolbar.getValue() : [],
            foreignRecordClass = filter.foreignRecordDefinition.foreignRecordClass,
            value;
            
        if (this.isGeneric) {
            value = {
                appName: foreignRecordClass.getMeta('appName'),
                modelName: foreignRecordClass.getMeta('modelName'),
                linkType: filter.foreignRecordDefinition.linkType,
                filterName : filter.foreignRecordDefinition.filterName,
                filters: filters
            };
            
        } else {
            value = filters;

            var operator = filter.get('operator') || filter.formFields.operator.origGetValue(),
                registeredOperator = _.get(_.find(this.operators, function(o) { return _.get(o, 'operator.filterName') == operator;}), 'operator', false);
            let [op, options] = me.parseOperator(filter.get('operator'));

            if (registeredOperator) {
                value.push({
                    filterName: registeredOperator.filterName,
                    field: ':' + registeredOperator.field,
                    operator: registeredOperator.operator,
                    value: filter.formFields.value.value
                });
            } else {
                // get value for idField if our own operator is not definedBy
                let opMap = {
                    not: 'equals', // we auto switch definedBy to notDefinedBy for not filters
                    notin: 'in', // we auto switch definedBy to notDefinedBy for notin filters
                    allOf: 'in' // allOf is a setOperator
                };

                if (op != 'definedBy') {
                    value.push({
                        field: ':' + (me.foreignRefIdField || foreignRecordClass.getMeta('idProperty')),
                        operator: opMap[op] || op,
                        value: filter.formFields.value.value
                    });
                }

                // get values of filters of our toolbar we are superfilter for (left hand stuff)
                this.ftb.filterStore.each(function (filter) {
                    var filterModel = this.ftb.getFilterModel(filter);
                    if (filterModel.superFilter && filterModel.superFilter == this) {
                        var filterData = this.ftb.getFilterData(filter);
                        value.push(filterData);
                    }
                }, this);
            }
            if (this.crossRecordClass) {
                value = [{'field': this.crossRecordForeignField, operator: 'definedBy?condition=and&setOperator=oneOf', value: value}]
            } else if (this.metaDataForField && operator !== 'definedBy') {
                value = [{'field': this.metaDataForField, operator: 'definedBy?condition=and&setOperator=oneOf', value: value}]
            }
            if ((me.crossRecordClass || me.metaDataForField) && op !== 'definedBy' && !filter.formFields.value.value) {
                value = null;
            }

        }
        
        return value;
    },
    
    /**
     * set related record value data
     * @param {} filter
     */
    setRelatedRecordValue: function(filter) {
        var _ = window.lodash,
            me = this,
            value = filter.get('value'),
            operator = filter.get('operator') || filter.formFields.operator.origGetValue(),
            def = _.get(_.find(this.operatorStore?.data.items, function (o) {
                return String(_.get(o, 'data.operator')) === String(operator);
            }), 'data.operator', {});

        if (_.get(def, 'isRegisteredOperator') || ['equals', 'not', 'in', 'notin', 'allOf'].indexOf(operator) >= 0) {
            // NOTE: if setValue got called in the valueField internally, value is arguments[1] (createCallback)
            value = arguments.length ? arguments[1] : value;
            if ((me.crossRecordClass || me.metaDataForField) && _.isArray(value) && ! value.length) {
                value = '';
            }
            return filter.formFields.value?.origSetValue(value);
        }
        
        // generic: choose right operator : appname -> generic filters have no subfilters an if one day, no left hand once!
        if (this.isGeneric) {
            // get operator
            this.operatorStore.each(function(r) {
                var operator = r.get('operator'),
                    foreignRecordClass = operator.foreignRecordClass;

                if ((foreignRecordClass.getMeta('appName') === value.appName
                        && foreignRecordClass.getMeta('modelName') === value.modelName)
                 || (filter.foreignRecordDefinition.foreignRecordClass === operator.foreignRecordClass)) {
                    filter.formFields.operator.setValue(operator);
                    filter.foreignRecordDefinition = operator;
                    return false;
                }
            }, this);
            
            // set all content on childToolbar
            if (Ext.isObject(filter.foreignRecordDefinition) && value && Ext.isArray(value.filters) && value.filters.length) {
                if (! filter.toolbar) {
                    this.createRelatedRecordToolbar(filter);
                }
                
                filter.toolbar.setValue(value.filters);
                
                // change button text
                if (filter.formFields.value && Ext.isFunction(filter.formFields.value.setText)) {
                    filter.formFields.value.setText(window.i18n._(this.editDefinitionText));
                }
            }
            
            
        } else {
            if (! Ext.isArray(value)) return;

            if (this.crossRecordForeignField && value[0].field === this.crossRecordForeignField) {
                value = value[0].value;
                filter.formFields.operator.setValue(value[0].operator);
            } else if (this.metaDataForField && value[0].field === this.metaDataForField && operator !== 'definedBy') {
                value = value[0].value;
                filter.formFields.operator.setValue(value[0].operator);
                filter.set('operator', value[0].operator);
            }

            // explicit chose right operator /equals / in /definedBy: left sided values create (multiple) subfilters in filterToolbar
            var foreignRecordDefinition = filter.foreignRecordDefinition,
                foreignRecordClass = foreignRecordDefinition.foreignRecordClass,
                foreignRecordIdProperty = me.foreignRefIdField || foreignRecordClass.getMeta('idProperty'),
                parentFilters = [];
                
            Ext.each(value, function(filterData, i) {
                if (! Ext.isString(filterData.field)) return;
                
                if (filterData.implicit) parentFilters.push(filterData);
                    
                var parts = filterData.field.match(/^(:)?(.*)/),
                    leftHand = !!parts[1],
                    field = parts[2];
                
                if (leftHand) {
                    // leftHand id property and registered operators are handled below
                    if (field == foreignRecordIdProperty || filterData.filterName) {
                        return;
                    }

                    // move filter to leftHand/parent filterToolbar
                    if (this.ftb.getFilterModel(filterData.field)) {
                        // ftb might have a record with this id
                        // and we can't keep it yet
                        delete filterData.id;
                        this.ftb.addFilter(new this.ftb.record(filterData));
                    }
                    
                    parentFilters.push(filterData);
                }
            }, this);
            
            // remove parent filters
            Ext.each(parentFilters, function(filterData) {value.remove(filterData);}, this);
            
            // if there where no remaining childfilters, hide this filterrow
            if (! value.length)  {
                // prevent loop
                filter.set('value', '###NOT SET###');
                filter.set('value', '');
                
                filter.formFields.operator.setValue(this.defaultOperator);
                this.onOperatorChange(filter, this.defaultOperator, false);
                
                // if (not empty value through operator chage)
                Tine.log.info('hide row -> not yet implemented');
            }
            
            // a single id filter is always displayed in the parent Toolbar with our own filterRow
            else if (value.length == 1 && [foreignRecordIdProperty, ':' + foreignRecordIdProperty].indexOf(value[0].field) > -1) {
                let finalOp = value[0].operator;
                
                // switch back negations of notDefinedby operator
                let [op, options] = this.parseOperator(operator);
                if (op === 'notDefinedBy') {
                    let opMap = {
                        'equals': 'not',
                        'in': 'notin'
                    };
                    finalOp = opMap[finalOp];
                }
                
                if (_.get(options, 'setOperator') === 'allOf') {
                    finalOp = 'allOf';
                }

                filter.set('value', value[0].value);
                filter.formFields.operator.setValue(finalOp);
                this.onOperatorChange(filter, finalOp, true);
            }

            // a single registered filter is always displayed in the parent Toolbar with our own filterRow
            else if (value.length == 1 && (registeredOperator = _.get(_.find(this.operators, function(o) { return _.get(o, 'operator.filterName') == value[0].filterName;}), 'operator', false))) {
                filter.set('value', value[0].value);
                filter.formFields.operator.setValue(registeredOperator.filterName);
                this.onOperatorChange(filter, registeredOperator.filterName, true);
                filter.formFields.value.origSetValue(value[0].value)
            }
            
            // set remaining child filters
            else {
                if (! filter.toolbar) {
                    this.createRelatedRecordToolbar(filter);
                }
                
                filter.toolbar.setValue(value);
                
                filter.formFields.operator.setValue('definedBy');
            }
        }
        
    },
    
    /**
     * create a related record toolbar
     */
    createRelatedRecordToolbar: function(filter) {
        Tine.log.debug('Tine.widgets.grid.ForeignRecordFilter::createRelatedRecordToolbar() - filter:');
        Tine.log.debug(filter);
        
        var foreignRecordDefinition = filter.foreignRecordDefinition,
            foreignRecordClass = foreignRecordDefinition.foreignRecordClass,
            filterModels = foreignRecordClass.getFilterModel(),
            ftb = this.ftb;

        if (! filter.toolbar) {
            // add our subfilters in this toolbar (right hand)
            if (Ext.isFunction(this.getSubFilters)) {
                filterModels = filterModels.concat(this.getSubFilters());
            }

            const defaultFilter = foreignRecordClass.getMeta('defaultFilter');
            const filterModel = foreignRecordClass.getModelConfiguration()?.filterModel;

            filter.toolbar = new Tine.widgets.grid.FilterToolbar({
                app: this.app,
                recordClass: foreignRecordClass,
                title: this.crossRecordForeignField || this.metaDataForField ? this.label : null,
                filterModels: filterModels,
                defaultFilter: filterModel ? (filterModel[defaultFilter] ? defaultFilter : Object.keys(filterModel)[0]) : (defaultFilter || 'query')
            });
            
            ftb.addFilterSheet(filter.toolbar);
            
            // force rendering as we can't set values on non rendered toolbar atm.
            this.ftb.setActiveSheet(filter.toolbar);
            this.ftb.setActiveSheet(this.ftb);
        }
    },
    
    /**
     * operator renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    operatorRenderer: function (filter, el) {
        var operator,
            me = this;
        
        // init operator value
        filter.set('operator', filter.get('operator') ? filter.get('operator') : this.defaultOperator);
        
        if (! this.isGeneric) {
            operator = Tine.widgets.grid.ForeignRecordFilter.superclass.operatorRenderer.apply(this, arguments);
            filter.foreignRecordDefinition = {linkType: this.linkType, foreignRecordClass: this.foreignRecordClass, filterName: this.filterName}
        } else {
            operator = new Ext.form.ComboBox({
                filter: filter,
                id: 'tw-ftb-frow-operatorcombo-' + filter.id,
                mode: 'local',
                lazyInit: false,
                emptyText: i18n._('select a operator'),
                forceSelection: true,
                typeAhead: true,
                triggerAction: 'all',
                store: this.operatorStore,
                displayField: 'label',
                valueField: 'operator',
                value: filter.get('operator'),
                renderTo: el
            });
            operator.on('select', function(combo, newRecord, newKey) {
                if (combo.value != combo.filter.get('operator')) {
                    this.onOperatorChange(combo.filter, combo.value);
                }
            }, this);
            
            // init foreignRecordDefinition
            filter.foreignRecordDefinition = filter.get('operator');
        }
        
        operator.origGetValue = operator.getValue.createDelegate(operator);
        
        /**
         * get operator value
         *
         * NOTE: operator filed is composed of "<operator>[?<option1>=<value1>[&<optionN>=<valueN]]
         *     operator can be definedBy and notDefinedBy
         *     options (urlencoded) options
         *          condition: condition of the subfiltergroup (and or or)
         *          setOperator: result of subfilter must match oneOf or allOf our records
         *                      (allOf makes sense if our field is of type records (1:n n:m))
         *
         * NOTE: for historic reasons operator might be AND, which means definedBy?condition=and&setOperator=oneOf
         *
         * @return {string}
         */
        operator.getValue = function () {
            // auto switch operator for single line filters
            var op = operator.origGetValue(),
                opMap = {
                    not: 'notDefinedBy?condition=and&setOperator=oneOf',
                    notin: 'notDefinedBy?condition=and&setOperator=oneOf',
                    allOf: 'definedBy?condition=and&setOperator=allOf'
                };

            if ((me.crossRecordClass || me.metaDataForField) && op !== 'definedBy' && !filter.formFields.value.value) {
                return op;
            }

            if (_.isFunction(me.getCustomOperators)) {
                me.getCustomOperators().map((def) => {
                    opMap[def.operator] = def.opValue || def.operator;
                })
            }
            return opMap[op] || 'definedBy?condition=and&setOperator=oneOf';
        };
        
        return operator;
    },

    parseOperator: function(operator) {
        const parts = String(operator).split('?');
        return [parts[0], Ext.urlDecode(parts[1])];
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator, keepValue) {
        if (this.isGeneric) {
            filter.foreignRecordDefinition = newOperator;
        }

        const oldOperator = filter.get('operator');

        if (this.metaDataForField) {
            filter.foreignRecordDefinition.foreignRecordClass = newOperator === 'definedBy' ? this.metaDataRecordClass : this.foreignRecordClass;
            if (newOperator !== oldOperator) {
                filter.set('value', null);
            }
        }

        if (oldOperator != newOperator) {
            if (filter.toolbar) {
                filter.toolbar.destroy();
                delete filter.toolbar;
            }
        }
        
        filter.set('operator', newOperator);

        if (['equals', 'not'].indexOf(oldOperator) > -1 && ['in', 'allOf', 'notin'].indexOf(newOperator) > -1) {
            filter.set('value', _.compact([filter.get('value')]));
        }
        
        if (! keepValue 
            || [oldOperator, newOperator].indexOf('definedBy') > -1
            || ['equals', 'not'].indexOf(newOperator) > -1 && ['in', 'allOf', 'notin'].indexOf(oldOperator) > -1) {
            filter.set('value', '');
        }
        
        var el = Ext.select('tr[id=' + this.ftb.frowIdPrefix + filter.id + '] td[class^=tw-ftb-frow-value]', this.ftb.el).first();
        
        // NOTE: removeMode got introduced on ext3.1 but is not docuemented
        //       'childonly' is no ext mode, we just need something other than 'container'
        if (filter.formFields.value && Ext.isFunction(filter.formFields.value.destroy)) {
            filter.formFields.value.removeMode = 'childsonly';
            filter.formFields.value.destroy();
            delete filter.formFields.value;
        }
        
        filter.formFields.value = this.valueRenderer(filter, el);

        var width = filter.formFields.value.el.up('.tw-ftb-frow-value').getWidth() -10;
        if (filter.formFields.value.wrap) {
            filter.formFields.value.wrap.setWidth(width);
        }
        filter.formFields.value.setWidth(width);
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var _ = window.lodash,
            me = this,
            operator = filter.get('operator') ? filter.get('operator') : this.defaultOperator,
            def = _.get(_.find(this.operatorStore?.data.items, function (o) {
                return String(_.get(o, 'data.operator')) === String(operator);
            }), 'data.operator', {}),
            value;

        if ( _.get(def, 'isRegisteredOperator')) {
            operator = 'equals';
        }

        switch(operator) {
            case 'equals':
            case 'not':
            case 'in':
            case 'notin':
            case 'allOf':
                //@TODO find it
                var pickerRecordClass = this.foreignRecordClass || def.foreignRecordClass;
                if (this.foreignRefIdField && !this.denormalizationRecordClass) {
                    // many 2 many relation
                    var foreignRecordConfig = _.get(this.foreignRecordClass.getModelConfiguration(), 'fields.' + this.foreignRefIdField + '.config');
                    pickerRecordClass = Tine.Tinebase.data.RecordMgr.get(foreignRecordConfig.appName, foreignRecordConfig.modelName);
                }

                value = Tine.widgets.form.RecordPickerManager.get(pickerRecordClass.getMeta('appName'), pickerRecordClass, Ext.apply({
                    filter: filter,
                    blurOnSelect: true,
                    listWidth: 500,
                    listAlign: 'tr-br',
                    value: filter.data.value ? filter.data.value : this.defaultValue,
                    renderTo: el,
                    useEditPlugin: false,
                    allowMultiple: ['in', 'notin', 'allOf'].indexOf(operator) > -1
                }, this.pickerConfig));
                
                value.on('specialkey', function(field, e){
                     if(e.getKey() == e.ENTER){
                         this.onFiltertrigger();
                     }
                }, this);

                value.origSetValue = value.setValue.createDelegate(value);

                break;

            default:
                // cope with operator registry values
                if ( _.get(def, 'valueType') ) {
                    const backup = _.reduce(_.keys(def), function(bkup, key) {
                        return _.set(bkup, key, me[key]);
                    }, {});
                    _.assign(me, def);
                    value = Tine.widgets.grid.FilterModel.prototype.valueRenderer.call(me, filter, el);
                    _.assign(me, backup);

                    value.origSetValue = value.setValue.createDelegate(value);
                } else {
                    this.setRelatedRecordValue(filter);

                    if (!filter.formFields.value) {
                        value = new Ext.Button({
                            text: i18n._(this.startDefinitionText),
                            width: el.getWidth() -10,
                            filter: filter,
                            renderTo: el,
                            handler: this.onDefineRelatedRecord.createDelegate(this, [filter]),
                            scope: this
                        });

                        // show button
                        el.addClass('x-btn-over');

                        // change text if setRelatedRecordValue had child filters
                        if (filter.toolbar) {
                            value.setText(window.i18n._(this.editDefinitionText));
                        }

                    } else {
                        value = filter.formFields.value;
                    }
                }
                break;
        }

        value.setValue = this.setRelatedRecordValue.createDelegate(this, [filter], 0);
        value.getValue = this.getRelatedRecordValue.createDelegate(this, [filter]);
        
        return value;
    },
    
//    getSubFilters: function() {
//        var filterConfigs = this.foreignRecordClass.getFilterModel();
//        
//        Ext.each(filterConfigs, function(config) {
//            this.subFilterModels.push(Tine.widgets.grid.FilterToolbar.prototype.createFilterModel.call(this, config));
//        }, this);
//        
//        return this.subFilterModels;
//    },
    
    objectToString: function() {
        return Ext.encode(this);
    },
    
    onDestroy: function(filterRecord) {
        if(filterRecord.toolbar) {
            this.ftb.removeFilterSheet(filterRecord.toolbar);
            
            delete filterRecord.toolbar;
        }
        
    }
});
    
/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterRegistry
 * @singleton
 */
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry = function() {
    var operators = {};
    
    return {
        register: function(appName, modelName, operator) {
            var key = appName + '.' + modelName;
            if (! operators[key]) {
                operators[key] = [];
            }

            operators[key].push(operator);
        },
        
        get: function(appName, modelName) {
            if (Ext.isFunction(appName.getMeta)) {
                modelName = appName.getMeta('modelName');
                appName = appName.getMeta('appName');
            }
        
            var key = appName + '.' + modelName;
            
            return operators[key] || [];
        }
    };
}();

Tine.widgets.grid.FilterToolbar.FILTERS['foreignrecord'] = Tine.widgets.grid.ForeignRecordFilter;
