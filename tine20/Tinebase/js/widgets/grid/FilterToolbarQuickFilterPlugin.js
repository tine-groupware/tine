/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FilterToolbarQuickFilterPlugin
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * This plugin provides an external filter field (quickfilter) as a plugin of a filtertoolbar.
 * The filtertoolbar itself will be hidden no filter is set.
 * 
 * @example
 <pre><code>
    // init quickfilter as plugin of filtertoolbar
    this.quickSearchFilterToolbarPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin();
    this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
        filterModels: Tine.Addressbook.Model.Contact.getFilterModel(),
        defaultFilter: 'query',
        filters: [],
        plugins: [
            this.quickSearchFilterToolbarPlugin
        ]
    });
    
    // put quickfilterfield in a toolbar
    this.tbar = new Ext.Toolbar({
        '->',
        this.quickSearchFilterToolbarPlugin.getQuickFilterField()
    })
</code></pre>
 */
Tine.widgets.grid.FilterToolbarQuickFilterPlugin = function(config) {
    config = config || {};
    
    this.criteriaIgnores = config.criteriaIgnores || [
        {field: 'container_id', operator: 'equals', value: {path: '/'}},
        {field: 'query',        operator: 'contains',    value: ''}
    ];
        
    Ext.apply(this, config);
};

