import BootstrapVueNext from 'bootstrap-vue-next'

Ext.form.VueAlert = Ext.extend(Ext.BoxComponent, {
    vueHandle: null,
    label: '',
    props: null,

    initComponent: async function() {
        const {createApp, h, reactive} = window.vue
        const {default: VueAlert} = await import(/* webpackChunkName: "Tinebase/vue/VueAlert"*/'./VueAlert.vue')
        this.props = reactive({
            label: this.label
        })
        this.vueHandle = createApp({
            render: () => h(VueAlert, this.props)
        });
        this.vueHandle.use(BootstrapVueNext)
        this.vueHandle.mount('#'+this.el.id)
    },

    beforeDestroy: function() {
        this.vueHandle.unmount()
    },

    setText: function(t){
        // in case the props or vueHandle is not initialized
        if(!this.props || !this.vueHandle) return;
        this.props.label = t
    }
})

Ext.reg('v-alert', Ext.form.VueAlert)