/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail.sieve');

 Tine.Felamimail.sieve.NotificationDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'NotificationWindow_',
    appName: 'Felamimail',
    loadRecord: true,
    tbarItems: [],
    evalGrants: false,
    readonlyReason: false,
    
    initComponent: function() {
        this.recordClass = Tine.Felamimail.Model.Account;
        this.recordProxy = Tine.Felamimail.accountBackend;
        
        Tine.Felamimail.sieve.NotificationDialog.superclass.initComponent.call(this);
    }, 
    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: function() {
    },
    
    /**
     * executed after record got updated from proxy
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        this.getForm().loadRecord(this.record);
    
        // load sieve notification emails
        const data = this.record.data.sieve_notification_email.split(',');
        this.sieveNotifyGrid.setStoreFromArray(data.map((e) => {return {'email': e}}));

        var title = String.format(this.app.i18n._('Notification for {0}'), this.record.get('name'));
        this.window.setTitle(title);

        this.loadMask.hide();
    },
    
     onRecordUpdate: function () {
         Tine.Felamimail.sieve.NotificationDialog.superclass.onRecordUpdate.call(this);
         // update sieve notification emails
         const notifyEmails = this.sieveNotifyGrid.getFromStoreAsArray();
         this.record.set('sieve_notification_email', notifyEmails.filter(e => e?.email).map(e => e?.email).join());
     },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Notifications'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1,
                },
                items: [[this.initSieveEmailNotifyGrid()]]
            }]
        };
    },
    
     initSieveEmailNotifyGrid: function(additionConfig) {
        const app = Tine.Tinebase.appMgr.get('Felamimail');
        const config = _.assign({
            autoExpandColumn: 'email',
            quickaddMandatory: 'email',
            frame: false,
            useBBar: true,
            height: 200,
            columnWidth: 1,
            recordClass: Ext.data.Record.create([
                { name: 'email' }
            ])
        }, additionConfig);
        
        this.sieveNotifyGrid = new Tine.widgets.grid.QuickaddGridPanel(
            Ext.apply({
                cm: new Ext.grid.ColumnModel([{
                    id: 'email',
                    header: app.i18n._('Notification Email'),
                    dataIndex: 'email',
                    hideable: false,
                    sortable: true,
                    quickaddField: new Ext.form.TextField({
                        emptyText: app.i18n.gettext('Add a new email...'),
                        vtype: 'email'
                    }),
                    editor: new Ext.form.TextField({allowBlank: false})
                }])
            }, config)
        );
        return this.sieveNotifyGrid;
    },
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.sieve.NotificationDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 320,
        height: 200,
        name: Tine.Felamimail.sieve.NotificationDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.sieve.NotificationDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};
