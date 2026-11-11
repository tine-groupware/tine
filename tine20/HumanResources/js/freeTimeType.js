/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.widgets.grid.RendererManager.register('HumanResources', 'FreeTimeType', 'name', (value, index, record) => {
    const app = Tine.Tinebase.appMgr.get('HumanResources');
    return Ext.util.Format.htmlEncode(app.i18n._hidden(value));
});

Tine.widgets.grid.RendererManager.register('HumanResources', 'FreeTimeType', 'abbreviation', (value, index, record) => {
    const app = Tine.Tinebase.appMgr.get('HumanResources');
    const name = app.i18n._hidden(record.get('name'));
    return Ext.util.Format.htmlEncode(name.match(/.*\[(.+)\].*/)?.[1] || value);
});


