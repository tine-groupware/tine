Ext.applyIf(String.prototype, {
    asString: function() {
        return new Promise(resolve => {
            resolve(this + '');
        })
    }
});