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

    initPollInterval: 1500,
    initMaxAttempts: 5,

    /**
     * Get translated application title
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n._('Chat');
    },

    init: function() {
        if (!!+this.getRegistry().get('preferences').get('startClientInBackground')) {
            // init client in background to get unread count
            let attempts = 0;

            const tryAttach = () => {
                if (Tine.Tinebase.MainScreen) {
                    const chatPanel = this.getMainScreen();
                    const mcp = Tine.Tinebase.MainScreen.getCenterPanel();

                    if (chatPanel && mcp) {
                        if (mcp.items.indexOf(chatPanel) < 0) {
                            chatPanel.keep = true; // see Ext.ux.layout.CardLayout.helper.cleanupCardPanelItems
                            mcp.add(chatPanel);
                            mcp.layout.renderItem(chatPanel, mcp.items.indexOf(chatPanel), mcp.getLayoutTarget());
                        }
                        return; // MainScreen ready & panel attached — stop polling
                    }
                }

                if (++attempts < this.initMaxAttempts) {
                    window.setTimeout(tryAttach, this.initPollInterval);
                }
            };

            window.setTimeout(tryAttach, this.initPollInterval);
        }
    },

    async onActivate() {
        const chatPanel = this.getMainScreen()
        chatPanel.onActivate()
    },
    
});
