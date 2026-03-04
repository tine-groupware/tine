/*
 * Tine 2.0
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */

Ext.ns('Tine.EventManager');

Tine.EventManager.EventGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    initComponent: function () {
        Tine.EventManager.EventGridPanel.superclass.initComponent.call(this);
    },

    createView: function () {
        const viewClass = this.groupField ? Ext.grid.GroupingView : Ext.grid.GridView;

        return new viewClass({
            getRowClass: this.getViewRowClass.bind(this),
            autoFill: true,
            forceFit: true,
            ignoreAdd: true,
            emptyText: this.i18nEmptyText,
            groupTextTpl: this.groupTextTpl,
            onLoad: Ext.grid.GridView.prototype.onLoad.createInterceptor(function () {
                if (this.grid.getView().isPagingRefresh) {
                    this.grid.getView().isPagingRefresh = false;
                    return true;
                }
                return false;
            }, this)
        });
    },

    getViewRowClass: function (record, index, rowParams, store) {
        let className = Tine.EventManager.EventGridPanel.superclass.getViewRowClass.call(this, record, index, rowParams, store);
        if (record.data && record.data.available_places <= 0) {
            className += ' event-full-row';
        } else if (record.data && record.data.available_places <= (0.1 * record.data.total_places)) {
            className += ' event-nearly-full-row';
        } else {
            className += ' event-available-row';
        }
        return className;
    },

    afterRender: function () {
        Tine.EventManager.EventGridPanel.superclass.afterRender.call(this);

        if (!document.getElementById('event-available-places-style')) {
            const style = document.createElement('style');
            style.id = 'event-available-places-style';
            style.textContent = `
                .event-full-row td.x-grid3-td-available_places .x-grid3-cell-inner {
                    background-color: #FF6464 !important;
                }
                .event-nearly-full-row td.x-grid3-td-available_places .x-grid3-cell-inner {
                    background-color: #FFE162 !important;
                }
                .event-available-row td.x-grid3-td-available_places .x-grid3-cell-inner {
                    background-color: #91C483 !important;
                }
            `;
            document.head.appendChild(style);
        }
    },

});
