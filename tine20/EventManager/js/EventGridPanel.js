/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
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
        if (record.data && record.data.registrations) {
            const hasWaitingList = record.data.registrations.some(function (registration) {
                return registration.status === "2"; //waiting list
            });
            if (hasWaitingList) {
                className += ' event-waiting-list-row';
            }
        }
        return className;
    },

    afterRender: function () {
        Tine.EventManager.EventGridPanel.superclass.afterRender.call(this);
        if (!document.getElementById('event-waiting-list-style')) {
            const style = document.createElement('style');
            style.id = 'event-waiting-list-style';
            style.textContent = `
                .event-waiting-list-row .x-grid3-cell {
                    color: #ff0000 !important;
                }
            `;
            document.head.appendChild(style);
        }
    },

});
