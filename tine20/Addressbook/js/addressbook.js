/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import '../styles/Addressbook.scss';
import '../styles/Contact.scss';
import '../styles/List.scss';
import '../styles/Structure.scss';
import '../styles/Resource.scss';
import './Application';
import './MainScreen';
import './ContactTreePanel';
import './ListTreePanel';
import './ContactFilterPanel';
import './ListFilterPanel';
import './Model';
import './IndustryEditDialog';
import './IndustrySearchCombo';
import './GenericContactGridPanelHook';
import './renderers';
import './ContactGridDetailsPanel';
import './ContactGrid';
import './ContactFilterModel';
import './ContactEditDialog';
import './ListGrid';
import './contactListsGridPanel';
import './ListGridDetailsPanel';
import './ListMemberRoleLayerCombo';
import './ListMemberRoleGridPanel';
import './ListEditDialogRoleGridPanel';
import './ListEditDialog';
import './ContactSearchCombo';
import './ListSearchCombo';
import './MapPanel';
import './CardDAVContainerPropertiesHookField';
import './AdminPanel';
import './Printer/ContactRecord';
import './Printer/ListRecord';
import './ContactsSearchCombo';
import './StructurePanel';
import './Model/ContactPersonalGrants';
import './Model/ListPersonalGrants';

Tine.Addressbook.handleRequestException = Tine.Tinebase.ExceptionHandler.handleRequestException;

Tine.widgets.container.GrantsManager.register('Addressbook_Model_Contact', function(container) {
    var _ = window.lodash,
        me = this,
        grants = Tine.widgets.container.GrantsManager.defaultGrants(container);

    grants.push('privateData');
    return grants;
});

Tine.widgets.container.GrantsManager.register('Addressbook_Model_List', function(container) {
    var _ = window.lodash,
        me = this,
        grants = Tine.widgets.container.GrantsManager.defaultGrants(container);

    grants.push('privateData');
    return grants;
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    privateDataGrantTitle: i18n._('Private'), // i18n._('Private')
    privateDataGrantDescription: i18n._('The grant to access contacts private information'), // i18n._('The grant to access contacts private information')
});
