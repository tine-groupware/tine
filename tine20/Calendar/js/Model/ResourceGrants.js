/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of a grant
 */
Tine.Calendar.Model.ResourceGrants = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'account_id'},
    {name: 'account_type'},
    {name: 'account_name'},
    {name: 'resourceInviteGrant',    type: 'boolean'},
    {name: 'resourceStatusGrant',    type: 'boolean'},
    {name: 'resourceNotificationGrant',    type: 'boolean'},
    {name: 'resourceReadGrant',      type: 'boolean'},
    {name: 'resourceEditGrant',      type: 'boolean'},
    {name: 'resourceExportGrant',    type: 'boolean'},
    {name: 'resourceSyncGrant',      type: 'boolean'},
    {name: 'resourceAdminGrant',     type: 'boolean'},
    {name: 'eventsAddGrant',         type: 'boolean'},
    {name: 'eventsReadGrant',        type: 'boolean'},
    {name: 'eventsExportGrant',      type: 'boolean'},
    {name: 'eventsSyncGrant',        type: 'boolean'},
    {name: 'eventsFreebusyGrant',    type: 'boolean'},
    {name: 'eventsEditGrant',        type: 'boolean'},
    {name: 'eventsDeleteGrant',      type: 'boolean'}
], {
    appName: 'Calendar',
    modelName: 'ResourceGrants',
    idProperty: 'id',
    titleProperty: 'account_name',
    // ngettext('Resource Grant', 'Resource Grants', n); gettext('Resource Grant');
    recordName: 'Resource Grant',
    recordsName: 'Resource Grants'
});

// register grants for calendar containers
Tine.widgets.container.GrantsManager.register('Calendar_Model_Event', function(container) {
    var _ = window.lodash,
        me = this,
        grantsModelName = _.get(container, 'xprops.Tinebase.Container.GrantsModel', 'Tinebase_Model_Grants');

    if (grantsModelName == 'Calendar_Model_ResourceGrants') {
        // resource events container
        return [
            'resourceInvite',
            'resourceStatus',
            'resourceNotification',
            'resourceRead',
            'resourceEdit',
            // 'resourceExport', // should be resource-admin?
            // 'resourceSync',   // no sync targets - let's save space
            'resourceAdmin',  // not yet used? - let's save space
            'eventsAdd',
            'eventsRead',
            'eventsExport',
            'eventsSync',
            'eventsFreebusy',
            'eventsEdit',
            'eventsDelete'
        ];

    } else {
        var grants = Tine.widgets.container.GrantsManager.defaultGrants(container);

        // normal events container
        if (container.type == 'personal') {
            grants.push('freebusy');
        }
        if (container.type == 'personal' && container.capabilites_private) {
            grants.push('private');
        }

        return grants;
    }
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    resourceInviteGrantTitle: 'Invite Resource', // i18n._('Invite Resource')
    resourceInviteGrantDescription: 'The permission to invite the resource to an event', // i18n._('The permission to invite the resource to an event')
    resourceStatusGrantTitle: 'Resource status', // i18n._('Resource status')
    resourceStatusGrantDescription: 'The permission to set the resource attendee status', // i18n._('The permission to set the resource attendee status')
    resourceNotificationGrantTitle: 'Resource notification', // i18n._('Resource notification')
    resourceNotificationGrantDescription: 'The permission to receive notifications for this resource', // i18n._('The permission to receive notifications for this resource')
    resourceReadGrantTitle: 'Read Resource', // i18n._('Read Resource')
    resourceReadGrantDescription: 'The permission to read the resource itself', // i18n._('The permission to read the resource itself')
    resourceEditGrantTitle: 'Edit Resource', // i18n._('Edit Resource')
    resourceEditGrantDescription: 'The permission to edit the resource itself', // i18n._('The permission to edit the resource itself')
    resourceExportGrantTitle: 'Export Resource', // i18n._('Export Resource')
    resourceExportGrantDescription: 'The permission to export the resource itself', // i18n._('The permission to export the resource itself')
    resourceSyncGrantTitle: 'Sync Resource', // i18n._('Sync Resource')
    resourceSyncGrantDescription: 'The permission to synchronise the resource itself', // i18n._('The permission to synchronise the resource itself')
    resourceAdminGrantTitle: 'Resource Admin', // i18n._('Resource Admin')
    resourceAdminGrantDescription: 'The permission to administrate the resource itself', // i18n._('The permission to administrate the resource itself')
    eventsAddGrantTitle: 'Add Events', // i18n._('Add Events')
    eventsAddGrantDescription: 'The permission to directly add events to this resource calendar', // i18n._('The permission to directly add events to this resource calendar')
    eventsReadGrantTitle: 'Read Events', // i18n._('Read Events')
    eventsReadGrantDescription: 'The permission to read events from this resource calendar', // i18n._('The permission to read events from this resource calendar')
    eventsExportGrantTitle: 'Export Events', // i18n._('Export Events')
    eventsExportGrantDescription: 'The permission to export events from this resource calendar', // i18n._('The permission to export events from this resource calendar')
    eventsSyncGrantTitle: 'Sync Events', // i18n._('Sync Events')
    eventsSyncGrantDescription: 'The permission to synchronise events from this resource calendar. A Read Events grant is required.', // i18n._('The permission to synchronise events from this resource calendar. A Read Events grant is required.')
    eventsFreebusyGrantTitle: 'Events Free/Busy', // i18n._('Events Free/Busy')
    eventsFreebusyGrantDescription: 'The permission to get free/busy information of events from this resource calendar', // i18n._('The permission to get free/busy information of events from this resource calendar')
    eventsEditGrantTitle: 'Edit Events', // i18n._('Edit Events')
    eventsEditGrantDescription: 'The permission to edit events directly saved in this resource calendar', // i18n._('The permission to edit events directly saved in this resource calendar')
    eventsDeleteGrantTitle: 'Delete Events', // i18n._('Delete Events')
    eventsDeleteGrantDescription: 'The permission to delete events directly stored in this resource calendar', // i18n._('The permission to delete events directly stored in this resource calendar')

    freebusyGrantTitle: 'Free/Busy', // i18n._('Free/Busy')
    freebusyGrantDescription: 'The permission to get free/busy information of events in this calendar', // i18n._('The permission to get free/busy information of events in this calendar')
    privateGrantTitle: 'Private', // i18n._('Private')
    privateGrantDescription: 'The permission to access events marked as private in this calendar', // i18n._('The permission to access events marked as private in this calendar')

});