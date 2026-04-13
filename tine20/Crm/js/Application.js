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
* @namespace   Tine.Crm
* @class       Tine.Crm.Application
* @extends     Tine.Tinebase.Application
* Crm Application Object <br>
*
* @author      Cornelius Weiss <c.weiss@metaways.de>
*/
Tine.Crm.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Lead')
     */
    addButtonText: 'New Lead',
    
    init: function() {
        Tine.Crm.Application.superclass.init.apply(this, arguments);
        
        new Tine.Crm.AddressbookGridPanelHook({app: this});
    }
});
