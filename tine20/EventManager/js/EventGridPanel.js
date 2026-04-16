/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

Ext.ns('Tine.EventManager');

Tine.EventManager.EventGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    initComponent: function () {
        Tine.EventManager.EventGridPanel.superclass.initComponent.call(this);
        const col = this.gridConfig.cm.getColumnById('available_places');
        if (col) {
            col.renderer = function (value, meta, record) {
                const total = record.get('total_places') ? record.get('total_places') : 0;
                const available = record.get('available_places') ? record.get('available_places') : 0;

                const percent = Math.round((available / total) * 100);

                return Ext.ux.PercentRenderer({
                    percent: percent,
                    text: available + ' / ' + total
                });
            };
        }
    },
});
