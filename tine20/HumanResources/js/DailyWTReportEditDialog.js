/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

Tine.HumanResources.DailyWTReportEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function() {
        Tine.HumanResources.DailyWTReportEditDialog.superclass.initComponent.apply(this, arguments);
    },

    saveAndCloseActionUpdater: function() {
        this.action_saveAndClose.setDisabled(! _.get(this.record, 'data.account_grants.updateTimeDataGrant'));
    }
});

