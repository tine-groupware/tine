/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ColorField
 * @extends     Ext.form.TriggerField
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * Provides a color input field with a {@link Ext.menu.ColorMenu} dropdown.
 * @constructor Create a new ColorField
 * @param {Object} config
 * @xtype colorfield
 */
Ext.ux.form.ColorField = Ext.extend(Ext.form.TriggerField, {
    listWidth: 150,
    editable: false,
    color: null,

    // private
    initComponent : function(){
        Ext.ux.form.ColorField.superclass.initComponent.call(this);
        
        this.store = new Ext.data.Store({});
        
        this.addEvents(
            /**
             * @event select
             * Fires when a color is selected via the color picker.
             * @param {Ext.form.ColorField} this
             * @param {String} color The color that was selected
             */
            'select'
        );

        this.on('afterrender', this.onAfterRender, this, {buffer: 500});
    },

    onAfterRender: function() {
        // if used as gridEditor
        if (this.inEditor) {
            var editorWrapEl = this.el.up('.x-grid-editor', 5);
            if (editorWrapEl) {
                this.editor = Ext.getCmp(editorWrapEl.id);
                this.editor.allowBlur = true;
                this.onTriggerClick();
            }
        }
    },

    // private
    onDestroy : function(){
        Ext.destroy(this.menu);
        Ext.ux.form.ColorField.superclass.onDestroy.call(this);
    },
    
    //private
    onTriggerClick : function(){
        if(this.disabled){
            return;
        }
        if(this.menu == null){
            this.menu = new Ext.menu.ColorMenu({
                hideOnClick: false,
                value: this.color,
                listeners: {
                    scope: this,
                    select: this.onSelect,
                    pickerShow: this.onPickerShow
                }
            });
        }
        this.onFocus();
        this.menuEvents('on');

        this.menu.show(this.el, "tl-bl?");
    },

    setValue : function(color){
        color = color || '#FFFFFF';
        this.color = color;

        this.el.setStyle('background', color);
        this.el.setStyle('color', color);

        return Ext.ux.form.ColorField.superclass.setValue.call(this, color);
    },
    
    //private
    menuEvents: function(method){
        this.menu[method]('hide', this.onMenuHide, this);
        this.menu[method]('show', this.onMenuShow, this);
    },
    
    //private
    onSelect: function(m, d){
        if (d !== null) {
            d = '#'+d
        }
        this.setValue(d);
        this.fireEvent('select', this, d);

        if (this.inEditor && this.editor) {
            this.editor.completeEdit();
        }

        this.menu.hide();
    },

    //private
    onMenuShow: function() {
        // manage z-index by windowMgr
        this.menu.setActive = Ext.emptyFn;
        this.menu.setZIndex = Ext.emptyFn;
        Ext.WindowMgr.register(this.menu);
        Ext.WindowMgr.bringToFront(this.menu);

        this.onFocus();
    },

    //private
    onPickerShow: function(win) {
        // win is shown in a menu! ok btn is outside the menu and thus
        // menu is hidden -> editing is canceled!
        win.on('close', () => {
            this.colorPickerWindow = null;
        });

        this.colorPickerWindow = win;
    },

    //private
    onMenuHide: function(){
        this.focus(false, 60);
        this.menuEvents('un');

        Ext.WindowMgr.unregister(this.menu);

        if (this.inEditor && this.editor && !this.colorPickerWindow) {
            this.editor.cancelEdit();
        }
    }
});

Ext.reg('colorfield', Ext.ux.form.ColorField);
