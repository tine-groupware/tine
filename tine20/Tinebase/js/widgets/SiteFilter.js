/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase');

/**
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.SiteFilterModel
 * @extends     Tine.widgets.grid.ForeignRecordFilter
 */
Tine.Tinebase.SiteFilter = Ext.extend(Tine.widgets.grid.ForeignRecordFilter, {

    // private
    field: 'site',

    /**
     * @private
     */
    initComponent: function() {
        this.label = i18n._('Site');
        this.gender = i18n._('GENDER_Site');
        this.pickerConfig = {
            emptyText: i18n._('no site association'),
            allowBlank: true,
            additionalFilterSpec: {
                config: {
                    name: 'siteFilter', appName: 'Tinebase'
                }
            }
        };
        Tine.Tinebase.SiteFilter.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tinebase.site'] = Tine.Tinebase.SiteFilter;