/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import waitFor from "util/waitFor.es6";
import * as async from 'async'
import { HTMLProxy, Expression } from 'twingEnv.es6'
import { getFromEvent } from '../Model/eventRoleConfig'
import * as eRC from "../Model/eventRoleConfig";
import AttendeeValidation from '../Model/AttendeeValidation';
import * as markdown from 'util/markdown'
import PollReply from "../Model/PollReply";

const plugin = {
    init(gridPanel) {
        if (gridPanel.canonicalName !== 'AttendeeGrid') return;

        const app = Tine.Tinebase.appMgr.get('CrewScheduling');

        const attendeeValidation = new AttendeeValidation({ formatMessage: app.formatMessage.bind(app) });

        let pollReplyMap = gridPanel.editDialog.record.phantom ? Promise.resolve({}) :
            Tine.CrewScheduling.searchPollReplys([{ field: 'event_ref', operator: 'equals', value: PollReply.getEventRef(gridPanel.editDialog.record)}]).then(response => {
                return _.reduce(response.results, (accu, poll_reply) => {
                    const participant = poll_reply.poll_participant_id
                    const poll = participant.poll_id
                    // contactid.eventid.roleid.reply
                    return _.set(accu, `${participant.contact_id}.${PollReply.getEventRef(gridPanel.editDialog.record)}.${poll.scheduling_role}`, Object.assign({poll, participant}, poll_reply))
                }, {})
            });

        gridPanel.colModel.config.splice(4, 0, new Ext.grid.Column({
            id: 'crewscheduling_roles',
            dataIndex: 'crewscheduling_roles',
            width: 150,
            sortable: false,
            header: app.i18n._('Crew Scheduling Roles'),
            renderer: (v,c,r) => {
                if (r.id && String(r.id).match(/^new-/)) return '';
                return new HTMLProxy(new Promise(async (resolve) => {
                    Promise.all(_.map(v, async (attendeeRole) => {
                        _.set(r, 'data.user_id.pollReplies', _.get(await pollReplyMap, r.data.user_id.id, {}))
                        const baseValidation = await attendeeValidation.validateBasics(r, gridPanel.record, attendeeRole);
                        const validationResult = attendeeValidation.mergeValidation(baseValidation, await attendeeValidation.validateEventRoleConfigCapability(r, gridPanel.record, attendeeRole));
                        const title = await Tine.Tinebase.data.Record.setFromJson(attendeeRole.role, 'CrewScheduling.SchedulingRole').getTitle().asString();
                        return { validationResult, title };
                    })).then((values) => {
                        return _.map(values, (value, idx) => {
                            const { validationResult, title } = value;
                            const types = _.map(_.map(_.get(v, `[${idx}].event_types`, []), 'name'), Ext.util.Format.htmlEncode);
                            return `<span ext:qtip="${app.i18n._('Event Types')}:<br />${_.join(types, '<br />')}">${Ext.util.Format.htmlEncode(title)}</span>
                                    <span ext:qtip="${app.i18n._('Edit Event Types')}" class="action_pencil tine-grid-row-action-icon" data-idx="${idx}"></span>
                                    <span ext:qtip="${app.i18n._('Invalid service assignment')}" class="x-dialog-warn tine-grid-row-action-icon" data-idx="${idx}" style="display: ${validationResult.isValid ? 'none' : 'inline-block'}"></span>`;
                        }).join(', ');
                    }).then(html => {
                        resolve(new Expression(html));
                    })
                }))
            },
            editor: Ext.create({
                xtype:'tinerecordspickercombobox',
                name: 'crewscheduling_roles',
                recordClass: 'CrewScheduling.AttendeeRole',
                refIdField: 'record',
                searchComboConfig: {useEditPlugin: false},
                editDialogConfig: {mode:  'local'},
                isMetadataModelFor: 'role',
                listeners: {
                    // autofill event_types of role
                    select: async (picker, attendeeRole) => {

                        // arg: this vue crashes multipicker (using beforeselect)
                        if (_.isArray(attendeeRole)) { return }; // @NOTE multislect picker fires select on single record select and on multi changes

                        const role = attendeeRole.get('role');
                        const roleConfigs = await getFromEvent(gridPanel.record, role);
                        const roleConfig = roleConfigs[roleConfigs.length < 2 ? 0 : await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                            title: app.formatMessage('Choose event types'),
                            questionText: app.formatMessage('The crew scheduling config of this event requires different attendee for the listed event types. Please select the event type(s) for this attendee.', {}),
                            allowCancel: false,
                            options: roleConfigs.map((config, idx) => {
                                return {
                                    text: _.map(config.event_types, 'name').join(', ') || `<i>${app.i18n._('Without Event Type')}</i>`,
                                    name: idx,
                                }
                            })
                        })];

                        // note: we're too late after MultiOptionsDialog, grid has cloned data of attendeeRole from picker.getValue
                        attendeeRole = _.find(_.compact(_.flatten(_.map(gridPanel.store.data.items, 'data.crewscheduling_roles'))), { id: attendeeRole.id }) || attendeeRole.data;
                        attendeeRole.event_types = _.cloneDeep(roleConfig?.event_types);
                        gridPanel.view.refresh();
                    }
                }
            })
        }));

        gridPanel.on('beforeedit', (o) => {

            // allow editing of roles
            if (o.field === 'crewscheduling_roles') {
                // do not allow to set roles for new attendee for now as we don't support searching for users
                // with given roles
                if (o.record.id && String(o.record.id).match(/^new-/)) {
                    o.cancel = true;
                    return;
                }

                o.cancel = ['any', 'user'].indexOf(o.record.get('user_type')) < 0;

                if(o.e && o.e.getTarget('.tine-grid-row-action-icon')) {
                    o.cancel = true;

                    if (o.e.getTarget('.action_pencil')) {
                        const idx = o.e.getTarget('.tine-grid-row-action-icon').dataset.idx;
                        const roles = Tine.Tinebase.common.assertComparable(_.cloneDeep(
                            Tine.Tinebase.common.assertComparable(o.record.get('crewscheduling_roles'))));

                        Tine.CrewScheduling.AttendeeRoleEditDialog.openWindow({
                            record: roles[idx],
                            mode: 'local',
                            fixedFields: {
                                role: '###CURRENT###'
                            },
                            listeners: {
                                scope: this,
                                'update': (updatedRole) => {
                                    roles[idx] = JSON.parse(updatedRole);
                                    o.record.set('crewscheduling_roles', roles);
                                }
                            }
                        });

                    } else if (o.e.getTarget('.x-dialog-warn')) {
                        const idx = o.e.getTarget('.tine-grid-row-action-icon').dataset.idx;
                        const role = _.get(o.record.get('crewscheduling_roles'), idx)

                        Promise.resolve().then(async () => {
                            _.set(o.record, 'data.user_id.pollReplies', _.get(await pollReplyMap, o.record.data.user_id.id, {}))
                            const validationResult = await attendeeValidation.validateBasics(o.record, gridPanel.record, role);
                            attendeeValidation.mergeValidation(validationResult, await attendeeValidation.validateEventRoleConfigCapability(o.record, gridPanel.record, role));
                            Ext.Msg.show({
                                buttons: Ext.Msg.OK,
                                icon: Ext.MessageBox.ERROR_MILD,
                                title: app.i18n._('Invalid service assignment'),
                                closeable: false,
                                msg: await markdown.parse((await async.map(validationResult.messages, async message => {
                                    return `* ${ message }`
                                })).join('\n'))
                            });
                        })
                    }
                }

                return;
            }

            // search for matching individuals if roles are set
            if (o.field === 'user_id') {
                // const colModel = o.grid.getColumnModel();
                // const attendeePickerCombo = colModel.config[o.column].editor.field;
                //
                // if(o.record.get('crewscheduling_roles')?.length) {
                //     attendeePickerCombo.additionalFilters = [{field: 'type', operator: 'oneof', value: ['user']}];
                // }

            }
        }, this);

        gridPanel.on('afteredit', async (o) => {
            // re run validations
            gridPanel.view.refresh();

            // save roles for next newAttendee record
            if (o.field === 'crewscheduling_roles') {
                this.defaultAttendeeRoles = o.value;
            }

            // reset roles if type is not appropriate
            if (o.field === 'user_type' && ['any', 'user'].indexOf(o.value) < 0) {
                o.record.set('crewscheduling_roles', null);
            }

            if (o.field === 'user_id') {
                const event = gridPanel.record
                const attendee = o.record // attendee _not_ member from csMemberStore!
                let options = await async.reduce( await eRC.getFromEvent(event), [], async (memo, eventRoleConfig) => {
                    _.set(attendee, 'data.user_id.pollReplies', _.get(await pollReplyMap, attendee.data.user_id.id, {}))
                    const baseValidation = await attendeeValidation.validateBasics(attendee, event, eventRoleConfig)
                    return memo.concat(baseValidation.isValid && (await attendeeValidation.validateEventRoleConfigCapability(attendee, event, eventRoleConfig)).isValid ? {
                        eventId: event.id, eventRoleConfig,
                        name: Tine.Tinebase.data.Record.generateUID(),
                        text: `${eventRoleConfig.role.name} (${_.map(eventRoleConfig.event_types, 'name').join(', ')  || this.app.i18n._('Without Event Type')})`,
                        checked: memo.length === 0
                    } : [])
                })

                if (options.length) {
                    try {
                        let toAssign = await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                            title: app.formatMessage('Assign Services?'),
                            questionText: app.formatMessage('The person is capable to perform the following services. Which of these should be assigned?'),
                            allowMultiple: true,
                            allowEmpty: false,
                            allowCancel: true,
                            height: options.length * 30 + 100,
                            options: options
                        })
                        o.record.set('crewscheduling_roles', Tine.Tinebase.common.assertComparable(_.map(toAssign, (option) => {
                            return {
                                attendee: null,
                                role: option.eventRoleConfig.role,
                                event_types: option.eventRoleConfig.event_types,
                            }
                        })))

                    } catch (e) {/* USERABORT */}
                }
            }

        }, this);

        gridPanel.on('removeentry', async () => {
            // re run validation
            Tine.Calendar.Model.Attender.getAttendeeStore.getData(gridPanel.store, gridPanel.record);
            gridPanel.view.refresh();
        }, this, {buffer: 100})

        // set default roles
        /*
        gridPanel.on('beforenewattendee', (c, newAttendee, record) => {
            newAttendee.set('crewscheduling_roles', this.defaultAttendeeRoles);
        });
         */

        /*
        gridPanel.on('beforeshowctxmenu', async (gridPanel, row, ctxMenu, e) => {
            const attendee = gridPanel.store.getAt(row);
            const roles = attendee.get('crewscheduling_roles');
            const event = gridPanel.record;

            if (roles.length) {
                // have all event types of event
                const roleConfigs = await Tine.CrewScheduling.Model.EventRoleConfig.getFromEvent(event);

                ctxMenu.add('-');
                ctxMenu.add({
                    text: app.i18n._('Crew Scheduling Roles'),
                    menu: roles.map((role) => {
                        const validTypes = _.reduce(roleConfigs, (acc, roleConfig) => {
                            return acc.concat(roleConfig.role.id === role.role.id ? roleConfig.event_types || [] : []);
                        }, []);

                        return {
                            text: role.role.name,
                            menu: [{
                                xtype: 'menutextitem',
                                text: app.i18n._('Event Types')
                            }].concat(_.map(validTypes, (type) => {
                                return {
                                    xtype: 'menucheckitem',
                                    checked: false,
                                    text: _.get(type, 'name', 'without type')
                                }
                            }))
                        }
                    })
                });
            }
        });
        */
    }
};

// Tine.Calendar.EventContextAttendeesItem = Ext.extend(Ext.menu.Item, {
//     event: null,
//     datetime: null,
//
//     initComponent: function () {
//         this.app = Tine.Tinebase.appMgr.get('CrewScheduling');
//
//         this.
//     }
// });


Ext.ux.pluginRegistry.register('Calendar.AttendeeGridPanel', plugin);
export default plugin;