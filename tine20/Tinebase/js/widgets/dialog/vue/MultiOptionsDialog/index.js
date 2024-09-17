import { Personas } from "../../../../ux/vue/PersonaContainer";

Ext.ns('Tine.widgets.dialog');

Tine.widgets.dialog.MultiOptionsDialog = function(config) {

    Tine.widgets.dialog.MultiOptionsDialog.superclass.constructor.call(this, config);

    this.options = config.options || {};
    this.scope = config.scope || window;
}

Ext.extend(Tine.widgets.dialog.MultiOptionsDialog, Tine.widgets.dialog.ModalDialog, {
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
     * @cfg {Boolean} allowEmpty selection
     */
    allowEmpty: false,
    /**
     * @cfg {Boolean} allowCancel
     */
    allowCancel: false,

    initComponent: async function() {
        // console.log(this)
        this.buttons = this.getButtons()
        this.closable = this.allowCancel
        this.persona = Personas.INFO
        this.supr().initComponent.call(this)

        const { default: MultiOptionsDialog } = await import(/* webpackChunkName: "Tinebase/js/MultiOptionsDialog"*/'./MultiOptionsDialog.vue')

        this.dlgContentComponent = MultiOptionsDialog

        this.vueEventBus.on('close', this.onCancel.bind(this))
        this.vueEventBus.on('ok', this.onOk.bind(this))
        this.vueEventBus.on('cancel', this.onCancel.bind(this))

        this.contentProps = window.vue.reactive({
            questionText: this.questionText,
            options: this.getItems(),
            allowMultiple: this.allowMultiple,
            allowEmpty: this.allowEmpty
        })
        this.postInit()
        this.showModal()
    },

    getButtons: function() {
        return [{
            name: 'cancel',
            text: i18n._('Cancel'),
            hidden: !this.allowCancel,
            iconCls: 'action_cancel',
            eventName: 'cancel'
        }, {
            name: 'ok',
            text: i18n._('Ok'),
            iconCls: 'action_saveAndClose',
            eventName: 'ok'
        }]
    },

    getItems: function() {
        const items = [];
        Ext.each(this.options, function(option) {
            items.push({
                checked: !! option.checked,
                disabled: !! option.disabled,
                fieldLabel: '',
                labelSeparator: '',
                boxLabel: option.text,
                name: this.itemsName,
                inputValue: option.name,
                originalOptObj: option
            });
        }, this);

        return items;
    },

    onOk: function(val){
        // console.log(this, val)
        this.handler.call(this.scope, val)
        this.handleClose()
    },

    onCancel: function(){
        this.handler.call(this.scope, 'cancel');
        this.handleClose()
    },

    handleClose: function() {
        this.destroy()
    },

    _onOk: function() {
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
        }

        if (! option) {
            field.markInvalid(this.invalidText || i18n._('You need to select an option!'));
            return;
        }
        this.handler.call(this.scope, option);
        this.window.close();
    },

    _onCancel: function() {
        this.handler.call(this.scope, 'cancel');
        this.window.close();
    }
})

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
    const d = new Tine.widgets.dialog.MultiOptionsDialog(config)
    return d.window;
};

// eslint-disable-next-line
const jsb2 =
    {
        "text": "index.js",
        "path": "js/widgets/dialog/vue/MultiOptionsDialog/"
    }
