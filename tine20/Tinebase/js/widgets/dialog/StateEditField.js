import FieldTriggerPlugin from "ux/form/FieldTriggerPlugin"

const StateEditField = Ext.extend(Ext.form.TriggerField, {
    hideTrigger: true,
    readOnly: true,
    anchor: '90%',
    labelSeparator: '',

    initComponent() {
        this.fieldLabel = formatMessage('Client states')
        this.description = formatMessage('Client states')

        // NOTE: we don't have a complete list of states here... we only "know" the state which are saved so far.
        this.allStates = Ext.state.Manager.getProvider().state
        this.value = formatMessage('{numSavedStates, plural, one {# saved state} other {# saved states}}', {
            numSavedStates: Object.keys(this.allStates).length
        })

        this.plugins = this.plugins || [];
        this.plugins.push(new FieldTriggerPlugin({
            triggerClass: 'action_edit',
            qtip: formatMessage('Edit states'),
            onTriggerClick: _.bind(this.openStateGrid, this)
        }));
        this.plugins.push(new FieldTriggerPlugin({
            triggerClass: 'action_delete',
            qtip: formatMessage('Delete All States'),
            onTriggerClick: _.bind(this.clearAllStates, this)
        }));

        this.on('render', () => {
            this.setVisible(!this.ownerCt.adminMode)
        })
        this.supr().initComponent.call(this)
    },

    async clearAllStates() {
        if (await Ext.MessageBox.show({
            icon: Ext.MessageBox.QUESTION,
            buttons: Ext.MessageBox.YESNO,
            title: formatMessage('Delete All States?'),
            msg: formatMessage('Really delete all client states)'),
        }) === 'yes') {
            _.each(this.allStates, (value, key) => {
                Ext.state.Manager.clear(key)
            })
        }
    },

    openStateGrid() {
        Tine.WindowFactory.getWindow({
            width: 600,
            height: 800,
            name: `Tinebase-StateGrid`,
            contentPanelConstructor: 'Tine.Tinebase.dialog.Dialog',
            contentPanelConstructorConfig: {
                contentPanelConstructorInterceptor: async (config) => {
                    config.window.setTitle(formatMessage('State List'))
                    config.store = new Ext.data.ArrayStore({
                        fields: [
                            {name: 'key'},
                            {name: 'value'}
                        ]
                    })
                    config.store.loadData(_.map(this.allStates, (value, key) => [key, value] ))
                    config.items = [{
                        xtype: 'grid',
                        store: config.store,
                        stripeRows: true,
                        autoExpandColumn: 'key',
                        columns: [
                            {id:'key',header: 'Key', width: 160, sortable: true, dataIndex: 'key'},
                            {id: 'actions', header: 'Actions', renderer: (val) => {
                                    return `<div class="tine-row-action-icons" style="width: 58px;">
                                            <div class="tine-recordclass-gridicon action_edit" data-action="edit" ext:qtip="${formatMessage('Edit')}">&nbsp;</div>
                                            <div class="tine-recordclass-gridicon action_delete" data-action="delete" ext:qtip="${formatMessage('Delete')}">&nbsp;</div>
                                        </div>`;
                                }}
                        ],
                        listeners: {
                            render: (cmp) => {
                                cmp.mon(cmp.el, 'click', async (e) => {
                                    const el = e.getTarget('.tine-recordclass-gridicon')
                                    if (el && !Ext.fly(el).hasClass('x-item-disabled')) {
                                        const row = cmp.view.findRowIndex(el)
                                        const state = cmp.store.getAt(row)
                                        const key = state.get('key')
                                        const action = el.dataset.action
                                        const win = cmp.findParentBy(function (c) { return c.window }).window.popup

                                        switch (action) {
                                            case 'delete':
                                                if (await win.Ext.MessageBox.show({
                                                    icon: Ext.MessageBox.QUESTION,
                                                    buttons: Ext.MessageBox.YESNO,
                                                    title: formatMessage('Delete State?'),
                                                    msg: formatMessage('Really delete state "{ key }"?', {key}),
                                                }) === 'yes') {
                                                    Ext.state.Manager.clear(key)
                                                    cmp.store.remove(state)
                                                }
                                                break
                                            case 'edit':
                                                this.openStateEditDialog({win, state, key,
                                                    store: cmp.store
                                                })
                                                break
                                        }
                                    }
                                })
                            }
                        }
                    }]
                }
            }
        })
    },
    openStateEditDialog(config) {
        const {win, state, key, store} = config
        win.Tine.WindowFactory.getWindow({
            width: 600,
            height: 800,
            name: `Tinebase-StateEditDialog`,
            contentPanelConstructor: 'Tine.Tinebase.dialog.Dialog',
            contentPanelConstructorConfig: {
                contentPanelConstructorInterceptor: async (config) => {
                    config.window.setTitle(formatMessage('Edit state "{ key }"', {key}))
                    config.items = [{
                        xtype: 'tw-acefield',
                        fieldLabel: key,
                        value: state.get('value'),
                    }]
                    config.listeners = {
                        'beforeapply': async(data, dlg) => {
                            const field = dlg.items.get(0)
                            if (!field.isValid()) {
                                await dlg.window.popup.Ext.MessageBox.show({
                                    icon: Ext.MessageBox.INFO_FAILURE,
                                    buttons: Ext.MessageBox.OK,
                                    title: formatMessage('Invalid State'),
                                    msg: formatMessage('State data for "{ key }" is not valid. You need to fix the errors first.', {key}),
                                })
                                return false
                            }

                            const value = field.getValue()
                            Ext.state.Manager.set(key, value)
                            state.set('value', value)
                        }
                    }
                }
            }
        })
    }
})

Ext.ux.ItemRegistry.registerItem('Tinebase-PreferencePanel', StateEditField, 300)