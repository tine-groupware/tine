/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tasks');

Tine.Tasks.TaskFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Tasks.TaskFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Tasks.TaskFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Tasks_Model_TaskFilter'}]
});


