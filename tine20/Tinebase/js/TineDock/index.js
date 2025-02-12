/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <sdeshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import TineDock from "./TineDock.vue";
import BootstrapVueNext from "bootstrap-vue-next";

Ext.ns('Tine.Tinebase');

Tine.Tinebase.TineDock = Ext.extend(Ext.BoxComponent, {
    vueHandle: null,
    vueEventBus: null,
    injectKey: null,
    vueProps: null,

    width: '60px',
    height: '100%',

    autoScroll: true,

    vueApp: null,

    dockedApps: [],
    activeApp: null,

    stateful: true,
    stateEvents: ['syncState'],
    stateId: 'tinebase-mainscreen-docked-apps',

    initComponent : function() {
        Ext.apply(this, Ext.state.Manager.get(this.stateId));
        if (this.dockedApps.length === 0){
            this.dockedApps = this.getDefaultApps()
        }
        this.activeApp = this.activeApp || this.dockedApps[0]
        this.vueEventBus = window.mitt()
        this.injectKey = 'injectKey' + this.id
        this.vueProps = window.vue.reactive({
            parentId: this.id,
            injectKey: this.injectKey,

            state: {
                activeApp: this.activeApp,
                dockedApps: this.dockedApps,
            },

            parentWidth: this.width,
        })
        this.vueApp = TineDock;
        Tine.Tinebase.MainScreen.on('appactivate', this._setState, this);
        Tine.Tinebase.TineDock.superclass.initComponent.call(this)
    },

    pinAppToDock: function(app) {
        const appName = app.name
        if (_.indexOf(this.vueProps.state.dockedApps, appName) !== -1) return // app already pinned
        this.vueProps.state.dockedApps.push(appName)
        this.fireEvent('syncState', this)
    },

    _setState: function(app){
        this.vueProps.state.activeApp = app.name
        this.pinAppToDock(app)
        // this.fireEvent('syncState', this)
    },

    activateApp: function(appName) {
        const app = Tine.Tinebase.appMgr.get(appName)
        Tine.Tinebase.MainScreen.activate(app)
        this._setState(app)
    },

    getDefaultApps: function() {
        let t = Tine.Tinebase.appMgr.getDefault()
        if ( Ext.isObject(t) ){
            return [t.name]
        } else {
            if ( Ext.isArray(t) ){
                return t.map(app => app.name)
            } else {
                return []
            }
        }
    },

    onRender: function (ct, position) {
        Tine.Tinebase.TineDock.superclass.onRender.call(this, ct, position)
        this.vueHandle = window.vue.createApp({
            render: () => window.vue.h(this.vueApp, this.vueProps)
        })
        this.vueHandle.provide(this.injectKey, this.vueEventBus)
        this.vueEventBus.on(
            'syncState',
            () => {
                this.fireEvent('syncState', this)
            }
        )
        this.vueHandle.use(BootstrapVueNext)
        this.vueHandle.config.globalProperties.window = window
        this.vueHandle.mount(this.el.dom)
    },

    getState: function (){
        const { activeApp, dockedApps } = this.vueProps.state
        return {
            activeApp,
            dockedApps: JSON.parse(JSON.stringify(dockedApps))
        }
    }
})