Tine.widgets.grid.FilterToolbarQuickFilterPlugin.prototype = {
    /**
     * @cfg {String} quickFilterField
     * 
     * name of quickfilter filed in filter definitions
     */
    quickFilterField: 'query',
    
    /**
     * filter toolbar we are plugin of
     * 
     * @type {Tine.widgets.grid.FilterToolbar} ftb
     */
    ftb: null,
    
    /**
     * external quick filter field
     * 
     * @type {Ext.ux.searchField} quickFilter
     */
    quickFilter: null,
    
    /**
     * filter row of filter toolbar where a quickfilter is set
     * 
     * @type {Ext.data.record}
     */
    quickFilterRow: null,
    
    /**
     * @cfg {Array} criterias to ignore
     */
    criteriaIgnores: null,

    /**
     * @cfg {Bool} syncFields
     * sync with filtertoolbar
     */
    syncFields: true,

    
    /**
     * bind value field of this.quickFilterRow to sync process
     */
    bind: function() {
        this.quickFilterRow.formFields.value.on('keyup', this.syncField, this);
        this.quickFilterRow.formFields.value.on('change', this.syncField, this);
    },
    
    /**
     * gets the (extra) quick filter toolbar items
     * 
     * @return {Ext.ButtonGroup}
     */
    getQuickFilterField: function() {
        if (! this.quickFilterGroup) {
            this.quickFilterGroup = new Ext.ButtonGroup({
                columns: 1,
                items: [
                    this.quickFilter, {
                        xtype: 'toolbar',
                        style: {border: 0, background: 'none'},
                        items: [this.criteriaText, '->', this.detailsToggleBtn]
                    }
                ]
            });
        }
        
        return this.quickFilterGroup;
    },
    
    getQuickFilterPlugin: function() {
        return this;
    },
    
    /**
     * gets the quick filter field from the filtertoolbar which is in sync with
     * the (extra) quick filter field. 
     */
    getQuickFilterRowField: function() {
        if (!this.quickFilterRow || this.ftb.filterStore.find('field', 'query') === -1) {
            // NOTE: at this point there is no query filter in the filtertoolbar
            this.quickFilterRow = new this.ftb.record({field: this.quickFilterField, value: this.quickFilter.getValue()});
            this.ftb.addFilter(this.quickFilterRow);
        }
        return this.quickFilterRow;
    },
    
    /**
     * called by filtertoolbar in plugin init process
     *
     * @param {Tine.widgets.grid.FilterToolbar} ftb
     * @param filterPanel
     */
    init: function(ftb, filterPanel = null) {
        this.ftb = ftb;
        this.filterPanel = filterPanel;
        
        if (this.syncFields) {
            this.ftb.renderFilterRow = this.ftb.renderFilterRow.createSequence(this.onAddFilter, this);
            this.ftb.onFieldChange = this.ftb.onFieldChange.createSequence(this.onFieldChange, this);
            this.ftb.deleteFilter = this.ftb.deleteFilter.createInterceptor(this.onBeforeDeleteFilter, this);
        }

        this.origGetValue        = this.ftb.getValue;
        this.ftb.getValue        = this.onGetValue.createDelegate(this);
        this.ftb.setValue        = this.ftb.setValue.createSequence(this.onSetValue, this);
        this.ftb.onRender        = this.ftb.onRender.createSequence(this.onRender, this);
        
        
        //this.ftb.onFilterRowsChange = this.ftb.onFilterRowsChange.createInterceptor(this.onFilterRowsChange, this);
        this.ftb.getQuickFilterField = this.getQuickFilterField.createDelegate(this);
        this.ftb.getQuickFilterPlugin = this.getQuickFilterPlugin.createDelegate(this);
        
        this.quickFilter = this.quickFilter ?? new Ext.ux.SearchField({
            width: 300,
            enableKeyEvents: true
        });
        
        this.quickFilter.onTrigger1Click = this.quickFilter.onTrigger1Click.createSequence(this.onQuickFilterClear, this);
        this.quickFilter.onTrigger2Click = this.quickFilter.onTrigger2Click.createSequence(this.onQuickFilterTrigger, this);

        if (this.syncFields) {
            this.quickFilter.on('keyup', this.syncField, this);
            this.quickFilter.on('change', this.syncField, this);
        }
        
        this.criteriaText = new Ext.Panel({
            border: 0,
            html: '',
            bodyStyle: {
                border: 0,
                background: 'none', 
                'text-align': 'left', 
                'line-height': '11px'
            }
        });
        
        var stateful = !! this.ftb.recordClass;
        // autogenerate stateId
        if (stateful) {
            var stateId = this.ftb.recordClass.getMeta('appName') + '-' + this.ftb.recordClass.getMeta('recordName') + '-FilterToolbar-QuickfilterPlugin';
        }
        
        this.detailsToggleBtn = new Ext.Button(Ext.apply({
            style: {'margin-top': '2px'},
            enableToggle: true,
            text: i18n._('show details'),
            tooltip: i18n._('Always show advanced filters'),
            scope: this,
            handler: this.onDetailsToggle,
            stateful: stateful,
            stateId : stateful ? stateId : null,
            getState: function () {
                return {detailsButtonPressed: this.pressed};
            },
            applyState: function(state) {
                if (state?.detailsButtonPressed) {
                    this.setText( i18n._('hide details'));
                    this.toggle(state.detailsButtonPressed);
                }
            },
            stateEvents: ['toggle'],
            listeners: {
                scope: this,
                render: function() {
                    // limit width of this.criteriaText
                    this.criteriaText.setWidth(this.quickFilterGroup.getWidth() - this.detailsToggleBtn.getWidth());
                }
            }
        }, this.detailsToggleBtnConfig));
        
        this.ftb.hide();
    },

    /**
     * called when a filter is added to the filtertoolbar
     * 
     * @param {Ext.data.Record} filter
     */
    onAddFilter: function(filter) {
        if (filter.get('field') === this.quickFilterField && ! this.quickFilterRow) {
            this.quickFilterRow = filter;
            this.bind();
            // preset quickFilter with filterrow value
            this.syncField(filter.formFields.value);
        }
    },
    
    /**
     * called when the details toggle button gets toggled
     * 
     * @param {Ext.Button} btn
     */
    onDetailsToggle: function(btn) {
        btn.setText(i18n._(`${!btn.pressed ? 'show' : 'hide'} details`));
        
        const action = btn.pressed ? 'show' : 'hide';
        this.ftb[action]();
        if (this.filterPanel) this.filterPanel[action]();
        
        // cares for resizing
        this.ftb.onFilterRowsChange();
    },
    
    /**
     * called when a filter field of the filtertoolbar changes
     */
    onFieldChange: function(filter, newField) {
        if (filter === this.quickFilterRow) {
            this.onBeforeDeleteFilter(filter);
        }
        
        if (newField === this.quickFilterField) {
            this.onAddFilter(filter);
        }
    },
    
    /**
     * called when the filterrows of the filtertoolbar changes
     * 
     * we detect the hidestatus of the filtertoolbar
     *
    onFilterRowsChange: function() {
        this.ftb.searchButtonWrap.removeClass('x-btn-over');
        
        if (this.ftb.filterStore.getCount() <= 1 
            && this.ftb.filterStore.getAt(0).get('field') == this.quickFilterField
            && !this.ftb.filterStore.getAt(0).formFields.value.getValue()
            && !this.detailsToggleBtn.pressed) {
            
            this.ftb.hide();
        } else {
            this.ftb.show();
        }
    },
    */
    
    /**
     * called before a filter row is deleted from filtertoolbar
     * 
     * @param {Ext.data.Record} filter
     */
    onBeforeDeleteFilter: function(filter) {
        if (filter === this.quickFilterRow) {
            this.quickFilter.setValue('');
            this.unbind();
            delete this.quickFilterRow;
            
            // look for another quickfilterrow
            this.ftb.filterStore.each(function(f) {
                if (f !== filter && f.get('field') === this.quickFilterField ) {
                    this.onAddFilter(f);
                    return false;
                }
            }, this);
        }
    },
    
    /**
     * called when the (external) quick filter is cleared
     */
    onQuickFilterClear: function() {
        if (this.syncFields) {
            this.ftb.deleteFilter(this.quickFilterRow);
        }
        this.quickFilter.reset();
        this.onQuickFilterTrigger();
    },
    
    /**
     * called when the (external) filter triggers filter action
     */
    onQuickFilterTrigger: function() {
        this.ftb.onFiltertrigger.call(this.ftb);
        this.ftb.onFilterRowsChange.call(this.ftb);
    },
    
    /**
     * called after onRender is called for the filter toolbar
     * 
     * @param {Array} filters
     */
    onRender: function() {
        const btn = this.detailsToggleBtn;

        if (btn.stateful && btn.stateId) {
            const state = Ext.state.Manager.get(btn.stateId);
            if (typeof state?.detailsButtonPressed === 'undefined') {
                const filterStore = this.ftb?.filterStore;
                const queryFilter = filterStore.find('field', 'query');
                const enabled = filterStore?.data?.length > 1 || queryFilter === -1;
                Ext.state.Manager.set(btn.stateId, {detailsButtonPressed: enabled});
                btn.pressed = enabled;
            }
        }

        this.onDetailsToggle(this.detailsToggleBtn);
    },
    
    /**
     * called after setValue is called for the filter toolbar
     * 
     * @param {Array} filters
     */
    onSetValue: function(filters) {
        var _ = window.lodash;

        this.setCriteriaText(filters);

        if (! this.syncFields) {
            this.quickFilter.setValue(_.get(_.find(filters, {field: this.quickFilterField}), 'value', ''));
        }
    },

    onGetValue: function() {
        const value = this.origGetValue.call(this.ftb);

        if (! this.syncFields) {
            value.push({field: this.quickFilterField, operator: 'contains', value: this.quickFilter.getValue(), id: 'quickFilter'});
        }

        return value;
    },

    getCriteriasText: function(filters) {
        var criterias = [];

        Ext.each(filters, function(f) {
            for (var i=0, criteria, ignore; i<this.criteriaIgnores.length; i++) {
                criteria = this.criteriaIgnores[i];
                ignore = true;

                for (var p in criteria) {
                    if (criteria.hasOwnProperty(p)) {
                        if (Ext.isString(criteria[p]) || Ext.isEmpty(f[p]) ) {
                            ignore &= f.hasOwnProperty(p) && f[p] === criteria[p];
                        } else {
                            for (var pp in criteria[p]) {
                                if (criteria[p].hasOwnProperty(pp)) {
                                    ignore &= f.hasOwnProperty(p) && typeof f[p].hasOwnProperty == 'function' && f[p].hasOwnProperty(pp) && f[p][pp] === criteria[p][pp];
                                }
                            }
                        }
                    }
                }

                if (ignore || f.id == 'quickFilter') {
                    // don't judge them as criterias
                    return;
                }
            }

            if (this.ftb.filterModelMap && this.ftb.filterModelMap[f.field]) {
                criterias.push(this.ftb.filterModelMap[f.field].label);
            } else if (f.condition == 'OR' || f.condition == 'AND'){
                criterias = criterias.concat(this.getCriteriasText(f.filters));
            }
        }, this);

        return criterias;
    },

    /**
     * sets this.criteriaText according to filters
     * 
     * @param {Array} filters
     */
    setCriteriaText: function(filters) {
        var text = '' , 
            criterias = this.getCriteriasText(filters);

        if (! Ext.isEmpty(criterias)) {
            text = String.format(i18n.ngettext('Your view is limited by {0} criteria:', 'Your view is limited by {0} criterias:', criterias.length), criterias.length) +
                   '<br />' +
                   '&nbsp;' + criterias.join(', ');
        }

        // If toolbar is hidden, there is no component to update!
        if(this.criteriaText.getContentTarget()) {
            this.criteriaText.update(text);
        }
    },
    
    /**
     * syncs field contents of this.quickFilterRow and this.quickFilter
     * 
     * @param {Ext.EventObject} e
     * @param {Ext.form.Field} field
     */
    syncField: function(field) {
        const quickFilterRow = this.getQuickFilterRowField();
        if (field === this.quickFilter) {
            quickFilterRow.formFields.value.setValue(this.quickFilter.getValue());
        } else {
            this.quickFilter.setValue(this.quickFilterRow.formFields.value.getValue());
        }
    },
    
    /**
     * unbind value field of this.quickFilterRow from sync process
     */
    unbind: function() {
        this.quickFilterRow.formFields.value.un('keyup', this.syncField, this);
        this.quickFilterRow.formFields.value.un('change', this.syncField, this);
    }
};
