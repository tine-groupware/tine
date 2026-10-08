/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Felamimail.sieve');
/**
 * @param config
 * @constructor
 */
Tine.Felamimail.sieve.VacationPanel = function(config) {
    Ext.apply(this, config);
    Tine.Felamimail.sieve.VacationPanel.superclass.constructor.call(this);
};

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.sieve.VacationPanel
 * @extends     Ext.Panel
 */
Ext.extend(Tine.Felamimail.sieve.VacationPanel, Ext.Panel, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,
    /**
     * @private
     */
    appName: 'Felamimail',
    tbarItems: [],
    evalGrants: false,
    readonlyReason: false,
    asAdminModule:false,
    
    layout: 'vbox',
    layoutConfig: {
        align:'stretch'
    },
    
    border: false,
    app: null,

    
    initComponent: function () {
        this.initReasonEditor();
        var generalItems = this.getGeneralItems();

        this.items = [{
                title: this.app.i18n._('General'),
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: generalItems
            },
            {
                title: this.app.i18n._('Advanced'),
                collapsible: true,
                collapseMode: 'mini',
                collapsed: true,
                autoScroll: true,
                border: false,
                frame: true,
                xtype: 'columnform',
                formDefaults: {
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: [[{
                    fieldLabel: this.app.i18n._('Only send to the same sender every X days'),
                    name: 'days',
                    value: 7,
                    xtype: 'numberfield',
                    allowNegative: false,
                    minValue: 1,
                    checkState: () => {
                        this.editDialog.getForm().findField('days')
                            .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
                    },
                }]]
        }];
        
        this.supr().initComponent.call(this);
    },

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     *
     * @private
     */
    updateToolbars: function () {
    },
    
    /**
     * init reason editor
     */
    initReasonEditor: function () {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        var reg = this.app.getRegistry(),
            readonly = reg.get('config').vacationMessageCustomAllowed && reg.get('config').vacationMessageCustomAllowed.value === 0;

        this.reasonEditor = new Ext.form.HtmlEditor({
            fieldLabel: this.app.i18n._('Incoming mails will be answered with this text:'),
            name: 'reason',
            allowBlank: true,
            disabled: true,
            readOnly: readonly,
            flex: 1,
            getDocMarkup: function () {
                var markup = '<html><body></body></html>';
                return markup;
            },
            checkState: () => {
                this.editDialog.getForm().findField('reason')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            },
            plugins: [
                new Ext.ux.form.HtmlEditor.RemoveFormat(),
                new Ext.ux.form.HtmlEditor.SelectImage()
            ],
        });
        this.reasonEditor.on('initialize', async function (a, value) {
            this.reasonEditor.getDoc().addEventListener('keyup', async () => {
                const value = this.reasonEditor.getValue();
                const templateIdField = this.editDialog.getForm().findField('template_id');
                if (this.template && value && this.template !== value) {
                    if (templateIdField && templateIdField.getValue() !== 'custom') {
                        if (this.customReason) {
                            if (await Ext.MessageBox.confirm(
                                this.app.i18n._('Overwrite Custom Template?'),
                                this.app.i18n._('You already have a custom template. Would you like to overwrite it with the new one?')
                            ) !== 'yes') {
                                this.reasonEditor.setValue(this.template);
                                return;
                            }
                        }
                        templateIdField.setValue('custom');
                        this.record.set('template_id', '');
                    }
                }
                if (this.record && (this.record.get('template_id') === '' || this.record.get('template_id') === 'custom')) {
                    this.customReason = value;
                    this.record.set('reason', value);
                }
            });
        }, this);
    },

    /**
     * get items for general tab
     *
     * @return Array
     */
    getGeneralItems: function () {
        let me = this;
        let items = [[{
            fieldLabel: this.app.i18n._('Status'),
            name: 'enabled',
            typeAhead: false,
            triggerAction: 'all',
            lazyRender: true,
            editable: false,
            mode: 'local',
            forceSelection: true,
            value: 0,
            xtype: 'combo',
            store: [
                [0, this.app.i18n._('I am available (vacation message disabled)')],
                [1, this.app.i18n._('I am not available (vacation message enabled)')]
            ],
            listeners: {
                scope: me,
                select: function (combo, record) {
                    this.record.set('enabled', combo.getValue());
                    me.editDialog.onRecordUpdate();
                }
            }
        }]];

        // TODO always add date items? check capabilities here / pass it in registry?
        items = items.concat(this.getDateItems());

        const templates = this.app.getRegistry().get('vacationTemplates');

        // always provide custom option
        templates.results.push({
            path: 'custom',
            name: this.app.i18n._('Custom Template'),
        })

        items = items.concat(this.getTemplateItems(templates));
        items.push([this.reasonEditor]);

        return items;
    },
    
   
    /**
     * @return Array
     */
    getDateItems: function () {
        const commonConfig = this.getCommonFieldConfig();
        return [[Ext.apply({
            fieldLabel: this.app.i18n._('Start Date'),
            emptyText: this.app.i18n._('Set vacation start date...'),
            name: 'start_date',
            xtype: 'datefield',
            checkState: () => {
                this.editDialog.getForm().findField('start_date')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            }
        }, commonConfig), Ext.apply({
            fieldLabel: this.app.i18n._('End Date'),
            emptyText: this.app.i18n._('Set vacation end date...'),
            name: 'end_date',
            xtype: 'datefield',
            checkState: () => {
                this.editDialog.getForm().findField('end_date')
                    .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            }
        }, commonConfig)]
        ];
    },

    getCommonFieldConfig: function () {
        return {
            columnWidth: 0.5
        };
    },

    /**
     * get items for vacation templates
     *
     * @return Array
     *
     * TODO use grid panel for x representatives?
     * @param templates
     */
    getTemplateItems: function (templates) {
        Tine.log.debug('Tine.Felamimail.sieve.VacationPanel::getTemplateItems()');
        Tine.log.debug(templates);

        const commonConfig = this.getCommonFieldConfig();

        return [[
            new Tine.Addressbook.SearchCombo(Ext.apply({
                fieldLabel: this.app.i18n._('Representative #1'),
                emptyText: this.app.i18n._('Choose the first Representative...'),
                blurOnSelect: true,
                name: 'contact_id1',
                selectOnFocus: true,
                forceSelection: false,
                allowBlank: true,
                checkState: () => {
                    this.editDialog.getForm().findField('contact_id1')
                        .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
                }
            }, commonConfig)),
            new Tine.Addressbook.SearchCombo(Ext.apply({
                fieldLabel: this.app.i18n._('Representative #2'),
                emptyText: this.app.i18n._('Choose second Representative...'),
                blurOnSelect: true,
                name: 'contact_id2',
                selectOnFocus: true,
                forceSelection: false,
                allowBlank: true,
                checkState: () => {
                    this.editDialog.getForm().findField('contact_id2')
                        .setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
                }
            }, commonConfig))
        ], [{
            fieldLabel: this.app.i18n._('Message Template'),
            xtype: 'combo',
            mode: 'local',
            listeners: {
                scope: this,
                select: this.updateTwigTemplate
            },
            displayField: 'name',
            name: 'template_id',
            valueField: 'path',
            triggerAction: 'all',
            emptyText: this.app.i18n._('Choose Template...'),
            editable: false,
            store: new Ext.data.JsonStore({
                id: 'timezone',
                root: 'results',
                totalProperty: 'totalcount',
                fields: ['path', 'name'], // TODO use Tine.Filemanager.Model.Node or generic File model?
                data: templates
            }),
            checkState: () => {
                const templateIdField = this.editDialog.getForm().findField('template_id');
                templateIdField.setDisabled(!this.editDialog.getForm().findField('enabled').getValue());
            },
        }]
        ];
    },

    async loadRecord(record) {
        this.record = record ?? this.editDialog.record;
        // mime type is always multipart/alternative
        this.record.set('mime', 'multipart/alternative');

        if (this.account.get('signature')) {
            this.record.set('signature', this.account.get('signature'));
        }

        const form = this.editDialog.getForm();
        const contactIds = this.record.get('contact_ids');
        ['contact_id1', 'contact_id2'].forEach((field, idx) => {
            if (form.findField(field) && contactIds?.[idx]) {
                form.findField(field).setValue(contactIds[idx]);
            }
        }, this);

        this.customReason = this.record.get('reason');
        form.findField('template_id').setValue(this.record.get('template_id') || 'custom');

        await this.updateTwigTemplate();
    },

    onRecordUpdate() {
        const contactIds = ['contact_id1', 'contact_id2']
            .map(fieldName => this.editDialog.getForm().findField(fieldName))
            .filter(field => field && field.getValue())
            .map(field => field.getValue());

        this.record.set('contact_ids', contactIds);

        ['enabled', 'start_date', 'end_date'].forEach((fieldName) => {
            const field = this.editDialog.getForm().findField(fieldName);
            this.record.set(fieldName, field.getValue());
        });

        this.record.set('reason', this.customReason);
    },

    updateTwigTemplate: async function () {
        try {
            this.record = this.record ?? this.editDialog.record;

            const templateIdField = this.editDialog.getForm().findField('template_id');
            const oldTemplateId = this.record.get('template_id');
            const templateId = templateIdField.getValue();
            this.record.set('template_id', templateId);

            if (templateId === 'custom') {
                this.reasonEditor.setValue(this.customReason);
            } else if (templateId && (!this.template || oldTemplateId !== templateId)) {
                if (!this.editDialog.loadMask) {
                    this.editDialog.loadMask = new Ext.LoadMask(this.editDialog.getEl(), {msg: i18n._('Loading Vacation Template...')});
                }
                this.editDialog.loadMask.show();

                try {
                    const response = await Tine.Felamimail.getVacationMessage(this.record.data);
                    this.template = null; // reset so setValue isn't treated as a user edit
                    this.reasonEditor.setValue(response.message);
                    this.template = this.reasonEditor.getValue();
                } finally {
                    this.editDialog.hideLoadMask();
                }
            }
        } catch (exception) {
            Tine.Felamimail.handleRequestException(exception);
        }
    }
});

Ext.reg('Tine.Felamimail.sieve.VacationPanel', Tine.Felamimail.sieve.VacationPanel);
