/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.OptionRelationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'OptionRelationEditWindow_',
    appName: 'EventManager',
    modelName: 'Option',
    recordClass: null,
    mode: 'local',
    evalGrants: false,

    initComponent: function () {
        this.recordClass = Tine.Tinebase.data.RecordMgr.get('EventManager', 'Option');
        this.app = this.app || Tine.Tinebase.appMgr.get('EventManager');
        Tine.EventManager.OptionRelationEditDialog.superclass.initComponent.call(this);
    },

    getFormItems: function () {
        const me = this;
        const fieldManager = _.bind(
            Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager,
            this.appName,
            this.modelName,
            _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
        );

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Rules'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'vbox',
                items: [{
                    xtype: 'panel',
                    layout: 'hbox',
                    align: 'stretch',
                    items: [{
                        flex: 1,
                        xtype: 'columnform',
                        autoHeight: true,
                        items: [
                            [fieldManager('option_rule')],
                            [fieldManager('rule_type')]
                        ]
                    }]
                }]
            }]
        };
    },

    onAfterRecordLoad: function () {
        Tine.EventManager.OptionRelationEditDialog.superclass.onAfterRecordLoad.call(this);

        // Update display fields with readable values
        const form = this.getForm();

        const displayField = form.findField('display');
        if (displayField && this.record.get('display')) {
            displayField.setValue(this.record.get('display'));
        }

        const optionRequiredField = form.findField('option_required');
        if (optionRequiredField && this.record.get('option_required')) {
            optionRequiredField.setValue(this.record.get('option_required'));
        }
    }
});


/**
 * Rules Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.EventManager.OptionRelationEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 400,
        height: 600,
        name: Tine.EventManager.OptionRelationEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.EventManager.OptionRelationEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
