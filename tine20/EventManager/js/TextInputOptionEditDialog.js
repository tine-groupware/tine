/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.TextInputOptionEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.TextInputOptionEditDialog.superclass.initComponent.call(this);
    },

    onAfterRecordLoad: function () {
        Tine.EventManager.TextInputOptionEditDialog.superclass.onAfterRecordLoad.call(this);
        this.setMultiLinesListener();
        this.setOnlyNumbersListener();
        this.updateMaxCharFieldVisibility();
    },

    setMultiLinesListener: function () {
        const multiLinesField = this.form.findField('multiple_lines');
        const numbersField = this.form.findField('only_numbers');
        if (multiLinesField) {
            multiLinesField.on('check', function (checkbox, checked) {
                this.toggleMaxCharField(checked);
                numbersField.setValue(false);
            }, this);
        }
    },

    setOnlyNumbersListener: function () {
        const multiLinesField = this.form.findField('multiple_lines');
        const numbersField = this.form.findField('only_numbers');
        if (numbersField) {
            numbersField.on('check', function () {
                multiLinesField.setValue(false);
            }, this);
        }
    },

    updateMaxCharFieldVisibility: function () {
        const multiLinesField = this.form.findField('multiple_lines');
        if (multiLinesField) {
            this.toggleMaxCharField(multiLinesField.getValue());
        }
    },

    toggleMaxCharField: function (visible) {
        const maxCharField = this.form.findField('max_characters');
        if (maxCharField) {
            const container = maxCharField.findParentByType('container') || maxCharField.findParentByType('fieldset');

            if (container) {
                if (visible) {
                    maxCharField.show();
                } else {
                    maxCharField.hide();
                    maxCharField.setValue('');
                }
                container.doLayout();
            }
        }
    }

});
