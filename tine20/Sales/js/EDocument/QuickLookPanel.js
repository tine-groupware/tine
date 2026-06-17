/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './quickLookAction'
import './ValidationPanel'
import FileLocation from 'Model/FileLocation'

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

        const fileLocation = FileLocation.create(this.nodeRecord)
        this.html = `<iframe 
            class="sales-quicklook-edocuemnt"
            style="width: 100%; height: 100%; border: none;"
            src="${Tine.Tinebase.common.getUrl()}index.php?method=Sales.getXRechnungView&fileLocation=${encodeURI(JSON.stringify(fileLocation.getData()))}" 
        />`;

        EDocumentQuickLookPanel.superclass.initComponent.call(this);
    }

});

Ext.reg('Sales.EDocumentQuickLookPanel', EDocumentQuickLookPanel);

export default EDocumentQuickLookPanel;
