/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         allow to add user defined part to Tine.title
 */

/*global Ext, Tine, google, OpenLayers, Locale, */

import waitFor from 'util/waitFor.es6';
import {init as initBroadcastClient} from './broadcastClient'
import * as localforage from "localforage";

var EventEmitter = require('events');

/** ------------------------- Ext.ux Initialisation ------------------------ **/

Ext.ux.Printer.BaseRenderer.prototype.stylesheetPath = 'Tinebase/js/ux/Printer/print.css';


/** ------------------------ Tine 2.0 Initialisation ----------------------- **/

/**
 * @class Tine
 */
Ext.namespace('Tine');

/**
 * version of Tine 2.0 javascript client version, gets set a build / release time <br>
 * <b>Supported Properties:</b>
 * <table>
 *   <tr><td><b>buildType</b></td><td> type of build</td></tr>
 *   <tr><td><b>buildDate</b></td><td> date of build</td></tr>
 *   <tr><td><b>buildRevision</b></td><td> revision of build</td></tr>
 *   <tr><td><b>codeName</b></td><td> codename of release</td></tr>
 *   <tr><td><b>packageString</b></td><td> packageString of release</td></tr>
 *   <tr><td><b>releaseTime</b></td><td> releaseTime of release</td></tr>
 * </table>
 * @type {Object}
 */
Tine.clientVersion = {};
Tine.clientVersion.buildType        = BUILD_TYPE;
Tine.clientVersion.buildDate        = BUILD_DATE;
Tine.clientVersion.buildRevision    = BUILD_REVISION;
Tine.clientVersion.codeName         = CODE_NAME;
Tine.clientVersion.packageString    = PACKAGE_STRING;
Tine.clientVersion.releaseTime      = RELEASE_TIME;

Tine.__appLoader = require('./app-loader!app-loader.js');
Tine.__onAllAppsLoaded = new Promise( (resolve) => {
    Tine.__onAllAppsLoadedResolve = resolve;
});

/**
 * returns promise that resolves when app code of given app is loaded
 *
 * @param appName
 * @return {*|Promise<never>}
 */
Tine.onAppLoaded = (appName) => {
    return _.get(Tine.__appLoader.appLoadedPromises, appName) || Promise.reject();
};

/**
 * returns promise that resolves when code of all user apps is loaded
 * @return {Promise<any>}
 */
Tine.onAllAppsLoaded = () => {
    return Tine.__onAllAppsLoaded;
};

/**
 * quiet logging in release mode
 */
Tine.log = Ext.ux.log;
Tine.log.setPrio(Tine.clientVersion.buildType === 'RELEASE' ? 0 : 7);

/**
 * in memory per window msg bus for sync events
 */
(() => {
    let ee = new EventEmitter();
    Tine.on = ee.on;
    Tine.emit = ee.emit;
})();

Ext.namespace('Tine.Tinebase');

/**
 * @class Tine.Tinebase.tineInit
 * @namespace Tine.Tinebase
 * @sigleton
 * static tine init functions
 */
