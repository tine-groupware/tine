/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { rowTint } from '../tags/tagStyle';

Ext.ns('Tine.widgets.grid');

const TINT_STYLE = /--tw-row-tint:[^;]*;/g;

/**
 * tints grid rows with the colors of their tags (like a text marker)
 *
 * - n tags: n colors side by side, blended into each other
 * - quickadd row (QuickaddGridPanel) is not tinted
 *
 * usage: grid config `plugins: [{ptype: 'tw-tagsrowtint'}]`
 *        or `tagsRowTint: true` in Tine.widgets.grid.GridPanel / PickerGridPanel / QuickaddGridPanel
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.TagsRowTintPlugin
 */
Tine.widgets.grid.TagsRowTintPlugin = function(config) {
    Ext.apply(this, config);
};

Tine.widgets.grid.TagsRowTintPlugin.prototype = {
    /**
     * @cfg {String} dataIndex of the tags
     */
    dataIndex: 'tags',

    /**
     * @cfg {Number} strength opacity of the tag colors in percent
     */
    strength: 14,

    init(grid) {
        this.grid = grid;
        grid.on('beforerender', this.onBeforeGridRender, this);
    },

    onBeforeGridRender() {
        const view = this.grid.getView();
        const getRowClass = view.getRowClass;

        view.getRowClass = (record, index, rowParams, store) => {
            const cls = getRowClass ? getRowClass.call(view, record, index, rowParams, store) || '' : '';
            // NOTE: rowParams are reused for all rows of a render run
            rowParams.tstyle = String(rowParams.tstyle || '').replace(TINT_STYLE, '');

            const tint = record !== this.grid.quickaddRecord ? rowTint(record.get(this.dataIndex), this.strength) : '';
            if (!tint) {
                return cls;
            }
            rowParams.tstyle += `--tw-row-tint: ${tint};`;
            return `${cls} tw-tags-row-tint`;
        };
    }
};

Ext.preg('tw-tagsrowtint', Tine.widgets.grid.TagsRowTintPlugin);
