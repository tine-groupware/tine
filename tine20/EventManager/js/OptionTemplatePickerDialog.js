/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.OptionTemplatePickerDialog = Ext.extend(Ext.FormPanel, {
    layout: 'form',
    border: false,
    frame: true,
    labelAlign: 'top',
    bodyStyle: 'padding: 5px',
    windowNamePrefix: 'OptionTemplatePickerWindow_',

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('EventManager');

        this.addEvents(
            /**
             * @event apply
             * @param {Object[]} optionsData data of all template options to create
             */
            'apply',
            'cancel'
        );

        // one entry per group, one per ungrouped option
        this.templateStore = new Ext.data.JsonStore({
            idProperty: 'id',
            fields: ['id', 'label', 'isGroup', 'options'],
            data: []
        });

        const app = this.app;
        this.templateCombo = new Ext.form.ComboBox({
            fieldLabel: this.app.i18n._('Option Template'),
            emptyText: this.app.i18n._('Choose a group or option ...'),
            anchor: '100%',
            store: this.templateStore,
            mode: 'local',
            valueField: 'id',
            displayField: 'label',
            triggerAction: 'all',
            forceSelection: true,
            allowBlank: false,
            editable: true,
            tpl: new Ext.XTemplate(
                '<tpl for="."><div class="x-combo-list-item">',
                '<tpl if="isGroup">',
                '<b>{label:htmlEncode}</b> ',
                '<span class="eventmanager-option-template-picker-count">({[this.count(values.options.length)]})</span>',
                '</tpl>',
                '<tpl if="!isGroup">{label:htmlEncode}</tpl>',
                '</div></tpl>',
                {
                    count: n => String.format(app.i18n.n_('{0} option', '{0} options', n), n)
                }
            ),
            listeners: {
                scope: this,
                select: this.onTemplateSelect
            }
        });

        // shows which options will be created
        this.previewPanel = new Ext.BoxComponent({
            hidden: true,
            cls: 'eventmanager-option-template-picker-preview'
        });

        this.items = [this.templateCombo, this.previewPanel];

        this.okButton = new Ext.Button({
            text: i18n._('Ok'),
            iconCls: 'action_saveAndClose',
            variant: 'primary',
            minWidth: 70,
            disabled: true,
            scope: this,
            handler: this.onApply
        });

        this.fbar = ['->', {
            text: i18n._('Cancel'),
            iconCls: 'action_cancel',
            minWidth: 70,
            scope: this,
            handler: this.onCancel
        }, this.okButton];

        Tine.EventManager.OptionTemplatePickerDialog.superclass.initComponent.call(this);
    },

    afterRender: function () {
        Tine.EventManager.OptionTemplatePickerDialog.superclass.afterRender.apply(this, arguments);
        this.loadTemplates();
    },

    loadTemplates: async function () {
        const mask = new Ext.LoadMask(this.getEl(), { msg: this.app.i18n._('Loading option templates...') });
        mask.show();

        try {
            const result = await Tine.EventManager.searchOptions(
                [{ field: 'is_option_template', operator: 'equals', value: true }],
                { sort: 'sorting', dir: 'ASC' }
            );
            this.templateStore.loadData(this.buildEntries(result.results || []));
        } catch (e) {
            Ext.MessageBox.alert(i18n._('Errors'), this.app.i18n._('Could not load option templates.'));
        } finally {
            mask.hide();
        }
    },

    buildEntries: function (options) {
        const entries = [];
        const groups = {};
        const seen = new Set();

        _.sortBy(options, o => Number(o.sorting) || 0).forEach(option => {
            const group = (option.group || '').trim();

            // skip duplicates (same name in the same group)
            const key = group + '\u0000' + option.name_option;
            if (seen.has(key)) return;
            seen.add(key);

            if (group) {
                if (!groups[group]) {
                    groups[group] = { id: 'group:' + group, label: group, isGroup: true, options: [] };
                    entries.push(groups[group]);
                }
                groups[group].options.push(option);
            } else {
                entries.push({ id: 'option:' + option.id, label: option.name_option, isGroup: false, options: [option] });
            }
        });

        return _.sortBy(entries, [e => !e.isGroup, e => e.label.toLowerCase()]);
    },

    onTemplateSelect: function (combo, record) {
        const options = record.get('options') || [];
        const isFileOption = o => o.option_config_class === 'EventManager_Model_FileOption';
        const fileOptions = options.filter(isFileOption);
        const encode = Ext.util.Format.htmlEncode;
        const list = items => '<ul>' + items.map(i => '<li>' + i + '</li>').join('') + '</ul>';

        let html = '';

        if (record.get('isGroup')) {
            html += '<b>' + encode(this.app.i18n._('The following options will be created:')) + '</b>' +
                list(options.map(o => encode(o.name_option) + (isFileOption(o)
                    ? ' <span class="eventmanager-option-template-picker-file-badge">(' + encode(this.app.i18n._('File')) + ')</span>'
                    : '')));
        }

        if (fileOptions.length) {
            html += '<div class="eventmanager-option-template-picker-reminder">' +
                '<b>' + encode(this.app.i18n._('Reminder:')) + '</b> ' +
                encode(this.app.i18n.n_(
                    'This template contains a file option. Please remember to replace or delete the file in the option configuration with the one for this event.',
                    'This template contains file options. Please remember to replace or delete the files in the option configurations with the ones for this event.',
                    fileOptions.length
                )) +
                (record.get('isGroup') ? list(fileOptions.map(o => encode(o.name_option))) : '') +
                '</div>';
        }

        if (html) {
            this.previewPanel.update(html);
            this.previewPanel.show();
        } else {
            this.previewPanel.hide();
        }

        this.okButton.setDisabled(!options.length);
        this.doLayout();
    },

    onApply: function () {
        const record = this.templateStore.getById(this.templateCombo.getValue());
        if (!record) {
            return;
        }
        this.fireEvent('apply', _.cloneDeep(record.get('options')));
        this.window.close();
    },

    onCancel: function () {
        this.fireEvent('cancel');
        this.window.close();
    }
});

Tine.EventManager.OptionTemplatePickerDialog.openWindow = function (config) {
    return Tine.WindowFactory.getWindow({
        width: 500,
        height: 350,
        modal: true,
        title: Tine.Tinebase.appMgr.get('EventManager').i18n._('Create Option from Template'),
        name: Tine.EventManager.OptionTemplatePickerDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.EventManager.OptionTemplatePickerDialog',
        contentPanelConstructorConfig: config
    });
};