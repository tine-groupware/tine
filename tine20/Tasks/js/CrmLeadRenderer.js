/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tasks');

Tine.Tasks.CrmLeadRenderer = new Tine.widgets.relation.GridRenderer({
    appName: 'Tasks', type: 'TASK', foreignApp: 'Crm', foreignModel: 'Lead'
});

Tine.widgets.grid.RendererManager.register(
    'Tasks',
    'Task',
    'lead',
    Tine.Tasks.CrmLeadRenderer.render,
    null,
    Tine.Tasks.CrmLeadRenderer
);

Tine.widgets.grid.RendererManager.register('Tasks', 'Task', 'percent', Ext.ux.PercentRenderer);

Tine.widgets.form.FieldManager.register('Tasks', 'Task', 'percent', {
    xtype: 'extuxpercentcombo',
    autoExpand: true,
    blurOnSelect: true
});