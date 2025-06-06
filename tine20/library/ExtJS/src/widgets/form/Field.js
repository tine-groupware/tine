/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.form.Field
 * @extends Ext.BoxComponent
 * Base class for form fields that provides default event handling, sizing, value handling and other functionality.
 * @constructor
 * Creates a new Field
 * @param {Object} config Configuration options
 * @xtype field
 */
Ext.form.Field = Ext.extend(Ext.BoxComponent,  {
    /**
     * <p>The label Element associated with this Field. <b>Only available after this Field has been rendered by a
     * {@link form Ext.layout.FormLayout} layout manager.</b></p>
     * @type Ext.Element
     * @property label
     */
    /**
     * @cfg {String} inputType The type attribute for input fields -- e.g. radio, text, password, file (defaults
     * to 'text'). The types 'file' and 'password' must be used to render those field types currently -- there are
     * no separate Ext components for those. Note that if you use <tt>inputType:'file'</tt>, {@link #emptyText}
     * is not supported and should be avoided.
     */
    /**
     * @cfg {Number} tabIndex The tabIndex for this field. Note this only applies to fields that are rendered,
     * not those which are built via applyTo (defaults to undefined).
     */
    /**
     * @cfg {Mixed} value A value to initialize this field with (defaults to undefined).
     */
    /**
     * @cfg {String} name The field's HTML name attribute (defaults to '').
     * <b>Note</b>: this property must be set if this field is to be automatically included with
     * {@link Ext.form.BasicForm#submit form submit()}.
     */
    /**
     * @cfg {String} cls A custom CSS class to apply to the field's underlying element (defaults to '').
     */

    /**
     * @cfg {String} emptyValue value for value===emptyText or undefined
     */
    emptyValue : null,
    /**
     * @cfg {String} invalidClass The CSS class to use when marking a field invalid (defaults to 'x-form-invalid')
     */
    invalidClass : 'x-form-invalid',
    /**
     * @cfg {String} invalidText The error text to use when marking a field invalid and no message is provided
     * (defaults to 'The value in this field is invalid')
     */
    invalidText : 'The value in this field is invalid',
    /**
     * @cfg {String} focusClass The CSS class to use when the field receives focus (defaults to 'x-form-focus')
     */
    focusClass : 'x-form-focus',
    /**
     * @cfg {Boolean} preventMark
     * <tt>true</tt> to disable {@link #markInvalid marking the field invalid}.
     * Defaults to <tt>false</tt>.
     */
    /**
     * @cfg {String/Boolean} validationEvent The event that should initiate field validation. Set to false to disable
      automatic validation (defaults to 'keyup').
     */
    validationEvent : 'keyup',
    /**
     * @cfg {Boolean} validateOnBlur Whether the field should validate when it loses focus (defaults to true).
     */
    validateOnBlur : true,
    /**
     * @cfg {Number} validationDelay The length of time in milliseconds after user input begins until validation
     * is initiated (defaults to 250)
     */
    validationDelay : 250,
    /**
     * @cfg {String/Object} autoCreate <p>A {@link Ext.DomHelper DomHelper} element spec, or true for a default
     * element spec. Used to create the {@link Ext.Component#getEl Element} which will encapsulate this Component.
     * See <tt>{@link Ext.Component#autoEl autoEl}</tt> for details.  Defaults to:</p>
     * <pre><code>{tag: 'input', type: 'text', size: '20', autocomplete: 'off'}</code></pre>
     */
    defaultAutoCreate : {tag: 'input', type: 'text', size: '20', autocomplete: 'off'},
    /**
     * @cfg {String} fieldClass The default CSS class for the field (defaults to 'x-form-field')
     */
    fieldClass : 'x-form-field',
    /**
     * @cfg {String} msgTarget<p>The location where the message text set through {@link #markInvalid} should display.
     * Must be one of the following values:</p>
     * <div class="mdetail-params"><ul>
     * <li><code>qtip</code> Display a quick tip containing the message when the user hovers over the field. This is the default.
     * <div class="subdesc"><b>{@link Ext.QuickTips#init Ext.QuickTips.init} must have been called for this setting to work.</b></div</li>
     * <li><code>title</code> Display the message in a default browser title attribute popup.</li>
     * <li><code>under</code> Add a block div beneath the field containing the error message.</li>
     * <li><code>side</code> Add an error icon to the right of the field, displaying the message in a popup on hover.</li>
     * <li><code>[element id]</code> Add the error message directly to the innerHTML of the specified element.</li>
     * </ul></div>
     */
    msgTarget : 'qtip',
    /**
     * @cfg {String} msgFx <b>Experimental</b> The effect used when displaying a validation message under the field
     * (defaults to 'normal').
     */
    msgFx : 'normal',
    /**
     * @cfg {Boolean} readOnly <tt>true</tt> to mark the field as readOnly in HTML
     * (defaults to <tt>false</tt>).
     * <br><p><b>Note</b>: this only sets the element's readOnly DOM attribute.
     * Setting <code>readOnly=true</code>, for example, will not disable triggering a
     * ComboBox or DateField; it gives you the option of forcing the user to choose
     * via the trigger without typing in the text box. To hide the trigger use
     * <code>{@link Ext.form.TriggerField#hideTrigger hideTrigger}</code>.</p>
     */
    readOnly : false,
    /**
     * @cfg {Boolean} disabled True to disable the field (defaults to false).
     * <p>Be aware that conformant with the <a href="http://www.w3.org/TR/html401/interact/forms.html#h-17.12.1">HTML specification</a>,
     * disabled Fields will not be {@link Ext.form.BasicForm#submit submitted}.</p>
     */
    disabled : false,
    /**
     * @cfg {Boolean} submitValue False to clear the name attribute on the field so that it is not submitted during a form post.
     * Defaults to <tt>true</tt>.
     */
    submitValue: true,

    // private
    isFormField : true,

    // private
    msgDisplay: '',

    // private
    hasFocus : false,

    // private
    initComponent : function(){
        Ext.form.Field.superclass.initComponent.call(this);
        this.addEvents(
            /**
             * @event focus
             * Fires when this field receives input focus.
             * @param {Ext.form.Field} this
             */
            'focus',
            /**
             * @event blur
             * Fires when this field loses input focus.
             * @param {Ext.form.Field} this
             */
            'blur',
            /**
             * @event specialkey
             * Fires when any key related to navigation (arrows, tab, enter, esc, etc.) is pressed.
             * To handle other keys see {@link Ext.Panel#keys} or {@link Ext.KeyMap}.
             * You can check {@link Ext.EventObject#getKey} to determine which key was pressed.
             * For example: <pre><code>
var form = new Ext.form.FormPanel({
    ...
    items: [{
            fieldLabel: 'Field 1',
            name: 'field1',
            allowBlank: false
        },{
            fieldLabel: 'Field 2',
            name: 'field2',
            listeners: {
                specialkey: function(field, e){
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.{@link Ext.EventObject#getKey getKey()} == e.ENTER) {
                        var form = field.ownerCt.getForm();
                        form.submit();
                    }
                }
            }
        }
    ],
    ...
});
             * </code></pre>
             * @param {Ext.form.Field} this
             * @param {Ext.EventObject} e The event object
             */
            'specialkey',
            /**
             * @event change
             * Fires just before the field blurs if the field value has changed.
             * @param {Ext.form.Field} this
             * @param {Mixed} newValue The new value
             * @param {Mixed} oldValue The original value
             */
            'change',
            /**
             * @event invalid
             * Fires after the field has been marked as invalid.
             * @param {Ext.form.Field} this
             * @param {String} msg The validation message
             */
            'invalid',
            /**
             * @event valid
             * Fires after the field has been validated with no errors.
             * @param {Ext.form.Field} this
             */
            'valid'
        );
    },

    /**
     * Returns the {@link Ext.form.Field#name name} or {@link Ext.form.ComboBox#hiddenName hiddenName}
     * attribute of the field if available.
     * @return {String} name The field {@link Ext.form.Field#name name} or {@link Ext.form.ComboBox#hiddenName hiddenName}
     */
    getName : function(){
        return this.rendered && this.el.dom.name ? this.el.dom.name : this.name || this.id || '';
    },

    // private
    onRender : function(ct, position){
        if(!this.el){
            var cfg = this.getAutoCreate();

            if(!cfg.name){
                cfg.name = this.name || this.id;
            }
            if(this.inputType){
                cfg.type = this.inputType;
            }
            
            if (this.qtip) {
                cfg.qtip = this.qtip;
            }
            this.autoEl = cfg;
        }
        Ext.form.Field.superclass.onRender.call(this, ct, position);
        if(this.submitValue === false){
            this.el.dom.removeAttribute('name');
        }
        var type = this.el.dom.type;
        if(type){
            if(type == 'password'){
                type = 'text';
            }
            this.el.addClass('x-form-'+type);
        }
        if(this.readOnly){
            this.setReadOnly(true);
        }
        if(this.tabIndex !== undefined){
            this.el.dom.setAttribute('tabIndex', this.tabIndex);
        }

        this.el.addClass([this.fieldClass, this.cls]);
    },

    // private
    getItemCt : function(){
        return this.itemCt;
    },

    // private
    initValue : function(){
        if(this.value !== undefined){
            this.setValue(this.value);
        }else if(!Ext.isEmpty(this.el.dom.value) && this.el.dom.value != this.emptyText){
            this.setValue(this.el.dom.value);
        }
        /**
         * The original value of the field as configured in the {@link #value} configuration, or
         * as loaded by the last form load operation if the form's {@link Ext.form.BasicForm#trackResetOnLoad trackResetOnLoad}
         * setting is <code>true</code>.
         * @type mixed
         * @property originalValue
         */
        this.originalValue = this.getValue();
    },

    /**
     * <p>Returns true if the value of this Field has been changed from its original value.
     * Will return false if the field is disabled or has not been rendered yet.</p>
     * <p>Note that if the owning {@link Ext.form.BasicForm form} was configured with
     * {@link Ext.form.BasicForm}.{@link Ext.form.BasicForm#trackResetOnLoad trackResetOnLoad}
     * then the <i>original value</i> is updated when the values are loaded by
     * {@link Ext.form.BasicForm}.{@link Ext.form.BasicForm#setValues setValues}.</p>
     * @return {Boolean} True if this field has been changed from its original value (and
     * is not disabled), false otherwise.
     */
    isDirty : function() {
        if(this.disabled || !this.rendered) {
            return false;
        }
        return String(this.getValue()) !== String(this.originalValue);
    },

    /**
     * Sets the read only state of this field.
     * @param {Boolean} readOnly Whether the field should be read only.
     */
    setReadOnly : function(readOnly){
        if(this.rendered){
            this.el.dom.readOnly = readOnly;
        }
        this.readOnly = readOnly;
    },

    // private
    afterRender : function(){
        Ext.form.Field.superclass.afterRender.call(this);
        this.initEvents();
        this.initValue();
    },

    // private
    fireKey : function(e){
        if(e.isSpecialKey()){
            this.fireEvent('specialkey', this, e);
        }
    },

    /**
     * Resets the current field value to the originally loaded value and clears any validation messages.
     * See {@link Ext.form.BasicForm}.{@link Ext.form.BasicForm#trackResetOnLoad trackResetOnLoad}
     */
    reset : function(){
        this.setValue(this.originalValue);
        this.clearInvalid();
    },

    // private
    initEvents : function(){
        this.mon(this.el, Ext.EventManager.useKeydown ? 'keydown' : 'keypress', this.fireKey,  this);
        this.mon(this.el, 'focus', this.onFocus, this);

        // standardise buffer across all browsers + OS-es for consistent event order.
        // (the 10ms buffer for Editors fixes a weird FF/Win editor issue when changing OS window focus)
        this.mon(this.el, 'blur', this.onBlur, this, this.inEditor ? {buffer:10} : null);
    },

    // private
    preFocus: Ext.emptyFn,

    // private
    onFocus : function(){
        this.preFocus();
        if(this.focusClass){
            this.el.addClass(this.focusClass);
        }
        if(!this.hasFocus){
            this.hasFocus = true;
            /**
             * <p>The value that the Field had at the time it was last focused. This is the value that is passed
             * to the {@link #change} event which is fired if the value has been changed when the Field is blurred.</p>
             * <p><b>This will be undefined until the Field has been visited.</b> Compare {@link #originalValue}.</p>
             * @type mixed
             * @property startValue
             */
            this.startValue = this.getValue();
            this.fireEvent('focus', this);
        }
    },

    // private
    beforeBlur : Ext.emptyFn,

    // private
    onBlur : function(){
        this.beforeBlur();
        if(this.focusClass){
            this.el.removeClass(this.focusClass);
        }
        this.hasFocus = false;
        if(this.validationEvent !== false && (this.validateOnBlur || this.validationEvent == 'blur')){
            this.validate();
        }
        var v = this.getValue();
        if(String(v) !== String(this.startValue)){
            this.fireEvent('change', this, v, this.startValue);
        }
        this.fireEvent('blur', this);
        this.postBlur();
    },

    // private
    postBlur : Ext.emptyFn,

    /**
     * Returns whether or not the field value is currently valid by
     * {@link #validateValue validating} the {@link #processValue processed value}
     * of the field. <b>Note</b>: {@link #disabled} fields are ignored.
     * @param {Boolean} preventMark True to disable marking the field invalid
     * @return {Boolean} True if the value is valid, else false
     */
    isValid : function(preventMark){
        if(this.disabled || this.hidden || this.readOnly){
            return true;
        }
        var restore = this.preventMark;
        this.preventMark = preventMark === true;
        var v = this.validateValue(this.processValue(this.getRawValue()));
        this.preventMark = restore;
        return v;
    },

    /**
     * Validates the field value
     * @return {Boolean} True if the value is valid, else false
     */
    validate : function(){
        if(this.disabled || this.hidden || this.readOnly || this.validateValue(this.processValue(this.getRawValue()))){
            this.clearInvalid();
            return true;
        }
        return false;
    },

    /**
     * This method should only be overridden if necessary to prepare raw values
     * for validation (see {@link #validate} and {@link #isValid}).  This method
     * is expected to return the processed value for the field which will
     * be used for validation (see validateValue method).
     * @param {Mixed} value
     */
    processValue : function(value){
        return value;
    },

    /**
     * @private
     * Subclasses should provide the validation implementation by overriding this
     * @param {Mixed} value
     */
    validateValue : function(value){
        return true;
    },

    /**
     * Gets the active error message for this field.
     * @return {String} Returns the active error message on the field, if there is no error, an empty string is returned.
     */
    getActiveError : function(){
        return this.activeError || '';
    },

    /**
     * <p>Display an error message associated with this field, using {@link #msgTarget} to determine how to
     * display the message and applying {@link #invalidClass} to the field's UI element.</p>
     * <p><b>Note</b>: this method does not cause the Field's {@link #validate} method to return <code>false</code>
     * if the value does <i>pass</i> validation. So simply marking a Field as invalid will not prevent
     * submission of forms submitted with the {@link Ext.form.Action.Submit#clientValidation} option set.</p>
     * {@link #isValid invalid}.
     * @param {String} msg (optional) The validation message (defaults to {@link #invalidText})
     */
    markInvalid : function(msg){
        function findParentTab(fromElement) {
            const tabpanel = fromElement.findParentByType('tabpanel');
            let tabPanelEl = null;
            if (tabpanel) {
                if (fromElement?.el) {
                    const tabpanelId = _.find(tabpanel.items.keys, (id)=>{return fromElement.el.up(`#${id}`)}) // Finde das richtige Tab
                    tabPanelEl = tabpanel.getTabEl(tabpanelId);
                } else {
                    const parent = fromElement.findParentBy(function(p){return p.tabEl;});
                    tabPanelEl = parent?.tabEl;
                }
                if (tabPanelEl) {
                    Ext.fly(tabPanelEl).setStyle('background','#ff7870')
                    tabPanelEl.dataset.invalidfield  = fromElement.id;
                }
                findParentTab(tabpanel)
            }
        }
        if (!this.preventMark) {
            findParentTab(this);
        }
        if(!this.rendered || this.preventMark){ // not rendered
            return;
        }
        msg = msg || this.invalidText;

        this.wrap?.addClass(this.invalidClass);
        var mt = this.getMessageHandler();
        if(mt){
            mt.mark(this, msg);
        }else if(this.msgTarget){
            this.el.addClass(this.invalidClass);
            var t = Ext.getDom(this.msgTarget);
            if(t){
                t.innerHTML = msg;
                t.style.display = this.msgDisplay;
            }
        }
        this.activeError = msg;
        this.fireEvent('invalid', this, msg);
    },

    /**
     * Clear any invalid styles/messages for this field
     */
    clearInvalid : function(){
        var mt = this.getMessageHandler();
        if(mt){
            mt.clear(this);

            function findParentTab(fromElement) {
                const tabpanel = fromElement.findParentByType('tabpanel');
                let tabPanelEl = null;
                if (tabpanel) {
                    if (fromElement?.el) {
                        const tabpanelId = _.find(tabpanel.items.keys, (id)=>{return fromElement.el.up(`#${id}`)}) // Finde das richtige Tab
                        tabPanelEl = tabpanel.getTabEl(tabpanelId);
                    } else {
                        const parent = fromElement.findParentBy(function(p){return p.tabEl;});
                        tabPanelEl = parent?.tabEl;
                    }
                    if (tabPanelEl?.dataset.invalidfield == fromElement.id) {
                        Ext.fly(tabPanelEl).setStyle('background', null)
                        tabPanelEl.dataset.invalidfield = '';
                        findParentTab(tabpanel)
                    }
                }
            }
            findParentTab(this);

        }

        if(!this.rendered || this.preventMark){ // not rendered
            return;
        }
        this.wrap?.removeClass(this.invalidClass);
        this.el.removeClass(this.invalidClass);

        if(!mt && this.msgTarget){
            this.el.removeClass(this.invalidClass);
            var t = Ext.getDom(this.msgTarget);
            if(t){
                t.innerHTML = '';
                t.style.display = 'none';
            }
        }
        delete this.activeError;
        this.fireEvent('valid', this);
    },

    // private
    getMessageHandler : function(){
        return Ext.form.MessageTargets[this.msgTarget];
    },

    // private
    getErrorCt : function(){
        return this.el.findParent('.x-form-element', 5, true) || // use form element wrap if available
            this.el.findParent('.x-form-field-wrap', 5, true);   // else direct field wrap
    },

    // Alignment for 'under' target
    alignErrorEl : function(){
        this.errorEl.setWidth(this.getErrorCt().getWidth(true) - 20);
    },

    // Alignment for 'side' target
    alignErrorIcon : function(){
        this.errorIcon.alignTo(this.el, 'tl-tr', [2, 0]);
    },

    /**
     * Returns the raw data value which may or may not be a valid, defined value.  To return a normalized value see {@link #getValue}.
     * @return {Mixed} value The field value
     */
    getRawValue : function(){
        var v = this.rendered ? this.el.getValue() || "" : Ext.value(this.value, '');
        if(v === this.emptyText){
            v = '';
        }
        return v;
    },

    /**
     * Returns the normalized data value (undefined or emptyText will be returned as '').  To return the raw value see {@link #getRawValue}.
     * @return {Mixed} value The field value
     */
    getValue : function(){
        if(!this.rendered) {
            return this.value;
        }
        var v = this.el.getValue();
        if(v === this.emptyText || v === undefined || v === ''){
            v = this.emptyValue;
        }
        return v;
    },

    /**
     * Sets the underlying DOM field's value directly, bypassing validation.  To set the value with validation see {@link #setValue}.
     * @param {Mixed} value The value to set
     * @return {Mixed} value The field value that is set
     */
    setRawValue : function(v){
        return this.rendered && this.el.dom ? (this.el.dom.value = (Ext.isEmpty(v) ? '' : v)) : '';
    },

    /**
     * Sets a data value into the field and validates it.  To set the value directly without validation see {@link #setRawValue}.
     * @param {Mixed} value The value to set
     * @return {Ext.form.Field} this
     */
    setValue : function(v){
        this.value = v;
        if(this.rendered && this.el.dom){
            this.el.dom.value = (Ext.isEmpty(v) ? '' : v);
            this.validate();
        }
        return this;
    },

    setFieldLabel : function(fieldLabel) {
        this.fieldLabel = fieldLabel;
        if(this.rendered){
            const labelEl = this.el.up('.x-form-item').down('label');
            if (labelEl) {
                labelEl.dom.innerHTML = fieldLabel;
            }
        }
    },
    // private, does not work for all fields
    append : function(v){
         this.setValue([this.getValue(), v].join(''));
    }

    /**
     * @cfg {Boolean} autoWidth @hide
     */
    /**
     * @cfg {Boolean} autoHeight @hide
     */

    /**
     * @cfg {String} autoEl @hide
     */
});


