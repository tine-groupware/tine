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

    const fieldManager = _.bind(
        Tine.widgets.form.FieldManager.get,
        Tine.widgets.form.FieldManager,
        app.appName,
        config.recordClass,
        _,
        Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID
    );

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
        config.customRenderers[name] =  (value, metaData, record) => {
            const renderer = Tine.widgets.grid.RendererManager.get(app.appName, config.recordClass, field.fieldName, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
            const isPreferred = isPreferredField(field.fieldName);

            if (isPreferred) {
                const el = document.createElement('div');
                el.className = 'tinebase-property-field';

                const renderedEl =  document.createElement('div');
                renderedEl.innerHTML = renderer(value, metaData, record);
                const preferredIconEl =  document.createElement('div');
                preferredIconEl.className = `tine-combo-icon renderer_PreferredIcon`;
                preferredIconEl.setAttribute('ext:qtip',  app.i18n._('Preferred'));
                el.append(renderedEl, preferredIconEl);
                return el.outerHTML;
            }

            return renderer(value, metaData, record);
        }
    });

    // config.isValid = function() {
    //     let valid = true;
    //     const values = this.getSource();
    //     _.forEach(config.customEditors, (gridEditor, name) => {
    //         const field = gridEditor.field;
    //         field.setValue(values[name]);
    //         if (!field.validate()) {
    //             valid = false;
    //             // mark row invalid somehow!
    //             return false;
    //         }
    //     });
    //     return valid;
    // };
    //
    // /* needed for isFormField cycle */
    // config.isFormField = true;
    // config.markInvalid = Ext.form.Field.prototype.markInvalid;
    // config.clearInvalid = Ext.form.Field.prototype.clearInvalid;
    // config.getMessageHandler = Ext.form.Field.prototype.getMessageHandler;
    // config.getName = Ext.form.Field.prototype.getName;
    // config.getValue = Ext.emptyFn;
    // config.setValue = Ext.emptyFn;
    // config.validate = function() { return this.isValid(); };

    const isPreferredField = (fieldName) => {
        let result = false;
        const editDialog = propertyGrid.findParentBy(function (c) { return c instanceof Tine.widgets.dialog.EditDialog});
        ['preferred_email', 'preferred_address'].forEach((preferred_field) => {
            if (editDialog.record.get(preferred_field) === fieldName) {
                result = true;
            }
        })
        return result;
    }

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
        const editDialog = propertyGrid.findParentBy(function (c) {
            return c instanceof Tine.widgets.dialog.EditDialog
        });
        editDialog.on('load', onRecordLoad);
        editDialog.on('recordUpdate', onRecordUpdate);
        propertyGrid.on('cellclick', onClick);

        //TODO: support propertyGrid im multiple edit mode ?
        if (editDialog.useMultiple) {
            propertyGrid.setDisabled(true);
        }

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

    const onClick = (e, row, c, d) => {
        const record = propertyGrid.store.getAt(row);
        const fieldName = record.data.name.replace(/^\d{3}_/, '');
        const value = record.data.value;
        const isPreferred = isPreferredField(fieldName);
        const emailFields = Tine.Addressbook.Model.EmailAddress.prototype.getEmailFields().map((f) => f.fieldName);
        if (value && c === 0 && emailFields.includes(fieldName)) {
            const ctxMenu = new Ext.menu.Menu({
                items: [new Ext.Action({
                    text: app.i18n._('Set as preferred E-Mail'),
                    iconCls: isPreferred ? 'action_enable' : '',
                    handler: async (item) => {
                        const editDialog = propertyGrid.findParentBy(function (c) { return c instanceof Tine.widgets.dialog.EditDialog});
                        if (fieldName.includes('email')) {
                            editDialog.record.set('preferred_email', fieldName);
                            propertyGrid.getView().refresh();
                        }
                    },
                })]
            });
            ctxMenu.showAt(d.getXY());
        }
    };

    return propertyGrid;
}
