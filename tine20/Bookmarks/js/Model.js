Ext.ns('Tine.Bookmarks.Model');

/**
 * @namespace Tine.Bookmarks.Model
 * @class Tine.Bookmarks.Model.BookmarkMixin
 *
 */
Tine.Bookmarks.Model.BookmarkMixin = {
  statics: {
    getDefaultData: function () {
      const dc = Tine.Bookmarks.registry.get('defaultBookmarksContainer');
      return _.assign({
        container_id: dc,
      });
    }
  }
}
