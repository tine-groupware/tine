/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const HTMLPanel = Ext.extend(Ext.Panel, {
    border: false,
    url: '',
    contentType: '',

    initComponent: function() {
        const iconCls = Tine.Tinebase.common.getMimeIconCls(this.contentType);

        this.html = `<iframe
            class="sales-quicklook-edocuemnt dark-reverse"
            style="width: 100%; height: 100%; border: none; background-color: white"
            src="${this.url}&disposition=inline"
        />`;

        HTMLPanel.superclass.initComponent.call(this);
    },
});

Ext.reg('Filemanager.QuickLookHTMLPanel', HTMLPanel);

Tine.Filemanager.QuickLookRegistry.registerContentType('text/html', 'Filemanager.QuickLookHTMLPanel');
