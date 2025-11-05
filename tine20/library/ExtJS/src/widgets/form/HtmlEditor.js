/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
import {contrastColors} from "../../../../../Tinebase/js/util/contrastColors";

/**
 * @class Ext.form.HtmlEditor
 * @extends Ext.form.Field
 * Provides a lightweight HTML Editor component. Some toolbar features are not supported by Safari and will be
 * automatically hidden when needed.  These are noted in the config options where appropriate.
 * <br><br>The editor's toolbar buttons have tooltips defined in the {@link #buttonTips} property, but they are not
 * enabled by default unless the global {@link Ext.QuickTips} singleton is {@link Ext.QuickTips#init initialized}.
 * <br><br><b>Note: The focus/blur and validation marking functionality inherited from Ext.form.Field is NOT
 * supported by this editor.</b>
 * <br><br>An Editor is a sensitive component that can't be used in all spots standard fields can be used. Putting an Editor within
 * any element that has display set to 'none' can cause problems in Safari and Firefox due to their default iframe reloading bugs.
 * <br><br>Example usage:
 * <pre><code>
// Simple example rendered with default options:
Ext.QuickTips.init();  // enable tooltips
new Ext.form.HtmlEditor({
    renderTo: Ext.getBody(),
    width: 800,
    height: 300
});

// Passed via xtype into a container and with custom options:
Ext.QuickTips.init();  // enable tooltips
new Ext.Panel({
    title: 'HTML Editor',
    renderTo: Ext.getBody(),
    width: 600,
    height: 300,
    frame: true,
    layout: 'fit',
    items: {
        xtype: 'htmleditor',
        enableColors: false,
        enableAlignments: false
    }
});
</code></pre>
 * @constructor
 * Create a new HtmlEditor
 * @param {Object} config
 * @xtype htmleditor
 */
