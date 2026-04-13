/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tasks');

Tine.Tasks.TaskTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'TasksTreePanel';
    this.recordClass = Tine.Tasks.Model.Task;
    
    this.filterMode = 'filterToolbar';
    Tine.Tasks.TaskTreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Tasks.TaskTreePanel, Tine.widgets.container.TreePanel, {
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);
    }
});
