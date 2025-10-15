/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.Exception');

Tine.Tinebase.Exception.HTMLReportDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    windowWidth: (screen.height * 0.8) / Math.sqrt(2),
    windowHeight: screen.height * 0.8,

    border: false,
    applyButtonText: null,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Tinebase');

        this.window.setTitle(this.app.formatMessage(`Exception: { message }`, this.exception))
        this.cancelButtonText = this.app.i18n._('Close');

        this.html = `<iframe
            class="sales-quicklook-edocuemnt"
            style="width: 100%; height: 100%; border: none;"
        />`;

        Tine.Tinebase.Exception.HTMLReportDialog.superclass.initComponent.call(this);
    },
    afterRender: function() {
        Tine.Tinebase.Exception.HTMLReportDialog.superclass.afterRender.call(this);
        
        const iframe = this.el.query('iframe')[0];
        const doc = iframe.contentWindow.document || iframe.contentDocument;
        doc.open();
        doc.write(this.exception.html || JSON.parse(this.exception.response).data.html);
        doc.close();
    }
});

Tine.Tinebase.Exception.HTMLReportDialog.openWindow = function(exception) {
    Tine.WindowFactory.getWindow({
        name: `Tinebase-Exception-HTMLReport-${Tine.Tinebase.data.Record.generateUID(5)}`,
        contentPanelConstructor: 'Tine.Tinebase.Exception.HTMLReportDialog',
        contentPanelConstructorConfig: { exception }
    });
}