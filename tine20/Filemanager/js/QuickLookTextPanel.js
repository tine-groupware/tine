/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const TextPanel = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'fit',
    url: '',
    contentType: '',

    initComponent: function() {
        this.contentsResponse = fetch(this.url);
        this.mode = 'json' //@TODO

        TextPanel.superclass.initComponent.call(this);
    },

    async afterRender() {
        TextPanel.superclass.afterRender.apply(this, arguments);

        await import(/* webpackChunkName: "Tinebase/js/ace" */ 'widgets/ace');
        this.ed = ace.edit(this.body.id, {
            mode: `ace/mode/${this.mode}`,
            fontFamily: 'monospace',
            fontSize: 12,
            useWorker: false
        });
        this.ed.setReadOnly(true);
        const response = await this.contentsResponse;
        const text = await response.text();
        this.ed.setValue(text);
    },

});

Ext.reg('Filemanager.QuickLookTextPanel', TextPanel);

Tine.Filemanager.QuickLookRegistry.registerContentType('application/json', 'Filemanager.QuickLookTextPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('text/plain', 'Filemanager.QuickLookTextPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('text/x-shellscript', 'Filemanager.QuickLookTextPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('text/x-php', 'Filemanager.QuickLookTextPanel');
Tine.Filemanager.QuickLookRegistry.registerContentType('text/x-java', 'Filemanager.QuickLookTextPanel');
