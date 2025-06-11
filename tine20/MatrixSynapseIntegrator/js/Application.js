/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
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
        // this.getRegistry().get('mx_hs_url')
    },

    
});
