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
        this.bootstrapDataPromise = Tine.MatrixSynapseIntegrator.getBootstrapdata();

        this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');
        const url = Tine.Tinebase.configManager.get('elementUrl', 'MatrixSynapseIntegrator');


        this.on('afterrender', async () => {
            const bootstrapData = await this.bootstrapDataPromise;

            if (bootstrapData.mx_user_id && url) {
                this.url = new URL(url.replace('{MATRIX_USER_ID}', await this.sha256(bootstrapData.mx_user_id)));
                this.el.dom.src = this.url.href
                this.el.dom.style.visibility = 'hidden'
                // todo add some kind of loading indicator
                // and some option to not hide element while loading for debugging

                // window.emnt = this.el.dom
                // @TODO paste some 'here we are msg'?
                //  we better wait for a query from the page
                // window.parent.postMessage('TEST', 'https://web:4430')
            } else {
                this.el.dom.srcdoc = 'No element url configured!'
            }
        });

        window.addEventListener("message", async (event) => {
            const bootstrapData = await this.bootstrapDataPromise;
            // note: elementRequestCredentials is only send by element, if it requires credentials and can not bootstrap from local storage
            if (event.origin !== this.url.origin) return;
            console.error(event)
            
            switch (event.data.type) {
                case "elementBootstrapdataRequest":
                    event.source.postMessage(Object.assign({
                        type: "elementBootstrapdataResponse",
                    }, bootstrapData), this.url.origin);
                    break;
                case "elementLogindataRequest":
                    event.source.postMessage(Object.assign({
                        type: "elementLogindataResponse",
                    }, await Tine.MatrixSynapseIntegrator.getLogindata()), this.url.origin);
                    break;
                case "elementSetupEncryptionDone":
                    this.el.dom.style.visibility = 'visible'
                    break
                case "elementStartupFailure":
                    this.el.dom.style.visibility = 'visible'
                    alert(event.data)
                    //todo implement
                    break
                default:
                    return
            }
        }, false);

        this.supr().initComponent.call(this);
    },
});