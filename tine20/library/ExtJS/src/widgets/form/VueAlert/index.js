// docs see https://bootstrap-vue.org/docs/components/alert
import BootstrapVueNext from 'bootstrap-vue-next'

Ext.form.VueAlert = Ext.extend(Ext.BoxComponent, {
    vueHandle: null,
    label: '',
    variant: 'primary',
    props: null,

    autoHeight: true,

    afterRender: async function() {
        this.supr().afterRender.call(this, arguments)

        const {createApp, h, reactive} = window.vue
        const {default: VueAlert} = await import(/* webpackChunkName: "Tinebase/js/VueAlert"*/'./VueAlert.vue')
        this.props = reactive({
            label: this.label,
            variant: this.variant
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
        if(!this.props || !this.vueHandle) {
            this.label = t
            return
        }
        this.props.label = t
    },

    onShow: function() {
        this.supr().onShow.call(this, arguments)
        this.getVisibilityEl().setStyle('display', 'initial')
    }
})

Ext.reg('v-alert', Ext.form.VueAlert)