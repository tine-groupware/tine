/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux');

/**
 * Class for handling of native browser popup window.
 * <p>This class is intended to make the usage of a native popup window as easy as dealing with a modal window.<p>
 * <p>Example usage:</p>
 * <pre><code>
 var win = new Ext.ux.PopupWindow({
     name: 'TasksEditWindow',
     width: 700,
     height: 300,
     url:index.php?method=Tasks.editTask&taskId=5
 });
 * </code></pre>
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.PopupWindow
 * @extends     Ext.Component
 */
Ext.ux.PopupWindow = function(config) {
    Ext.apply(this, config);
    this.contentPanelConstructorConfig = this.contentPanelConstructorConfig || {};

    this.addEvents({
        /**
         * @event beforecolse
         * @desc Fires before the Window is closed. A handler can return false to cancel the close.
         * @param {Ext.ux.PopupWindow}
         */
        "beforeclose" : true,
        /**
         * @event render
         * @desc  Fires after the viewport in the popup window is rendered
         * @param {Ext.ux.PopupWindow}
         */
        "render" : true,
        /**
         * @event close
         * @desc  Fired, when the window got closed
         */
        "close" : true
    });

    Ext.ux.PopupWindow.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.PopupWindow, Ext.Component, {
    /**
     * @cfg    {String}
     * @param  {String} url
     * @desc   url to open
     */
    url: null,
    /**
     * @cfg {String} internal name of new window
     */
    name: 'new window',
    /**
     * @cfg {Int} width of new window
     */
    width: 600,
    /**
     * @cfg {Int} height of new window
     */
    height: 500,
    /**
     * @cfg {Bolean}
     */
    modal: false,
    /**
     * @cfg {Boolean} cut relation to opener
     * needed e.g. when opening a new mainWindow
     */
    noParent: false,
    /**
     * @cfg {String}
     */
    layout: 'fit',
    /**
     * @cfg {String}
     */
    title: null,
    /**
     * @cfg {String} Name of a constructor to create item property
     */
    contentPanelConstructor: null,
    /**
     * @cfg {Object} Config object to pass to itemContructor
     */
    contentPanelConstructorConfig: null,
    /**
     * @property {Browser Window}
     */
    popup: null,
    /**
     * @property {Ext.ux.PopupWindowMgr}
     */
    windowManager: null,

    renderTo: 'useRenderFn',

    /**
     * @private
     */
    initComponent: function(){
        if (! this.title) {
            this.title = Tine.title;
        }
    
        this.manager = this.windowManager = Ext.ux.PopupWindowMgr;

        this.stateful = true;
        this.stateId = 'ux.popupwindow-' + this.contentPanelConstructor;

        Ext.ux.PopupWindow.superclass.initComponent.call(this);
    },

    render: function() {
        // open popup window first to save time
        if (! this.popup) {
            try {
                // NOTE: FF scales windows itself -> no need for adoption
                if (!Ext.isGecko) {
                    const zoomRatiosAvailable = [ 0.25, 1/3, 0.5, 2/3, 0.75, 0.9, 1, 1.1, 1.25, 1.5, 1.75, 2, 2.5, 3, 4, 5 ];
                    const widthCorrection = 16;
                    const cssPixelRatio = (window.outerWidth-widthCorrection) / window.innerWidth;
                    const diffs = zoomRatiosAvailable.map((r) => {return Math.abs(r-cssPixelRatio)});
                    const zoomRatio = zoomRatiosAvailable[diffs.indexOf(Math.min.apply(Math, diffs))];

                    this.width = Math.round(this.width * zoomRatio);
                    this.height = Math.round(this.height * zoomRatio);
                }

                // safari counts window decoration to window height oo
                this.height += Ext.isSafari ? 28 : 0;

                //limit the window size
                this.width = Math.min(screen.availWidth, this.width);
                this.height = Math.min(screen.availHeight, this.height);

                if (this.asIframe) {
                    this.popup = document.createElement('iframe');

                    Ext.fly(this.popup).set({
                        width: '100%',
                        height: '100%',
                        class: 'tw-window-iframe',
                        style: 'border: 0;',
                        id: this.name,
                        name: this.name,
                        src: this.url
                    });

                } else {
                    this.popup = this.openWindow(this.name, this.url, this.width, this.height);

                    if (this.noParent) {
                        this.popup.opener = null;
                    }

                    // closing properly or prevent close otherwise
                    this.popup.addEventListener('beforeunload', _.bind((e) => {
                        if(!this.forceClose && this.fireEvent("beforeclose", this) === false){
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    }, this));

                    // NOTE: 'beforeunload' in Chrome does not work and there is no event to detect window moves,
                    //       so we frequently check for state updates
                    this.popup.setInterval(() => {
                        if (_.reduce(this.getState(), (save, value, key) => {
                            // save state if difference > 5% (to suppress annoying window manager effects)
                            if (Math.max(value, this[key])/Math.min(value, this[key]) > 1.05) {
                                this[key] = value;
                                save = true;
                            }
                            return save;
                        }, false)) {
                            this.saveState();
                        }
                    }, 2000);
                }

            } catch (e) {
                this.popup = null;
            }
        }

        if (! this.popup) {
            return Ext.MessageBox.alert(
                i18n._('Cannot open new window'),
                String.format(i18n._('An attempt to open a new window failed. When clicking OK, the opening procedure will be tried again. To avoid this message, please deactivate your browsers popup blocker for {0}'), Tine.title),
                function () {
                    this.render();
                }.bind(this)
            );
        }
        
        //. register window ( in fact register complete PopupWindow )
        this.windowManager.register(this);
    },

    /**
     * Open browsers native popup
     *
     * @param {string}     windowName
     * @param {string}     url
     * @param width
     * @param height
     */
    openWindow: function (windowName, url, width, height) {
        windowName = Ext.isString(windowName) ? windowName.replace(/[^a-zA-Z0-9_]/g, '') : windowName;

        // thanks to http://www.nigraphic.com/blog/java-script/how-open-new-window-popup-center-screen

        // Determine offsets in case of dualscreen
        const dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
        const dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

        // Window should be opened on mid of tine window
        const w = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        const h = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

        // Determine correct left and top values including dual screen setup
        this.screenX = _.isNumber(this.screenX) ? this.screenX : ((w / 2) - (width / 2)) + dualScreenLeft;
        this.screenY = _.isNumber(this.screenY) ? this.screenY : ((h / 2) - (height / 2)) + dualScreenTop;

        const popup = window.open(url, windowName,
            'width=' + width + ',height=' + height + ',screenY=' + this.screenY + ',screenX=' + this.screenX +
            ',directories=no,toolbar=no,location=no,menubar=no,scrollbars=no,status=no,resizable=yes,dependent=no');

        // FF 105 on linux always opens new windows in fullscreen
        if (Ext.isGecko && Ext.isLinux) {
            // NOTE: resizeTo includes window decoration (in contrast to window.open)
            const heightOffset = popup.outerHeight - popup.innerHeight;
            // NOTE: it's a race with some internal FF process we try to resize as soon as possible
            //       there's no way to measure if the resize succeeded in this early stage
            Array.from(Array(10)).forEach((v, i) => {
                window.setTimeout(() => {
                    popup.resizeTo(width, heightOffset + height);
                }, i * 100);
            });
        }

        return popup;
    },
    
    getState : function() {
        // NOTE: innerWidth/Height is the original dimension without scaling
        // NOTE: FF does auto scaling!
        const state = {
            width: this.popup.innerWidth,
            height: this.popup.innerHeight,
            screenX: this.popup.screenX,
            screenY: this.popup.screenY
        };
        return state;
    },

    // state might have tiny window (e.g. because of small beamer attached)
    // -> only apply bigger states
    applyState : function(state){
        if(state){
            state.width = Math.max(state.width, this.width);
            state.height = Math.max(state.height, this.height);
        }

        Ext.ux.PopupWindow.superclass.applyState.call(this, state);
    },

    /**
     * rename window name
     * 
     * @param {String} new name
     */
    rename: function(newName) {
        this.windowManager.unregister(this);
        this.name = this.popup.name = newName;
        this.windowManager.register(this);
    },
    
    /**
     * Sets the title text for the panel and optionally the icon class.
     * 
     * @param {String} title The title text to set
     * @param {String} iconCls (optional) iconCls A user-defined CSS class that provides the icon image for this panel
     */
    setTitle: function(title, iconCls) {
        if (this.popup && this.popup.document) {
            this.popup.document.title = Ext.util.Format.stripTags(title);
        }
    },

    /**
     * Closes the window, removes it from the DOM and destroys the window object. 
     * The beforeclose event is fired before the close happens and will cancel 
     * the close action if it returns false.
     */
    close: function(force) {
        if(force || this.fireEvent("beforeclose", this) !== false){
            this.forceClose = true;

            this.fireEvent('close', this);

            var popup = this.popup;

            this.destroy();

            if (this.navigateBackOnClose) {
                popup.history.back();
            } else {
                Ext.ux.PopupWindow.close(popup);
            }

            return true;
        } else {
            this.confirmLeavSite(this);
        }
    },
    
    /**
     * @private
     * 
     * called after this.popups native onLoad
     * note: 'this' references the popup, whereas window references the parent
     */
    onLoad: function() {
        this.Ext.onReady(function() {
            this.navigateBackOnClose = this.popup.history.length > 1;
        }, this);
    },
    
    /**
     * @private
     * 
     * note: 'this' references the popup, whereas window references the parent
     */
    onClose: function() {

    },
    
    /**
     * @private
     */
    destroy: function() {
        Ext.ux.PopupWindow.superclass.destroy.call(this);

        this.purgeListeners();
        this.windowManager.unregister(this);

        this.popup = null;
    }
});

/**
 * close window and show close message
 *
 * @static
 * @param win
 */
Ext.ux.PopupWindow.close = function(win) {
    win = win || window;

    // defer messagebox as it should not be displayed too early
    win.setTimeout(function(){
        if (! win) {
            return;
        }

        win.Ext.MessageBox.alert(
            i18n._('Window can be closed'),
            String.format(i18n._('This Window can be closed now. To avoid this message please deactivate your browsers popup blocker for {0}'), Tine.title),
            function() {
                win.close();
            }
        );
    }, 2000);

    win.close();
};
