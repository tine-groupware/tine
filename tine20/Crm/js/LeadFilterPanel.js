/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Crm');

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.FilterPanel
 * @extends Tine.widgets.persistentfilter.PickerPanel
 * Crm Filter Panel<br>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.LeadFilterPanel = function(config) {
    config.app.enableAdvancedSearch = true;

    Ext.apply(this, config);
    Tine.Crm.LeadFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Crm.LeadFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Crm_Model_LeadFilter'}]
});
