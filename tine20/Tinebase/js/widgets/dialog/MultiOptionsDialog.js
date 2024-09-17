import {PersonaContainer, Personas} from "../../ux/vue/PersonaContainer";

Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.MultiOptionsDialog = function(config) {
    
    Tine.widgets.dialog.MultiOptionsDialog.superclass.constructor.call(this, config);
    
    this.options = config.options || {};
    this.scope = config.scope || window;
};

/**
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.MultiOptionsDialog
 * @extends     Ext.FormPanel
 */
Ext.extend(Tine.widgets.dialog.MultiOptionsDialog, Ext.FormPanel, {
    /**
     * @cfg {Array} options
     * @see {Ext.fom.CheckBoxGroup}
     */   
    options: null,
    /**
     * @cfg {Object} scope
     */
    scope: null,
    /**
     * @cfg {String} questionText defaults to i18n._('What would you like to do?')
     */
    questionText: null,
    /**
     * @cfg {String} invalidText defaults to i18n._('You need to select an option!')
     */
    invalidText: null,
    /**
     * @cfg {Function} handler
     */
    handler: Ext.emptyFn,
    /**
     * @cfg {Boolean} allowMultiple options at once
     */
    allowMultiple: false,
    /**
     * @cfg {Boolean} allowCancel
     */
    allowCancel: false,
    
    windowNamePrefix: 'MultiOptionsDialog',
    bodyStyle:'padding:5px',
    layout: 'fit',
    border: false,
    cls: 'tw-editdialog',
    anchor:'100% 100%',
    deferredRender: false,
    buttonAlign: null,
    bufferResize: 500,

    persona: Personas.QUESTION_OPTION,
    
    initComponent: function() {
        // init buttons and tbar
        this.initButtons();

        this.itemsName = this.id + '-radioItems';
        
        // get items for this dialog
        this.items = [{
            layout: 'hbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [new PersonaContainer({
                persona: this.persona,
                flex: 0,
            }), {
                border: false,
                layout: 'fit',
                flex: 1,
                autoHeight: true,
                autoScroll: true,
                items: [{
                    xtype: 'v-alert',
                    label: this.questionText || i18n._('What would you like to do?')
                }, {
                    xtype: this.allowMultiple ? 'checkboxgroup' : 'radiogroup',
                    hideLabel: true,
                    itemCls: 'x-check-group-alt',
                    columns: 1,
                    name: 'optionGroup',
                    items: this.getItems()
                }]
            }]
        }];
        
        this.afterIsRendered().then(() => {
            this.el.child('input').focus(100);
            this.mon(this.el, 'dblclick', (e) => {
                window.getSelection().removeAllRanges();
                _.defer(_.bind(this.onOk, this));
            });
            this.mon(this.el, 'keydown', (e) => {
                if (e.getKey() === e.ENTER) {
                    window.getSelection().removeAllRanges();
                    _.defer(_.bind(this.onOk, this));
                }
            });
            this.mon(this.el, 'keydown', (e) => {
                if (e.getKey() === e.TAB) {
                    if (this.allowMultiple) {
                        // TODO not supported yet
                    } else {
                        const idx = _.indexOf(this.options, _.find(this.options,
                            {name: this.getForm().findField('optionGroup').getValue()?.inputValue}));
                        const selectIdx = idx === this.options.length - 1 ? 0 : idx + 1;
                        this.getForm().findField('optionGroup').setValue(this.options[selectIdx].name);
                        this.el.child('input').focus(100);
                    }
                }
            });
        });
        this.supr().initComponent.call(this);
    },
    
    /**
     * init buttons
     */
    initButtons: function() {
        this.fbar = ['->', {
            xtype: 'button',
            text: i18n._('Cancel'),
            minWidth: 70,
            scope: this,
            hidden: !this.allowCancel,
            handler: this.onCancel,
            iconCls: 'action_cancel'
        }, {
            xtype: 'button',
            text: i18n._('Ok'),
            minWidth: 70,
            scope: this,
            handler: this.onOk,
            iconCls: 'action_saveAndClose'
        }];
    },
    
    getItems: function() {
        var items = [];
        Ext.each(this.options, function(option) {
            items.push({
                checked: !! option.checked,
                disabled: !! option.disabled,
                fieldLabel: '',
                labelSeparator: '',
                boxLabel: option.text,
                name: this.itemsName,
                inputValue: option.name
            });
        }, this);
        
        return items;
    },
    
    onOk: function() {
        const field = this.getForm().findField('optionGroup');
        const selected = field.getValue();
        let option = null;
        if (this.allowMultiple) {
            const values = _.map(selected, 'initialConfig.inputValue');
            option = selected.length ? this.options.filter((option) => {
                return values.indexOf(option.name) >= 0;
            }) : null;
        } else {
            option = selected ? selected.getGroupValue() : null;
            option = _.find(this.options, {name: option})?.value || option;
        }

        if (! option) {
            field.markInvalid(this.invalidText || i18n._('You need to select an option!'));
            return;
        }
        this.handler.call(this.scope, option);
        this.window.close();
    },
    
    onCancel: function() {
        this.handler.call(this.scope, 'cancel');
        this.window.close();
    }
});

Tine.widgets.dialog.MultiOptionsDialog.getOption = function(config) {
    return new Promise((resolve, reject) => {
        const dlg = Tine.widgets.dialog.MultiOptionsDialog.openWindow(_.assign(config, {handler: (option) => {
            if (option === 'cancel') {
                reject(new Error('USERABORT'));
            }
            resolve(option);
        }}));
    });
} 
/**
 * grants dialog popup / window
 */
Tine.widgets.dialog.MultiOptionsDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: config.width || 400,
        height: Math.max(config.height || 150, 200),
        closable: false,
        name: Tine.widgets.dialog.MultiOptionsDialog.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.widgets.dialog.MultiOptionsDialog',
        contentPanelConstructorConfig: config,
        modal: true
    });
    return window;
};
