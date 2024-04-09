import GenericDialog from "./GenericDialog.vue";
import {BootstrapVueNext} from "bootstrap-vue-next";

Ext.ns('Tine.widgets.dialog');

const EXT_WIN_EVENT_PREFIX = 'ext_win_event'
Tine.widgets.dialog.ModalDialog = Ext.extend(Ext.Component, {

    title: null,
    persona: "",
    skinColor: "#FFFFFF",
    buttons: null,
    closable: true,
    injectKey: null,
    visible: false,
    zIndex: 9000,

    maskModal: false,
    maskMessage: undefined,

    focusTrapStack: null,

    vueHandle: null,
    vueMountPoint: null,
    vueEventBus: null,
    contentProps: null,
    windowProxy: null,
    modalProps: null,
    vueProps: null,

    window: null,

    dlgContentComponent: null,

    injected: {},

    _mask: null,

    setZIndex: function(val){
        if(this.modalProps) this.modalProps.zIndex = val
    },

    registerEventListener: function(eventName, cb){
        if(this.vueEventBus) this.vueEventBus.on(`${EXT_WIN_EVENT_PREFIX}_${eventName}`, cb)
        else console.warn('Event Bus not yet created')
    },

    on: function (eventName, cb) {
        this.registerEventListener(eventName, cb)
    },

    initEventBus: function (){
        this.vueEventBus = window.mitt()
        this.vueEventBus.on('*', this.onEvent.bind(this))
        this.vueEventBus.on('apply', this.destroy.bind(this))
        this.vueEventBus.on('cancel', this.destroy.bind(this))
    },

    onEvent: function(eventName, data){
        const ext_event = `${EXT_WIN_EVENT_PREFIX}_${eventName}`
        if(this.vueEventBus.all.has(ext_event)) this.vueEventBus.emit(ext_event, data)
    },

    initWindowProxy: function() {
        this.window = new Ext.Window()
        const me = this
        const handler = {
            get(target, key){
                switch(key){
                    case "hidden":
                        return !me.modalProps?.visible
                    case "setZIndex":
                        return me.setZIndex.bind(me)
                    case "setActive":
                        return Ext.emptyFn
                    default:
                        return target[key]
                }
            }
        }
        this.windowProxy = new Proxy(this.window, handler)
        Ext.WindowMgr.register(this.windowProxy)
    },

    postInit: function() {
        this.vueProps = window.vue.reactive({
            modalProps: this.modalProps,
            dlgContentComponent: window.vue.h(this.dlgContentComponent, this.contentProps)
        })

        this.vueHandle = window.vue.createApp({
            render: () => window.vue.h(GenericDialog, this.vueProps)
        })
        this.vueHandle.config.globalProperties.window = window

        this.injected['eventBus'] = this.vueEventBus
        this.vueHandle.provide(this.injectKey, this.injected)
        this.vueHandle.use(BootstrapVueNext)
        this.vueHandle.mount(this.vueMountPoint)
    },

    initModalProps: function() {
        // fixme: better way to initialize the focusTrapStack
        Tine.Tinebase.vue = Tine.Tinebase.vue || {}
        Tine.Tinebase.vue.focusTrapStack = Tine.Tinebase.vue.focusTrapStack || []
        this.focusTrapStack = Tine.Tinebase.vue.focusTrapStack

        this.injectKey = this.id
        this.modalProps = window.vue.reactive({
            title: this.title,
            persona: this.persona,
            buttons: this.buttons,
            closable: this.closable,
            injectKey: this.injectKey,
            visible: this.visible,
            zIndex: this.zIndex,

            maskModal: this.maskModal,
            maskMessage: window.i18n._('Loading'),

            focusTrapStack: window.vue.markRaw(this.focusTrapStack)
        })
    },

    initComponent: async function() {
        Tine.widgets.dialog.ModalDialog.superclass.initComponent.call(this)
        this.buttons = this.getButtons()
        this.initEventBus()
        this.initWindowProxy()
        this.initModalProps()
        this.vueMountPoint = document.createElement('div')
        this.vueMountPoint.id = this.id

        document.body.append(this.vueMountPoint)
    },

    showMask: function(maskMessage) {
        if(this.modalProps) {
            this.modalProps.maskModal = true
            this.modalProps.maskMessage = maskMessage
        }
    },

    hideMask: function() {
        if(this.modalProps) this.modalProps.maskModal = false
    },

    show: function() {
      this.showModal()
    },

    showModal: function() {
        this._mask = this._mask || new Ext.LoadMask(document.body, {msg: i18n._('Loading')})
        if(!(this.vueHandle && this.modalProps && !this.modalProps.visible)){
            if(this._mask.disabled) this._mask.show()
            setTimeout(() => this.showModal(), 10)
            return
        }
        this._mask.hide()
        Ext.WindowMgr.bringToFront(this.windowProxy)
        this.modalProps.visible = true
    },

    destroy: function() {
        this.vueHandle?.unmount()
        this.vueMountPoint?.remove()
        delete this.windowProxy
    },

    hideModal: function() {
        this.modalProps.visible = false
    },

    getButtons: function() {
        return [{
            name: 'cancel',
            text: i18n._('Cancel'),
            iconCls: 'action_cancel',
            eventName: 'cancel'
        }, {
            name: 'ok',
            text: i18n._('Ok'),
            iconCls: 'action_saveAndClose',
            eventName: 'apply'
        }]
    },

})
