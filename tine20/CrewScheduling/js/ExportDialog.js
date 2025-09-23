/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.CrewScheduling');

/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.ExportDialog
 * @extends     Tine.widgets.dialog.ExportDialog
 */
Tine.CrewScheduling.ExportDialog = Ext.extend(Tine.widgets.dialog.ExportDialog, {


    appName: 'CrewScheduling',
    height: 500,

    initComponent: function() {
        var _ = window.lodash,
            me = this;

        this.app = Tine.Tinebase.appMgr.get(this.appName);

        this.record = new Tine.Tinebase.Model.ExportJob({
            scope: Tine.widgets.exportAction.SCOPE_MULTI,
            model: 'Calendar_Model_Event',
            exportFunction: 'CrewScheduling.exportEvents',
            filter: this.app.getMainScreen().eventStore.proxy.jsonReader.jsonData.filter,
            recordsName: Tine.Calendar.Model.Event.getRecordsName(),
            count: this.app.getMainScreen().eventStore.getCount()
        });


        Tine.CrewScheduling.ExportDialog.superclass.initComponent.call(this);

        // in cs model does not belong to app so let's adopt it here instead of rewrite the generic code
        me.definitionsStore.clearData();
        _.each(_.get(Tine[me.appName].registry.get('exportDefinitions'), 'results', []), function(defData) {
            var options = defData.plugin_options,
                extension = options ? options.extension : null;

            defData.label = me.app.i18n._hidden(defData.label ? defData.label : defData.name);
            me.definitionsStore.addSorted(new Tine.Tinebase.Model.ImportExportDefinition(defData, defData.id));

        });
        me.definitionsStore.sort('label');
        me.getForm().findField('definitionId').setValue(_.get(me.definitionsStore.getAt(0), 'id', ''));
    },

    /**
     * returns dialog
     */
    getFormItems: function() {
        var _ = window.lodash,
            me = this,
            items = Tine.CrewScheduling.ExportDialog.superclass.getFormItems.call(this);

        items.items = items.items.concat([{
            xtype: 'fieldset',
            autoScroll: true,
            height: 260,
            title: this.app.i18n._('Choose Roles'),
            items: _.map(me.app.getMainScreen().csRolesStore.data.items, function(role) {
                return {xtype: 'checkbox', boxLabel: role.get('name'), name: role.get('key'), checked: _.findIndex(me.rolesVisible, function (k) { return k == role.get('key'); }) > -1 ? true : false};
            })
        }/*, {
            xtype: 'checkbox',
            boxLabel: this.app.i18n._('Send export via E-Mail to all corresponding group members'),
            name: 'sendEmail'
        }*/]);

        return items;
    },

    onRecordUpdate: function() {
        Tine.CrewScheduling.ExportDialog.superclass.onRecordUpdate.apply(this, arguments);

        var _ = window.lodash,
            me = this,
            options = Tine.Tinebase.common.assertComparable(me.record.get('options') || {});

        me.record.set('options', Ext.apply(options, {
            roles: _.reduce(me.app.getMainScreen().csRolesStore.data.items, function(roles, role) {
                var key = role.get('key'),
                    checked = me.getForm().findField(key).checked;

                return roles.concat(checked ? key : []);
            }, []),
            //sendEmail: me.getForm().findField('sendEmail').checked
        }));
    }
});

Tine.CrewScheduling.ExportDialog.openWindow = function (config) {
    return Tine.WindowFactory.getWindow({
        width: Tine.CrewScheduling.ExportDialog.prototype.width,
        height: Tine.CrewScheduling.ExportDialog.prototype.height,
        name: Tine.CrewScheduling.ExportDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.CrewScheduling.ExportDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
};