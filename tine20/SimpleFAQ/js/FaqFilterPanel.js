/*
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine', 'Tine.SimpleFAQ');

Tine.SimpleFAQ.FaqFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.SimpleFAQ.FaqFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.SimpleFAQ.FaqFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'SimpleFAQ_Model_FaqFilter'}]
});
