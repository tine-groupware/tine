/*
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

Ext.ns('Tine.Poll');

Tine.Poll.PollGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {


    getActionToolbarItems: function () {
        this.start_poll = new Ext.Action({
            //requiredGrant: 'addGrant',
            //actionType: 'add',
            text: this.app.i18n._('Umfrage Starten'),
            iconCls: 'action_bill', //@todo icon ändern
            scope: this,
            disabled: false,
            allowMultiple: false,
            handler: function(){
                    //if(this.initialConfig.selections.length == 1) {
                    Tine.Poll.AnswerEditDialog.openWindow({
                        pollRecord: this.selectionModel.getSelected() //@ todo search poll_id and user_ip
                        });
                //}
            },

        });

        this.actionUpdater.addActions(this.start_poll);

        var startPoll = Ext.apply(new Ext.Button(this.start_poll), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });

        return [startPoll];
    },
});
