/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
import {BootstrapVueNext} from 'bootstrap-vue-next'

/**
 * @class Ext.MessageBox
 * <p>Utility class for generating different styles of message boxes.  The alias Ext.Msg can also be used.<p/>
 * <p>Note that the MessageBox is asynchronous.  Unlike a regular JavaScript <code>alert</code> (which will halt
 * browser execution), showing a MessageBox will not cause the code to stop.  For this reason, if you have code
 * that should only run <em>after</em> some user feedback from the MessageBox, you must use a callback function
 * (see the <code>function</code> parameter for {@link #show} for more details).</p>
 * <p>Example usage:</p>
 *<pre><code>
// Basic alert:
Ext.Msg.alert('Status', 'Changes saved successfully.');

// Prompt for user data and process the result using a callback:
Ext.Msg.prompt('Name', 'Please enter your name:', function(btn, text){
    if (btn == 'ok'){
        // process text value and close...
    }
});



// Show a dialog using config options:
Ext.Msg.show({
   title:'Save Changes?',
   msg: 'You are closing a tab that has unsaved changes. Would you like to save your changes?',
   buttons: Ext.Msg.YESNOCANCEL,
   fn: processResult,
   animEl: 'elId',
   icon: Ext.MessageBox.QUESTION
});
</code></pre>
 * @singleton
 */
