/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

const ImagePanel = Ext.extend(Ext.Panel, {
    border: false,
    url: '',
    contentType: '',

    initComponent: function() {
        const iconCls = Tine.Tinebase.common.getMimeIconCls(this.contentType);

        this.html = `<div class="filemanager-quicklook-image">
          <img class="dark-reverse" style="max-width: 100%; max-height: 100%; object-fit: contain;" src="${this.url}"/>
        </div>`;
        ImagePanel.superclass.initComponent.call(this);
    },
});

Ext.reg('Filemanager.QuickLookImagePanel', ImagePanel);

Tine.Filemanager.QuickLookRegistry.registerContentType('image/jpeg', 'Filemanager.QuickLookImagePanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('image/png', 'Filemanager.QuickLookImagePanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('image/gif', 'Filemanager.QuickLookImagePanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('image/apng', 'Filemanager.QuickLookImagePanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('image/avif', 'Filemanager.QuickLookImagePanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('image/svg+xml', 'Filemanager.QuickLookImagePanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('image/webp', 'Filemanager.QuickLookImagePanel');
