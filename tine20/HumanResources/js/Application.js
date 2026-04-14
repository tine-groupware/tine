/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.HumanResources');

Tine.HumanResources.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: true,

    init: function() {
        if (this.featureEnabled(('workingTimeAccounting'))) {
            Tine.widgets.MainScreen.registerContentType('HumanResources', {
                contentType: 'FreeTimePlanning',
                text: this.i18n._('Absence Planning'),
                xtype: 'humanresources.freetimeplanning'
            });
        }
        if (this.featureEnabled(('revenueAnalysis'))) {
            Tine.widgets.MainScreen.registerContentType('HumanResources', {
                contentType: 'RevenueAnalysis',
                text: this.i18n._('Revenue Analysis'),
                xtype: 'humanresources.revenueanalysis'
            });
        }

    },

    /**
     * Get translated application title of the HumanResources App
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Human Resources', 'Human Resources', 1);
    },


    registerCoreData: function() {
        Tine.log.info('Tine.HumanResources.Application - registering core data ... ');
        Tine.CoreData.Manager.registerGrid('hr_wts', Tine.HumanResources.WorkingTimeSchemeGridPanel);
    }
});
