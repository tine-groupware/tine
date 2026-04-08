/*
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

Ext.ns('Tine.Poll');

/**
 * @namespace   Tine.Poll
 * @class       Tine.Poll.AnswerEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Poll Edit Dialog</p>
 * <p>
 * TODO         refactor this: remove initRecord/containerId/relatedApp,
 *              adopt to normal edit dialog flow and add getDefaultData to task model
 * </p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl <c.feitl@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Poll.AnswerEditDialog
 */
Tine.Poll.AnswerEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'AnswerEditWindow_',
    appName: 'Poll',

    hideRelationsPanel: true,

    initRecord: function() {
        this.loadRemoteRecord();
    },


    /**
     * load record via record proxy
     */
    loadRemoteRecord: function() {
        Tine.log.info('initiating record load via proxy');
        Tine.Poll.searchAnswers([
            {field: 'poll_id' , operator: 'equals', value: this.pollRecord.get('id')},
            {field: 'user_id' , operator: 'equals', value: Tine.Tinebase.registry.get('currentAccount').accountId}
        ], [])
            .then((results) => {
                debugger;
                let recordData = !results.results.length ? new this.recordClass({}) : results.results[0];

                if(results.results.length == 1) {
                    recordData = Tine.Tinebase.data.Record.setFromJson(recordData, this.recordClass)
                } else {
                    console.warn('duplicate found!');
                }

                this.record = recordData;
                this.onRecordLoad();

            });
    },


     /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initalisation is done.
     * @private
     */
    getFormItems: function() {
         let fieldDefinitions,
             app = Tine.Tinebase.appMgr.get(this.appName),
             items = [];
        if (this.pollRecord.get('questions') !== "") {
            fieldDefinitions = JSON.parse(this.pollRecord.get('questions'));
            Ext.each(fieldDefinitions, function (fieldDefinition) {
                fieldDefinition.appName = this.appName;
                let field = Tine.widgets.form.FieldManager.getByFieldDefinition(fieldDefinition, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

                if (field) {
                    items.push([field]);
                }
            }, this);
        }
        return {
            xtype: 'tabpanel',
            border: false,
            plain:true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items:[{
                title: this.app.i18n.n_('Poll', 'Poll', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: items
                }]}]
        };
    },


    onAfterRecordLoad: function() {
        var _ = window.lodash,
            form = this.getForm();

        Ext.each(this.record.data.answer, function (answer) {
            form.findField(answer.name).setValue(answer.value);
        },this);


        Tine.Poll.AnswerEditDialog.superclass.onAfterRecordLoad.call(this);
    },


    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function(callback, scope) {
        var items = this.form.items.items;
        var answer = [];

        Ext.each(items, function (item) {
            answer.push({name : item.name,
            value: item.getValue()});
        },this);

        this.record.data.answer = answer;

        this.record.data.poll_id = this.pollRecord.get('id');
        this.record.data.user_id = Tine.Tinebase.registry.get('currentAccount').accountId;
    },

});



/**
 * Poll Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Poll.AnswerEditDialog.openWindow = function (config) {
    var id = (config.record && config.record.id) ? config.record.id : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 900,
        height: 490,
        name: Tine.Poll.AnswerEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Poll.AnswerEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
