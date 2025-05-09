const OneIsTruePlugin = function(config) {
    Ext.apply(this, config);
}

Object.assign(OneIsTruePlugin.prototype, {
    /**
     * @cfg {String}
     */
    field: null,

    init: function(grid) {
        const fn = (store, record, operation) => {
            if (!!+record.get(this.field)) {
                store.each((r) => {
                    if (r !== record && !!+r.get(this.field)) {
                        r.set(this.field, false);
                    }
                })
            }
        }

        grid.store.on('add', (store, rs) => {
            rs.forEach((r) => {fn(store, r)});
        });
        grid.store.on('update', fn);
    }
})


Ext.preg('tb-grid-one-is-true', OneIsTruePlugin);

export default OneIsTruePlugin