Tine.Tinebase.tineInit = {
    /**
     * @cfg {String} getAllRegistryDataMethod
     */
    getAllRegistryDataMethod: 'Tinebase.getAllRegistryData',

    /**
     * @cfg {Boolean} stateful
     */
    stateful: true,

    /**
     * @cfg {String} requestUrl
     */
    requestUrl: 'index.php',

    /**
     * prefix for localStorage keys
     * @type String
     */
    lsPrefix: Tine.Tinebase.common.getUrl('path') + 'Tine',

    onPreferenceChangeRegistered: false,

    initCustomJS: function() {
        _.each(_.get(window, 'Tine.customJS', []), function(initCustomJS) {
            initCustomJS()
        })
    },

    initWindow: function () {
        const initialDataRe = /initialData\/(.*)\/?/;
        if (initialDataRe.test(window.location.hash)) {
            let hashData = initialDataRe.exec(window.location.hash)[1];
            window.location.href = location.href.replace(initialDataRe, '');
            try {
                window.initialData = window.initialData || {};
                Object.assign(window.initialData, JSON.parse(decodeURIComponent(hashData)));
            } catch (e) {
                console.error("can't decode initialData", hashData, e);
            }
        }

        Ext.getBody().on('keydown', function (e) {
            if (e.ctrlKey && e.getKey() === e.A && ! (e.getTarget('form') || e.getTarget('input') || e.getTarget('textarea'))) {
                // disable the native 'select all'
                e.preventDefault();
            } else if (e.getKey() === e.BACKSPACE && ! (e.getTarget('form') || e.getTarget('input') || e.getTarget('textarea'))) {
                // disable the native 'history back'
                e.preventDefault();
            } else if (!window.isMainWindow && e.ctrlKey && e.getKey() === e.T) {
                // disable the native 'new tab' if in popup window
                e.preventDefault();
            } else if (window.isMainWindow && e.ctrlKey && (e.getKey() === e.L || e.getKey() === e.DELETE)) {
                // reload on ctrl-l
                Tine.Tinebase.common.reload({
                    clearCache: true
                });
            } else if (e.ctrlKey && e.altKey && e.getKey() === e.S ) {
                Ext.ux.screenshot.ux(window, {download: true, grabMouse: !e.shiftKey});
            }  else if (window.isMainWindow) {
                // select first row of current grid panel if available
                var app = Tine.Tinebase.MainScreen ? Tine.Tinebase.MainScreen.getActiveApp() : null,
                    centerPanel = app?.getMainScreen?.().getCenterPanel?.() ?? null,
                    grid = centerPanel && Ext.isFunction(centerPanel.getGrid) ? centerPanel.getGrid() : null,
                    sm = grid ? grid.getSelectionModel() : null;
                if (grid) {
                    if (e.getKey() === e.ESC && sm) {
                        sm.selectFirstRow();
                        grid.getView().focusRow(0);
                    } else {
                        grid.fireEvent('keydown', e);
                    }
                }
            }
        });

        // disable generic drops
        Ext.getBody().on('dragover', function (e) {
            e.stopPropagation();
            e.preventDefault();
            e.browserEvent.dataTransfer.dropEffect = 'none';
        }, this);

        // generic context menu
        Ext.getBody().on('contextmenu', function (e) {
            const target = e.getTarget('a', 1 , true) ||
                e.getTarget('input[type=text]', 1 , true) ||
                e.getTarget('textarea', 1, true);
            
            if (target) {
                // allow native context menu for links + textareas + (text)input fields
                return;
            }
            
            if (window.getSelection().toString() !== '') {
                /* Don't do anything on text selection */
                return;
            }

            // allow native context menu on second context click
            if (Tine.Tinebase.MainContextMenu.isVisible()) {
                Tine.Tinebase.MainContextMenu.hide();
                return;
            }

            // deny native context menu if we have an own one
            if (Tine.Tinebase.MainContextMenu.showIf(e)) {
                e.stopPropagation();
                e.preventDefault();
            }
        }, this);

        Ext.getBody().on('click', async function (e) {

            var target = e.getTarget('a', 2, true),
                href = target ? target.getAttribute('href') : '';
            const position = e.getXY();

            // add menuitems for email links
            if (target?.dom?.className === 'tinebase-email-link' || target?.dom?.href.includes('mailto:')) {
                // disable default mailto link
                e.preventDefault();

                // search Contact first
                const targetClassName = target?.dom?.className;
                const emailData = Tine.Tinebase.common.findEmailData(targetClassName === 'tinebase-email-link' ? target?.id : target?.dom?.href);
                this.contextMenu = await this.getEmailContextMenu(target, emailData?.email, emailData?.name);
                this.contextMenu.showAt(position);
            }
    
            const [recordClass, recordId] = Tine.Tinebase.common.findRecordFromTarget(target);
            if (recordClass && recordId) {
                e.stopEvent();
                const EditDialog = Tine.widgets.dialog.EditDialog.getConstructor(recordClass);
                if (EditDialog?.openWindow) {
                    EditDialog.openWindow({recordId: recordId, record: {id: recordId}, mode: 'remote'});
                }
            }
            
            if (target && href && href !== '#' && href !== Tine.Tinebase.common.getUrl()) {
                target.set({
                    href: decodeURI(href),
                    rel: "noreferrer",
                    target: "_blank"
                });

                // open internal links in same window (use router)
                const mainWindow = Ext.ux.PopupWindowMgr.getMainWindow();
                if (window === mainWindow) {
                    if (href.match(new RegExp('^' + window.lodash.escapeRegExp(Tine.Tinebase.common.getUrl())))) {
                        target.set({
                            href: decodeURI(href),
                            target: "_self"
                        });
                    }
                } else {
                    e.preventDefault();

                    if (e.ctrlKey || e.metaKey) {
                        const win = window.open(href, '_blank', null, true);
                        win.opener = null;
                    } else {
                        if (href !== mainWindow.location.href) {
                            mainWindow.location.href = href;
                        }
                    }
                }
            } else {
                if (e.getTarget('.x-treegrid-col', 10, true)) {
                    return;
                }
                
                let wavesEl = e.getTarget('.x-btn', 10, true)
                    || e.getTarget('.tine-recordclass-gridicon', 10, true)
                    || e.getTarget('.x-tree-node-el', 10, true);
                if (wavesEl && !wavesEl.hasClass('x-item-disabled')) {
                    wavesEl.addClass('waves-effect');
                    Waves.ripple(wavesEl.dom);
                    wavesEl.removeClass.defer(1500, wavesEl, ['waves-effect']);
                }
            }
        }, this);

        Tine.clientVersion.assetHash = window.assetHash;
    },

    initPostal: function () {
        if (! window.postal) {
            return;
        }

        var config = postal.fedx.transports.xwindow.configure();
        postal.fedx.transports.xwindow.configure( {
            localStoragePrefix: Tine.Tinebase.tineInit.lsPrefix + '.' + config.localStoragePrefix
        } );
        postal.instanceId('xwindow-' + _.random(0,1000));
        postal.configuration.promise.createDeferred = function() {
            return Promise.defer();
        };
        postal.configuration.promise.getPromise = function(dfd) {
            return dfd.promise;
        };
        postal.fedx.configure({
            filterMode: 'blacklist'
        });

        postal.fedx.signalReady();

        postal.addWireTap( function( d, e ) {
            Tine.log.debug( "ID: " + postal.instanceId() + " " + JSON.stringify( e, null, 4 ) );
        } );
    },

    initDebugConsole: function () {
        var map = new Ext.KeyMap(Ext.getDoc(), [{
            key: [122], // F11
            ctrl: true,
            fn: Tine.Tinebase.common.showDebugConsole
        }]);
    },

    /**
     * Each window has exactly one viewport containing a card layout in its lifetime
     * The default card is a splash screen.
     *
     * default wait panel (picture only no string!)
     */
    initBootSplash: function () {
        Tine.Tinebase.viewport = new Ext.Viewport({
            layout: 'fit',
            border: false,
            items: {
                xtype: 'container',
                ref: 'tineViewportMaincardpanel',
                isWindowMainCardPanel: true,
                layout: 'card',
                border: false,
                activeItem: 0,
                items: [{
                    xtype: 'container',
                    border: false,
                    layout: 'fit',
                    width: 16,
                    height: 16,
                    // the content elements come from the initial html so they are displayed fastly
                    contentEl: Ext.select('div[class^=tine-viewport-]')
                }]
            },
            async setWaitText(text) {
                const msgEl = this.el.child('.tine-viewport-waittext') || this.el.child('.tine-viewport-waitcycle').wrap({cls: 'tine-viewport-waitbox'}).createChild({cls: 'tine-viewport-waittext'});
                let msg = '';
                return [...text].asyncForEach(async (chr) => {
                    msg += chr;
                    msgEl.update(msg);
                    return new Promise((resolve) => setTimeout(resolve, 20));
                });

            }
        });
    },

    initLoginPanel: function() {
        if (window.isMainWindow && ! Tine.loginPanel) {
            var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;
            Tine.loginPanel = new Tine.Tinebase.LoginPanel({
                defaultUsername: Tine.Tinebase.registry.get('defaultUsername'),
                defaultPassword: Tine.Tinebase.registry.get('defaultPassword'),
                allowBrowserPasswordManager: Tine.Tinebase.registry.get('allowBrowserPasswordManager')
            });
            mainCardPanel.add(Tine.loginPanel);
        }
    },

    showLoginBox: function(cb, scope) {
        var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel,
            activeItem = mainCardPanel.layout.activeItem;

        mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
        Tine.loginPanel.doLayout();
        Tine.loginPanel.onLogin = function(response) {
            mainCardPanel.layout.setActiveItem(activeItem);
            cb.call(scope||window, response);
        };
    },

    async getEmailContextMenu(target, email, name, type = 'contact') {
        // store click position, make sure menuItem diaplay around the link
        if (! Tine.Addressbook  ||  ! email) return;
        
        const contextMenu = new Ext.menu.Menu({items: []});
        let items = [];
        
        if (type === 'group' || type ===  'mailingList' || type ===  'list') {
            items = await this.getListContextMenuItems(email, name);
        } else {
            // fixme: search contact name sometimes get empty result , skip it for now
            items = await this.getContactContextMenuItems(target, email, '');
        }
    
        items.forEach((item) => {
            contextMenu.addMenuItem(item);
        });
        
        this.action_addContact = new Ext.Action({
            text: i18n._('Create Contact'),
            handler: this.contactHandler.createDelegate(this, [target, null, email]),
            iconCls: 'action_add',
            scope: this,
            hidden: items.length,
        });

        contextMenu.addMenuItem(this.action_addContact);
        
        this.action_copyEmailPlainText = new Ext.Action({
            text: i18n._('Copy to Clipboard'),
            handler: async function () {
                await navigator.clipboard.writeText(email)
                    .then(() => {
                        // Success!
                    })
                    .catch(err => {
                        console.log('Something went wrong', err);
                    });
            },
            iconCls: 'action_copy',
            scope: this
        });

        this.action_comeposeEmail = new Ext.Action({
            text: i18n._('Compose Message To'),
            handler: async function () {
                let defaults = Tine.Felamimail.Model.Message.getDefaultData();
                defaults.to = [email];
                const record = new Tine.Felamimail.Model.Message(defaults, 0);
                Tine.Felamimail.MessageEditDialog.openWindow({
                    record: record
                });
            },
            iconCls: 'action_composeEmail',
            scope: this
        });

        contextMenu.addMenuItem(this.action_comeposeEmail);
        contextMenu.addMenuItem(this.action_copyEmailPlainText);

        return contextMenu;
    },
    
    async getContactContextMenuItems(target, email, name) {
        const contactItems = [];
        const filters = [{
            condition: "OR", filters: [
                {field: 'email', operator: 'equals', value: email},
                {field: 'email_home', operator: 'equals', value: email}
            ]
        }];
    
        if (name && name !== '') {
            filters.push({
                condition: "OR", filters: [
                    {field: 'n_fn', operator: 'contains', value: name},
                    {field: 'n_fileas', operator: 'contains', value: name}
                ]
            });
        }
    
        const { results: contacts } = await Tine.Addressbook.searchContacts(filters);
    
        if (contacts.length > 0) {
            contacts.forEach((contact) => {
                contact = Tine.Tinebase.data.Record.setFromJson(contact, Tine.Addressbook.Model.Contact);
                const action_editContact = new Ext.Action({
                    text: i18n._('Edit') + '  ' + contact.get('n_fileas'),
                    handler: this.contactHandler.createDelegate(this, [target, contact, email]),
                    iconCls: 'AddressbookIconCls',
                    scope: this
                });
            
                contactItems.push(action_editContact);
            });
        }
        
        return contactItems;
    },
    
    async getListContextMenuItems(email, name) {
        const listItems = [];
        const listFilters = [{field: 'email', operator: 'equals', value: email}];
        
        if (name && name !== '') {
            listFilters.push({
                condition: "AND", filters: [
                    {field: 'name', operator: 'equals', value: name},
                ]
            });
        }
        
        const { results: contacts } = await Tine.Addressbook.searchLists(listFilters);
        
        this.contextMenu = new Ext.menu.Menu({
            items: []
        });
        
        if (contacts.length > 0) {
            contacts.forEach((contact) => {
                contact = Tine.Tinebase.data.Record.setFromJson(contact, Tine.Addressbook.Model.List);
                const action_editContact = new Ext.Action({
                    text: i18n._('Edit') + '  ' + contact.get('name'),
                    handler: this.listHandler.createDelegate(this, [contact]),
                    iconCls: 'AddressbookIconCls',
                    scope: this
                });
    
                listItems.push(action_editContact);
            });
        }

        return listItems;
    },

    contactHandler (target, record, email) {
        // check if addressbook app is available
        if (! Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
            return;
        }
        
        email = Array.isArray(email) && email.length > 0 ? email[0] : Ext.isString(email) ? email : '';

        Tine.Addressbook.ContactEditDialog.openWindow({
            record: record ?? new Tine.Addressbook.Model.Contact({
                email: email
            }),
            listeners: {
                scope: this,
                'load': function(editdlg) {
                    if (!record) {
                        if (email) {
                            editdlg.record.set('email', email);
                        }
                        
                        if (target) {
                            const linkified = target?.dom?.className === 'linkified';
                            const contactInfo = Ext.util.Format.htmlDecode(linkified ? target?.dom?.href : target.id);
    
                            if (linkified) {
                                editdlg.record.set('email', contactInfo.replace('mailto:', ''));
                            } else {
                                const parts = contactInfo.split(':');
        
                                editdlg.record.set('email', parts[1]);
                                editdlg.record.set('n_given', parts[2]);
                                editdlg.record.set('n_family', parts[3]);
                            }
                        }
                    }
                }
            }
        });
    },
    
    listHandler(record) {
        // check if addressbook app is available
        if (! Tine.Tinebase.common.hasRight('run', 'Addressbook')) {
            return;
        }

        Tine.Addressbook.ListEditDialog.openWindow({
            record: record,
        });
    },

    renderWindow: function () {
        Tine.log.info('renderWindow::start');
        Ext.MessageBox.hide();

        // check if user is already logged in
        if (! Tine.Tinebase.registry.get('currentAccount')) {
            if (! window.isMainWindow) {
                window.close();
                // just in case it didn't succeed
                return Ext.MessageBox.show({
                    title: i18n._('Session Timed Out'),
                    msg: i18n._('You can close this window.'),
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.INFO,
                    fn: window.close
                });
            }

            const areaLockException = Tine.Tinebase.registry.get('areaLockedException')
            if (areaLockException) {
                // login from post - user is authenticated but mfa is required
                return Tine.Tinebase.areaLocks.handleAreaLockException(areaLockException).then(() => {
                    Ext.MessageBox.wait(String.format(i18n._('Login successful. Loading {0}...'), Tine.title), i18n._('Please wait!'));
                    Tine.Tinebase.tineInit.initRegistry(true, Tine.Tinebase.tineInit.renderWindow, Tine.Tinebase.tineInit);
                }).catch(async (error) => {
                    Ext.MessageBox.wait(i18n._('Logging you out...'), i18n._('Please wait!'));
                    await Tine.Tinebase.logout();
                    return Tine.Tinebase.common.reload({
                        keepRegistry: false,
                        clearCache: true
                    });

                });
            }
            Tine.Tinebase.tineInit.showLoginBox(function(response){
                Tine.log.info('tineInit::renderWindow -fetch users registry');
                Tine.Tinebase.tineInit.initRegistry(true, function() {
                    if (Ext.isWebApp) {
                        Tine.Tinebase.registry.set('sessionId', response.responseData.sessionId);
                        Tine.Tinebase.registry.set('usercredentialcache', Tine.Tinebase.tineInit.cookieProvider.get('usercredentialcache'));
                    }
                    Tine.log.info('tineInit::renderWindow - registry fetched, render main window');
                    Ext.MessageBox.hide();
                    Tine.Tinebase.tineInit.checkClientVersion();
                    Tine.Tinebase.tineInit.initWindowMgr();
                    Tine.Tinebase.tineInit.renderWindow();
                });
            });

            return;
        } else {
            var sessionLifeTime = Tine.Tinebase.registry.get('sessionLifeTime') || 86400,

                // log out after sessionLifeTime of absence (NOTE: session is not over due to background requests)
                sessionLifeTimeObserver = new Tine.Tinebase.PresenceObserver({
                    maxAbsenceTime: sessionLifeTime / 60,
                    absenceCallback: function(lastPresence, po) {
                        Tine.Tinebase.MainMenu.prototype._doLogout();
                    }
                }),

                // report users presence to server
                userPresenceObserver = new Tine.Tinebase.PresenceObserver({
                    maxAbsenceTime: 3,
                    presenceCallback: function(lastPresence, po) {
                        Tine.Tinebase.reportPresence(lastPresence);
                    }
                });
        }

        Tine.Tinebase.router = new director.Router().init();
        Tine.Tinebase.router.configure({notfound: function () {
            var defaultApp = Tine.Tinebase.appMgr.getDefault();
            if (defaultApp) {
                Tine.Tinebase.router.setRoute(defaultApp.getRoute());
            }
        }});
    
        if (! window.isMainWindow) {
            Tine.Tinebase.appMgr.apps.each((app) => {
                const initRoutes = _.get(window, `Tine.${app.appName}.Application.prototype.initRoutes`);
                if(_.isFunction(initRoutes)) {
                    initRoutes.call(Object.assign({
                        appName: app.appName,
                        routes: app.routes
                    }, _.get(window, `Tine.${app.appName}.Application.prototype`)));
                }
            })
        }
        
        var route = Tine.Tinebase.router.getRoute(),
            winConfig = Ext.ux.PopupWindowMgr.get(window);

        Tine.Tinebase.ApplicationStarter.init();
        Tine.Tinebase.appMgr.getAll();

        // dispatch _after_ init resolvers/awaits
        _.defer(async () => {
            if (winConfig) {
                var mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel,
                    card = await Tine.WindowFactory.getCenterPanel(winConfig);

                mainCardPanel.add(card);
                mainCardPanel.layout.setActiveItem(card.id);
                card.doLayout();
            } else {
                Tine.Tinebase.router.dispatch('on', '/' + route.join('/'));
            }
        });
    },

    initAjax: function () {
        Ext.Ajax.url = Tine.Tinebase.tineInit.requestUrl;
        Ext.Ajax.method = 'POST';

        Ext.Ajax.defaultHeaders = {
            'X-Tine20-Request-Type' : 'JSON'
        };

        Ext.Ajax.transactions = {};

        Tine.Tinebase.tineInit.cookieProvider = new Ext.ux.util.Cookie({
            path: String(Tine.Tinebase.common.getUrl('path')).replace(/\/$/, '')
        });

        /**
         * inspect all requests done via the ajax singleton
         *
         * - send custom headers
         * - send json key
         * - implicitly transform non jsonrpc requests
         *
         * NOTE: implicitly transformed reqeusts get their callback fn's proxied
         *       through generic response inspectors as defined below
         */
        Ext.Ajax.on('beforerequest', function (connection, options) {

            const jsonKey = Tine.Tinebase.registry && Tine.Tinebase.registry.get ? Tine.Tinebase.registry.get('jsonKey') : '';

            options.headers = options.headers || {};
            options.headers['X-Tine20-JsonKey'] = jsonKey;
            options.headers['X-Tine20-TransactionId'] = Tine.Tinebase.data.Record.generateUID();

            // server might not accept outdated clients
            if (Tine.clientVersion.assetHash) {
                options.headers['X-Tine20-ClientAssetHash'] = Tine.clientVersion.assetHash;
            }

            options.url = Ext.urlAppend((options.url ? options.url : Tine.Tinebase.tineInit.requestUrl),  'transactionid=' + options.headers['X-Tine20-TransactionId']);

            // convert non Ext.Direct request to jsonrpc
            // - convert params
            // - convert error handling
            if (options.params && !options.isUpload) {
                var params = {};

                var def = Tine.Tinebase.registry.get('serviceMap') ? Tine.Tinebase.registry.get('serviceMap').services[options.params.method] : false;
                if (def) {
                    // sort parms according to def
                    for (var i = 0, p; i < def.parameters.length; i += 1) {
                        p = def.parameters[i].name;
                        params[p] = options.params[p];
                    }
                } else {
                    for (var param in options.params) {
                        if (options.params.hasOwnProperty(param) && param !== 'method') {
                            params[param] = options.params[param];
                        }
                    }
                }

                options.jsonData = Ext.encode({
                    jsonrpc: '2.0',
                    method: options.params.method,
                    params: params,
                    id: ++Ext.Direct.TID
                });

                options.cbs = {};
                options.cbs.success  = options.success  || null;
                options.cbs.failure  = options.failure  || null;
                options.cbs.callback = options.callback || null;

                options.isImplicitJsonRpc = true;
                delete options.params;
                delete options.success;
                delete options.failure;
                delete options.callback;
            }

            Ext.Ajax.transactions[options.headers['X-Tine20-TransactionId']] = {
                date: new Date(),
                json: options.jsonData
            };
        });



        /**
         * inspect completed responses => staus code == 200
         *
         * - detect resoponse errors (e.g. html from xdebug) and convert to exceptional states
         * - implicitly transform requests from JSONRPC
         *
         *  NOTE: All programatically catchable exceptions lead to successfull requests
         *        with the jsonprc protocol. For implicitly converted jsonprc requests we
         *        transform error states here and route them to the error methods defined
         *        in the request options
         *
         *  NOTE: Illegal json data responses are mapped to error code 530
         *        Empty resonses (Ext.Decode can't deal with them) are maped to 540
         *        Memory exhausted to 550
         */
        Ext.Ajax.on('requestcomplete', function (connection, response, options) {
            delete Ext.Ajax.transactions[options.headers['X-Tine20-TransactionId']];

            // detect resoponse errors (e.g. html from xdebug) and convert into error response
            if (! options.isUpload && ! response.responseText.match(/^([{\[])|(<\?xml)+/)) {
                var exception = {
                    code: response.responseText !== "" ? 530 : 540,
                    message: response.responseText !== "" ? 'illegal json data in response' : 'empty response',
                    traceHTML: response.responseText,
                    request: options.jsonData,
                    response: response.responseText
                };

                // Fatal error: Allowed memory size of n bytes exhausted (tried to allocate m bytes)
                if (response.responseText.match(/^Fatal error: Allowed memory size of /m)) {
                    Ext.apply(exception, {
                        code: 550,
                        message: response.responseText
                    });
                }

                // encapsulate as jsonrpc response
                var requestOptions = Ext.decode(options.jsonData);
                response.responseText = Ext.encode({
                    jsonrpc: requestOptions.jsonrpc,
                    id: requestOptions.id,
                    error: {
                        code: -32000,
                        message: exception.message,
                        data: exception
                    }
                });
            }

            // strip jsonrpc fragments for non Ext.Direct requests
            if (options.isImplicitJsonRpc) {
                var jsonrpc = Ext.decode(response.responseText);
                if (jsonrpc.result) {
                    response.responseText = Ext.encode(jsonrpc.result);

                    if (options.cbs.success) {
                        options.cbs.success.call(options.scope, response, options);
                    }
                    if (options.cbs.callback) {
                        options.cbs.callback.call(options.scope, options, true, response);
                    }
                } else {

                    response.responseText = Ext.encode(jsonrpc.error);

                    if (options.cbs.failure) {
                        options.cbs.failure.call(options.scope, response, options);
                    } else if (options.cbs.callback) {
                        options.cbs.callback.call(options.scope, options, false, response);
                    } else {
                        var responseData = Ext.decode(response.responseText);

                        exception = responseData.data ? responseData.data : responseData;
                        exception.request = options.jsonData;
                        exception.response = response.responseText;

                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                    }
                }
            }
        });

        /**
         * inspect request exceptions
         *  - convert to jsonrpc compatiple exceptional states
         *  - call generic exception handler if no handler is defined in request options
         *
         * NOTE: Request exceptions are exceptional state from web-server:
         *       -> status codes != 200 : This kind of exceptions are not part of the jsonrpc protocol
         *       -> timeouts: status code 520
         */
        Ext.Ajax.on('requestexception', function (connection, response, options) {
            delete Ext.Ajax.transactions[options.headers['X-Tine20-TransactionId']];
            // map connection errors to errorcode 510 and timeouts to 520
            var errorCode = response.status > 0 ? response.status :
                            (response.status === 0 ? 510 : 520);

            // convert into error response
            if (! options.isUpload) {
                var exception = {
                    code: errorCode,
                    message: 'request exception: ' + response.statusText,
                    traceHTML: response.responseText,
                    request: options.jsonData,
                    requestHeaders: options.headers,
                    openTransactions: Ext.Ajax.transactions,
                    response: response.responseText
                };

                // encapsulate as jsonrpc response
                var requestOptions = _.isString(options.jsonData) ? Ext.decode(options.jsonData) : options.jsonData;
                response.responseText = Ext.encode({
                    jsonrpc: requestOptions.jsonrpc,
                    id: requestOptions.id,
                    error: {
                        code: -32000,
                        message: exception.message,
                        data: exception
                    }
                });
            }

            // NOTE: Tine.data.RecordProxy is implicitRPC atm.
            if (options.isImplicitJsonRpc) {
                var jsonrpc = Ext.decode(response.responseText);

                response.responseText = Ext.encode(jsonrpc.error);

                if (options.cbs.failure) {
                    options.cbs.failure.call(options.scope, response, options);
                } else if (options.cbs.callback) {
                    options.cbs.callback.call(options.scope, options, false, response);
                } else {
                    var responseData = Ext.decode(response.responseText);

                    exception = responseData.data ? responseData.data : responseData;

                    Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }

            } else if (! options.failure && ! options.callback) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }
        });
    },
    
    getRegistryDB() {
        if (!this.registryDB) {
            this.registryDB = localforage.createInstance({
                driver: localforage.INDEXEDDB, // or use localforage.WEBSQL or localforage.LOCALSTORAGE
                name: Tine.Tinebase.tineInit.lsPrefix + '.registry', // optional
                version: 1.0, // optional
                storeName: 'registry', // optional
            });
        }
        return this.registryDB;
    },

    /**
     * init registry
     *
     * @param {Boolean} forceReload
     * @param {Function} cb
     * @param {Object} scope
     */
    initRegistry: async function (forceReload, cb, scope) {
        const registryDB = this.getRegistryDB();
        const registryData =  await registryDB.getItem('data');
        Tine.Tinebase.registry = new Ext.util.MixedCollection(false, false, true);
        Tine.Tinebase.registry.addAll(registryData?.Tinebase || {});
        
        const version = Tine.Tinebase.registry.get('version');
        const userApplications = Tine.Tinebase.registry.get('userApplications') || [];
        const reloadNeeded = !version
            || !userApplications
            || userApplications.length < 2;

        const initAppRegistry = (app, registryData) => {
            Ext.ns('Tine.' + app);
            const setItems = async(collection, key, value) => {
                const reference = collection === 'preferences' ? registryData[app]['preferences'] : registryData[app];

                if (value instanceof Ext.util.MixedCollection) value = value.getAll();
                reference[key] = value;
                await registryDB.setItem('data', registryData);
            };
            ['registry', 'preferences'].forEach(((collection) => {
                Tine[app][collection] = new Ext.util.MixedCollection(false, false, true);
                Tine[app][collection].on('replace', async (key, oldValue, newValue) => {
                    await setItems(collection, key, newValue);
                });
                Tine[app][collection].on('add', async (index, newValue, key) => {
                    await setItems(collection, key, newValue);
                });
            }))
            Tine[app].registry.addAll(registryData[app] || {}, true)
            Tine[app].preferences.addAll(registryData[app].preferences || {}, true);
            Tine[app].registry.set('preferences', Tine[app].preferences, true);
        }
        if (forceReload || reloadNeeded) {
            await this.clearRegistry();
            Ext.Ajax.request({
                timeout: 120000, // 2 minutes
                params: {
                    method: Tine.Tinebase.tineInit.getAllRegistryDataMethod
                },
                failure: function (exception) {
                    if (exception.responseText.match(/"code":426/)) {
                        return Tine.Tinebase.common.reload({
                            keepRegistry: false,
                            clearCache: true
                        });
                    }
                    // if registry could not be loaded, this is mostly due to misconfiguration
                    // don't send error reports for that!
                    Tine.Tinebase.ExceptionHandler.handleRequestException({
                        code: 503
                    });
                },
                success: async (response, request) => {
                    const registryData = Ext.util.JSON.decode(response.responseText);
                    if (Tine.Tinebase.tineInit.checkServerUpdateRequired(registryData)) return;
                    await registryDB.setItem('data', registryData);
                    
                    Object.keys(registryData).forEach((app) => {
                        initAppRegistry(app, registryData);
                    });
                    
                    Tine.Tinebase.tineInit.onRegistryLoad().then(function() {
                        Ext.util.CSS.refreshCache();
                        if (Ext.isFunction(cb)) {
                            cb.call(scope);
                        }
                    });
                }
            })
        } else {
            if (window.isMainWindow) {
                await new Promise((resolve) => {
                    Ext.Ajax.request({
                        timeout: 120000, // 2 minutes
                        params: {
                            method: 'Tinebase.ping'
                        },
                        callback: (request, success, response) => {
                            try {
                                if (success && JSON.parse(response.responseText) === 'ack') return resolve();
                            } catch (e) {
                            }
                            Tine.Tinebase.common.reload({
                                keepRegistry: false,
                                clearCache: true
                            });
                        }
                    });
                });
            }
            userApplications.forEach((app) => {
                initAppRegistry(app.name, registryData);
            })
            
            Tine.Tinebase.tineInit.onRegistryLoad().then(function() {
                Ext.util.CSS.refreshCache();
                if (Ext.isFunction(cb)) {
                    cb.call(scope);
                }
            });
        }
    },

    /**
     * apply registry data
     */
    onRegistryLoad: async function() {
        if (! Tine.Tinebase.tineInit.onPreferenceChangeRegistered
            && Tine.Tinebase.registry.get('preferences')
            && Tine.Tinebase.registry.get('currentAccount')
        ) {
            // NOTE: safari (and maybe other slow browsers) need some time till all events from initial preferences
            //       loading are processed. so we wait a little bit until we register the listeners to not get notified
            //       about initial loading.
            (function() {
                Tine.log.info('tineInit::onRegistryLoad - register onPreferenceChange handler');
                Tine.Tinebase.preferences.on('replace', Tine.Tinebase.tineInit.onPreferenceChange);
                Tine.Tinebase.tineInit.onPreferenceChangeRegistered = true;
            }).defer(500);
        }

        Ext.util.CSS.updateRule('.tine-favicon', 'background-image', 'url(' + Tine.Tinebase.registry.get('brandingFaviconSvg') + ')');

        Tine.title = Tine.Tinebase.registry.get('brandingTitle');
        Tine.descriptoion = Tine.Tinebase.registry.get('brandingTitle');
        Tine.logo = Tine.Tinebase.registry.get('brandingLogo');
        Tine.weburl = Tine.Tinebase.registry.get('brandingWeburl');
        Tine.helpUrl = Tine.Tinebase.registry.get('brandingHelpUrl');
        Tine.shop = Tine.Tinebase.registry.get('brandingShopUrl');
        Tine.bugreportUrl = Tine.Tinebase.registry.get('brandingBugsUrl');

        Tine.installLogo = Tine.Tinebase.registry.get('installLogo') ?
            Tine.Tinebase.registry.get('installLogo') :
            Tine.Tinebase.registry.get('brandingLogo');
        Tine.websiteUrl = Tine.Tinebase.registry.get('websiteUrl') ?
            Tine.Tinebase.registry.get('websiteUrl') :
            Tine.Tinebase.registry.get('brandingWeburl');

        if (Ext.isWebApp && Tine.Tinebase.registry.get('sessionId')) {
            // restore session cookie
            Tine.Tinebase.tineInit.cookieProvider.set('TINE20SESSID', Tine.Tinebase.registry.get('sessionId'));
            Tine.Tinebase.tineInit.cookieProvider.set('usercredentialcache', Tine.Tinebase.registry.get('usercredentialcache'));
        }

        Ext.override(Ext.ux.file.Upload, {
            maxFileUploadSize: Tine.Tinebase.registry.get('maxFileUploadSize'),
            maxPostSize: Tine.Tinebase.registry.get('maxPostSize')
        });

        Tine.Tinebase.tineInit.initExtDirect();


        Ext.form.NumberField.prototype.decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');
        Ext.ux.form.NumberField.prototype.thousandSeparator = Tine.Tinebase.registry.get('thousandSeparator');
        Ext.grid.GridView.prototype.resizingStrategy = Tine.Tinebase.registry.get('preferences')?.get('gridResizingStrategy');

        formatMessage.setup({
            locale: Tine.Tinebase.registry.get('locale').locale || 'en'
        });

        await Tine.Tinebase.tineInit.initState();

        if (Tine.Tinebase.registry.get('currentAccount')) {
            Tine.Tinebase.tineInit.initAppMgr();
            if (window.isMainWindow && Tine.Tinebase.configManager.get('broadcasthub')?.active) {
                initBroadcastClient();
            }
        }

        Tine.Tinebase.tineInit.initUploadMgr();

        Tine.Tinebase.tineInit.initLoginPanel();

        // we don't want iOS/Android to place stuff to some other cloud
        // we might need to add a real config for this
        // it's not clear how to detect devices w.o. local storage or clients which place
        // downloads in a cloud :-(
        // but we allow download on mobile devices for now
        Tine.Tinebase.configManager.set('downloadsAllowed', true);

        var AreaLocks = require('./AreaLocks');
        Tine.Tinebase.areaLocks = new AreaLocks.AreaLocks();

        // load initial js of user enabled apps
        // @TODO: move directly after login (login should return requested parts of registry)
        return Tine.__appLoader.loadAllApps(Tine.Tinebase.registry.get('userApplications')).then(function() {
            const rejectedApps = _.reduce(Tine.__appLoader.appLoadedStates, (a,state,app) => {return a.concat(state === 'rejected' ? app : [])}, []);
            if (rejectedApps.length) {
                alert('Your installation in broken. ' + rejectedApps.join(' and ') + ' could not be loaded, please contact your administrator!');
            }
            Tine.Tinebase.tineInit.initCustomJS();
            Tine.__onAllAppsLoadedResolve();
        });
    },

    /**
     * check client version and reload on demand
     */
    checkClientVersion: function() {
        var serverHash = Tine.Tinebase.registry.get('version').assetHash,
            buildType = Tine.Tinebase.registry.get('version').buildType,
            clientHash = Tine.clientVersion.assetHash;

        if (clientHash && clientHash != serverHash && ['RELEASE', 'DEBUG'].indexOf(buildType) > -1) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: i18n._('Your Client is Outdated'),
                msg: i18n._('A new client is available, press OK to get this version'),
                fn: function() {
                    Tine.Tinebase.common.reload({
                        keepRegistry: false,
                        clearCache: true
                    });
                }
            });
        }
    },

    /**
     * check if server schema update is required
     *
     * @return {boolean}
     */
    checkServerUpdateRequired: function (registryData) {
        if (_.get(registryData, 'Tinebase.setupRequired')) {
            const msg = i18n._('Tine 2.0 needs to be updated or is not installed yet.');
            const title =  i18n._('Please wait or contact your administrator');

            Ext.MessageBox.show({
                title : title,
                msg : msg,
                buttons: false,
                closable:false,
                wait:true,
                modal:true,
                icon: Ext.MessageBox.WARNING,
                minWidth: Ext.MessageBox.minProgressWidth
            });

            window.setTimeout(() => {
                Tine.Tinebase.common.reload({
                    keepRegistry: false,
                    clearCache: true
                });
            }, 20000);
            return true;
        }
    },

    /**
     * remove all registry data
     */
    clearRegistry: async function() {
        Tine.log.info('tineInit::clearRegistry');
        await this.getRegistryDB().clear();
    },

    /**
     * executed when a value in Tinebase registry/preferences changed
     *
     * NOTE: this also happens when registry gets cleared e.g. through a logout in another tab
     * @TODO we might face a race condition / loop here:
     *  -> other tab reloads registry
     *  -> this tab reloads and clears cache
     *  -> while this tab reloads the registry is empty
     *  -> if the other tab does a request in the meantime it lacks of registry (and json-key)
     *  -> other tab gets unauth exception and reloads (with clear registry again)
     *  -> ...
     *
     * @param {string} key
     * @param {value} oldValue
     * @param {value} newValue
     */
    onPreferenceChange: function (key, oldValue, newValue) {
        if (Tine.Tinebase.tineInit.isReloading) {
            return;
        }

        switch (key) {
            case 'windowtype':
            case 'confirmLogout':
            case 'timezone':
            case 'locale':
                Tine.log.info('tineInit::onPreferenceChange - reload mainscreen');
                Tine.Tinebase.common.reload({
                    clearCache: key == 'locale'
                });

                break;
        }
    },

    /**
     * initialise window and windowMgr (only popup atm.)
     */
    initWindowMgr: function () {
        // touch UI support
        if (Ext.isTouchDevice) {
            require.ensure(["hammerjs"], function() {
                require('hammerjs'); // global by include :-(

                Ext.apply (Ext.EventObject, {
                    // NOTE: multipoint gesture events have no xy, so we need to grab it from gesture
                    getXY: function() {
                        if (this.browserEvent &&
                            this.browserEvent.gesture &&
                            this.browserEvent.gesture.center) {
                            this.xy = [this.browserEvent.gesture.center.x, this.browserEvent.gesture.center.y];
                        }

                        return this.xy;
                    }
                });

                var mc = new Hammer.Manager(Ext.getDoc().dom, {
                    domEvents: true
                });

                // convert two finger taps into contextmenu clicks (deprecated)
                mc.add(new Hammer.Tap({
                    event: 'contextmenu',
                    pointers: 2
                }));
                // convert long press into contextmenu clicks
                mc.add(new Hammer.Press({
                    event: 'contextmenu',
                    time: 300
                }));
                // convert double taps into double clicks
                mc.add(new Hammer.Tap({
                    event: 'dblclick',
                    taps: 2
                }));

                // NOTE: document scroll only happens when soft keybord is displayed and therefore viewport scrolls.
                //       in this case, content might not be accessable
                //Ext.getDoc().on('scroll', function() {
                //
                //}, this);

            }, 'Tinebase/js/hammerjs');
        }

        Ext.getDoc().on('orientationchange', function() {
            // @TODO: iOS safari only?
            var metas = document.getElementsByTagName('meta');
            for (var i = 0; i < metas.length; i++) {
                if (metas[i].name == "viewport") {
                    metas[i].content = "width=device-width, maximum-scale=1.0";
                    // NOTE: if we don't release the max scale here, we get wired layout effects
                    metas[i].content = "width=device-width, maximum-scale=10, user-scalable=no";
                }
            }
            // NOTE: need to hide soft-keybord before relayouting to preserve layout
            document.activeElement.blur();
            Tine.Tinebase.viewport.doLayout.defer(500, Tine.Tinebase.viewport);
        }, this);

        // adjust modal windows when browser gets resized (also orientation change)
        Tine.Tinebase.viewport.on('resize', function(viewport, adjWidth, adjHeight, rawWidth, rawHeight) {
            Ext.WindowMgr.each(function(win) {
                if (!win.modal) return;
                var currSize = win.getSize(),
                    normSize = win.normSize || currSize,
                    maxSize = {width: adjWidth, height: adjHeight};

                win.setSize(
                    Math.min(Math.max(currSize.width, normSize.width), maxSize.width),
                    Math.min(Math.max(currSize.height, normSize.height), maxSize.height)
                );

                win.center();
            });
        }, this, {buffer: 150});

        // initialise window types
        var windowType = '';
        Ext.ux.PopupWindow.prototype.url = Tine.Tinebase.common.getUrl();
        if (Tine.Tinebase.registry && Tine.Tinebase.registry.get('preferences')) {
            // update window factory window type (required after login)
            windowType = Tine.Tinebase.registry.get('preferences').get('windowtype');
        }

        if (! windowType || windowType == 'autodetect') {
            // var browserDetection = require('browser-detection');
            windowType = Ext.supportsPopupWindows ? 'Browser' : 'Ext';
        }

        Tine.WindowFactory = new Ext.ux.WindowFactory({
            windowType: windowType
        });

        Tine.Tinebase.vue = Tine.Tinebase.vue || {}
        Tine.Tinebase.vue.focusTrapStack = Tine.Tinebase.vue.focusTrapStack || []
    },
    /**
     * initialise state provider
     */
    initState: async function () {
        if (Tine.Tinebase.tineInit.stateful === true) {
            if (window.isMainWindow) {
                // NOTE: IE is as always pain in the ass! cross window issues prohibit serialisation of state objects
                const provider = new Tine.Tinebase.StateProvider();
                await provider.readRegistry();
                Ext.state.Manager.setProvider(provider);
            } else {
                var mainWindow = Ext.ux.PopupWindowMgr.getMainWindow();
                Ext.state.Manager = mainWindow.Ext.state.Manager;
            }
        }
    },

    /**
     * add provider to Ext.Direct based on Tine servicemap
     */
    initExtDirect: function () {
        var sam = Tine.Tinebase.registry.get('serviceMap');

        Ext.Direct.addProvider(Ext.apply(sam, {
            'type'     : 'jsonrpcprovider',
            'namespace': 'Tine',
            'url'      : sam.target
        }));
    },

    /**
     * initialise application manager
     */
    initAppMgr: function () {
        if (! window.isMainWindow) {
            // return app from main window for non-IE browsers
            Tine.Tinebase.appMgr = Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.appMgr;
        } else {
            Tine.Tinebase.appMgr = new Tine.Tinebase.AppManager();
        }
    },

    /**
     * initialise upload manager
     */
    initUploadMgr: function () {
        Tine.Tinebase.uploadManager = window.isMainWindow ? new Ext.ux.file.UploadManager()
            : Ext.ux.PopupWindowMgr.getMainWindow().Tine.Tinebase.uploadManager;
    },

    /**
     * config locales
     */
    initLocale: async function () {
        var _ = window.lodash,
            formatMessage = require('format-message');

        // NOTE: we use gettext tooling for template selection
        //       as there is almost no tooling for icu-messages out there
        formatMessage.setup({
            missingTranslation: 'ignore'
        });

        // auto template selection with gettext
        window.formatMessage = function(template) {
            arguments[0] = window.i18n._hidden(template);
            return formatMessage.apply(formatMessage, arguments);
        };
        window.lodash.assign(window.formatMessage, formatMessage);

        require('Locale');
        require('Locale/Gettext');

        await waitFor( function() { return Tine.__translationData?.__isLoaded; });
        Tine.__applyExtTranslations();

        _.each(Tine.__translationData.msgs, function(msgs, category) {
            Locale.Gettext.prototype._msgs[category] = new Locale.Gettext.PO(msgs);
        });

        window.i18n = new Locale.Gettext();
        window.i18n.textdomain('Tinebase');
    }
};

Ext.onReady(async function () {
    Tine.Tinebase.tineInit.initWindow();
    Tine.Tinebase.tineInit.initPostal();
    Tine.Tinebase.tineInit.initDebugConsole();
    Tine.Tinebase.tineInit.initBootSplash();
    await Tine.Tinebase.tineInit.initLocale();
    Tine.Tinebase.tineInit.initAjax();

    Tine.Tinebase.tineInit.initRegistry(false, function() {
        Tine.Tinebase.tineInit.checkClientVersion();
        Tine.Tinebase.tineInit.initWindowMgr();
        Tine.Tinebase.tineInit.renderWindow();
    });
});
