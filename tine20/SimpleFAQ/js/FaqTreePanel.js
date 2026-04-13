/*
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine', 'Tine.SimpleFAQ');

Tine.SimpleFAQ.FaqTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'SimpleFAQTreePanel';
    this.recordClass = Tine.SimpleFAQ.Model.Faq;

    this.filterMode = 'filterToolbar';
    Tine.SimpleFAQ.FaqTreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.SimpleFAQ.FaqTreePanel , Tine.widgets.container.TreePanel);
