/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Timetracker');

/**
 * @namespace   Tine.Timetracker
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @class       Tine.Timetracker.TimeaccountPickerCombo
 * @extends     Tine.Tinebase.widgets.form.RecordPickerComboBox
 * 
 * adds show closed handling
 */

Tine.Timetracker.TimeaccountPickerCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    /**
     * @cfg {Bool} showClosed
     */
    showClosed: false,

    /**
     * @cfg {bool} blurOnSelect
     * blur this combo when record got selected, useful to be used in editor grids (defaults to false)
     */
    blurOnSelect: true,

    /**
     * @property showClosedBtn
     * @type Ext.Button
     */
    showClosedBtn: null,
    
    sortBy: 'number',
    
    initComponent: function() {
        this.recordProxy = Tine.Timetracker.timeaccountBackend;
        this.recordClass = Tine.Timetracker.Model.Timeaccount;
        this.initTemplate();

        Tine.Timetracker.TimeaccountPickerCombo.superclass.initComponent.apply(this, arguments);

        this.store.on('beforeloadrecords', this.onStoreBeforeLoadRecords, this);
    },
    
    initList: function() {
        Tine.Timetracker.TimeaccountPickerCombo.superclass.initList.apply(this, arguments);
        
        if (this.pageTb && ! this.showClosedBtn) {
            this.showClosedBtn = new Tine.widgets.grid.FilterButton({
                text: this.app.i18n._('Show closed'),
                iconCls: 'action_showArchived',
                field: 'is_open',
                invert: true,
                pressed: this.showClosed,
                scope: this,
                handler: function() {
                    this.showClosed = this.showClosedBtn.pressed;
                    this.store.load();
                }
                
            });
            
            this.pageTb.add('-', this.showClosedBtn);
            this.pageTb.doLayout();
        }
    },

    /**
     * init template
     * @private
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate('<tpl for=".">',
                '<div class="x-combo-list-item" {[this.getQtip(values.' + this.recordClass.getMeta('idProperty') + ')]}>',
                '<table>',
                '<tr>',
                '<td style="min-width: 16px;">{[this.invoiceableRenderer(null, null, values)]}</td>',
                '<td width="100%">{[this.getTitle(values.' + this.recordClass.getMeta('idProperty') + ')]}</td>',
                '</tr>',
                '</table>',
                '</div>',
                '</tpl>', {
                    invoiceableRenderer: this.invoiceableRenderer,
                    getTitle: this.getTitle.createDelegate(this),
                    getQtip: this.getQtip.createDelegate(this)
                }
            );
        }
    },
    
    /**
     * apply showClosed value
     */
    onStoreBeforeLoadRecords: function(o, options, success, store) {
        if (this.showClosedBtn) {
            this.showClosedBtn.setValue(options.params.filter);
        }
    },

    /**
     * append showClosed value
     */
    onBeforeLoad: function (store, options) {
        Tine.Timetracker.TimeaccountPickerCombo.superclass.onBeforeLoad.apply(this, arguments);

        if (this.showClosedBtn) {
            Ext.each(store.baseParams.filter, function(filter, idx) {
                if (filter.field == 'is_open'){
                    store.baseParams.filter.remove(filter);
                }
            }, this);
            
            if (this.showClosedBtn.getValue().value === true) {
                store.baseParams.filter.push(this.showClosedBtn.getValue());
            }
        }
    },

    /**
     * list type renderer
     *
     * @private
     * @return {String} HTML
     */
    invoiceableRenderer: function(data, cell, record) {
        const is_billable = record.is_billable;
        const cssClass = (is_billable ? 'tine-grid-row-action-icon TimetrackerTimeaccount_Invoice' : '');
        return '<div style="background-position:1px;" class="' + cssClass + '">&#160</div>';
    },

    getListItemQtip(record) {
        let result = Tine.Timetracker.TimeaccountPickerCombo.superclass.getListItemQtip.apply(this, arguments);
        if (record.get('is_billable')) {
            result = `[ ${this.app.i18n._('Project time is invoiceable')} ] ${result}`;
        }
        return result;
    },
});

Tine.widgets.form.RecordPickerManager.register('Timetracker', 'Timeaccount', Tine.Timetracker.TimeaccountPickerCombo);
