/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.MatrixSynapseIntegrator');

Tine.MatrixSynapseIntegrator.Application = Ext.extend(Tine.Tinebase.Application, {

    hasMainScreen: true,

    /**
     * Get translated application title
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Chat');
    },

    init: function() {
        // init client in background to get unread count
        window.setTimeout(() => {
            const chatPanel = this.getMainScreen()
            const mcp = Tine.Tinebase.MainScreen.getCenterPanel()
            if (mcp.items.indexOf(chatPanel) < 0) {
                chatPanel.keep = true // see Ext.ux.layout.CardLayout.helper.cleanupCardPanelItems
                mcp.add(chatPanel)
                mcp.layout.renderItem(chatPanel, mcp.items.indexOf(chatPanel), mcp.getLayoutTarget())
            }

        }, 1500)
    },

    async onActivate() {
        const chatPanel = this.getMainScreen()
        chatPanel.onActivate()
    },
    
});
