/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import '../styles/Crm.scss';
import '../styles/Lead.scss';
import './Application';
import './MainScreen';
import './LeadTreePanel';
import './LeadFilterPanel';
import './AddressbookGridPanelHook';
import './SearchCombo';
import './Model';
import './ProductPickerCombo';
import './LinkGridPanel';
import './LeadGridContactFilter';
import './LeadGridPanel';
import './LeadGridDetailsPanel';
import './LeadEditDialog';
import './AddToLeadPanel';
import './AdminPanel';
import './Contact';
import './Product';

/**
 * returns ids of ended lead states
 */
Tine.Crm.getEndedLeadStateIds = function() {
    var leadstates = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Crm', 'leadstates');
    var ids = [];
    leadstates.each(function(leadstate) {
        if (leadstate.json.endslead == 1) {
            ids.push(leadstate.id);
        }
    }, this);

    return ids;
}

/**
 * @namespace Tine.Crm
 * @class Tine.Crm.leadBackend
 * @extends Tine.Tinebase.data.RecordProxy
 *
 * Lead Backend
 */
Tine.Crm.leadBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Crm',
    modelName: 'Lead',
    recordClass: Tine.Crm.Model.Lead
});

