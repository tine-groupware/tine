/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Promise.all([Tine.Tinebase.appMgr.isInitialised('EventManager'), Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('EventManager');

    const translation = new Locale.Gettext();
    translation.textdomain('EventManager');

    const config = Tine.EventManager.registry.get('config');
    const pastoralUrl = config?.eventPastoralUrl.value;
    const callHomepage = async function (item) {
        if (pastoralUrl) {
            window.open(pastoralUrl);
        } else {
            const url = window.location.href;
            window.open(url.replace('#/EventManager', 'EventManager/view/events'));
        }
    }

    const callDetailsPage = async function (item) {
        const selections = this.mainScreen.EventGridPanel.selectionModel.selections.items ?? []
        if (selections.length === 0 || selections.length > 1) {
            return;
        }
        const event = selections[0].get('id');
        // todo add event id when pastoral url is the correct url
        /*if (pastoralUrl) {
            window.open(pastoralUrl + `?event=${event}`);
        } else {*/
            const url = window.location.href;
            window.open(url.replace('#/EventManager', `EventManager/view/event/${event}`));
        //}
    }

    const actionHomepageConfig = {
        app: app,
        allowMultiple: false,
        iconCls: 'action_image',
        text: app.i18n._('Go to Homepage'),
        handler: callHomepage.createDelegate(app),
    }

    const actionDetailsPageConfig = {
        app: app,
        allowMultiple: false,
        iconCls: 'action_next',
        text: app.i18n._('Go to Event Details page'),
        actionUpdater(action, grants, records) {
            let enabled = records.length === 1
            action.setDisabled(!enabled)
            action.baseAction.setDisabled(!enabled)
        },
        handler: callDetailsPage.createDelegate(app),
    }

    const actionHomepage = new Ext.Action(actionHomepageConfig);
    const actionDetailsPage = new Ext.Action(actionDetailsPageConfig);
    const mediumBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}

    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(actionHomepage), mediumBtnStyle), 50)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ContextMenu`, actionHomepage, 5)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(actionDetailsPage), mediumBtnStyle), 60)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ContextMenu`, actionDetailsPage, 6)
});
