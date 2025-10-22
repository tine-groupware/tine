/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'

/**
 * @namespace Tine.Calendar.Model
 * @class Resource
 * @extends Record
 * Resource Record Definition
 */
const Resource = Record.create(Record.genericFields.concat([
    {name: 'id'},
    {name: 'name'},
    {name: 'hierarchy'},
    {name: 'description'},
    {name: 'email'},
    {name: 'max_number_of_people', type: 'int'},
    {name: 'type', type: 'keyField', keyFieldConfigName: 'resourceTypes'},
    {name: 'status', type: 'keyField', keyFieldConfigName: 'attendeeStatus'},
    {name: 'status_with_grant', type: 'keyField', keyFieldConfigName: 'attendeeStatus'},
    {name: 'busy_type', type: 'keyField', keyFieldConfigName: 'freebusyTypes'},
    {name: 'location_address'},
    {name: 'suppress_notification', type: 'bool'},
    {name: 'tags'},
    {name: 'notes'},
    {name: 'grants'},
    { name: 'attachments'},
    { name: 'relations',   omitDuplicateResolving: true},
    { name: 'customfields', omitDuplicateResolving: true},
    {name: 'color'}
]), {
    appName: 'Calendar',
    modelName: 'Resource',
    idProperty: 'id',
    titleProperty: 'name',
    containerProperty: 'container_id',
    // ngettext('Resource', 'Resources', n); gettext('Resources');
    recordName: 'Resource',
    recordsName: 'Resources',

    initData: function() {
        if (Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources')) {
            const account_grants = _.get(this, this.grantsPath, {});

            _.assign(account_grants, {
                'resourceInviteGrant': true,
                'resourceReadGrant': true,
                'resourceEditGrant': true,
                'resourceExportGrant': true,
                'resourceSyncGrant': true,
                'resourceAdminGrant': true
            });
            _.set(this, this.grantsPath, account_grants);
        }
    }
});

/**
 * get default data for a new resource
 *
 * @return {Object} default data
 * @static
 */
Resource.getDefaultData = function() {
    // add admin (and other) grant for resource managers
    var grants = Tine.Tinebase.common.hasRight('manage', 'Calendar', 'resources') ? [{
        account_id: Tine.Tinebase.registry.get('currentAccount').accountId,
        account_type: "user",
        account_name: Tine.Tinebase.registry.get('currentAccount').accountDisplayName,
        'resourceInviteGrant': true,
        'resourceReadGrant': true,
        'resourceEditGrant': true,
        'resourceExportGrant': true,
        'resourceSyncGrant': true,
        'resourceAdminGrant': true
    }]: []

    grants.push({
        account_id: "0",
        account_type: "anyone",
        account_name: i18n._('Anyone'),
        resourceInviteGrant: true,
        eventsFreebusyGrant: true
    });

    var data = {
        grants: grants
    };

    return data;
};

Resource.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar');

    return [
        {label: i18n._('Quick Search'), field: 'query', operators: ['contains']},
        {label: app.i18n._('Name'), field: 'name'},
        {label: app.i18n._('Calendar Hierarchy/Name'), field: 'hierarchy'},
        {label: app.i18n._('Email'), field: 'email'},
        {label: app.i18n._('Description'), field: 'description', operators: ['contains', 'notcontains']},
        {label: app.i18n._('Maximum number of attendee'), field: 'max_number_of_people'},
        {
            label: app.i18n._('Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'resourceTypes'
        },
        {
            label: app.i18n._('Default attendee status'),
            field: 'status',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'attendeeStatus'
        },
        {
            label: app.i18n._('Busy Type'),
            field: 'type',
            filtertype: 'tine.widget.keyfield.filter',
            app: app,
            keyfieldName: 'freebusyTypes'
        },
        {filtertype: 'tinebase.tag', app: app}
    ];
};

export default Resource