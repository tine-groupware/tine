/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.UserManual');
Tine.UserManual.helpMap = null;
Tine.UserManual.helpMapPath = '/UserManual/help-map.json';

Tine.UserManual.UserManualDialog = Ext.extend(Ext.FormPanel, {

    /**
     * @cfg {String} initial context for context sensitive help
     */
    context: '',

    // private
    bodyStyle:'padding:5px',
    layout: 'fit',
    border: false,
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    deferredRender: false,
    buttonAlign: null,
    bufferResize: 500,
    windowNamePrefix: 'tine20-usermanual-usermanualdialog',

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('UserManual');
        this.i18n = this.app.i18n;

        this.helpBaseUrl = Tine.Tinebase.configManager.get('helpBaseUrl', 'UserManual');

        this.fbar = ['->', {
            text: this.app.i18n._('Close'),
            minWidth: 70,
            handler: this.onClose.createDelegate(this),
            iconCls: 'action_cancel'
        }];

        this.items = [{
            ref: 'manualFrame',
            afterRender: this.onManualWindowRender.createDelegate(this),
            autoEl: {
                tag: 'iframe',
                style: 'width: 100%; height: 100%;'
            }
        }];

        this.supr().initComponent.call(this);
    },

    /**
     * iframe is ready
     */
    onManualWindowRender: async function() {

        var frameEl = this.manualFrame.el.dom;

        this.loadMask = new Ext.LoadMask(this.getEl(), {
            msg: this.app.i18n._('Loading Manual Page...')
        });

        this.loadMask.show();
        frameEl.addEventListener("load", () => { this.loadMask.hide() });

        const path = await this.resolveHelpUrl(this.context) || '/users/manual';
        frameEl.src = this.helpBaseUrl + path;
    },

    resolveHelpUrl: async function(context) {
        var map = Tine.UserManual.helpMap ?? await (fetch( Tine.Tinebase.common.getUrl() + Tine.UserManual.helpMapPath)
            .then(r => r.json())
            .catch(e => console.error('Could not load help-map.json:', e))
        )

        var current = context;

        while (current.length > 0) {

            if (map[current]) {
                return map[current];
            }

            var idx = current.lastIndexOf('/');
            if (idx <= 0) {
                break;
            }

            current = current.substring(0, idx);
        }
    },

    /**
     * @private
     */
    onClose: function() {
        this.fireEvent('close');
        this.purgeListeners();
        this.window.close();
    }

});

/**
 * Opens a new user manual dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.UserManual.UserManualDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 1200,
        height: 800,
        name: Tine.UserManual.UserManualDialog.prototype.windowNamePrefix,
        contentPanelConstructor: 'Tine.UserManual.UserManualDialog',
        contentPanelConstructorConfig: config || {}
    });
    return window;
};