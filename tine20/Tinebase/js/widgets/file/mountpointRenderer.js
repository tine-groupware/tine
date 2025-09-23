
Ext.ns('Tine.Tinebase.widgets.file');

Tine.widgets.grid.RendererManager.register('Tinebase', 'Tree_FlySystem', 'mount_point', function(v) {
    if (v === "") return '';
    const obj = JSON.parse(v);
    if (obj?.path) return obj.path;
    if (arguments[2]?.json?.mount_point?.path) return arguments[2]?.json.mount_point.path;
    return '';
});
