
Ext.reg('tinebase.mountpointpicker', Ext.extend(Tine.Tinebase.widgets.file.SelectionField, {
    specialType: 'filelocation',
    locationTypesEnabled: 'fm_node',
    allowMultiple:false,
    constraint: 'folder',

    setValue: async function(locations, record) {
        if (record?.json?.path) {
            this.value = {
                type: 'fm_node',
                fm_path: record.json.path,
                node_id: record.json,
            };
            this.setRawValue(record.json?.path);
        } else {
            this.supr().setValue.call(this, locations);
        }
    },

    getValue: function() {
        const location = _.get(this.value, '[0]');
        return location?.node_id?.id ?? '';
    },
}));