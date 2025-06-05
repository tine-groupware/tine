/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import DocumentMixin from '../Document/AbstractMixin'

const AbstractMixin = {
    parentOnly() {
        console.error('parentOnly');
    },
    parent() {
        console.error('parent');
    },

    setFromProduct(product, lang, document) {
        const productClass = Tine.Sales.Model.Product;
        const productData = product.data || product;
        const vatProcedure = _.get(document, 'vat_procedure', 'standard');

        if (!lang) {
            const languagesAvailableDef = _.get(productClass.getModelConfiguration(), 'languagesAvailable')
            const keyFieldDef = Tine.Tinebase.widgets.keyfield.getDefinition(_.get(languagesAvailableDef, 'config.appName', productClass.getMeta('appName')), languagesAvailableDef.name)
            lang = keyFieldDef.default
        }
        const genericFieldNames = Tine.Tinebase.Model.modlogFields.reduce((a, f) => {return a.concat(f.name);}, [this.constructor.getMeta('idProperty')]);
        Object.keys(productData).forEach((fieldName) => {
            if (genericFieldNames.indexOf(fieldName) < 0 && this.constructor.hasField(fieldName)) {
                const value = _.get(productClass.getField(fieldName), 'fieldDefinition.config.specialType') === 'localizedString' ?
                    _.find(productData[fieldName], { language: lang })?.text || _.get(productData, `${fieldName}[0].text`) : productData[fieldName];
                this.set(fieldName, value);
            }
        });

        this.set('type', 'PRODUCT');
        this.set('title', _.find(productData['name'], { language: lang })?.text || _.get(productData, 'name[0].text'));
        this.set('product_id', productData);
        this.set('quantity', 1);
        this.set('position_discount_type', 'SUM');
        this.set('position_discount_percentage', 0);
        this.set('position_discount_sum', 0);
        this.set('unit_price_type', productData.salesprice_type || 'net');
        this.set('unit_price', productData.salesprice||0);
        this.set('sales_tax_rate', productData.salestaxrate || 0)
        this.set('grouping', productData.default_grouping);
        this.set('sorting', productData.default_sorting);

        if (vatProcedure !== 'standard' && this.get('unit_price_type') === 'gross') {
            this.computePrice();
            this.set('unit_price', this.get('unit_price') - (this.get('sales_tax') || 0))
            this.set('unit_price_type', 'net')
            this.set('sales_tax_rate', 0)
        }

        this.computePrice();
        this.commit();
    },

    clearPrice() {
        this.set('unit_price_type', null);
        this.set('unit_price', null);
        this.set('position_price', null);
        this.set('position_discount_type', null);
        this.set('position_discount_sum', null);
        this.set('position_discount_percentage', null);
        this.set('net_price', null);
        this.set('sales_tax_rate', null);
        this.set('sales_tax', null);
        this.set('gross_price', null);
    },

    computePrice() {
        if (this.isProductType()) {
            const price = this.constructor.toFixed(this.get('unit_price') * this.get('quantity'));
            this.set('position_price', price);
            const discount = this.get('position_discount_type') === 'SUM' ? this.get('position_discount_sum') :
                (price / 100 * this.get('position_discount_percentage'));
            const total = price - discount;
            let tax = 0
            const taxRate = this.get('sales_tax_rate') || 0;
            if (this.get('unit_price_type') === 'gross') {
                this.set('gross_price', this.constructor.toFixed(total));
                tax = total - total * 100/(100+taxRate);
                this.set('net_price', this.constructor.toFixed(total - tax));
            } else {
                this.set('net_price', this.constructor.toFixed(total));
                tax = total / 100 * taxRate;
                this.set('gross_price', this.constructor.toFixed(total + tax));
            }
            this.set('sales_tax', this.constructor.toFixed(tax));
        }
    },

    isProductType() {
        return ['PRODUCT', 'ALTERNATIVE', 'OPTIONAL'].indexOf(this.get('type')) >= 0;
    },

    statics: {
        toFixed: DocumentMixin.statics.toFixed,
        parentOnly() {
            console.error('parentOnlyStatic');
        },
        parent() {
            console.error('parentStatic');
        },
    }
}

export default AbstractMixin
