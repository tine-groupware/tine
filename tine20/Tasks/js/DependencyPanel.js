import {avatarRenderer} from "../../Addressbook/js/renderers";

Ext.reg('tasks.dependency', Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    initComponent: function() {
        this.supr().initComponent.call(this);
        const summary = Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'summary', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
        const status = Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskStatus', 'icon');
        const organizer = (account) => {
            // resolved?
            return account && account.accountLastName ? avatarRenderer('', {}, Tine.Tinebase.data.Record.setFromJson(account, Tine.Tinebase.Model.User)) : '';
        };

        this.colModel.config[0].renderer = function(value, row, record) {
            const task = Tine.Tinebase.data.Record.setFromJson(value, Tine.Tasks.Model.Task);
            return `${summary(task.get('summary'), {}, task)} ${organizer(task.get('organizer'), {}, task)} ${status(task.get('status'), {}, task)}`;
        }
    }
}));