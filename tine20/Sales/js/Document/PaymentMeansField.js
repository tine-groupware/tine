/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const PaymentMeansField = Ext.extend(Tine.Tinebase.widgets.form.VMultiPicker, {
    name: 'payment_means',
    recordClass: 'Sales.PaymentMeans',

    // legacy invoice
    recipientField: 'recipient_id',

    // get from debitor
    searchComboConfig: {
        multiPicker: this,
        mode: 'local',
        initList: function() {
            Tine.Tinebase.widgets.form.RecordPickerComboBox.prototype.initList.apply(this, arguments);

            const recipient = this.editDialog.getForm().findField(this.recipientField)?.selectedRecord;
            const means = _.get(recipient, 'data.debitor_id.payment_means');

            if (!means) return;

            this.onBeforeLoad(this.store, {});
            this.store.loadData(means);
            this.store.fireEvent('datachanged', this.store);
            this.onLoad();

        }
    },

    initComponent () {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.fieldLabel =  this.app.i18n._('Payment Means');
        this.searchComboConfig.recipientField = this.recipientField;
        this.supr().initComponent.call(this)
    },

    onSelect: function(combo, record){
        if (this.editDialog.recordClass.getMeta('modelName').match(/Invoice/)) {
            // NOTE: xRechnung limits payment means see "PAYMENT INSTRUCTIONS" BG-16 in EN 16931
            this.reset()
            record.set('default', true)
        }
        return this.supr().onSelect.call(this, combo, record)
    },

    afterRender () {
        this.supr().afterRender.call(this)
        this.editDialog.getForm().findField(this.recipientField)?.on('select', (combo, record, index) => {
            let means = _.get(record, 'data.debitor_id.payment_means', [])
            if (this.editDialog.recordClass.getMeta('modelName').match(/Invoice/)) {
                // NOTE: xRechnung limits payment means see "PAYMENT INSTRUCTIONS" BG-16 in EN 16931
                means = _.filter(means, { default: true })
            }
            this.setValue(means)
        })
    }
})

export default PaymentMeansField
