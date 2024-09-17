/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Tinebase');

/**
 * Tine 2.0 jsclient MainScreen with app selection, menu etc.
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.MainScreenPanel
 * @extends     Ext.Container
 * @singleton   
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.MainScreen = null;
Tine.Tinebase.MainScreenPanel = function(config) {
    this.addEvents(
        /**
         * @event beforeappappactivate
         * fired before an application gets appactivated. Retrun false to stop activation
         * @param {Tine.Aplication} app about to appactivate
         */
        'beforeappappactivate',
        /**
         * @event appactivate
         * fired when an application gets appactivated
         * @param {Tine.Aplication} appactivated app
         */
        'appactivate',
        /**
         * @event beforeappappdeactivate
         * fired before an application gets appdeactivated. Retrun false to stop deactivation
         * @param {Tine.Aplication} app about to appdeactivate
         */
        'beforeappappdeactivate',
        /**
         * @event appdeactivate
         * fired when an application gets appdeactivated
         * @param {Tine.Aplication} appdeactivated app
         */
        'appdeactivate',
        /**
         * @event windowopenexception
         * windowopenexceptionated
         * @param {} Exception
         */
        'windowopenexception'
    );

    // NOTE: this is a cruid method to create some kind of singleton...
    Tine.Tinebase.MainScreen = this;

    Tine.Tinebase.MainScreenPanel.superclass.constructor.call(this, config);
}

