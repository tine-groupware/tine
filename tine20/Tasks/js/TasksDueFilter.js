/*
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldInfoPlugin from "ux/form/FieldInfoPlugin";

Tine.widgets.grid.FilterToolbar.FILTERS['tasks.tasksdue'] =  Ext.extend(Tine.widgets.grid.FilterModel, {
    operators: ['equals'],

    valueRenderer: function(filter, el) {
        const i18n = Tine.Tinebase.appMgr.get('Tasks').i18n;

        return new Tine.Addressbook.ContactSearchCombo({
            filter: filter,
            renderTo: el,
            value: filter.data.value ? filter.data.value : this.defaultValue,
            plugins: [new FieldInfoPlugin({
                onTriggerClick: () => {
                    Ext.MessageBox.show({
                        icon: Ext.MessageBox.INFO_INSTRUCTION,
                        buttons: Ext.MessageBox.OK,
                        title: i18n._('Filter: To be done for'),
                        msg: `${i18n._('Show all tasks matching all the following criteria:')} <br />
<ul class="x-ux-messagebox-msg">
    <li>${i18n._('Task has an open status.')}</li>
    <li>${i18n._('Filtered person is either:')}</li>
    <ul class="x-ux-messagebox-msg">
        <li>${i18n._('Organizer and no coworker with open status is assigned.')}</li>
        <li>${i18n._('Organizer and the task is over due date, regardless of assigned coworkers.')}</li>
        <li>${i18n._('Coworker with an open status assigned.')}</li>
    </ul>
</ul>
<br /><br>
${i18n._('Please note: Tasks are hidden if the filtered person is the organizer, the due date has not yet passed, and a coworker with an open status is assigned.')}`

                    });
                }
            })],
            listeners: {
                'specialkey': function(field, e) {
                    if(e.getKey() == e.ENTER){
                        this.onFiltertrigger();
                    }
                },
                'select': this.onFiltertrigger,
                scope: this
            }
        });
    }
});