/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// make sure url's are opened via bookmarks app to ensure proxy auth
const renderer = (value, index, record) => {
    const url = Tine.Tinebase.common.getUrl() + '/Bookmarks/openBookmark/' + record.getId()
    return '<a href=' + Tine.Tinebase.EncodingHelper.encode(url, 'href') + ' target="_blank">' + Tine.Tinebase.EncodingHelper.encode(value, 'shorttext') + '</a>';
}

Tine.widgets.grid.RendererManager.register('Bookmarks', 'Bookmark', 'url', renderer, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
Tine.widgets.grid.RendererManager.register('Bookmarks', 'Bookmark', 'url', renderer, Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);

