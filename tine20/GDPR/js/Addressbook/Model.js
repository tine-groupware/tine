/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.GDPR.Addressbook');

// NOTE server announces 'records' as refIdField, but we need intendedPurpose to search in the right records
//      is it \Tinebase_ModelConfiguration_Const::IS_METADATA_MODEL_FOR in fact?
Tine.widgets.grid.FilterRegistry.register('Addressbook', 'Contact', {
    filtertype: 'foreignrecord',
    foreignRecordClass: 'GDPR.DataIntendedPurposeRecord',
    foreignRefIdField: 'intendedPurpose',
    linkType: 'foreignId',
    filterName: 'GDPRDataIntendedPurposeFilter',
    independentRecords: true,
    multipleForeignRecords: true,
    ownField: 'GDPR_DataIntendedPurposeRecord',
    // i18n._('GDPR Purpose of processing')
    label: 'GDPR Purpose of processing'
});

