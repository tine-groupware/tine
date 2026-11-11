/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const renderer = (value, index, record) => {
    return Tine.Tinebase.common.urlRenderer(record.getUrl());
}

Tine.widgets.grid.RendererManager.register('Calendar', 'FreeBusyUrl', 'url', renderer, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
Tine.widgets.grid.RendererManager.register('Calendar', 'FreeBusyUrl', 'url', renderer, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);