/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Promise.all([Tine.Tinebase.appMgr.isInitialised('EventManager'), Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('EventManager');

    const config = Tine.EventManager.registry.get('config');
    const pastoralUrl = config?.eventPastoralUrl?.value;

    const getBaseUrl = () => window.location.href.split('#')[0].replace(/\/$/, '');

    const openHomepage = () => {
        window.open(pastoralUrl || `${getBaseUrl()}/EventManager/view/events`);
    };

    const openDetailsPage = (eventId) => {
        if (!eventId) return;
        window.open(`${getBaseUrl()}/EventManager/view/event/${eventId}`);
    };

    const openRegistrationPage = async (eventId) => {
        if (!eventId) return;
        const baseUrl = getBaseUrl();
        const previewUrl = `${baseUrl}/EventManager/view/event/${eventId}/registration/preview`;
        const win = window.open('about:blank');

        try {
            const resp = await fetch(`${baseUrl}/EventManager/registration/doubleOptIn/${eventId}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ eventId, email: '' }),
            });
            const data = resp.ok ? await resp.json() : null;
            const link = Array.isArray(data) && data[0] ? `${baseUrl}${data[0]}` : previewUrl;
            win ? (win.location.href = link) : window.open(link);
        } catch (e) {
            console.error(e);
            if (win) win.location.href = previewUrl;
        }
    };

    // --- grid panel ---
    const getSelectedEventId = () => {
        const selections = app.getMainScreen().EventGridPanel?.selectionModel.selections.items ?? [];
        return selections.length === 1 ? selections[0].get('id') : null;
    };

    const singleRecordUpdater = (action, grants, records) => {
        const enabled = records.length === 1;
        action.setDisabled(!enabled);
        action.baseAction.setDisabled(!enabled);
    };

    const actionHomepage = new Ext.Action({
        app, allowMultiple: false,
        iconCls: 'action_image',
        text: app.i18n._('Go to Homepage'),
        handler: openHomepage,
    });
    const actionDetailsPage = new Ext.Action({
        app, allowMultiple: false,
        iconCls: 'action_next',
        text: app.i18n._('Go to Event Details page'),
        actionUpdater: singleRecordUpdater,
        handler: () => openDetailsPage(getSelectedEventId()),
    });
    const actionRegistrationPage = new Ext.Action({
        app, allowMultiple: false,
        iconCls: 'action_next',
        text: app.i18n._('Go to Preview Registration'),
        actionUpdater: singleRecordUpdater,
        handler: () => openRegistrationPage(getSelectedEventId()),
    });

    const mediumBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top' };
    const gridTbarKey = 'EventManager-Event-GridPanel-ActionToolbar-leftbtngrp';
    const ctxMenuKey = 'EventManager-Event-GridPanel-ContextMenu';

    Ext.ux.ItemRegistry.registerItem(gridTbarKey, Ext.apply(new Ext.Button(actionHomepage), mediumBtnStyle), 50);
    Ext.ux.ItemRegistry.registerItem(ctxMenuKey, actionHomepage, 5);
    Ext.ux.ItemRegistry.registerItem(gridTbarKey, Ext.apply(new Ext.Button(actionDetailsPage), mediumBtnStyle), 60);
    Ext.ux.ItemRegistry.registerItem(ctxMenuKey, actionDetailsPage, 6);
    Ext.ux.ItemRegistry.registerItem(gridTbarKey, Ext.apply(new Ext.Button(actionRegistrationPage), mediumBtnStyle), 70);
    Ext.ux.ItemRegistry.registerItem(ctxMenuKey, actionRegistrationPage, 7);

    // --- edit dialog toolbar ---
    const getEditDialog = (cmp) => cmp.findParentBy(c => c instanceof Tine.widgets.dialog.EditDialog);
    const getDialogEventId = (dlg) => (dlg?.record && !dlg.isNewRecord()) ? dlg.record.id : null;

    const dialogButton = (text, iconCls, onClick, needsSavedRecord) => ({
        xtype: 'button',
        text,
        iconCls,
        disabled: needsSavedRecord,
        handler: function (btn) {
            onClick(getDialogEventId(getEditDialog(btn)));
        },
        listeners: {
            afterrender: function (btn) {
                if (!needsSavedRecord) return;
                const dlg = getEditDialog(btn);
                if (!dlg) return;
                const update = () => btn.setDisabled(!getDialogEventId(dlg));
                dlg.on('load', update);
                update();
            }
        }
    });

    const dialogTbarKey = 'EventManager-Event-editDialog-Toolbar';
    Ext.ux.ItemRegistry.registerItem(dialogTbarKey,
        dialogButton(app.i18n._('Go to Homepage'), 'action_image', openHomepage, false), 10);
    Ext.ux.ItemRegistry.registerItem(dialogTbarKey,
        dialogButton(app.i18n._('Go to Event Details page'), 'action_next', openDetailsPage, true), 20);
    Ext.ux.ItemRegistry.registerItem(dialogTbarKey,
        dialogButton(app.i18n._('Go to Preview Registration'), 'action_next', openRegistrationPage, true), 30);
});