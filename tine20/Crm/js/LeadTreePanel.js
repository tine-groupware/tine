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
 * @class Tine.Crm.TreePanel
 * @extends Tine.widgets.container.TreePanel
 * Left Crm Panel including Tree<br>
 *
 * TODO add d&d support to tree
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.LeadTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'CrmLeadTreePanel';
    this.filterMode = 'filterToolbar';
    this.recordClass = Tine.Crm.Model.Lead;
    Tine.Crm.LeadTreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Crm.LeadTreePanel , Tine.widgets.container.TreePanel);
