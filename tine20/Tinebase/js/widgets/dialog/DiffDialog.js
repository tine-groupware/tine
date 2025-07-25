
const DiffDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    record: null,
    editDialog: null,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Tinebase')
        // this.elementId = `ace-diff-${Ext.id()}`
        this.items = [{
            xtype: 'box',
            border: false,
            tag: 'div',
            id: this.elementId,
            listeners: {
                scope: this,
                afterrender: this.onElementAfterRender
            }
        }]

        return this.supr().initComponent.call(this)
    },

    onElementAfterRender(cmp) {
        Promise.all([
            import(/* webpackChunkName: "Tinebase/js/ace" */ 'widgets/ace'),
            import(/* webpackChunkName: "Tinebase/js/ace-diff" */ 'ace-diff'),
            import(/* webpackChunkName: "Tinebase/js/ace-diff-styles" */ 'ace-diff/dist/ace-diff.min.css')
            // import(/* webpackChunkName: "Tinebase/js/ace-diff-dark-styles" */ 'ace-diff/dist/ace-diff-dark.min.css')
        ]).then(args => {
            const AceDiff = args[1].default

            this.differ = new AceDiff(Object.assign({
                // ace: window.ace,
                element: `#${cmp.id}`,
            }, this.diffConfig));
        })

    },
})

Tine.Tinebase.dialog.DiffDialog = DiffDialog