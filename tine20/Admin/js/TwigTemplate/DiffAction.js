/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import 'widgets/dialog/DiffDialog'

Promise.all([Tine.Tinebase.appMgr.isInitialised('Admin'),
    Tine.Tinebase.appMgr.isInitialised('Tinebase'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Admin')

    const getAction = (config) => {
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Show diff'),
            qtip: config.qtip || app.formatMessage('Compare with original version'),
            iconCls: `action_diff`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                enabled = enabled && _.get(records, '[0].data.has_original') && !_.get(records, '[0].data.is_original')

                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0]
                let editDialog = cmp.editDialog || cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.Tinebase.dialog.Dialog})

                editDialog ? editDialog.onRecordUpdate() : null

                Tine.WindowFactory.getWindow({
                    width: 1200,
                    height: 800,
                    name: `Tinebase-TwigTemplate-DiffDialog-${record.get('path')}`,
                    contentPanelConstructor: 'Tine.Tinebase.dialog.DiffDialog',
                    contentPanelConstructorConfig: {
                        record,
                        editDialog,
                        diffConfig: {
                            left: {
                                content: record.get('original_twig'),
                                mode: 'ace/mode/twig',
                                editable: false,
                            },
                            right: {
                                content: record.get('twig_template'),
                                mode: 'ace/mode/twig'
                            }
                        },
                        listeners: {
                            'beforeapply': async (o, dlg) => {
                                const content = dlg.differ.getEditors().right.getValue()
                                if (content !== record.get('twig_template')) {
                                    if (await dlg.window.popup.Ext.MessageBox.confirm(
                                        app.formatMessage('Apply Changes?', {}),
                                        app.formatMessage('Do you want to apply the changes you made to the twig template?', {})
                                    ) !== 'yes') { return false }
                                    record.set('twig_template', content)
                                    editDialog?.getForm?.()?.findField('twig_template')?.setValue(content)
                                }
                            }
                        }
                    }
                })
            }
        }, config))
    }

    const action = getAction({})
    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    Ext.ux.ItemRegistry.registerItem(`Tinebase-TwigTemplate-GridPanel-ContextMenu`, action, 49)
    Ext.ux.ItemRegistry.registerItem(`Tinebase-TwigTemplate-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 39)
    Ext.ux.ItemRegistry.registerItem(`Tinebase-TwigTemplate-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 100)

})

