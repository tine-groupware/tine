/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

Tine.Tinebase.TwigTemplateGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // evalGrants: false,

    initComponent: function() {
        // this.defaultFilters = [
        //     {field: 'month', operator: 'equals', value: new Date().format('Y-m')}/*,
        //     {field: 'employee_id', operator: 'equals', value: null}*/
        // ];
        this.editDialogConfig = this.editDialogConfig || {}
        this.editDialogConfig.mode = 'load(local):save(remote)'

        Tine.Tinebase.TwigTemplateGridPanel.superclass.initComponent.apply(this, arguments)
    },
})