Ext.form.HtmlEditor = Ext.extend(Ext.form.Field, {
    /**
     * @cfg {Boolean} enableFormat Enable the bold, italic and underline buttons (defaults to true)
     */
    enableFormat : true,
    /**
     * @cfg {Boolean} enableFontSize Enable the increase/decrease font size buttons (defaults to true)
     */
    enableFontSize : true,
    /**
     * @cfg {Boolean} enableColors Enable the fore/highlight color buttons (defaults to true)
     */
    enableColors : true,
    /**
     * @cfg {Boolean} enableAlignments Enable the left, center, right alignment buttons (defaults to true)
     */
    enableAlignments : true,
    /**
     * @cfg {Boolean} enableLists Enable the bullet and numbered list buttons. Not available in Safari. (defaults to true)
     */
    enableLists : true,
    /**
     * @cfg {Boolean} enableSourceEdit Enable the switch to source edit button. Not available in Safari. (defaults to true)
     */
    enableSourceEdit : true,
    /**
     * @cfg {Boolean} enableLinks Enable the create link button. Not available in Safari. (defaults to true)
     */
    enableLinks : true,
    /**
     * @cfg {Boolean} enableFont Enable font selection. Not available in Safari. (defaults to true)
     */
    enableFont : true,
    /**
     * @cfg {String} createLinkText The default text for the create link prompt
     */
    createLinkText : 'Please enter the URL for the link:',
    /**
     * @cfg {String} defaultLinkValue The default value for the create link prompt (defaults to http:/ /)
     */
    defaultLinkValue : 'http:/'+'/',
    /**
     * @cfg {Array} fontFamilies An array of available font families
     */
    fontFamilies : [
        'Arial',
        'Courier New',
        'Tahoma',
        'Times New Roman',
        'Verdana'
    ],
    defaultFont: 'tahoma',
    /**
     * @cfg {String} defaultValue A default value to be put into the editor to resolve focus issues (defaults to &#160; (Non-breaking space) in Opera and IE6, &#8203; (Zero-width space) in all other browsers).
     */
    defaultValue: (Ext.isOpera || Ext.isIE6) ? '&#160;' : '&#8203;',

    // private properties
    actionMode: 'wrap',
    validationEvent : false,
    deferHeight: true,
    initialized : false,
    activated : false,
    sourceEditMode : false,
    onFocus : Ext.emptyFn,
    iframePad:3,
    hideMode:'offsets',
    defaultAutoCreate : {
        tag: "textarea",
        style:"width:500px;height:300px;",
        autocomplete: "off"
    },

    // private
    initComponent : function(){
        this.addEvents(
            /**
             * @event initialize
             * Fires when the editor is fully initialized (including the iframe)
             * @param {HtmlEditor} this
             */
            'initialize',
            /**
             * @event activate
             * Fires when the editor is first receives the focus. Any insertion must wait
             * until after this event.
             * @param {HtmlEditor} this
             */
            'activate',
             /**
             * @event beforesync
             * Fires before the textarea is updated with content from the editor iframe. Return false
             * to cancel the sync.
             * @param {HtmlEditor} this
             * @param {String} html
             */
            'beforesync',
             /**
             * @event beforepush
             * Fires before the iframe editor is updated with content from the textarea. Return false
             * to cancel the push.
             * @param {HtmlEditor} this
             * @param {String} html
             */
            'beforepush',
             /**
             * @event sync
             * Fires when the textarea is updated with content from the editor iframe.
             * @param {HtmlEditor} this
             * @param {String} html
             */
            'sync',
             /**
             * @event push
             * Fires when the iframe editor is updated with content from the textarea.
             * @param {HtmlEditor} this
             * @param {String} html
             */
            'push',
             /**
             * @event editmodechange
             * Fires when the editor switches edit modes
             * @param {HtmlEditor} this
             * @param {Boolean} sourceEdit True if source edit, false if standard editing.
             */
            'editmodechange'
        )
    },

    // private
    createFontOptions : function(){
        var buf = [], fs = this.fontFamilies, ff, lc;
        for(var i = 0, len = fs.length; i< len; i++){
            ff = fs[i];
            lc = ff.toLowerCase();
            buf.push(
                '<option value="',lc,'" style="font-family:',ff,';"',
                    (this.defaultFont == lc ? ' selected="true">' : '>'),
                    ff,
                '</option>'
            );
        }
        return buf.join('');
    },

    /*
     * Protected method that will not generally be called directly. It
     * is called when the editor creates its toolbar. Override this method if you need to
     * add custom toolbar buttons.
     * @param {HtmlEditor} editor
     */
    createToolbar : function(editor){
        var items = [];
        var tipsEnabled = Ext.QuickTips && Ext.QuickTips.isEnabled();


        function btn(id, toggle, handler, priority=null){
            const cfg =  {
                itemId : id,
                cls : 'x-btn-icon',
                iconCls: 'x-edit-'+id,
                enableToggle:toggle !== false,
                scope: editor,
                handler:handler||editor.relayBtnCmd,
                clickEvent:'mousedown',
                tooltip: tipsEnabled ? _.get(editor, `buttonTips[${id}]`, undefined) : undefined,
                overflowText: _.get(editor, `buttonTips[${id}].title`, undefined),
                tabIndex:-1,
            };
            return priority !== null ? Object.assign(cfg, { displayPriority: priority}) : cfg
        }

        function btnGrp(btnList, priority=0) {
            return {
                xtype: 'buttongroup',
                cols: btnList.length,
                displayPriority: priority,
                items: btnList
            }
        }


        if(this.enableFont && !Ext.isSafari2){
            var fontSelectItem = new Ext.Toolbar.Item({
               autoEl: {
                    tag:'select',
                    cls:'x-font-select',
                    html: this.createFontOptions()
               }, displayPriority: 120,
            });

            items.push(
                fontSelectItem,
                '-'
            );
        }

        if(this.enableFormat){
            items.push(
                btnGrp(
                    [
                        btn('bold'),
                        btn('italic'),
                        btn('underline'),
                        btn('strikeThrough'),
                        btn('formatblockPre', false, this.toggleCodeBlock)
                    ],
                    100
                )
            );
        }

        if(this.enableFontSize){
            items.push(
                '-',
                btnGrp(
                    [
                        btn('increasefontsize', false, this.adjustFont),
                        btn('decreasefontsize', false, this.adjustFont)
                    ],
                    90
                )
            );
        }

        if(this.enableColors){
            let colors = [
                '000000', '993300', '333300', '003300', '003366', '000080', '333399', '333333',
                '800000', 'FF6600', '808000', '008000', '008080', '0000FF', '666699', '808080',
                'FF0000', 'FF9900', '99CC00', '339966', '33CCCC', '3366FF', '800080', '969696',
                'FF00FF', 'FFCC00', 'FFFF00', '00FF00', '00FFFF', '00CCFF', '993366', 'C0C0C0',
                'FF99CC', 'FFCC99', 'FFFF99', 'CCFFCC', 'CCFFFF', '99CCFF', 'CC99FF', 'FFFFFF',
                'FFECF6', 'FFF3E7', 'FFFFE7', 'E2FFE2', 'DCFCFF', 'EDF6FF', 'auto', 'picker'
            ]
            const forecolorCfg = {
                itemId:'forecolor',
                cls:'x-btn-icon',
                iconCls: 'x-edit-forecolor',
                clickEvent:'mousedown',
                tooltip: tipsEnabled ? editor.buttonTips.forecolor || undefined : undefined,
                tabIndex:-1,
                menu : new Ext.menu.ColorMenu({
                    allowReselect: true,
                    focus: Ext.emptyFn,
                    value:'000000',
                    colors: colors,
                    plain:true,
                    listeners: {
                        scope: this,
                        select: function(cp, color){
                            if (color === null) {
                                let container = this.win.getSelection().getRangeAt(0).commonAncestorContainer
                                if (container.nodeName === '#text') {
                                    let element = container.parentElement
                                    this.removeColor(element)
                                    return
                                }
                                let all = container.getElementsByTagName('*')
                                _.forEach(all, (element) => {
                                    if (!this.win.getSelection().containsNode(element, true)) {
                                        return
                                    }
                                    this.removeColor(element)
                                })
                                return
                            }
                            this.execCmd('forecolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
                            this.deferFocus();
                        }
                    },
                    clickEvent:'mousedown'
                })
            }
            const backcolorCfg = {

                itemId:'backcolor',
                cls:'x-btn-icon',
                iconCls: 'x-edit-backcolor',
                clickEvent:'mousedown',
                tooltip: tipsEnabled ? editor.buttonTips.backcolor || undefined : undefined,
                tabIndex:-1,
                menu : new Ext.menu.ColorMenu({
                    focus: Ext.emptyFn,
                    value:'FFFFFF',
                    colors: colors,
                    plain:true,
                    allowReselect: true,
                    listeners: {
                        scope: this,
                        select: function(cp, color){
                            if (color === null) {
                                let container = this.win.getSelection().getRangeAt(0).commonAncestorContainer
                                if (container.nodeName === '#text') {
                                    let element = container.parentElement
                                    this.removeBackground(element)
                                    return
                                }
                                let all = container.getElementsByTagName('*')
                                _.forEach(all, (element) => {
                                    if (!this.win.getSelection().containsNode(element, true)) {
                                        return
                                    }
                                    this.removeBackground(element)
                                })

                                return
                            }

                            if(Ext.isGecko){
                                this.execCmd('useCSS', false);
                                this.execCmd('hilitecolor', color);
                                this.execCmd('useCSS', true);
                                this.deferFocus();
                            }else{
                                this.execCmd(Ext.isOpera ? 'hilitecolor' : 'backcolor', Ext.isWebKit || Ext.isIE ? '#'+color : color);
                                this.deferFocus();
                            }
                        }
                    },
                    clickEvent:'mousedown'
                })
            }
            items.push(
                '-',
                btnGrp([forecolorCfg, backcolorCfg], 80)
            );
        }

        if(this.enableAlignments){
            items.push(
                '-',
                btnGrp([
                    btn('justifyleft'),
                    btn('justifycenter'),
                    btn('justifyright')
                ], 85),
            );
        }

        if(!Ext.isSafari2){
            if(this.enableLinks){
                items.push(
                    '-',
                    btn('createlink', false, this.createLink, 95)
                );
            }

            if(this.enableLists){
                items.push(
                    '-',
                    btnGrp([
                        btn('insertorderedlist'),
                        btn('insertunorderedlist')
                    ], 95)
                );
            }
            if(this.enableSourceEdit){
                items.push(
                    '-',
                    btn('sourceedit', true, function(btn){
                        this.toggleSourceEdit(!this.sourceEditMode);
                    }, 70)
                );
            }
        }

        // build the toolbar
        var tb = new Ext.Toolbar({
            renderTo: this.wrap.dom.firstChild,
            items: items,
            layoutConfig: {
                enableResponsive: true,
            },
            enableOverflow: true,
        });

        if (fontSelectItem) {
            this.fontSelect = fontSelectItem.el;

            this.mon(this.fontSelect, 'change', function(){
                var font = this.fontSelect.dom.value;
                this.relayCmd('fontname', font);
                this.deferFocus();
            }, this);
        }


        // stop form submits
        this.mon(tb.el, 'click', function(e){
            e.preventDefault();
        });

        this.tb = tb;
    },

    removeColor: function (element) {
        if (element.style.color !== '') {
            element.style.color = ''
        }
        if (element.hasAttribute('color')) {
            element.removeAttribute('color')
        }
    },

    removeBackground: function (element) {
        if (element.style.backgroundColor !== '') {
            element.style.backgroundColor = ''
        }
        if (element.style.background !== '') {
            element.style.background = ''
        }
    },

    onDisable: function(){
        this.wrap.mask();
        Ext.form.HtmlEditor.superclass.onDisable.call(this);
    },

    onEnable: function(){
        this.wrap.unmask();
        Ext.form.HtmlEditor.superclass.onEnable.call(this);
    },

    setReadOnly: function(readOnly){

        Ext.form.HtmlEditor.superclass.setReadOnly.call(this, readOnly);
        if(this.initialized){
            this.setDesignMode(!readOnly);
            var bd = this.getEditorBody();
            if(bd){
                bd.style.cursor = this.readOnly ? 'default' : 'text';
            }
            this.disableItems(readOnly);
        }
    },

    /**
     * Protected method that will not generally be called directly. It
     * is called when the editor initializes the iframe with HTML contents. Override this method if you
     * want to change the initialization markup of the iframe (e.g. to add stylesheets).
     *
     * Note: IE8-Standards has unwanted scroller behavior, so the default meta tag forces IE7 compatibility
     */
    getDocMarkup : function(){
        return '<html><head><meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /><style type="text/css">body{border:0;margin:0;padding:3px;height:98%;cursor:text;}</style></head><body></body></html>';
    },

    // private
    getEditorBody : function(){
        var doc = this.getDoc();
        return doc.body || doc.documentElement;
    },

    // private
    getDoc : function(){
        return Ext.isIE ? this.getWin().document : (this.iframe.contentDocument || this.getWin().document);
    },

    // private
    getWin : function(){
        return Ext.isIE ? this.iframe.contentWindow : window.frames[this.iframe.name];
    },

    // private
    onRender : function(ct, position){
        Ext.form.HtmlEditor.superclass.onRender.call(this, ct, position);
        this.el.dom.style.border = '0 none';
        this.el.dom.setAttribute('tabIndex', -1);
        this.el.addClass('x-hidden');
        if(Ext.isIE){ // fix IE 1px bogus margin
            this.el.applyStyles('margin-top:-1px;margin-bottom:-1px;')
        }
        this.wrap = this.el.wrap({
            cls:'x-html-editor-wrap', cn:{cls:'x-html-editor-tb'}
        });

        this.createToolbar(this);

        this.disableItems(true);

        this.tb.doLayout();

        this.createIFrame();

        if(!this.width){
            var sz = this.el.getSize();
            this.setSize(sz.width, this.height || sz.height);
        }
        this.resizeEl = this.positionEl = this.wrap;
    },

    createIFrame: function(){
        var iframe = document.createElement('iframe');
        iframe.name = Ext.id();
        iframe.frameBorder = '0';
        iframe.style.overflow = 'auto';

        this.wrap.dom.appendChild(iframe);
        this.iframe = iframe;

        this.monitorTask = Ext.TaskMgr.start({
            run: this.checkDesignMode,
            scope: this,
            interval:100
        });
    },

    initFrame : function(){
        Ext.TaskMgr.stop(this.monitorTask);
        var doc = this.getDoc();
        this.win = this.getWin();

        doc.open();
        doc.write(this.getDocMarkup());
        doc.close();

        var task = { // must defer to wait for browser to be ready
            run : function(){
                var doc = this.getDoc();
                if(doc.body || doc.readyState == 'complete'){
                    Ext.TaskMgr.stop(task);
                    this.setDesignMode(true);
                    this.initEditor.defer(10, this);
                }
            },
            interval : 10,
            duration:10000,
            scope: this
        };
        Ext.TaskMgr.start(task);
    },


    checkDesignMode : function(){
        if(this.wrap && this.wrap.dom.offsetWidth){
            var doc = this.getDoc();
            if(!doc){
                return;
            }
            if(!doc.editorInitialized || this.getDesignMode() != 'on'){
                this.initFrame();
            }
        }
    },

    /* private
     * set current design mode. To enable, mode can be true or 'on', off otherwise
     */
    setDesignMode : function(mode){
        var doc ;
        if(doc = this.getDoc()){
            if(this.readOnly){
                mode = false;
            }
            doc.designMode = (/on|true/i).test(String(mode).toLowerCase()) ?'on':'off';
        }

    },

    // private
    getDesignMode : function(){
        var doc = this.getDoc();
        if(!doc){ return ''; }
        return String(doc.designMode).toLowerCase();

    },

    disableItems: function(disabled){
        if(this.fontSelect){
            this.fontSelect.dom.disabled = disabled;
        }
        this.tb.items.each(function(item){
            if(item.getItemId() != 'sourceedit'){
                item.setDisabled(disabled);
            }
        });
    },

    // private
    onResize : function(w, h){
        Ext.form.HtmlEditor.superclass.onResize.apply(this, arguments);
        if(this.el && this.iframe){
            if(Ext.isNumber(w)){
                var aw = w - this.wrap.getFrameWidth('lr');
                this.el.setWidth(aw);
                this.tb.setWidth(aw);
                this.iframe.style.width = Math.max(aw, 0) + 'px';
            }
            if(Ext.isNumber(h)){
                var ah = h - this.wrap.getFrameWidth('tb') - this.tb.el.getHeight();
                this.el.setHeight(ah);
                this.iframe.style.height = Math.max(ah, 0) + 'px';
                var bd = this.getEditorBody();
                if(bd){
                    bd.style.height = Math.max((ah - (this.iframePad*2)), 0) + 'px';
                }
            }
        }
    },

    /**
     * Toggles the editor between standard and source edit mode.
     * @param {Boolean} sourceEdit (optional) True for source edit, false for standard
     */
    toggleSourceEdit : function(sourceEditMode){
        if(sourceEditMode === undefined){
            sourceEditMode = !this.sourceEditMode;
        }
        this.sourceEditMode = sourceEditMode === true;
        var btn = this.tb.getComponent('sourceedit');

        if(btn.pressed !== this.sourceEditMode){
            btn.toggle(this.sourceEditMode);
            if(!btn.xtbHidden){
                return;
            }
        }
        if(this.sourceEditMode){
            this.disableItems(true);
            this.syncValue();
            this.iframe.className = 'x-hidden';
            this.el.removeClass('x-hidden');
            this.el.dom.removeAttribute('tabIndex');
            this.el.focus();
        }else{
            if(this.initialized){
                this.disableItems(this.readOnly);
            }
            this.pushValue();
            this.iframe.className = '';
            this.el.addClass('x-hidden');
            this.el.dom.setAttribute('tabIndex', -1);
            this.deferFocus();
        }
        var lastSize = this.lastSize;
        if(lastSize){
            delete this.lastSize;
            this.setSize(lastSize);
        }
        this.fireEvent('editmodechange', this, this.sourceEditMode);
    },

    // private used internally
    createLink : function(){
        var url = prompt(this.createLinkText, this.defaultLinkValue);
        if(url && url != 'http:/'+'/'){
            this.relayCmd('createlink', url);
        }
    },

    // private
    initEvents : function(){
        this.originalValue = this.getValue();
        this.on('activate', this.onActivate);
    },

    /**
     * Overridden and disabled. The editor element does not support standard valid/invalid marking. @hide
     * @method
     */
    markInvalid : Ext.emptyFn,

    /**
     * Overridden and disabled. The editor element does not support standard valid/invalid marking. @hide
     * @method
     */
    clearInvalid : Ext.emptyFn,

    // docs inherit from Field
    setValue : function(v){
        Ext.form.HtmlEditor.superclass.setValue.call(this, v);
        this.pushValue();
        return this;
    },

    /**
     * Protected method that will not generally be called directly. If you need/want
     * custom HTML cleanup, this is the method you should override.
     * @param {String} html The HTML to be cleaned
     * @return {String} The cleaned HTML
     */
    cleanHtml: function(html) {
        html = String(html);
        if(Ext.isWebKit){ // strip safari nonsense
            html = html.replace(/\sclass="(?:Apple-style-span|khtml-block-placeholder)"/gi, '');
        }

        /*
         * Neat little hack. Strips out all the non-digit characters from the default
         * value and compares it to the character code of the first character in the string
         * because it can cause encoding issues when posted to the server.
         */
        if(html.charCodeAt(0) == this.defaultValue.replace(/\D/g, '')){
            html = html.substring(1);
        }
        return html;
    },

    /**
     * Protected method that will not generally be called directly. Syncs the contents
     * of the editor iframe with the textarea.
     */
    syncValue : function(){
        if(this.initialized){
            var bd = this.getEditorBody();
            var html = bd.innerHTML;
            if(Ext.isWebKit){
                var bs = bd.getAttribute('style'); // Safari puts text-align styles on the body element!
                var m = bs.match(/text-align:(.*?);/i);
                if(m && m[1]){
                    html = '<div style="'+m[0]+'">' + html + '</div>';
                }
            }
            html = this.cleanHtml(html);
            if(this.fireEvent('beforesync', this, html) !== false){
                this.el.dom.value = html;
                this.fireEvent('sync', this, html);
            }
        }
    },

    //docs inherit from Field
    getValue : function() {
        if (this.selectedImage) {
            this.selectedImage.style = 'outline: none'
            this.selectedImage = false
        }
        this[this.sourceEditMode ? 'pushValue' : 'syncValue']();
        return Ext.form.HtmlEditor.superclass.getValue.call(this);
    },

    /**
     * Protected method that will not generally be called directly. Pushes the value of the textarea
     * into the iframe editor.
     */
    pushValue : function(){
        if(this.initialized){
            var v = this.el.dom.value;
            if(!this.activated && v.length < 1){
                v = this.defaultValue;
            }
            if(this.fireEvent('beforepush', this, v) !== false){
                this.getEditorBody().innerHTML = v;
                if(Ext.isGecko){
                    // Gecko hack, see: https://bugzilla.mozilla.org/show_bug.cgi?id=232791#c8
                    this.setDesignMode(false);  //toggle off first

                }
                this.setDesignMode(true);
                this.fireEvent('push', this, v);
            }
        }
    },

    // private
    deferFocus : function(){
        this.focus.defer(10, this);
    },

    // docs inherit from Field
    focus : function(){
        if(this.win && !this.sourceEditMode){
            this.win.focus();
        }else{
            this.el.focus();
        }
    },

    // private
    initEditor : function(){
        //Destroying the component during/before initEditor can cause issues.
        try{
            var dbody = this.getEditorBody(),
                ss = this.el.getStyles('font-size', 'font-family', 'background-image', 'background-repeat'),
                doc,
                fn;

            ss['background-attachment'] = 'fixed'; // w3c
            dbody.bgProperties = 'fixed'; // ie

            if(this.enableFont && !Ext.isSafari2 && this.defaultFont) {
                ss['font-family'] = this.defaultFont;
            }

            Ext.DomHelper.applyStyles(dbody, ss);

            doc = this.getDoc();

            if(doc){
                try{
                    Ext.EventManager.removeAll(doc);
                }catch(e){}
            }

            /*
             * We need to use createDelegate here, because when using buffer, the delayed task is added
             * as a property to the function. When the listener is removed, the task is deleted from the function.
             * Since onEditorEvent is shared on the prototype, if we have multiple html editors, the first time one of the editors
             * is destroyed, it causes the fn to be deleted from the prototype, which causes errors. Essentially, we're just anonymizing the function.
             */
            fn = this.onEditorEvent.createDelegate(this);
            Ext.EventManager.on(doc, {
                mousedown: fn,
                dblclick: fn,
                click: fn,
                keyup: fn,
                buffer:100
            });

            if(Ext.isGecko){
                Ext.EventManager.on(doc, 'keypress', this.applyCommand, this);
            }
            if(Ext.isIE || Ext.isWebKit || Ext.isOpera){
                Ext.EventManager.on(doc, 'keydown', this.fixKeys, this);
            }
            dbody.addEventListener('paste', this.onClipboardEvent.bind(this));
    
            this.plugins.forEach((plugin) => {
                //fixme: default dropEl(textarea) does not work , we have to use body as element
                if (plugin.dropEl) {
                    this.docElement = new Ext.Element(this.iframe.contentDocument);
                    plugin.dropEl = this.docElement;
                    plugin.initEvents(this.docElement);
                }
            })

            doc.editorInitialized = true;
            this.initialized = true;
            this.pushValue();
            this.setReadOnly(this.readOnly);
            this.fireEvent('initialize', this);
        }catch(e){}
    },

    // private
    onDestroy : function(){
        if(this.monitorTask){
            Ext.TaskMgr.stop(this.monitorTask);
        }
        if(this.rendered){
            Ext.destroy(this.tb);
            var doc = this.getDoc();
            if(doc){
                try{
                    Ext.EventManager.removeAll(doc);
                    for (var prop in doc){
                        delete doc[prop];
                    }
                }catch(e){}
            }
            if(this.wrap){
                this.wrap.dom.innerHTML = '';
                this.wrap.remove();
            }
        }

        if(this.el){
            this.el.removeAllListeners();
            this.el.remove();
        }
        this.purgeListeners();
    },

    // private
    onFirstFocus : function(){
        this.activated = true;
        this.disableItems(this.readOnly);
        if(Ext.isGecko){ // prevent silly gecko errors
            this.win.focus();
            var s = this.win.getSelection();
            if(!s.focusNode || s.focusNode.nodeType != 3){
                var r = s.getRangeAt(0);
                r.selectNodeContents(this.getEditorBody());
                r.collapse(true);
                this.deferFocus();
            }
            try{
                this.execCmd('useCSS', true);
                this.execCmd('styleWithCSS', false);
            }catch(e){}
        }
        this.fireEvent('activate', this);
    },

    onActivate: function (e) {
        if (this.getEditorBody().classList.contains('dark-mode')) {
            contrastColors.darkBg = '#000000'
            contrastColors.darkMode = true
            _.forEach(this.getEditorBody().children, (c) => {
                if (c.classList.contains('felamimail-body-blockquote') || c.classList.contains('felamimail-body-forwarded')) {
                    contrastColors.findBackground(c)
                }
            })
        }
    },

    // private
    adjustFont: function(btn){
        var adjust = btn.getItemId() == 'increasefontsize' ? 1 : -1,
            doc = this.getDoc(),
            v = parseInt(doc.queryCommandValue('FontSize') || 2, 10);

        this.execCmd('FontSize', Math.max(1, v+adjust));
    },

    // private
    toggleCodeBlock: function(btn) {
        let doc = this.getDoc();
        if (doc.queryCommandValue('formatblock') === 'pre') {
            let selection = this.getWin().getSelection()
            _.each(doc.querySelectorAll('pre'), function (node) {
                if (selection.containsNode(node, true)) {
                    node.after(document.createElement('br'));
                    node.replaceWith(node.textContent);
                }
            });
        } else {
            this.execCmd('formatblock', 'pre');
        }
    },

    // private
    onEditorEvent : function(e){
        if (this.selectedImage) {
            this.selectedImage.style = 'outline: none'
        }
        if (e.getTarget('img')) {
            let img = e.getTarget('img')
            img.style = 'outline: 2px solid #6060FF'
            this.selectedImage = img
        } else {
            this.selectedImage = false
        }

        this.updateToolbar(e);
    },

    /**
     * Protected method that will not generally be called directly. It triggers
     * a toolbar update by reading the markup state of the current selection in the editor.
     */
    updateToolbar: function(e){

        if(this.readOnly){
            return;
        }

        if(!this.activated){
            this.onFirstFocus();
            return;
        }

        const btns = Object.entries(this.tb.items.map).reduce((acc, [key, sb]) => {
            const items = sb.isXType('buttongroup') ? sb.items.map : { [key]: sb };
            return Object.assign(acc, items);
        }, {});

        var doc = this.getDoc();

        if(this.enableFont && !Ext.isSafari2){
            var name = (doc.queryCommandValue('FontName')||this.defaultFont).toLowerCase().replace(/"/g, '');
            
            if(name != this.fontSelect.dom.value){
                this.fontSelect.dom.value = name;
            }
        }
        if(this.enableFormat){
            btns.bold.toggle(doc.queryCommandState('bold'));
            btns.italic.toggle(doc.queryCommandState('italic'));
            btns.underline.toggle(doc.queryCommandState('underline'));
            btns.strikeThrough.toggle(doc.queryCommandState('strikeThrough'));
            btns.formatblockPre.toggle(doc.queryCommandValue('formatblock') === 'pre');
        }
        if(this.enableAlignments){
            btns.justifyleft.toggle(doc.queryCommandState('justifyleft'));
            btns.justifycenter.toggle(doc.queryCommandState('justifycenter'));
            btns.justifyright.toggle(doc.queryCommandState('justifyright'));
        }
        if(!Ext.isSafari2 && this.enableLists){
            btns.insertorderedlist.toggle(doc.queryCommandState('insertorderedlist'));
            btns.insertunorderedlist.toggle(doc.queryCommandState('insertunorderedlist'));
        }

        Ext.menu.MenuMgr.hideAll();

        if(this.selectedImage) {
            let menuItems = []
            if (this.fileCache && this.fileCache[this.selectedImage.alt]) {
                menuItems = [
                    `<b>${window.i18n._('Image size')}</b>`,
                    {text: window.i18n._('small'), checked: (this.selectedImage.dataset.size === '300'), group: 'image-size', handler: this.setImageSize, scope: this, size: 300},
                    {text: window.i18n._('medium'), checked: (this.selectedImage.dataset.size === '500'), group: 'image-size', handler: this.setImageSize, scope: this, size: 500},
                    {text: window.i18n._('large'), checked: (this.selectedImage.dataset.size === '800'), group: 'image-size', handler: this.setImageSize, scope: this, size: 800},
                    new Ext.menu.Separator()
                ]
            }
            menuItems.push({text: window.i18n._('delete'), handler: this.deleteImage, scope: this})
            const menu = new Ext.menu.Menu({
                items: menuItems,
                renderTo: this.wrap,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   'Tinebase-MainContextMenu'
                }]
            });
            const offset = this.wrap.getXY()
            const pos = e.getXY()

            menu.showAt([pos[0] + offset[0], pos[1] + offset[1]], undefined, true)
        }

        this.syncValue();
    },

    deleteImage: function ()
    {
        if (this.selectedImage) {
            this.selectedImage.remove()
        }
    },

    getImageBase64: async function (result, max, target, type = 'image/jpeg')
    {
        let img = new Image()
        img.src = result
        img.onload = () => {
            this.imgOnload(img, target, max, type)
        }
    },

    imgOnload: async function (img, target, max, type = 'image/jpeg') {
        let maxWidth = max
        let maxHeight = max

        let canvas = document.createElement('canvas')
        let ctx = canvas.getContext('2d')
        ctx.clearRect(0, 0, canvas.width, canvas.height)

        let width = img.width
        let height = img.height
        if (width > height) {
            if (width > maxWidth) {
                height *= maxWidth / width
                width = maxWidth
            }
        } else {
            if (height > maxHeight) {
                width *= maxHeight / height
                height = maxHeight
            }
        }

        canvas.width = width
        canvas.height = height
        ctx.drawImage(img, 0, 0, width, height)

        target.src = canvas.toDataURL(type);
    },

    setImageSize: function (button, event) {
        if (!this.fileCache[this.selectedImage.alt]) {
            return
        }

        let name = this.selectedImage.alt
        let fileBlob = this.fileCache[name]
        let reader = new FileReader()
        reader.readAsDataURL(fileBlob)
        reader.onload = () => {
            let type = 'image/jpeg'
            switch (this.selectedImage.alt.slice(-3)) {
                case 'png':
                    type = 'image/png'
                    break;
                case  'gif':
                    type = 'image/gif'
                    break;
            }

            this.getImageBase64(reader.result, button.size, this.selectedImage, type).then (() => {
                this.selectedImage.dataset.size = button.size
            })
        }
    },

    // private
    relayBtnCmd : function(btn){
        this.relayCmd(btn.getItemId());
    },

    /**
     * Executes a Midas editor command on the editor document and performs necessary focus and
     * toolbar updates. <b>This should only be called after the editor is initialized.</b>
     * @param {String} cmd The Midas command
     * @param {String/Boolean} value (optional) The value to pass to the command (defaults to null)
     */
    relayCmd : function(cmd, value){
        (function(){
            this.focus();
            this.execCmd(cmd, value);
            this.updateToolbar();
        }).defer(10, this);
    },

    /**
     * Executes a Midas editor command directly on the editor document.
     * For visual commands, you should use {@link #relayCmd} instead.
     * <b>This should only be called after the editor is initialized.</b>
     * @param {String} cmd The Midas command
     * @param {String/Boolean} value (optional) The value to pass to the command (defaults to null)
     */
    execCmd : function(cmd, value){
        var doc = this.getDoc();
        doc.execCommand(cmd, false, value === undefined ? null : value);
        this.syncValue();
    },

    // private
    applyCommand : function(e){
        if(e.ctrlKey){
            var c = e.getCharCode(), cmd;
            if(c > 0){
                c = String.fromCharCode(c);
                switch(c){
                    case 'b':
                        cmd = 'bold';
                    break;
                    case 'i':
                        cmd = 'italic';
                    break;
                    case 'u':
                        cmd = 'underline';
                    break;
                    case 'd':
                        cmd = 'strikeThrough';
                    break;
                }
                if(cmd){
                    this.win.focus();
                    this.execCmd(cmd);
                    this.deferFocus();
                    e.preventDefault();
                }
            }
        }
    },

    /**
     * Inserts the passed text at the current cursor position. Note: the editor must be initialized and activated
     * to insert text.
     * @param {String} text
     */
    insertAtCursor : function(text){
        if(!this.activated){
            return;
        }
        if(Ext.isIE){
            this.win.focus();
            var doc = this.getDoc(),
                r = doc.selection.createRange();
            if(r){
                r.pasteHTML(text);
                this.syncValue();
                this.deferFocus();
            }
        }else{
            this.win.focus();
            this.execCmd('InsertHTML', text);
            this.deferFocus();
        }
    },

    // private
    fixKeys : function(){ // load time branching for fastest keydown performance
        if(Ext.isIE){
            return function(e){
                var k = e.getKey(),
                    doc = this.getDoc(),
                        r;
                if(k == e.TAB){
                    e.stopEvent();
                    r = doc.selection.createRange();
                    if(r){
                        r.collapse(true);
                        r.pasteHTML('&nbsp;&nbsp;&nbsp;&nbsp;');
                        this.deferFocus();
                    }
                }else if(k == e.ENTER){
                    r = doc.selection.createRange();
                    if(r){
                        var target = r.parentElement();
                        if(!target || target.tagName.toLowerCase() != 'li'){
                            e.stopEvent();
                            r.pasteHTML('<br />');
                            r.collapse(false);
                            r.select();
                        }
                    }
                }
            };
        }else if(Ext.isOpera){
            return function(e){
                var k = e.getKey();
                if(k == e.TAB){
                    e.stopEvent();
                    this.win.focus();
                    this.execCmd('InsertHTML','&nbsp;&nbsp;&nbsp;&nbsp;');
                    this.deferFocus();
                }
            };
        }else if(Ext.isWebKit){
            return function(e){
                var k = e.getKey();
                if(k == e.TAB){
                    e.stopEvent();
                    this.execCmd('InsertText','\t');
                    this.deferFocus();
                }else if(k == e.ENTER){
                    e.stopEvent();
                    this.execCmd('InsertHtml','<br /><br />');
                    this.deferFocus();
                }
             };
        }
    }(),

    /**
     * Returns the editor's toolbar. <b>This is only available after the editor has been rendered.</b>
     * @return {Ext.Toolbar}
     */
    getToolbar : function(){
        return this.tb;
    },

    /**
     * Object collection of toolbar tooltips for the buttons in the editor. The key
     * is the command id associated with that button and the value is a valid QuickTips object.
     * For example:
<pre><code>
{
    bold : {
        title: 'Bold (Ctrl+B)',
        text: 'Make the selected text bold.',
        cls: 'x-html-editor-tip'
    },
    italic : {
        title: 'Italic (Ctrl+I)',
        text: 'Make the selected text italic.',
        cls: 'x-html-editor-tip'
    },
    ...
</code></pre>
    * @type Object
     */
    buttonTips : {
        bold : {
            title: 'Bold (Ctrl+B)',
            text: 'Make the selected text bold.',
            cls: 'x-html-editor-tip'
        },
        italic : {
            title: 'Italic (Ctrl+I)',
            text: 'Make the selected text italic.',
            cls: 'x-html-editor-tip'
        },
        underline : {
            title: 'Underline (Ctrl+U)',
            text: 'Underline the selected text.',
            cls: 'x-html-editor-tip'
        },
        strikeThrough : {
            title: 'Strike out',
            text: 'Strike out the selected text.',
            cls: 'x-html-editor-tip'
        },
        increasefontsize : {
            title: 'Grow Text',
            text: 'Increase the font size.',
            cls: 'x-html-editor-tip'
        },
        decreasefontsize : {
            title: 'Shrink Text',
            text: 'Decrease the font size.',
            cls: 'x-html-editor-tip'
        },
        backcolor : {
            title: 'Text Highlight Color',
            text: 'Change the background color of the selected text.',
            cls: 'x-html-editor-tip'
        },
        forecolor : {
            title: 'Font Color',
            text: 'Change the color of the selected text.',
            cls: 'x-html-editor-tip'
        },
        justifyleft : {
            title: 'Align Text Left',
            text: 'Align text to the left.',
            cls: 'x-html-editor-tip'
        },
        justifycenter : {
            title: 'Center Text',
            text: 'Center text in the editor.',
            cls: 'x-html-editor-tip'
        },
        justifyright : {
            title: 'Align Text Right',
            text: 'Align text to the right.',
            cls: 'x-html-editor-tip'
        },
        insertunorderedlist : {
            title: 'Bullet List',
            text: 'Start a bulleted list.',
            cls: 'x-html-editor-tip'
        },
        insertorderedlist : {
            title: 'Numbered List',
            text: 'Start a numbered list.',
            cls: 'x-html-editor-tip'
        },
        createlink : {
            title: 'Hyperlink',
            text: 'Make the selected text a hyperlink.',
            cls: 'x-html-editor-tip'
        },
        formatblockPre : {
            title: 'Code block',
            text: 'Insert pre-formatted code block.',
            cls: 'x-html-editor-tip'
        },
        sourceedit : {
            title: 'Source Edit',
            text: 'Switch to source editing mode.',
            cls: 'x-html-editor-tip'
        }
    }

    // hide stuff that is not compatible
    /**
     * @event blur
     * @hide
     */
    /**
     * @event change
     * @hide
     */
    /**
     * @event focus
     * @hide
     */
    /**
     * @event specialkey
     * @hide
     */
    /**
     * @cfg {String} fieldClass @hide
     */
    /**
     * @cfg {String} focusClass @hide
     */
    /**
     * @cfg {String} autoCreate @hide
     */
    /**
     * @cfg {String} inputType @hide
     */
    /**
     * @cfg {String} invalidClass @hide
     */
    /**
     * @cfg {String} invalidText @hide
     */
    /**
     * @cfg {String} msgFx @hide
     */
    /**
     * @cfg {String} validateOnBlur @hide
     */
    /**
     * @cfg {Boolean} allowDomMove  @hide
     */
    /**
     * @cfg {String} applyTo @hide
     */
    /**
     * @cfg {String} autoHeight  @hide
     */
    /**
     * @cfg {String} autoWidth  @hide
     */
    /**
     * @cfg {String} cls  @hide
     */
    /**
     * @cfg {String} disabled  @hide
     */
    /**
     * @cfg {String} disabledClass  @hide
     */
    /**
     * @cfg {String} msgTarget  @hide
     */
    /**
     * @cfg {String} readOnly  @hide
     */
    /**
     * @cfg {String} style  @hide
     */
    /**
     * @cfg {String} validationDelay  @hide
     */
    /**
     * @cfg {String} validationEvent  @hide
     */
    /**
     * @cfg {String} tabIndex  @hide
     */
    /**
     * @property disabled
     * @hide
     */
    /**
     * @method applyToMarkup
     * @hide
     */
    /**
     * @method disable
     * @hide
     */
    /**
     * @method enable
     * @hide
     */
    /**
     * @method validate
     * @hide
     */
    /**
     * @event valid
     * @hide
     */
    /**
     * @method setDisabled
     * @hide
     */
    /**
     * @cfg keys
     * @hide
     */
});
Ext.reg('htmleditor', Ext.form.HtmlEditor);
