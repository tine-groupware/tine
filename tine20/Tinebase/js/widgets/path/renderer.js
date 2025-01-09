/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.path');

/**
 * paths block renderer
 *
 * @param {Array} paths
 * @param {String} queryString
 * @returns {string}
 */
Tine.widgets.path.pathsRenderer = function(paths, queryString) {
    var pathsString = '';

    if (Ext.isArray(paths)) {
        Ext.each(paths, function(path) {
            pathsString += Tine.widgets.path.pathRenderer(path, queryString);
        });
    }

    return pathsString ? '<div class="tb-widgets-path-pathsblock">' + pathsString + '</div>' : pathsString;
};

/**
 * single path renderer
 *
 * @param path
 * @param queryString
 * @returns {string}
 */
Tine.widgets.path.pathRenderer = function(path, queryString) {
    var _ = window.lodash,
        pathName = String(_.get(path, "path", path)),
        queryParts = queryString ? String(queryString).trim().split(' ') : [];

    pathName = pathName
        .replace(/^\//, '')
        .replace(/\//g, '\u0362');

    pathName = Ext.util.Format.htmlEncode(pathName);

    if (queryParts.length) {
        var queryMatchCount = 0,
            search = '';

        Ext.each(queryParts, function(queryPart, idx) {
            search += (search ? '|(' :'(') + window.lodash.escapeRegExp(queryPart) + ')';
        });

        pathName = pathName.replace(new RegExp(search,'gi'), function(match) {
            queryMatchCount++;
            return '<span class="tb-widgets-path-pathitem-match">' + match + '</span>';
        });

        // skip path if no token matched
        if (queryParts.length > queryMatchCount) {
            pathName = '';
        }
    }

    var qtip = pathName.replace(/(?:{(.*?)}){0,1}\u0362/g, function(all, type) {
        return "<br/>&nbsp;" + (type ? type + ' ' : '') + '<span class="tb-widgets-path-pathitem-separator">' + Ext.util.Format.htmlEncode('\u00BB') + '</span>&nbsp;';
    });

    pathName = pathName.replace(/(?:{(.*?)}){0,1}\u0362/g, function(all, type) {
        return "&nbsp;" + (type ? '<span class="tb-widgets-path-pathitem-type">' + type[0] + '</span>' : '') + '<span class="tb-widgets-path-pathitem-separator">' + Ext.util.Format.htmlEncode('\u00BB') + '</span>&nbsp;';
    });


    return pathName ? '<div class="tb-widgets-path-pathitem" ext:qtip="' + Ext.util.Format.htmlEncode(qtip) + '">' + pathName + '</div>' : pathName;
}
