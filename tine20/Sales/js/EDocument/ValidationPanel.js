/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Tine.Sales.EDocumentValidationPanel = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    windowWidth: (screen.height * 0.8) / Math.sqrt(2),
    windowHeight: screen.height * 0.8,

    border: false,
    applyButtonText: null,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Sales');

        this.window.setTitle(this.app.formatMessage(`Validation Report: { title }`, { title: this.nodeRecord.getTitle() }))
        this.cancelButtonText = this.app.i18n._('Close');

        this.html = `<iframe
            class="sales-quicklook-edocuemnt"
            style="width: 100%; height: 100%; border: none;"
            src="${Tine.Tinebase.common.getUrl()}index.php?method=Sales.getXRechnungValidation&fileNodeId=${this.nodeRecord.id}" 
        />`;

        Tine.Sales.EDocumentValidationPanel.superclass.initComponent.call(this);
    }
});

Tine.Sales.EDocumentValidationPanel.openWindow = function(config) {
    Tine.WindowFactory.getWindow({
        name: `EDocumentQuickLookPanel-XRechnungValidation-${config.nodeRecord.id}`,
        contentPanelConstructor: 'Tine.Sales.EDocumentValidationPanel',
        contentPanelConstructorConfig: config
    });
}