/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.namespace('Tine.GDPR');

Tine.GDPR.DataIntendedPurposeURLField = Ext.extend(Ext.form.TextField, {
    plugins: [{
        ptype: 'ux.fieldclipboardplugin',
    }],
    name: 'url',
    value: 'test',
    maxLength: 100,
    allowBlank: true,
    readOnly: true,
});
Ext.reg('Tine.GDPR.DataIntendedPurposeURLField', Tine.GDPR.DataIntendedPurposeURLField);