/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

Tine.Addressbook.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    activeContentType: 'Contact',
    contentTypes: [
        {model: 'Contact', requiredRight: null, singularContainerMode: false}
    ],

    initComponent: function() {
        var app = Tine.Tinebase.appMgr.get('Addressbook');

        if (app.featureEnabled('featureListView')) {
            this.contentTypes.push({model: 'List', requiredRight: null, singularContainerMode: false});
        }

        if (app.featureEnabled('featureStructurePanel')) {
            this.contentTypes.push({
                contentType: 'structure',
                app: app,
                text: app.i18n._('Structure'), // _('Structure')
                iconCls: 'AddressbookStructure',
                xtype: 'addressbook.structurepanel'
            });
        }

        // only show if calendar is available and user has manage_resources right
        if (app.featureEnabled('featureResources')
            && Tine.Tinebase.common.hasRight('run', 'Calendar')
            && Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources')
        ) {
            var cal = Tine.Tinebase.appMgr.get('Calendar');
            this.contentTypes.push({
                contentType: 'resource',
                app: cal,
                text: cal.i18n._('Resources'),
                iconCls: 'CalendarResource',
                xtype: 'calendar.resourcegridpanel',
                ownActionToolbar: false,
                singularContainerMode: true
            });
        }

        Tine.Addressbook.MainScreen.superclass.initComponent.call(this);
    }
});
