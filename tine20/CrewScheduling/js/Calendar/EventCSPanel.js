/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as async from 'async';
import { getTypes } from "../Calendar/Model/eventType";
import { getRole, getRoles } from "../Model/schedulingRole";
import { createFromEventTypes } from "../Model/eventRoleConfig"

Ext.ux.ItemRegistry.registerItem('Calendar-Event-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    frame: true,
    requiredGrant: 'editGrant',
    layout: 'fit',

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('CrewScheduling');
        this.title = this.app.getTitle();

        this.eventRolesGrid = new Tine.widgets.grid.PickerGridPanel({
            height: 300,
            recordClass: 'CrewScheduling.EventRoleConfig',
            isFormField: true,
            isMetadataModelFor: 'role',
            allowCreateNew: true,
            refIdField: 'cal_event',
            allowDuplicatePicks: true,
            editDialogConfig: {
                mode: 'local'
            }
        });

        this.eventRolesGrid.on('beforeaddrecord', this.onValidateRecord, this);
        this.eventRolesGrid.on('beforeupdaterecord', this.onValidateRecord, this);
        this.eventRolesGrid.on('validateedit', this.onValidateEdit, this);

        this.items = [
            this.eventRolesGrid
        ];
        this.supr().initComponent.call(this);
    },

    onValidateEdit: function(o) {
        if (o.field === 'event_types') {
            const r = o.record.copy();
            r.set(o.field, o.value);
            return this.onValidateRecord(r, this);
        }
    },

    onValidateRecord: function(r) {
        const newRoleId = r.get('role').id;
        const newTypesIds = _.map(r.get('event_types'), 'id');
        const conflictingTypeIds = _.reduce(_.map(this.eventRolesGrid.store.data.items, 'data'), (a, d) => {
            const typeIds = _.map(d.event_types, 'id');
            return a.concat(r.id !== d.id && d.role.id === newRoleId ? (newTypesIds?.length ?_.intersection(newTypesIds, typeIds) : (typeIds?.length ? [] : [null])) : []);
        }, []);

        if (conflictingTypeIds.length) {
            Ext.Msg.alert(this.app.i18n._("Role Config can't be Added"), this.app.formatMessage('A role config for {role} with event types "{types}" already exists.', {
                role: r.get('role').name,
                types: newTypesIds.length ? _.map(conflictingTypeIds, (id) => {
                    return _.find(r.get('event_types'), { id }).name;
                }).join(', ') : this.app.i18n._('without event type')
            }));
            return false;
        }
    },

    // event type of event changes
    onEventTypesChange: async function(field, eventTypes) {
        // NOTE we recalculate all stuff here as merging is really complicated
        //      e.g. deleting a role might change the complete situation an all recent calculation

        const current = this.editDialog.record.get('event_types');
        if (_.difference(_.map(current, 'id'), _.map(eventTypes, 'id')).length) {
            // recalculate config as removing event_types changes the complete situation
            const roleConfigs = await createFromEventTypes(eventTypes);
            this.eventRolesGrid.store.loadData(roleConfigs);
        } else {
            let added = _.difference(_.map(eventTypes, 'id'), _.map(current, 'id'));
            added = _.filter(eventTypes, t => { return added.indexOf(t.id) >= 0})
            if (added.length) {
                const modified = _.map(_.filter(this.eventRolesGrid.store.data.items, 'dirty'), 'id');
                // NOTE: this alters existing records
                const roleConfigs = await createFromEventTypes(added, this.eventRolesGrid.store.getData());
                this.eventRolesGrid.store.mergeData(roleConfigs);
                _.forEach(_.filter(this.eventRolesGrid.store.data.items, 'dirty'), (r) => {
                    if (_.indexOf(modified, r.id) < 0) r.commit();
                });
            }
        }
    },

    onRecordLoad: async function(editDialog, record) {
        let roleConfigs = record.get('cs_roles_configs') || [];

        if (! roleConfigs.length) {
            // NOTE: we can't handle if user deletes all configs...
            roleConfigs = await createFromEventTypes(record.get('event_types'));
        }

        this.eventRolesGrid.store.loadData(roleConfigs);
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {
        const eventTypes = record.get('event_types');

        this.ownerCt[(eventTypes?.length ? 'un' : '') +'hideTabStripItem'](this);

        const data = [];
        let isModified = !!this.eventRolesGrid.store.removed.length;
        _.each(this.eventRolesGrid.store.data.items, (r) => {
            if (r.phantom || r.isModified()) isModified = true;
            data.push(r.getData());
        });
        if (isModified) {
            record.set('cs_roles_configs', data);
            // debugger
        }
    },

    setOwnerCt: function(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.editDialog.getForm().findField('event_types').on('change', this.onEventTypesChange, this)

        // NOTE: in case record is already loaded
        if (! this.setOwnerCt.initialOnRecordLoad) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }

    }

}), 2);