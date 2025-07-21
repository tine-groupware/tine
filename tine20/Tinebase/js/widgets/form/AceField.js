/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');
const supportedTypes = ['json', 'xml', 'twig', 'sieve']

const AceField = Ext.extend(Ext.form.Field, {
    defaultAutoCreate: {tag: 'div'},
    mode: 'json',
    fontFamily: 'monospace',
    fontSize: 12,
    
    afterRender() {
        AceField.superclass.afterRender.apply(this, arguments);
        
        import(/* webpackChunkName: "Tinebase/js/ace" */ 'widgets/ace').then(() => {
            this.ed = ace.edit(this.el.id, {
                mode: `ace/mode/${this.mode}`,
                fontFamily: this.fontFamily,
                fontSize: this.fontSize
            });
            
            // prevent value override from empty ed.getValue()
            this.setValue(this.value || '');
        });
    },

    getValue() {
        let value = this.value;
        
        if (this.ed) {
            value = this.ed.getValue();
        }

        if (_.isString(value)) {
            // NOTE: JSON fields do not store a JSON strings - they store js strings!
            try {
                value = JSON.parse(value);
            } catch (e) {}
        }
        return value;
    },
    
    setValue(value) {
        this.supr().setValue.apply(this, arguments);
    
        if (!this.ed) return;
        
        switch (this.mode) {
            case 'json':
                this.ed.setValue(Ext.isString(value) ? value : JSON.stringify(value, undefined, 4), -1);
                break;
            default:
                this.ed.setValue(value);
                break;
        }
        this.ed.clearSelection();
        if (this.readOnly) {
            this.ed.setReadOnly(true)
        }
    },

    setReadOnly: function(readOnly) {
        if (this.ed) {
            this.ed.setReadOnly(readOnly);
        }
        this.readOnly = readOnly;
    }
});
Ext.reg('tw-acefield', AceField);

export default AceField
export {
    supportedTypes
}