Ext.extend(Tine.Tinebase.MainScreenPanel, Ext.Container, {
    
    border: false,
    layout: {
        type:'vbox',
        align:'stretch',
        padding:'0'
    },

    /**
     * the active app
     * @type {Tine.Tinebase.Application}
     */
    app: null,

    /**
     * @private
     */
    initComponent: function() {
        this.initLayout();
        this.supr().initComponent.apply(this, arguments);
    },

    /**
     * @private
     */
    initLayout: function() {
        this.items = [{
            ref: 'topBox',
            cls: 'tine-mainscreen-topbox',
            border: false,
            html: '<div class="tine-mainscreen-topbox-left"></div><div class="tine-mainscreen-topbox-middle"></div><div class="tine-mainscreen-topbox-right"></div>'
        }, {
            height: 40,
            layout: 'fit',
            border: false,
            items: this.getTineBar()
        }, {
            ref: 'mainPanel',
            border: false,
            flex: 1,
            layout: {
                type: 'hbox',
                align: 'stretch',
            },
            items: [{
                ref: 'dock',
                border: false,
                height: '100%',
                items: this.getDock()
            }, {
                ref: 'centerPanel',
                cls: 'tine-mainscreen-centerpanel',
                flex: 1,
                width: '100%',
                height: '100%',
                border: false,
                layout: 'card'
            }]
        }];
    },

    getDock: function() {
        this.dock = this.dock || new Tine.Tinebase.TineDock();
        return this.dock
    },

    getTineBar: function() {
        this.tineBar = this.tineBar || new Tine.Tinebase.TineBar()
        return this.tineBar;
    },
    
    /**
     * returns main menu
     * 
     * @return {Ext.Menu}
     */
    getMainMenu: function() {
        return this.getTineBar()
    },

    /**
     * returns center (card) panel
     *
     * @returns {Ext.Panel}
     */
    getCenterPanel: function() {
        return this.mainPanel.centerPanel;
    },

    /**
     * appMgr app activation listener
     * 
     * @param {Tine.Application} app
     */
    onAppActivate: function(app) {
        Tine.log.info('Activating app ' + app.appName);
        
        this.app = app;
        
        // set document / browser title
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '',
            // some apps (Felamimail atm) can add application specific title postfixes
            // TODO generalize this
            appPostfix = (document.title.match(/^\([0-9]+\) /)) ? document.title.match(/^\([0-9]+\) /)[0] : '';
        document.title = Ext.util.Format.stripTags(appPostfix + Tine.title + postfix  + ' - ' + app.getTitle());
    },
    
    /**
     * executed after rendering process
     * 
     * @private
     */
    afterRender: function() {
        this.supr().afterRender.apply(this, arguments);

        // check for new version
        // TODO add helper function for fetching config ... this condition sucks.
        if ((      ! Tine.Tinebase.registry.get("config")
                || ! Tine.Tinebase.registry.get("config").versionCheck
                ||   Tine.Tinebase.registry.get("config").versionCheck.value
            ) && Tine.Tinebase.common.hasRight('check_version', 'Tinebase')
        ) {
            Tine.widgets.VersionCheck();
        }
        
        if (Tine.Tinebase.registry.get('mustchangepw') && Tine.Tinebase.configManager.get('changepw')) {
            var passwordDialog = new Tine.Tinebase.PasswordChangeDialog({
                dialogText: Tine.Tinebase.registry.get('mustchangepw') + '\n' + i18n._('You need to set a new password.')
            });
            passwordDialog.show();
        }

        // don't bother user with two dialogs at one login
        else if (Tine.Tinebase.registry.get('encourage_mfa') && Tine.Tinebase.configManager.get('mfa_encourage')) {
            Ext.MessageBox.show({
                title: i18n._('Multi Factor Authentication'),
                msg: `
                    ${i18n._('We strongly encourage you to enable Multi-Factor Authentication for added security.')}<br />
                    <br />
                    ${i18n._('Multi-Factor Authentication adds an extra layer of protection to logins. Once enabled & configured, each time you sing in you will be asked to enter both your username & password as well as a second factor such as a security code.')}
                `,
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
            }).then(() => {
                Tine.Tinebase.MFA.DeviceSelfServiceDialog.openWindow({})
            });
        }
    },

    /**
     * activate application
     *
     * @param {Tine.Application} app
     * @return {Boolean}
     */
    activate: function(app) {
        if (app) {
            // activation via routing only
            if (Tine.Tinebase.router.getRoute()[0] != app.appName) {
                Tine.Tinebase.router.setRoute(app.getRoute());
                return;
            }

            if (app == this.getActiveApp()) {
                // app is already active, nothing to do
                return true;
            }

            if (this.app) {
                if ((this.fireEvent('beforeappappdeactivate', this.app) === false || this.app.onBeforeDeActivate() === false)) {
                    return false;
                }

                this.app.onDeActivate();
                this.fireEvent('appdeactivate', this.app);
                this.app = null;
            }

            if (this.fireEvent('beforeappappactivate', app) === false || app.onBeforeActivate() === false) {
                return false;
            }

            this.setActiveCenterPanel(app.getMainScreen(), true);

            this.app = app;
            this.onAppActivate(app);

            app.onActivate();
            this.fireEvent('appactivate', app);
        } else {
            app = Tine.Tinebase.appMgr.getDefault();
            Tine.Tinebase.router.setRoute(app.getRoute());
        }
    },

    /**
     * returns currently activated app
     * @return {Tine.Application}
     */
    getActiveApp: function() {
        return this.app;
    },

    /**
     * set the active center panel
     * @param panel
     */
    setActiveCenterPanel: function(panel, keep) {
        if (panel.app) {
            // neede for legacy handling
            this.app = panel.app;
        }
        var cardPanel = this.getCenterPanel();

        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(cardPanel, panel, keep);
    },


    /**
     * sets the active content panel
     *
     * @deprecated
     * @param {Ext.Panel} item Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveContentPanel: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveContentPanel is deprecated, use <App>.Mainscreen.setActiveContentPanel instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveContentPanel(panel, keep);
    },

    /**
     * sets the active tree panel
     *
     * @deprecated
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveTreePanel is deprecated, use <App>.Mainscreen.setActiveTreePanel instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveTreePanel(panel, keep);
    },

    /**
     * sets the active module tree panel
     *
     * @deprecated
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveModulePanel: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveModulePanel is deprecated, use <App>.Mainscreen.setActiveModulePanel instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveModulePanel(panel, keep);
    },

    /**
     * sets item
     *
     * @deprecated
     * @param {Ext.Toolbar} panel toolbar to activate
     * @param {Bool} keep keep panel
     */
    setActiveToolbar: function(panel, keep) {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.setActiveToolbar is deprecated, use <App>.Mainscreen.setActiveToolbar instead ' + new Error().stack);
        return this.app.getMainScreen().setActiveToolbar(panel, keep);
    },

    /**
     * gets the currently displayed toolbar
     *
     * @deprecated
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        Tine.log.warn('Tine.Tinebase.MainScreenPanel.getActiveToolbar is deprecated, use <App>.Mainscreen.getActiveToolbar instead ' + new Error().stack);
        return this.app.getMainScreen().getActiveToolbar();
    }
});

/**
 * lazy mainscreen init
 *
 * @static
 * @param app
 */
Tine.Tinebase.MainScreenPanel.show = function(app) {
    var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;

    if (! Tine.Tinebase.MainScreen) {
        new Tine.Tinebase.MainScreenPanel();
        mainCardPanel.add(Tine.Tinebase.MainScreen);
        mainCardPanel.layout.setActiveItem(Tine.Tinebase.MainScreen.id);
        Tine.Tinebase.MainScreen.doLayout();
    }

    Tine.Tinebase.MainScreen.activate(app);
};
