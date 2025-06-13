/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import waitFor from "util/waitFor.es6";

Ext.namespace('Tine.MatrixSynapseIntegrator');

Tine.MatrixSynapseIntegrator.MainScreen = Ext.extend(Ext.BoxComponent, {
    url: null,
    autoEl: { tag: 'iframe', cls: 't-app-matrixsynapseintegrator', style: 'width:100%; height: 100%; border: none;', allow: 'camera; microphone; display-capture', scrolling: 'no' },

    // @TODO move somewhere
    sha256: async function (message){
        const msgBuffer = new TextEncoder().encode(message);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    },

    initComponent: function () {
        this.accountDataPromise = Tine.MatrixSynapseIntegrator.getAccountData();

        this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');
        const url = Tine.Tinebase.configManager.get('elementUrl', 'MatrixSynapseIntegrator');


        this.on('afterrender', async () => {
            const accountData = await this.accountDataPromise;

            if (accountData.mx_user_id && url) {
                this.url = new URL(url.replace('{MATRIX_USER_ID}', await this.sha256(accountData.mx_user_id)));
                this.el.dom.src = this.url.href

                // window.emnt = this.el.dom
                // @TODO paste some 'here we are msg'?
                //  we better wait for a query from the page
                // window.parent.postMessage('TEST', 'https://web:4430')
            } else {
                this.el.dom.srcdoc = 'No element url configured!'
            }
        });

        window.addEventListener("message", async (event) => {
            const accountData = await this.accountDataPromise;
            // note: elementRequestCredentials is only send by element, if it requires credentials and can not bootstrap from local storage
            if (event.origin !== this.url.origin || event.data.type != "elementUserdataRequest") return;
            console.error(event)
            
            event.source.postMessage(Object.assign({
                type: "elementUserdataResponse",
            }, accountData), this.url.origin);
        }, false);

        this.supr().initComponent.call(this);
    },
});