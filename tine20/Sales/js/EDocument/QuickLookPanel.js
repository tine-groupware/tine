/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import('./ValidationPanel')

const EDocumentQuickLookPanel = Ext.extend(Ext.Panel, {
    border: false,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sales');

        this.tbar = ['->', {
            text: this.app.i18n._('Validate eDocument'),
            iconCls: 'action-sales-validate-document',
            handler: (btn) => {
                Tine.Sales.EDocumentValidationPanel.openWindow({
                    nodeRecord: this.nodeRecord
                });
            }
        }]

        this.html = `<iframe 
            class="sales-quicklook-edocuemnt"
            style="width: 100%; height: 100%; border: none;"
            src="${Tine.Tinebase.common.getUrl()}index.php?method=Sales.getXRechnungView&fileNodeId=${this.nodeRecord.id}" 
        />`;

        EDocumentQuickLookPanel.superclass.initComponent.call(this);
    }

});

Ext.reg('Sales.EDocumentQuickLookPanel', EDocumentQuickLookPanel);

// NOTE: xml should be quicklooked with an ace by default. we need a mechanism to switch views if multiple registrations
//       are there for the same contentType/extension
if (_.isFunction(_.get(Tine, 'Filemanager.QuickLookRegistry.registerContentType'))) {
    Tine.Filemanager.QuickLookRegistry.registerContentType('application/xml', 'Sales.EDocumentQuickLookPanel');
}
