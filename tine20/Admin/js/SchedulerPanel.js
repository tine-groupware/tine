Tine.Admin.registerItem({
    text: 'Scheduler', // _('Scheduler')
    iconCls: 'admin-node-scheduler',
    pos: 900,
    dataPanelType: "Tine.Admin.SchedulerTaskGridPanel",
    hidden: !Tine.Admin.showModule('scheduler')
});
Tine.widgets.grid.RendererManager.register('Admin', 'SchedulerTask', 'application_id', function(applicationId) {
    const app = Tine.Tinebase.appMgr.getById(applicationId);
    if (!app) return '';

    this.translation = new Locale.Gettext();
    this.translation.textdomain(app.name);
    return this.translation.gettext(app.name);
});