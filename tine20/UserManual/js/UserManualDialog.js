/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.UserManual');

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
        
        this.tbar = [{
            direction: 'p',
            disabled: true,
            overflowText: this.app.i18n._('Previous'),
            minWidth: 30,
            handler: this.onNavigate.createDelegate(this, ['p']),
            iconCls: 'usermanual-action-go-previous'
        }, {
            direction: 'n',
            disabled: true,
            overflowText: this.app.i18n._('Next'),
            minWidth: 30,
            handler: this.onNavigate.createDelegate(this, ['n']),
            iconCls: 'usermanual-action-go-next'
        }, '-', {
            direction: 'u',
            disabled: true,
            overflowText: this.app.i18n._('Up'),
            minWidth: 30,
            handler: this.onNavigate.createDelegate(this, ['u']),
            iconCls: 'usermanual-action-go-up'
        }, '-', {
            direction: 'h',
            overflowText: this.app.i18n._('Go to Index'),
            minWidth: 30,
            handler: this.onNavigate.createDelegate(this, ['t']),
            iconCls: 'usermanual-action-go-home'
        }, '-', {
            ref: '../searchField',
            xtype: 'tinerecordpickercombobox',
            triggerClass: 'x-form-search-trigger',
            recordClass: Tine.UserManual.Model.ManualPage,
            recordProxy: Tine.UserManual.manualpageBackend,
            width: 300,
            emptyText: this.app.i18n._('Search in User Manual'),
            listeners: {
                scope: this,
                select: this.onSearchSelect
            }
        }];
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

    getUserManualUrl: function() {
        return Tine.Tinebase.common.getUrl() + 'index.php?method=UserManual.get&file=';
    },

    onSearchSelect: function(field, record, index) {

        if (! record) {
        } else {
            var frameEl = this.manualFrame.el.dom;
            frameEl.src = this.getUserManualUrl() + record.get('file');
        }
    },

    /**
     * iframe is ready
     */
    onManualWindowRender: function() {
        var frameEl = this.manualFrame.el.dom,
            manualUrl = this.getUserManualUrl();

        this.loadMask = new Ext.LoadMask(this.getEl(), {
            msg: this.app.i18n._('Loading Manual Page...')
        });
        this.loadMask.show();

        frameEl.addEventListener("load", this.onManualWindowLoad.createDelegate(this));

        if (this.context) {
            manualUrl = manualUrl.replace(/&file=$/, 'Context&context=') + encodeURIComponent(this.context);
        }

        frameEl.src = manualUrl;
    },

    /**
     * on iframe attr change
     */
    onManualWindowBeforeUnLoad: function() {
        this.loadMask.show();
    },

    /**
     * document got loaded into iframe
     */
    onManualWindowLoad: function() {
        var win = this.getManualWindow(),
            doc = win.document,
            title = doc.getElementsByTagName('title')[0],
            navheader = doc.getElementsByClassName('navheader')[0],
            navfooter = doc.getElementsByClassName('navfooter')[0],
            anchors = navfooter ? navfooter.getElementsByTagName('a') : [],
            initialAnchor = doc.querySelector('meta[name=initial_anchor]');

        win.addEventListener("beforeunload", this.onManualWindowBeforeUnLoad.createDelegate(this));

        this.window.setTitle(this.app.i18n._(title ? title.innerText : this.app.getTitle()));

        // hide default nav
        if (navheader && navfooter) {
            navheader.style.display = 'none';
            navfooter.style.display = 'none';
        }

        // find navigation links
        this.navigationLinks = {};
        Ext.each(anchors, function(a) {
            this.navigationLinks[a.accessKey] = a.href;
        }, this);

        // update navigationButtons
        this.getTopToolbar().items.each(function(item) {
            if (item.direction) {
                item.setDisabled(! this.navigationLinks.hasOwnProperty(item.direction));
            }
        }, this);

        // jump to initial anchor
        if (initialAnchor) {
            win.location.href = win.location.href + '#' + initialAnchor.content;
        }
        this.loadMask.hide();
    },

    /**
     * private
     */
    getManualWindow: function() {
        return this.manualFrame.el.dom.contentWindow;
    },

    /**
     * @private
     */
    onNavigate: function(direction) {
        var frameEl = this.manualFrame.el.dom;

        frameEl.src = this.navigationLinks[direction];
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
        width: 600,
        height: 800,
        name: Tine.UserManual.UserManualDialog.prototype.windowNamePrefix,
        contentPanelConstructor: 'Tine.UserManual.UserManualDialog',
        contentPanelConstructorConfig: config || {}
    });
    return window;
};