Ext.MessageBox = function(){
    const MSG_BOX_MOUNT_ID = "Vue-Message-Box-Mount-Point"

    let opt, dlg;

    const skinShades = ["#ffffff", "#fad9b4", "#fcbf89", "#ec8f2e", "#d97103", "#b75b01", "#924500"]

    const __HIDDEN = false // for readability and clarity
    let synchronousVisibilityState = __HIDDEN
    let initialized = false

    // start vue properties
    let vueHandle, vueProps, vueEmitter;
    const defaultConfigs = Object.freeze({
        animEl: null,
        buttons: null,
        closable: true,
        cls: "",
        defaultTextAreaHeight: 3,
        fn: null,
        scope: null,
        icon: "",
        iconCls: "",
        maxWidth: 600,
        minWidth: 100,
        modal: true,
        msg: "",
        multiline: false,
        progressValue: 0,
        progress: false,
        progressText: "",
        prompt: false,
        proxyDrag: false,
        title: "",
        value: "",
        wait: false,
        waitConfig: null,
        width: 600,
        skinColor: '#FFFFFF'
    })

    // modal container config
    const otherConfigs = {
        height: 100,
        minHeight: 80,
        visible: false,
        zIndex: 5000
    }

    const setZIndex = function(zIndex){
        vueProps.otherConfigs.zIndex = zIndex
    }
    // end vue properties

    // private
    const handleButton = function({buttonName, textElValue} = arg){
        handleHide()
        Ext.callback(opt.fn, opt.scope||window, [buttonName, textElValue, opt], 1);
    }
    
    // private
    const handleHide = function() {
        synchronousVisibilityState = __HIDDEN
        if(vueProps) vueProps.otherConfigs.visible = false;
    }

    return {

        getDialog: function(titleText){
            if(!dlg){
                dlg = new Ext.Window()
                const handler = {
                    get(target, key){
                        switch(key){
                            case "hidden":
                                return !synchronousVisibilityState
                            case "setZIndex":
                                return setZIndex
                            case "setActive":
                                return Ext.emptyFn
                            default:
                                return target[key]
                        }
                    }
                }
                const dlg_proxy = new Proxy(dlg, handler)
                Ext.WindowMgr.register(dlg_proxy)
            }
            return dlg
        },
        /**
         * Updates the message box body text
         * @param {String} text (optional) Replaces the message box element's innerHTML with the specified string (defaults to
         * the XHTML-compliant non-breaking space character '&amp;#160;')
         * @return {Ext.MessageBox} this
         */
        updateText: function(text){
            vueProps.opt.msg = text || '&#160;';
            return this;
        },

        /**
         * Updates a progress-style message box's text and progress bar. Only relevant on message boxes
         * initiated via {@link Ext.MessageBox#progress} or {@link Ext.MessageBox#wait},
         * or by calling {@link Ext.MessageBox#show} with progress: true.
         * @param {Number} value Any number between 0 and 1 (e.g., .5, defaults to 0)
         * @param {String} progressText The progress text to display inside the progress bar (defaults to '')
         * @param {String} msg The message box's body text is replaced with the specified string (defaults to undefined
         * so that any existing body text will not get overwritten by default unless a new value is passed in)
         * @return {Ext.MessageBox} this
         */
        updateProgress: function(value, progressText, msg){
            vueProps.opt.progressValue = value;
            if(progressText) vueProps.opt.progressText = progressText;
            if(msg) vueProps.opt.msg = msg;
            return this;
        },
        /**
         * Returns true if the message box is currently displayed
         * @return {Boolean} True if the message box is visible, else false
         */
        isVisible : function(){
            return vueProps.otherConfigs.visible
        },

        /**
         * Hides the message box if it is displayed
         * @return {Ext.MessageBox} this
         */
        hide: function(){
            handleHide();
            return this;
        },

        /**
         * Displays a new message box, or reinitializes an existing message box, based on the config options
         * passed in. All display functions (e.g. prompt, alert, etc.) on MessageBox call this function internally,
         * although those calls are basic shortcuts and do not support all of the config options allowed here.
         * @param {Object} config The following config options are supported: <ul>
         * <li><b>animEl</b> : String/Element<div class="sub-desc">An id or Element from which the message box should animate as it
         * opens and closes (defaults to undefined)</div></li>
         * <li><b>buttons</b> : Object/Boolean<div class="sub-desc">A button config object (e.g., Ext.MessageBox.OKCANCEL or {ok:'Foo',
         * cancel:'Bar'}), or false to not show any buttons (defaults to false)</div></li>
         * <li><b>closable</b> : Boolean<div class="sub-desc">False to hide the top-right close button (defaults to true). Note that
         * progress and wait dialogs will ignore this property and always hide the close button as they can only
         * be closed programmatically.</div></li>
         * <li><b>cls</b> : String<div class="sub-desc">A custom CSS class to apply to the message box's container element</div></li>
         * <li><b>defaultTextHeight</b> : Number<div class="sub-desc">The default height in pixels of the message box's multiline textarea
         * if displayed (defaults to 75)</div></li>
         * <li><b>fn</b> : Function<div class="sub-desc">A callback function which is called when the dialog is dismissed either
         * by clicking on the configured buttons, or on the dialog close button, or by pressing
         * the return button to enter input.
         * <p>Progress and wait dialogs will ignore this option since they do not respond to user
         * actions and can only be closed programmatically, so any required function should be called
         * by the same code after it closes the dialog. Parameters passed:<ul>
         * <li><b>buttonId</b> : String<div class="sub-desc">The ID of the button pressed, one of:<div class="sub-desc"><ul>
         * <li><tt>ok</tt></li>
         * <li><tt>yes</tt></li>
         * <li><tt>no</tt></li>
         * <li><tt>cancel</tt></li>
         * </ul></div></div></li>
         * <li><b>text</b> : String<div class="sub-desc">Value of the input field if either <tt><a href="#show-option-prompt" ext:member="show-option-prompt" ext:cls="Ext.MessageBox">prompt</a></tt>
         * or <tt><a href="#show-option-multiline" ext:member="show-option-multiline" ext:cls="Ext.MessageBox">multiline</a></tt> is true</div></li>
         * <li><b>opt</b> : Object<div class="sub-desc">The config object passed to show.</div></li>
         * </ul></p></div></li>
         * <li><b>scope</b> : Object<div class="sub-desc">The scope of the callback function</div></li>
         * <li><b>icon</b> : String<div class="sub-desc">A CSS class that provides a background image to be used as the body icon for the
         * dialog (e.g. Ext.MessageBox.WARNING or 'custom-class') (defaults to '')</div></li>
         * <li><b>iconCls</b> : String<div class="sub-desc">The standard {@link Ext.Window#iconCls} to
         * add an optional header icon (defaults to '')</div></li>
         * <li><b>maxWidth</b> : Number<div class="sub-desc">The maximum width in pixels of the message box (defaults to 600)</div></li>
         * <li><b>minWidth</b> : Number<div class="sub-desc">The minimum width in pixels of the message box (defaults to 100)</div></li>
         * <li><b>modal</b> : Boolean<div class="sub-desc">False to allow user interaction with the page while the message box is
         * displayed (defaults to true)</div></li>
         * <li><b>msg</b> : String<div class="sub-desc">A string that will replace the existing message box body text (defaults to the
         * XHTML-compliant non-breaking space character '&amp;#160;')</div></li>
         * <li><a id="show-option-multiline"></a><b>multiline</b> : Boolean<div class="sub-desc">
         * True to prompt the user to enter multi-line text (defaults to false)</div></li>
         * <li><b>progress</b> : Boolean<div class="sub-desc">True to display a progress bar (defaults to false)</div></li>
         * <li><b>progressText</b> : String<div class="sub-desc">The text to display inside the progress bar if progress = true (defaults to '')</div></li>
         * <li><a id="show-option-prompt"></a><b>prompt</b> : Boolean<div class="sub-desc">True to prompt the user to enter single-line text (defaults to false)</div></li>
         * <li><b>proxyDrag</b> : Boolean<div class="sub-desc">True to display a lightweight proxy while dragging (defaults to false)</div></li>
         * <li><b>title</b> : String<div class="sub-desc">The title text</div></li>
         * <li><b>value</b> : String<div class="sub-desc">The string value to set into the active textbox element if displayed</div></li>
         * <li><b>wait</b> : Boolean<div class="sub-desc">True to display a progress bar (defaults to false)</div></li>
         * <li><b>waitConfig</b> : Object<div class="sub-desc">A {@link Ext.ProgressBar#waitConfig} object (applies only if wait = true)</div></li>
         * <li><b>width</b> : Number<div class="sub-desc">The width of the dialog in pixels</div></li>
         * </ul>
         * Example usage:
         * <pre><code>
Ext.Msg.show({
   title: 'Address',
   msg: 'Please enter your address:',
   width: 300,
   buttons: Ext.MessageBox.OKCANCEL,
   multiline: true,
   fn: saveAddress,
   animEl: 'addAddressBtn',
   icon: Ext.MessageBox.INFO
});
</code></pre>
         * @return {Ext.MessageBox} this
         */
        show: async function(options){
            options.skinColor = skinShades[Math.floor(Math.random()*skinShades.length)]
            synchronousVisibilityState = !__HIDDEN
            Ext.getBody().mask("Loading");
            const {MessageBoxApp, SymbolKeys} = await import(/* webpackChunkName: "Tinebase/js/VueMessageBox"*/'./VueMessageBox')

            // initializing vue stuff
            if(!initialized){
                const {createApp, h, reactive} = window.vue
                initialized = true
                otherConfigs.buttonText = this.buttonText;
                vueProps = reactive({
                    opt: JSON.parse(JSON.stringify(defaultConfigs)),
                    otherConfigs: JSON.parse(JSON.stringify(otherConfigs)),
                });

                Tine.Tinebase.vue = Tine.Tinebase.vue || {}
                Tine.Tinebase.vue.focusTrapStack = Tine.Tinebase.vue.focusTrapStack || []
                vueProps.opt.focusTrapStack = window.vue.markRaw(Tine.Tinebase.vue.focusTrapStack)

                // app initialization and mounting
                vueHandle = createApp({
                    render: () => h(MessageBoxApp, vueProps)
                });

                // events initialization
                vueEmitter = window.mitt();
                vueEmitter.on("close", handleHide);
                vueEmitter.on("buttonClicked", handleButton);
                vueEmitter.on("messageClicked", e => {
                    if (_.isFunction(opt.onMessageClick)) {
                        opt.onMessageClick(e)
                    }
                });

                vueHandle.config.globalProperties.ExtEventBus = vueEmitter;
                vueHandle.provide(SymbolKeys.ExtEventBusInjectKey, vueEmitter);
                vueHandle.use(BootstrapVueNext)

                const mp = document.createElement("div");
                mp.id = MSG_BOX_MOUNT_ID;
                document.body.appendChild(mp);
                vueHandle.mount(mp);
            }

            opt = {...defaultConfigs,...options};
            opt["closable"] = (opt.closable !== false && opt.progress !== true && opt.wait !== true);
            opt["prompt"] = opt.prompt || (opt.multiline ? true : false);

            // if a MessageBox.show() came right after the first MessageBox calls
            // the async module are being loaded, so
            if(!vueProps || !vueEmitter || !vueHandle) return
            // setting the prop values to the ones passed in config option
            // only those values are taken whose keys are present in the
            // defaultOpt as these are the only ones allowed
            Object.keys(opt).forEach(key => {
                if(key in vueProps.opt){
                    vueProps.opt[key] = opt[key];
                }
            })

            Ext.getBody().unmask();

            // as the other modules are async loaded,
            // the following checks if the visibility state was changed while
            // the modules were being loaded.
            if (synchronousVisibilityState === __HIDDEN) return this;
            vueProps.otherConfigs.visible = true;
            const d = this.getDialog("");
            Ext.WindowMgr.bringToFront(d)
            if (! opt.fn && !options.progress) {
                return new Promise((resolve) => {
                    opt.fn = function () {
                        resolve.call(opt.scope || window, _.get(arguments, '[2].prompt') ? [... arguments] : arguments[0]);
                    }
                })
            }
            return this
        },

       
        /**
         * Adds the specified icon to the dialog.  By default, the class 'ext-mb-icon' is applied for default
         * styling, and the class passed in is expected to supply the background image url. Pass in empty string ('')
         * to clear any existing icon. This method must be called before the MessageBox is shown.
         * The following built-in icon classes are supported, but you can also pass in a custom class name:
         * <pre>
Ext.MessageBox.INFO
Ext.MessageBox.WARNING
Ext.MessageBox.QUESTION
Ext.MessageBox.ERROR
         *</pre>
         * @param {String} icon A CSS classname specifying the icon's background image url, or empty string to clear the icon
         * @return {Ext.MessageBox} this
         */
        setIcon : function(icon){
            if(!vueProps){
                return
            } else{
                vueProps.opt.icon = icon
                return this
            }
        },

        /**
         * Displays a message box with a progress bar.  This message box has no buttons and is not closeable by
         * the user.  You are responsible for updating the progress bar as needed via {@link Ext.MessageBox#updateProgress}
         * and closing the message box when the process is complete.
         * @param {String} title The title bar text
         * @param {String} msg The message box body text
         * @param {String} progressText (optional) The text to display inside the progress bar (defaults to '')
         * @return {Ext.MessageBox} this
         */
        progress : function(title, msg, progressText){
            return this.show({
                title : title,
                msg : msg,
                buttons: false,
                progress:true,
                closable:false,
                minWidth: this.minProgressWidth,
                progressText: progressText
            });
        },

        /**
         * Displays a message box with an infinitely auto-updating progress bar.  This can be used to block user
         * interaction while waiting for a long-running process to complete that does not have defined intervals.
         * You are responsible for closing the message box when the process is complete.
         * @param {String} msg The message box body text
         * @param {String} title (optional) The title bar text
         * @param {Object} config (optional) A {@link Ext.ProgressBar#waitConfig} object
         * @return {Ext.MessageBox} this
         */
        wait : function(msg, title, config){
            return this.show({
                title : title,
                msg : msg,
                buttons: false,
                closable:false,
                wait:true,
                modal:true,
                minWidth: this.minProgressWidth,
                waitConfig: config,
                fn: 'fake',
                icon: this.INFO_WAIT
            });
        },

        /**
         * Displays a standard read-only message box with an OK button (comparable to the basic JavaScript alert prompt).
         * If a callback function is passed it will be called after the user clicks the button, and the
         * id of the button that was clicked will be passed as the only parameter to the callback
         * (could also be the top-right close button).
         * @param {String} title The title bar text
         * @param {String} msg The message box body text
         * @param {Function} fn (optional) The callback function invoked after the message box is closed
         * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the callback is executed. Defaults to the browser wnidow.
         * @return {Ext.MessageBox} this
         */
        alert : function(title, msg, fn, scope){
            return this.show({
                title : title,
                msg : msg,
                buttons: this.OK,
                fn: fn,
                scope : scope,
                minWidth: this.minWidth,
                icon: this.ERROR,
            });
        },

        /**
         * Displays a confirmation message box with Yes and No buttons (comparable to JavaScript's confirm).
         * If a callback function is passed it will be called after the user clicks either button,
         * and the id of the button that was clicked will be passed as the only parameter to the callback
         * (could also be the top-right close button).
         * @param {String} title The title bar text
         * @param {String} msg The message box body text
         * @param {Function} fn (optional) The callback function invoked after the message box is closed
         * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the callback is executed. Defaults to the browser wnidow.
         * @return {Ext.MessageBox} this
         */
        confirm : function(title, msg, fn, scope){
            return this.show({
                title : title,
                msg : msg,
                buttons: this.YESNO,
                fn: fn,
                scope : scope,
                icon: this.QUESTION,
                minWidth: this.minWidth
            });
        },

        /**
         * Displays a message box with OK and Cancel buttons prompting the user to enter some text (comparable to JavaScript's prompt).
         * The prompt can be a single-line or multi-line textbox.  If a callback function is passed it will be called after the user
         * clicks either button, and the id of the button that was clicked (could also be the top-right
         * close button) and the text that was entered will be passed as the two parameters to the callback.
         * @param {String} title The title bar text
         * @param {String} msg The message box body text
         * @param {Function} fn (optional) The callback function invoked after the message box is closed
         * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the callback is executed. Defaults to the browser wnidow.
         * @param {Boolean/Number} multiline (optional) True to create a multiline textbox using the defaultTextHeight
         * property, or the height in pixels to create the textbox (defaults to false / single-line)
         * @param {String} value (optional) Default value of the text input element (defaults to '')
         * @return {Ext.MessageBox} this
         */
        prompt : function(title, msg, fn, scope, multiline, value){
            return this.show({
                title : title,
                msg : msg,
                buttons: this.OKCANCEL,
                fn: fn,
                minWidth: this.minPromptWidth,
                scope : scope,
                prompt:true,
                multiline: multiline,
                value: value
            });
        },

        /**
         * Button config that displays a single OK button
         * @type Object
         */
        OK : {ok:true},
        /**
         * Button config that displays a single Cancel button
         * @type Object
         */
        CANCEL : {cancel:true},
        /**
         * Button config that displays OK and Cancel buttons
         * @type Object
         */
        OKCANCEL : {ok:true, cancel:true},
        /**
         * Button config that displays Yes and No buttons
         * @type Object
         */
        YESNO : {yes:true, no:true},
        /**
         * Button config that displays Yes, No and Cancel buttons
         * @type Object
         */
        YESNOCANCEL : {yes:true, no:true, cancel:true},
        /**
         * The CSS class that provides the INFO icon image
         * @type String
         */
        INFO : 'info_default',
            INFO_INSTRUCTION: 'info_instruction',
            INFO_FAILURE: 'info_failure',
            INFO_SUCCESS: 'info_success',
            INFO_WAIT: 'info_wait',
        /**
         * The CSS class that provides the WARNING icon image
         * @type String
         */
        WARNING : 'warning',
        /**
         * The CSS class that provides the QUESTION icon image
         * @type String
         */
        QUESTION : 'question_default',
            QUESTION_INPUT: 'question_input',
            QUESTION_OPTION: 'question_option',
            QUESTION_CONFIRM: 'question_confirm',
            QUESTION_WARN: 'question_warn',
        /**
         * The CSS class that provides the ERROR icon image
         * @type String
         */
        ERROR : 'error_default',
            ERROR_MILD: 'error_mild',
            ERROR_SEVERE: 'error_severe',

        /**
         * The default height in pixels of the message box's multiline textarea if displayed (defaults to 75)
         * @type Number
         */
        defaultTextHeight : 75,
        /**
         * The maximum width in pixels of the message box (defaults to 600)
         * @type Number
         */
        maxWidth : 600,
        /**
         * The minimum width in pixels of the message box (defaults to 100)
         * @type Number
         */
        minWidth : 100,
        /**
         * The minimum width in pixels of the message box if it is a progress-style dialog.  This is useful
         * for setting a different minimum width than text-only dialogs may need (defaults to 250).
         * @type Number
         */
        minProgressWidth : 250,
        /**
         * The minimum width in pixels of the message box if it is a prompt dialog.  This is useful
         * for setting a different minimum width than text-only dialogs may need (defaults to 250).
         * @type Number
         */
        minPromptWidth: 250,
        /**
         * An object containing the default button text strings that can be overriden for localized language support.
         * Supported properties are: ok, cancel, yes and no.  Generally you should include a locale-specific
         * resource file for handling language support across the framework.
         * Customize the default text like so: Ext.MessageBox.buttonText.yes = "oui"; //french
         * @type Object
         */
        buttonText : {
            ok : "OK",
            cancel : "Cancel",
            yes : "Yes",
            no : "No"
        }
    };
}();

/**
 * Shorthand for {@link Ext.MessageBox}
 */
Ext.Msg = Ext.MessageBox;
