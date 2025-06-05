/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * Model of filter
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.MonthFilter
 * @extends     Tine.widgets.grid.FilterModel
 */

Tine.widgets.grid.MonthFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    
    valueType: 'date',
    defaultValue: 'monthLast',
    dateFilterSupportsPeriod: false,

    appName: null,
    label: null,
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.widgets.grid.MonthFilter.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get(this.appName);
        this.label = this.label ? this.app.i18n._hidden(this.label) : i18n._("Month");
        
        this.operators = ['within', 'before', 'after', 'equals'];
    },
    
    /**
     * called on operator change of a filter row
     * @private
     */
    onOperatorChange: function(filter, newOperator) {
        filter.set('operator', newOperator);
        filter.set('value', '');
        
        // for date filters we need to rerender the value section
        switch (newOperator) {
            case 'within':
                filter.numberfield.hide();
                filter.datePicker.hide();
                filter.monthPicker.hide();
                filter.withinCombo.show();
                filter.formFields.value = filter.withinCombo;
                break;
            case 'inweek':
                filter.withinCombo.hide();
                filter.datePicker.hide();
                filter.monthPicker.hide()
                filter.numberfield.show();
                filter.formFields.value = filter.numberfield;
                break;
            case 'equals':
                filter.numberfield.hide()
                filter.withinCombo.hide()
                filter.datePicker.hide()
                filter.monthPicker.show()
                filter.formFields.value = filter.monthPicker
                break;
            default:
                filter.withinCombo.hide();
                filter.numberfield.hide();
                filter.monthPicker.hide()
                filter.datePicker.show();
                filter.formFields.value = filter.datePicker;
        }

        var width = filter.formFields.value.el.up('.tw-ftb-frow-value').getWidth() -10;
        if (filter.formFields.value.wrap) {
            filter.formFields.value.wrap.setWidth(width);
        }
        filter.formFields.value.setWidth(width);
    },
    
    /**
     * render a date value
     * 
     * we place a picker and a combo in the dom element and hide the one we don't need yet
     */
    dateValueRenderer: function(filter, el) {
        var operator = filter.get('operator') ? filter.get('operator') : this.defaultOperator;
        const me = this

        if (! filter.monthPicker) {
            filter.monthPicker = new Ext.form.ComboBox({
                hidden: operator !== 'equals',
                filter: filter,
                renderTo: el,
                mode: 'local',
                lazyInit: false,
                forceSelection: true,
                hideTrigger: true,
                editable: false,
                listeners: {
                    'specialkey': function(field, e) {
                        if(e.getKey() == e.ENTER){
                            me.onFiltertrigger();
                        }
                    },
                    'select': function(c) {
                        if (c.getValue() != 'period') {
                            me.onFiltertrigger();
                        }
                    },
                    scope: this
                }
            })
            filter.monthPicker.origGetValue = filter.monthPicker.getValue;
            filter.monthPicker.getValue = function(v) {
                v = this.origGetValue()
                if (v && v.from && Ext.isDate(v.from)) {
                    v = v.from.format('Y-m')
                }
                if (this.pp.value && this.pp.value.from) {
                    v = this.pp.value.from.format('Y-m')
                }
                return v
            }
            filter.monthPicker.origSetValue = filter.monthPicker.setValue
            filter.monthPicker.setValue = function (v) {
                if (! this.pp) {
                    this.pp = new Ext.ux.form.PeriodPicker({
                        range: 'month',
                        availableRanges: 'month',
                        periodIncludesUntil: true,
                        cls: 'x-pp-combo',
                        'renderTo': this.wrap
                    });
                    filter.monthPicker.on('resize', function(cmp, w) {
                        this.pp.setSize(w-18);
                    });
                    this.pp.on('change', me.onFiltertrigger, me , {buffer: 250});
                    filter.monthPicker.on('resize', function(cmp, w) {
                        this.pp.setSize(w-18);
                    });
                }
                this.pp.show()
                if (v.from) {
                    this.pp.setValue(v)
                    return
                }

                let value = {from: null, until: null}
                if (Ext.isString(v) && v.match(/^[0-9]{4}-[0-9]{2}$/)) {
                    value.from = new Date(v+'-01')
                    value.until = new Date(v+'-28')
                }
                if (Ext.isDate(v)) {
                    value.from = v
                    value.until = v
                }
                this.pp.setValue(value)
            }

            filter.monthPicker.origOnSelect = filter.monthPicker.onSelect;
            filter.monthPicker.onSelect = function() {
                this.manualSelect = true;
                return this.origOnSelect.apply(this, arguments)
            }

            const today = new Date()
            let monthValue = today.format('Y-m')
            if (filter.data.value) {
                if (filter.data.value.from) {
                    monthValue = filter.data.value
                }

                if (filter.data.value.toString().match(/^[0-9]{4}-[0-9]{2}$/)) {
                    monthValue = filter.data.value
                }
            }
            filter.monthPicker.setValue(monthValue)
        }
        
        if (operator != 'equals') {
            return Tine.widgets.grid.MonthFilter.superclass.dateValueRenderer.call(this, filter, el);
        }
        
        return filter.textfield;
    },
    /**
     * returns past operators for date fields, may be overridden
     * 
     * @return {Array}
     */
    getDatePastOps: function() {
        return [
            ['monthThis',       i18n._('this month')],
            ['monthLast',       i18n._('last month')],
            ['quarterThis',     i18n._('this quarter')],
            ['quarterLast',     i18n._('last quarter')],
            ['yearThis',        i18n._('this year')],
            ['yearLast',        i18n._('last year')]
        ];
    },
    
    /**
     * returns future operators for date fields, may be overridden
     * 
     * @return {Array}
     */
    getDateFutureOps: function() {
        return [
        ];
    }
});