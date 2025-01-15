/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const HTMLPanel = Ext.extend(Ext.Panel, {
    border: false,

    initComponent: function() {
        const record = this.nodeRecord;

        const contentType = record.get('contenttype');
        const iconCls = Tine.Tinebase.common.getMimeIconCls(contentType);
        const url = Tine.Filemanager.Model.Node.getDownloadUrl(record);

        this.html = `<iframe
            class="sales-quicklook-edocuemnt"
            style="width: 100%; height: 100%; border: none;"
            src="${url}&disposition=inline"
        />`;

        HTMLPanel.superclass.initComponent.call(this);
    },


});

Ext.reg('Filemanager.QuickLookHTMLPanel', HTMLPanel);

Tine.Filemanager.QuickLookRegistry.registerContentType('text/html', 'Filemanager.QuickLookHTMLPanel');
