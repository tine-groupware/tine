Tine.Tinebase.appMgr.isInitialised('Calendar').then(() => {

    if (Tine.Calendar.registry.get('floorplansEnabled')) {
        const app = Tine.Tinebase.appMgr.get('Calendar');
        const openFloorplanBtn = Ext.extend(Ext.Button, {
            text: app.i18n._('Floorplan'),
            scale: 'medium',
            minWidth: 60,
            rowspan: 2,
            iconAlign: 'top',
            requiredGrant: 'readGrant',
            iconCls: 'cal-floorplan-view-type',
            handler: () => {
                window.open(Tine.Tinebase.common.getUrl() + '/Calendar/view/floorplan/', '_blank');
            }
        });

        Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ViewBtnGrp', openFloorplanBtn, 40);
    }
});
