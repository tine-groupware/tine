/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const ObjectPanel = Ext.extend(Ext.Panel, {
    border: false,
    url: '',
    contentType: '',

    initComponent: function() {
        const iconCls = Tine.Tinebase.common.getMimeIconCls(this.contentType);
        this.contentsResponse = fetch(this.url);

        ObjectPanel.superclass.initComponent.call(this);
    },

    async afterRender() {
        ObjectPanel.superclass.afterRender.apply(this, arguments);

        const response = await this.contentsResponse;
        const text = await response.blob().then(
            blob => new Promise((resolve, reject) => {
                resolve(URL.createObjectURL(blob));
            })
        )

        this.body.dom.innerHTML =
            `<object
                class="sales-quicklook-edocuemnt dark-reverse"
                style="width: 100%; height: 100%; border: none; background-color: white"
                data="${text}"
                type="${this.contentType}"
            ></object>`;
    },
});

ObjectPanel.negotiate = async (fileLocation, config) => {
    return {
        label: window.i18n._('Native Browser Preview'),
        iconCls: config.contentType ? Tine.Tinebase.common.getMimeIconCls(config.contentType) : 'mime-icon-file'
    };
}

Ext.reg('Filemanager.QuickLookObjectPanel', ObjectPanel);

Tine.Filemanager.QuickLookRegistry.registerContentType('application/pdf', 'Filemanager.QuickLookObjectPanel');
