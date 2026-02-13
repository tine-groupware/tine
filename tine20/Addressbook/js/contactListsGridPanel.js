/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

import {listTypeRenderer} from './renderers'

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.contactListsGridPanel
 * @extends     Tine.widgets.grid.PickerGridPanel
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
Tine.Addressbook.contactListsGridPanel = Ext.extend(Tine.widgets.grid.PickerGridPanel, {

    recordClass: 'Addressbook.Model.List',
    autoExpandColumn: 'name',
    // the list record
    record: null,

    /**
     * init component
     */
    initComponent: function() {
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Addressbook');
        this.store = new Ext.data.JsonStore({
            fields: Tine.Addressbook.Model.List
        });

        this.initColumns();

        Tine.Addressbook.contactListsGridPanel.superclass.initComponent.call(this);

        this.searchCombo.additionalFilters = [
            { field: 'account_only', operator: 'equals', value: this.record.get('type') === 'user' }
        ];
    },

    initColumns: function() {
        this.columns = [
            { id: 'type', header: this.app.i18n._('Type'), width: 20, renderer: listTypeRenderer },
            { id: 'name', header: this.app.i18n._('Name'), width: 100, renderer: this.nameRenderer}
        ];
        return this.columns;
    },

    nameRenderer(value, metadata, record) {
        const memberroles = record.get('memberroles');
        return Ext.util.Format.htmlEncode(record.getTitle()) + (!memberroles ? '' : (
            '<br /><ul>' + _.map(memberroles, (memberrole) => {
                return '<li>&gt; ' + Ext.util.Format.htmlEncode(_.get(memberrole, 'list_role_id.name', window.i18n._hidden('unknown'))) + '</li>';
            }).join('') + '</ul>'
        ));
    }
});
