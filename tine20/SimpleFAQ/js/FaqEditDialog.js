/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.SimpleFAQ');

Tine.SimpleFAQ.FaqEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @private
     */
    windowNamePrefix: 'FaqEditWindow_',
    appName: 'SimpleFAQ',
    recordClass: Tine.SimpleFAQ.Model.Faq,
    recordProxy: Tine.SimpleFAQ.faqBackend,
    showContainerSelector: true,
    displayNotes: true,

    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initalisation is done.
     *
     * @return {Object}
     * @private
     */
    getFormItems: function(){
        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            defaults: {
                hideMode: 'offsets'
            },
            items:[{
                title: this.app.i18n.n_('FAQ', 'FAQs', 1),
                autoScroll: true,
                border: true,
                frame: true,
                layout: 'border',
                id: 'editCenterPanel',
                items:[{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textarea',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .5,
                        // TODO make htmleditor work again?
                        // xtype:'htmleditor',
                        // enableFont: false,
                        // enableFontSize: false,
                        // enableLinks: false
                    },
                    items: [[{
                            columnWidth: 1,
                            height: 100,
                            fieldLabel: this.app.i18n._('Question'),
                            emptyText: this.app.i18n._('Enter a question...'),
                            name: 'question',
                            allowBlank: false
                        }, {
                            columnWidth: 1,
                            height: 150,
                            fieldLabel: this.app.i18n._('Answer'),
                            emptyText: this.app.i18n._('Enter a answer...'),
                            name: 'answer'
                        }], [{
                            xtype: 'combo',
                            mode: 'local',
                            triggerAction: 'all',
                            editable: false,
                            valueField:'id',
                            fieldLabel: this.app.i18n._('Type'),
                            id:'faqtype',
                            name:'faqtype_id',
                            store: Tine.SimpleFAQ.FaqType.getStore(),
                            value: Tine.SimpleFAQ.FaqType.getStore().getAt(0).id,
                            displayField:'faqtype'
                        }, {
                            xtype: 'combo',
                            mode: 'local',
                            triggerAction: 'all',
                            editable: false,
                            valueField:'id',
                            fieldLabel: this.app.i18n._('Status'),
                            id:'faqstatus',
                            name:'faqstatus_id',
                            store: Tine.SimpleFAQ.FaqStatus.getStore(),
                            value: Tine.SimpleFAQ.FaqStatus.getStore().getAt(0).id,
                            displayField:'faqstatus'
                        }]] //end of center panel items
                    }, {
                        layout: 'ux.multiaccordion',
                        animate: true,
                        region: 'east',
                        width: 210,
                        split: true,
                        collapsible: true,
                        collapseMode: 'mini',
                        header: false,
                        margins: '0 5 0 5',
                        border: true,
                        items: [
                            new Tine.widgets.tags.TagPanel({
                                app: 'SimpleFAQ',
                                border: false,
                                bodyStyle: 'border:1px solid #B5B8C8;'
                            })
                        ]} // end of accordion panel (east)
                ] // end of editCenterPanel
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                    app: this.appName,
                    record_id: this.record.id,
                    record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
               })
           ] //end of main tabpanel
        }
    }
});

Tine.SimpleFAQ.FaqEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 700,
        height: 450,
        name: Tine.SimpleFAQ.FaqEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.SimpleFAQ.FaqEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