Ext.form.MessageTargets = {
    'qtip' : {
        mark: function(field, msg){
            field.el.addClass(field.invalidClass);
            field.el.dom.qtip = msg;
            field.el.dom.qclass = 'x-form-invalid-tip';
            if(Ext.QuickTips){ // fix for floating editors interacting with DND
                Ext.QuickTips.enable();
            }
        },
        clear: function(field){
            field.el?.removeClass(field.invalidClass);
            if (field?.dom?.dom) field.el.dom.qtip = '';
            if (field?.el?.dom?.qclass) field.el.dom.qclass = '';
        }
    },
    'title' : {
        mark: function(field, msg){
            field.el.addClass(field.invalidClass);
            field.el.dom.title = msg;
        },
        clear: function(field){
            field.el.dom.title = '';
        }
    },
    'under' : {
        mark: function(field, msg){
            field.el.addClass(field.invalidClass);
            if(!field.errorEl){
                var elp = field.getErrorCt();
                if(!elp){ // field has no container el
                    field.el.dom.title = msg;
                    return;
                }
                field.errorEl = elp.createChild({cls:'x-form-invalid-msg'});
                field.on('resize', field.alignErrorEl, field);
                field.on('destroy', function(){
                    Ext.destroy(this.errorEl);
                }, field);
            }
            field.alignErrorEl();
            field.errorEl.update(msg);
            Ext.form.Field.msgFx[field.msgFx].show(field.errorEl, field);
        },
        clear: function(field){
            field.el.removeClass(field.invalidClass);
            if(field.errorEl){
                Ext.form.Field.msgFx[field.msgFx].hide(field.errorEl, field);
            }else{
                field.el.dom.title = '';
            }
        }
    },
    'side' : {
        mark: function(field, msg){
            field.el.addClass(field.invalidClass);
            if(!field.errorIcon){
                var elp = field.getErrorCt();
                if(!elp){ // field has no container el
                    field.el.dom.title = msg;
                    return;
                }
                field.errorIcon = elp.createChild({cls:'x-form-invalid-icon'});
                field.on('resize', field.alignErrorIcon, field);
                field.on('destroy', function(){
                    Ext.destroy(this.errorIcon);
                }, field);
            }
            field.alignErrorIcon();
            field.errorIcon.dom.qtip = msg;
            field.errorIcon.dom.qclass = 'x-form-invalid-tip';
            field.errorIcon.show();
        },
        clear: function(field){
            field.el.removeClass(field.invalidClass);
            if(field.errorIcon){
                field.errorIcon.dom.qtip = '';
                field.errorIcon.hide();
            }else{
                field.el.dom.title = '';
            }
        }
    }
};

// anything other than normal should be considered experimental
Ext.form.Field.msgFx = {
    normal : {
        show: function(msgEl, f){
            msgEl.setDisplayed('block');
        },

        hide : function(msgEl, f){
            msgEl.setDisplayed(false).update('');
        }
    },

    slide : {
        show: function(msgEl, f){
            msgEl.slideIn('t', {stopFx:true});
        },

        hide : function(msgEl, f){
            msgEl.slideOut('t', {stopFx:true,useDisplay:true});
        }
    },

    slideRight : {
        show: function(msgEl, f){
            msgEl.fixDisplay();
            msgEl.alignTo(f.el, 'tl-tr');
            msgEl.slideIn('l', {stopFx:true});
        },

        hide : function(msgEl, f){
            msgEl.slideOut('l', {stopFx:true,useDisplay:true});
        }
    }
};
Ext.reg('field', Ext.form.Field);
