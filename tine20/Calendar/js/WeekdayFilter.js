/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar.
 * @class       Tine.Calendar.TagFilter
 * @extends     Tine.widgets.grid.FilterModel
 */
Tine.Calendar.WeekdayFilter = Ext.extend(Tine.widgets.grid.FilterModel, {
    app: null,

    field: 'weekday',

    operators: ['startson', 'endson'],

    defaultOperator: 'startson',

    initComponent: function () {
        Tine.Calendar.WeekdayFilter.superclass.initComponent.call(this);

        this.app = this.app || Tine.Tinebase.appMgr.get('Calendar');

        this.label = this.app.i18n._('Event Weekday');
    },

    valueRenderer: function (filter, el) {
        const value = new Tine.Calendar.WeekdayFilterValueField({
            app: this.app,
            filter: filter,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });

        value.on('select', this.onFiltertrigger, this);
        value.onCheckboxSelect(null, null);

        return value;
    }
});
Tine.widgets.grid.FilterToolbar.FILTERS['calendar.weekday'] = Tine.Calendar.WeekdayFilter;

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.RruleFilterValueField
 * @extends     Ext.ux.form.LayerCombo
 *
 * @author      Michael Spahn <m.spahn@metaways.de>
 */
Tine.Calendar.WeekdayFilterValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,

    lazyInit: false,

    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },

    initComponent: function () {
        this.supr().initComponent.apply(this, arguments);
    },

    getFormValue: function () {
        let i = 0, d, data = [];
        for (; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7;
            const result = this.bydayItems[i].getValue() ? d : false;
            data.push(result);
        }
        return data;
    },

    getItems: function () {
        this.bydayItems = [];
        let i = 0, d;
        for (; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7;
            this.bydayItems.push(new Ext.form.Checkbox({
                boxLabel: Date.dayNames[d],
                name: Tine.Calendar.RrulePanel.prototype.wkdays[d],
                columnWidth: 1,
                readOnly: false,
                disabled: false,
                checked: false,
                listeners: {scope: this, check: this.onCheckboxSelect}
            }))
        }

        return this.bydayItems;
    },

    onCheckboxSelect: function (cb, checked) {
        const selection = [];
        this.bydayItems.forEach((item) => {
            if (item.getValue()) {
                selection.push(item.boxLabel);
            }
        })
        this.setRawValue(selection.join(', '));
        this.setValue(this.getFormValue());
    },

    setValue: function (value) {
        value = Ext.isArray(value) ? value : [value];

        this.setFormValue(value);

        return this.supr().setValue.apply(this, [value]);
    },

    setFormValue: function(value) {
        if (!value) return ;
        value.forEach((data, idx) => {
            this.bydayItems[idx].setValue(!!data);
        })
    }
});

