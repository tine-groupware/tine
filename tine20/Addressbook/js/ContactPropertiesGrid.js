/*
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 *
 * @param config {Object}
 *  config.fields {Collection} of recordClass fields
 *  config.recordClass {Tine.Tinebase.data.Record}
 * @returns {Ext.grid.PropertyGrid}
 */
export default (config) => {
    const app = Tine.Tinebase.appMgr.get(config.recordClass.getMeta('appName'));

    let editDialog;

    const fieldManager = _.bind(
        Tine.widgets.form.FieldManager.get,
        Tine.widgets.form.FieldManager,
        app.appName,
        config.recordClass,
        _,
        Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID
    );

    const multiRenderer = (renderer, value, meta, record) => {
        if (editDialog.useMultiple) {
            const fieldName = record.get('name').replace(/^\d{3}_/, '');
            const multiData = _.find(editDialog.interRecord.multiData, { name: fieldName });
            if (multiData?.equalValues === false) {
                meta.css += 'tinebase-editmultipledialog-noneedit ';
            }
        }
        return renderer(value, meta, record);
    }

    config.propertyNames = {};
    config.customEditors = {};
    config.customRenderers = {};
    config.fields.forEach((field, idx) => {
        if (field?.specialType === 'Addressbook_Model_ContactProperties_Url') {
            field.type = 'url';
        }
        
        const editor = fieldManager(field.fieldName, {
            selectOnFocus:true,
            expandOnFocus:true,
            cls: `x-grid-editor-${field.fieldName}`,
            type: field.type,
        });
        const name = `${_.padStart( String(idx), 3, '0')}_${field.fieldName}`;
        config.propertyNames[name] = editor.fieldLabel;
        config.customEditors[name] = new Ext.grid.GridEditor(Ext.create(editor));
        config.customRenderers[name] = _.wrap(Tine.widgets.grid.RendererManager.get(app.appName, config.recordClass, field.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL), multiRenderer);
    });

    const propertyGrid = new Ext.grid.PropertyGrid(Object.assign({
        border: false,
        hideHeaders: true,
        storeConfig: {
            isEditableValue: () => {
                return true
            },
        },
    }, config));

    propertyGrid.afterIsRendered().then(() => {
        editDialog = propertyGrid.findParentBy(function (c) {
            return c instanceof Tine.widgets.dialog.EditDialog
        });
        editDialog.on('load', onRecordLoad);
        editDialog.on('recordUpdate', onRecordUpdate);

        editDialog.on('multipleRecordUpdate', onMultipleRecordUpdate);

        // NOTE: in case we are rendered after record was load
        onRecordLoad(editDialog, editDialog.record);
    });

    const onRecordLoad = (editDialog, record) => {
        const recordGrants = _.get(record, record.constructor.getMeta('grantsPath'));
        propertyGrid.setSource(config.fields.reduce((source, field, idx) => {
            const requiredGrants = field.requiredGrants; // NOTE: at the moment this means rw!
            if (! requiredGrants 
                || recordGrants?.adminGrant 
                || requiredGrants?.some((requiredGrant) => { return recordGrants?.[requiredGrant] })
            ) {
                const name = `${_.padStart(String(idx), 3, '0')}_${field.fieldName}`;
                source[name] = record.get(field.fieldName);
            }
            return source;
        }, {}));

    };

    const onRecordUpdate = (editDialog, record) => {
        _.forEach(propertyGrid.getSource(), (value, name) => {
            const fieldName = name.replace(/^\d{3}_/, '');
            record.set(fieldName, value);
        });
    };

    const onMultipleRecordUpdate = (p, changes) => {
        // if currentValue differs from startValue add to changes
        _.forEach(propertyGrid.getSource(), (value, name) => {
            const fieldName = name.replace(/^\d{3}_/, '');
            const multiData = _.find(p.interRecord.multiData, { name: fieldName });
            if (multiData && multiData.startValue != value) {
                changes.push({name: fieldName, value});
            }
        });
    };

    return propertyGrid;
}
