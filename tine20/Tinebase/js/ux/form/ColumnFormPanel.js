/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ColumnFormPanel
 * @description
 * Helper Class for creating form panels with a horizontal layout. This class could be directly
 * created using the new keyword or by specifying the xtype: 'columnform'
 * Example usage:</p>
 * <pre><code>
var p = new Ext.ux.form.ColumnFormPanel({
    title: 'Horizontal Form Layout',
    items: [
        [
            {
                columnWidth: .6,
                fieldLabel:'Company Name', 
                name:'org_name'
            },
            {
                columnWidth: .4,
                fieldLabel:'Street', 
                name:'adr_one_street'
            }
        ],
        [
            {
                columnWidth: .7,
                fieldLabel:'Region',
                name:'adr_one_region'
            },
            {
                columnWidth: .3,
                fieldLabel:'Postal Code', 
                name:'adr_one_postalcode'
            }
        ]
    ]
});
</code></pre>
 */
Ext.ux.form.ColumnFormPanel = Ext.extend(Ext.Panel, {

    formDefaults: {
        xtype:'icontextfield',
        anchor: '100%',
        labelSeparator: '',
    },
    
    layout: 'hfit',
    labelAlign: 'top',

    /**
     * @private
     */
    initComponent: function() {
        var items = [];

        // each item is an array with the config of one row
        for (var i=0,j=this.items.length; i<j; i++) {
            
            var initialRowConfig = this.items[i];
            var rowConfig = {
                xtype: 'container',
                border: false,
                layout: 'column',
                cls: initialRowConfig.cls || '',
                items: [],
                listeners: {
                    scope: this,
                    beforeadd: this.onBeforeAddFromItem
                }
            };
            // autoWidth
            if (! this.formDefaults.columnWidth) {
                const tcw = _.sum(_.map(initialRowConfig, 'columnWidth')) || 0;
                const nw = _.filter(initialRowConfig, c => { return c && !c.columnWidth });
                nw.forEach(c => {c.columnWidth = (1-tcw)/nw.length});
            }

            // each row consists of n column objects
            rowConfig.hidden = true;
            for (var n=0,m=initialRowConfig.length; n<m; n++) {
                const column = initialRowConfig[n];
                if (column) {
                    // @TODO register show/hide listeners to manage col show/hide state
                    rowConfig.hidden = rowConfig.hidden && column.hidden;
                    const cell = this.wrapFormItem(column);
                    rowConfig.items.push(cell);
                }
            }
            items.push(rowConfig);
        }
        this.items = items;
        
        Ext.ux.form.ColumnFormPanel.superclass.initComponent.call(this);
    },

    onBeforeAddFromItem: function(column, c, index) {
        if (!c.isWraped) {
            var cell = this.wrapFormItem(c),
                rowItems = [cell].concat(column.items.items),
                totalWidth = 0;

            // normalize columnWidth to fit in row
            Ext.each(rowItems, function(c) {totalWidth += (c.columnWidth || 0)});
            Ext.each(rowItems, function(c) {
                if (c.columnWidth) {
                    c.columnWidth = c.columnWidth/totalWidth;
                }
            });

            column.insert(index, cell);
            return false;
        }
    },

    wrapFormItem: function(c) {
        var cell = {
            isWraped: true,
            columnWidth: c.columnWidth ? c.columnWidth : this.formDefaults.columnWidth,
            labelWidth: c.labelWidth ? c.labelWidth : this.formDefaults.labelWidth,
            layout: 'form',
            labelAlign: this.labelAlign,
            defaults: this.formDefaults,
            bodyStyle: 'padding-left: 2px; padding-right: 2px;',
            border: false,
            items: c
        };

        if (c.width) {
            cell.width = c.width;
            delete cell.columnWidth;
        }

        return cell;
    }
});

Ext.reg('columnform', Ext.ux.form.ColumnFormPanel);

