/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeeFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.AttendeeFilterModel = Ext.extend(Tine.widgets.grid.FilterModel, {
    /**
     * @property Tine.Tinebase.Application app
     */
    app: null,
    
    field: 'attender',
    defaultOperator: 'in',
    
    /**
     * @private
     */
    initComponent: function() {
        Tine.Calendar.AttendeeFilterModel.superclass.initComponent.call(this);
        
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.operators = ['in'/*, 'notin'*/];
        this.label = this.app.i18n._('Attendee');
        this.gender = this.app.i18n._('GENDER_Attendee');
        
        this.defaultValue = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
            user_type: 'user',
            user_id: Tine.Tinebase.registry.get('userContact')
        });
    },
    
    /**
     * value renderer
     * 
     * @param {Ext.data.Record} filter line
     * @param {Ext.Element} element to render to 
     */
    valueRenderer: function(filter, el) {
        var value = new Tine.Calendar.AttendeeFilterModelValueField({
            app: this.app,
            filter: filter,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            renderTo: el
        });
        value.on('select', this.onFiltertrigger, this);
        return value;
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['calendar.attendee'] = Tine.Calendar.AttendeeFilterModel;

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeeFilterModelValueField
 * @extends     Ext.ux.form.LayerCombo
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.AttendeeFilterModelValueField = Ext.extend(Ext.ux.form.LayerCombo, {
    hideButtons: false,
    layerAlign : 'tr-br?',
    minLayerWidth: 400,
    layerHeight: 300,
    
    lazyInit: true,
    
    formConfig: {
        labelAlign: 'left',
        labelWidth: 30
    },
    
    attendeeData: null,
    
    initComponent: function() {
        this.on('beforecollapse', this.onBeforeCollapse, this);
        
        this.supr().initComponent.call(this);
    },
    
    getFormValue: function() {
        return this.attendeeFilterGrid.getValue();
    },
    
    getItems: function() {
        
        this.attendeeFilterGrid = new Tine.Calendar.AttendeeFilterGrid({
            title: this.app.i18n._('Select Attendee'),
            height: this.layerHeight - 40 || 'auto',
            showNamesOnly: true,
            showMemberOfType: true,
            onStoreChange: Ext.emptyFn
        });
        this.attendeeFilterGrid.store.on({
            'add': function (store) {
                this.action_ok.setDisabled(this.attendeeFilterGrid.store.getCount() === 1);
            },
            'remove': function (store) {
                this.action_ok.setDisabled(this.attendeeFilterGrid.store.getCount() === 1);
            },
            scope: this
        });
        
        var items = [this.attendeeFilterGrid];
        
        return items;
    },
    
    /**
     * cancel collapse if ctx menu is shown
     */
    onBeforeCollapse: function() {
        
        return (!this.attendeeFilterGrid.ctxMenu || this.attendeeFilterGrid.ctxMenu.hidden) &&
                !this.attendeeFilterGrid.editing;
    },
    
    /**
     * @param {String} value
     * @return {Ext.form.Field} this
     */
    setValue: function(value) {
        value = Ext.isArray(value) ? value : [value];
        this.attendeeData = value;
        this.currentValue = [];
        
        var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(value);
        
        var a = [];
        attendeeStore.each(function(attender) {
            this.currentValue.push(attender.data);
            var name = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('user_id'), {noIcon: true}, attender);
            //var status = Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderStatus.call(Tine.Calendar.AttendeeGridPanel.prototype, attender.get('status'), {}, attender);
            a.push(name/* + ' (' + status + ')'*/);
        }, this);
        
        this.setRawValue(a.join(', '));
        return this;
        
    },
    
    /**
     * sets values to innerForm
     */
    setFormValue: function(value) {
        this.attendeeFilterGrid.setValue(this.attendeeData);
    }
});
