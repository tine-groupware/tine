/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const timezoneCache = {}

const TimezoneCombo = Ext.extend(Ext.form.ComboBox, {
    displayField:'translatedName',
    valueField:'shortName',
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    selectOnFocus:true,
    filterAnyMatch: true,

    /**
     * @private
     */
    initComponent: function() {
        this.store = new Ext.data.JsonStore({
            root: 'results',
            id: 'shortName',
            fields: ['shortName', 'translatedName'],
            remoteSort: false,
            sortInfo: {
                field: 'translatedName',
                direction: 'ASC'
            }
        });

        this.locale = this.locale || Tine.Tinebase.registry.get('locale').locale
        if (! timezoneCache[this.locale]) {
            timezoneCache[this.locale] = Tine.Tinebase.getTimezoneList(this.locale);
        }
        timezoneCache[this.locale].then((result) => {
            this.store.loadData(result);
        })

        this.emptyText = i18n._('Select a timezone...');

        TimezoneCombo.superclass.initComponent.call(this);
    }
});

Ext.reg('widget-timezonecombo', TimezoneCombo);

export default TimezoneCombo