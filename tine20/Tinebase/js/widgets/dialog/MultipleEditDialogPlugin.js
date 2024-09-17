/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.widgets.editDialog');

/**
 * @namespace   Tine.widgets.editDialog
 * @class       Tine.widgets.dialog.MultipleEditDialogPlugin
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @plugin for Tine.widgets.editDialog
 */
Tine.widgets.dialog.MultipleEditDialogPlugin = function(config) {
    Ext.apply(this, config);
    // uhh, quick hack to circumvent large refactoring
    // -> limitation: no two multiedit plugins shown at once (seems acceptable)
    Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.skipItems = [];
};

Tine.widgets.dialog.MultipleEditDialogPlugin.prototype = {
    /**
     * the application calling this plugin
     */
    app : null,
    /**
     * the editDialog the plugin is applied to
     */
    editDialog : null,
    /**
     * the selected records 
     */
    selectedRecords: null,
    /**
     * the selections' filter
     */
    selectionFilter: null,
    /**
     * this record is created on the fly and never saved as is, only changes to this record are sent to the backend
     */
    interRecord: null,   
    /**
     * a shorthand for this.editDialog.getForm() 
     */
    form : null,
    /**
     * Array which holds the fieldConfigs which can be handled by this plugin
     * Array of Objects: { key: <the raw key>, type: <custom/default>', formField: <the corresponding form field>, recordKey: <used for getters/setters of the record>}
     * @type Array
     */
    handleFields: null,
    /**
     * if records are defined by a filterselection
     * @type 
     */
    isFilterSelect: null,
    
    /**
     * holds the fields which have been changed
     * @type {array} changedFields
     */
    changedFields: null,
    
    /**
     * count of all handled records
     * @type {Integer} totalRecordCount
     */
    totalRecordCount: null,
    
    /**
     * component registry to skip on multiple edit
     * @type {Array} skipItems
     */
    skipItems: [],
    
    // private
    isInitialised: false,
    
    /**
     * initializes the plugin
     */    
    init : function(ed) {
        if (this.isInitialised) {
            return;
        }
        this.isInitialised = true;
        ed.mode = 'local';
        ed.evalGrants = false;
        ed.onRecordLoad = Ext.emptyFn;
        ed.onApplyChanges = ed.onApplyChanges.createInterceptor(this.onRecordUpdate, this);
        ed.onRecordUpdate = Ext.emptyFn;
        ed.initRecord = Ext.emptyFn;
        ed.useMultiple = true;
        ed.interRecord = this.interRecord = new ed.recordClass({});
        ed.loadRecord = true;
        ed.checkUnsavedChanges = false;
        
        this.app = Tine.Tinebase.appMgr.get(ed.app);
        this.handleFields = [];

        if (ed.action_saveAndClose) {
            ed.action_saveAndClose.enable();
        }
        ed.on('fieldchange', this.findChangedFields, this);
        
        if (ed.hasOwnProperty('isMultipleValid') && Ext.isFunction(ed.isMultipleValid)) {
            ed.isValid = ed.isMultipleValid;
        } else {
            ed.isValid = function() {return true};
        }
        
        this.editDialog = ed;
        
        // disable clearer of relation picker combos, otherwise the clearer will be shown before initializing
        if (this.editDialog.relationPickers) {
            Ext.each(this.editDialog.relationPickers, function(ff) {
                ff.combo.blurOnSelect = true;
                ff.combo.disableClearer = true;
            });
        }

        ed.on('render', async () => {
            await ed.showLoadMask();
            this.onAfterRender();
        }, this);
    },
    
    /**
     * method to register components which are disabled on multiple edit
     * call this from the component to disable by: Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this);
     * @param {} item
     */
    registerSkipItem: function(item) {
        this.skipItems.push(item);
    },
    
    /**
     * handle fields for multiedit
     */
    onAfterRender : function() {
        // skip registered form items
        Ext.each(this.skipItems, function(item) {
            item.setDisabled(true);
        }, this);

        const keys = [];

        // get fields to handle
        Ext.each(this.editDialog.recordClass.getFieldDefinitions(), function(item) {
            const key = item.name;
            const field = this.editDialog.getForm().findField(item.name);
            if (!field) {
                if (item?.fieldDefinition?.specialType) {
                    Tine.log.info('Field found for contact property"' + key + '" with specialType : ' + item?.fieldDefinition.specialType);
                    keys.push({key: key, type: 'default', formField: null, recordKey: key, specialType: item?.fieldDefinition.specialType});
                    return true;
                } else {
                    Tine.log.info('No field found for property "' + key + '". Ignoring...');
                    return true;
                }
            }
            Tine.log.info('Field found for property "' + key + '".');
            keys.push({key: key, type: 'default', formField: field, recordKey: key});
        }, this);
        
        // get customfields to handle
        const cfConfigs = Tine.widgets.customfields.ConfigManager.getConfigs(this.app, this.editDialog.recordClass);
        if (cfConfigs) {
            Ext.each(cfConfigs, function (config) {
                const field = this.editDialog.getForm().findField('customfield_' + config.data.name);
                if (!field) {
                    Tine.log.info('No customfield found for property "' + config.data.name + '". Ignoring...');
                    return true;
                }
                Tine.log.info('Customfield found for property "' + config.data.name + '".');
                keys.push({key: config.data.name, type: 'custom', formField: field, recordKey: '#' + config.data.name});
            }, this);
        }
        
        // get relationpickerfields to handle
        if (this.editDialog.relationPickers) {
            Ext.each(this.editDialog.relationPickers, function(picker) {
                Tine.log.info('RelationPicker found. Using "' + '%' + picker.relationType + '-' + picker.fullModelName + '" as key.');
                keys.push({key: picker.relationType + '-' + picker.fullModelName, type: 'relation', formField: picker.combo, recordKey: '%' + picker.relationType + '-' + picker.fullModelName});
            });
        }
        
        Ext.each(keys, function(field) {
            const ff = field.formField;
            // disable fields which cannot be handled atm.
            if (!ff) {
                Tine.log.debug('Disabling key "' + field?.recordKey + '" with specialType : ' + field?.specialType + '. Cannot be handled atm.');
                return true;
            }
            if ((!(ff.isXType('textfield'))) && (!(ff.isXType('checkbox'))) && (!(ff.isXType('datetimefield'))) || ff.multiEditable === false) {
                Tine.log.debug('Disabling field for key "' + field.recordKey + '". Cannot be handled atm.');
                ff.setDisabled(true);
                return true;
            }
            // remove empty text
            if (ff.hasOwnProperty('emptyText')) {
                ff.emptyText = '';
            }
            
            // default event for init
            ff.startEvents = ['focus'];
            // default trigger event
            ff.triggerEvents = ['blur'];
            
            // special events for special field types
            if (ff.isXType('durationspinner')) {
                ff.emptyOnZero = true;
                ff.startEvents = ['focus', 'spin'];
                ff.triggerEvents = ['spin', 'blur'];
            } else if (ff.isXType('tinerelationpickercombo')) {
                // leave as it is, otherwise tinerecordpickercombobox would match
            } else if (ff.isXType('tinerecordpickercombobox')) {
                ff.startEvents = ['focus', 'expand'];
                ff.triggerEvents = ['select', 'blur'];
            } else if (ff.isXType('checkbox')) {
                ff.startEvents = ['check'];
                ff.triggerEvents = ['check', 'blur'];
            } else if (ff.isXType('tinewidgetscontainerselectcombo')) {
                ff.startEvents = ['focus', 'expand'];
                ff.triggerEvents = ['select', 'blur'];
            }
            // add field to handlefields array
            this.handleFields.push(field);
            
            Ext.each(ff.startEvents, function(initEvent) {
                ff.on(initEvent, this.onInitField, this, [ff]);
            }, this);

        }, this);
        
        this.editDialog.record = this.interRecord;
        this.onRecordLoad();
    },
    
    /**
     * is called when an init handler for this field is called
     * @param {Object} ff the form field
     */
    onInitField: function(ff) {
        Tine.log.info('Init handler called for field "' + ff.name + '".');
        
        // if already initialized, dont't repeat setting start values and inserting button
        if (! ff.multipleInitialized) {
            if (! ff.isXType('checkbox')) {
                this.createMultiButton(ff);
            }
            
            var match = ff.hasOwnProperty('name') ? ff.name.match(/customfield_(.*)/) : null;
            if (match && match.length == 2) {
                var cf = this.interRecord.get('customfields');
                if (cf && cf.hasOwnProperty(match[1])) {
                    var startValue = cf[match[1]];
                } else {
                    var startValue = '';
                }
            } else {
                var startValue = this.interRecord.get(ff.name);
            }
            
            if (ff.isXType('addressbookcontactpicker') && ff.useAccountRecord) {
                ff.startRecord = startValue ? startValue : null;
                startValue = startValue['accountId'] ? startValue['accountId'] : null;
            } else if (ff.isXType('timefield')) {
                if (startValue.length) {
                    startValue = startValue.replace(/\d{4}-\d{2}-\d{2}\s/, '').replace(/:\d{2}$/, '');
                }
            } else if (ff.isXType('trigger') && ff.triggers) {
                ff.on('blur', this.hideTriggerClearer);
                ff.on('focus', this.hideTriggerClearer);
                ff.on('select', this.hideTriggerClearer);
            } else if (Ext.isObject(startValue)) {
                startValue = startValue[ff.recordClass.getMeta('idProperty')];
                ff.startRecord = new ff.recordClass(startValue);
            } else if (ff.isXType('checkbox')) {
                var startValue = (startValue == 1) ? true : false;
                ff.setValue(startValue);
            } else if (ff.isXType('tinewidgetscontainerselectcombo')) {
                startValue = ff.getValue()
                ff.on('blur', this.onTriggerField, ff)
                ff.on('select', this.onTriggerField, ff)
            }
            ff.startingValue = (startValue == undefined || startValue == null) ? '' : startValue;
            
            Tine.log.info('Setting start value to "' + startValue + '".');
            
            Ext.each(ff.triggerEvents, function(triggerEvent) {
                ff.on(triggerEvent, this.onTriggerField, ff);
                ff.on('fieldchange', function() {
                    this.editDialog.fireEvent('fieldchange');
                }, this);
            }, this);
    
            ff.multipleInitialized = true;

            if (ff.isXType('durationspinner') || ff.isXType('checkbox')) {
                ff.fireEvent(ff.triggerEvents[1]);
            }
            
        } else {
            if (ff.multiButton) {
                ff.multiButton.removeClass('hidden');
            }
        }
    },
    
    /**
     * hides the default clearer
     */
    hideTriggerClearer: function() {
        this.triggers[0].hide();
    },
    /**
     * creates the multibutton (button to reset or clear the field value)
     * @param {Ext.form.field} formField
     */
    createMultiButton: function(formField) {
        var subLeft = 18;
        if (formField.isXType('tinerecordpickercombobox')) {
            formField.disableClearer = true;
            subLeft += 19;
        } else if (formField.isXType('extuxclearabledatefield')) {
            formField.disableClearer = true;
            if (!formField.multi) {
                subLeft += 17;
            }
        } else if (formField.isXType('tine.widget.field.AutoCompleteField')) {
            // is trigger, but without button, so do nothing
        } else if (formField.isXType('trigger')) {
            if (! formField.hideTrigger) {
                subLeft += 17;
            }
        } else if (formField.isXType('datetimefield')) {
            subLeft += 17; 
        } else if (formField.isXType('numberfield')) {
            formField.el.dom.style.textAlign = 'left';
        }
        var el = formField.el.parent().select('.tinebase-editmultipledialog-clearer'), 
            width = formField.getWidth(), 
            left = (width - subLeft) + 'px';
            
        formField.startLeft = (width - subLeft);

        if (el.elements.length > 0) {
            el.setStyle({left: left});
            el.removeClass('hidden');
            return;
        }
        
        if (formField.isXType('combo') || formField.isXType('extuxclearabledatefield') || formField.isXType('datefield') 
            || formField.isXType('uxspinner') || formField.isXType('numberfield'))
        {
            const top = formField?.multi ? ';top: 3px' : ';top: 5px';
            left = left + top;
        }
        
        // create Button
        formField.multiButton = new Ext.Element(document.createElement('img'));
        formField.multiButton.set({
            'src': Ext.BLANK_IMAGE_URL,
            'ext:qtip': Ext.util.Format.htmlEncode(i18n._('Delete value from all selected records')),
            'class': 'tinebase-editmultipledialog-clearer',
            'style': 'left:' + left,
            });
        
        formField.multiButton.addClassOnOver('over');
        formField.multiButton.addClassOnClick('click');

        // handles the reset/restore button
        formField.multiButton.on('click', this.onMultiButton, formField);
        formField.el.insertSibling(formField.multiButton);
    },
    
    /**
     * is called if the multibutton of "this" field is triggered
     */
    onMultiButton: function() {
        Tine.log.debug('Multibutton called.');
        // scope: formField
        
        if (this.multiButton.hasClass('undo')) {
            Tine.log.debug('Resetting value to "' + this.startingValue + '".');
            
            if (this.startRecord) {
                
                this.store.removeAll();
                this.reset();
                
                if (this.multi) {
                    this.setValue('');
                } else {
                    this.setValue(this.startRecord);
                    this.value = this.startingValue;
                }
            } else {
                this.setValue(this.startingValue);
            }
            
            if (this.isXType('extuxclearabledatefield') && this.multi) {
                var startLeft = this.startLeft ? this.startLeft : 0;
                var parent = this.el.parent().select('.tinebase-editmultipledialog-clearer');
                parent.setStyle('left', startLeft + 'px');
            }
            if (this.multi) {
                this.cleared = false;
            }
        } else {
            Tine.log.debug('Clearing value.');
            if (this.isXType('extuxclearabledatefield') && this.multi) {
                var startLeft = this.startLeft ? this.startLeft : 0;
                startLeft -= 17;
                var parent = this.el.parent().select('.tinebase-editmultipledialog-clearer');
                parent.setStyle('left', startLeft + 'px');
            }
            if (this.store) {
                this.store.removeAll();
            }
            if (this.isXType('textarea')) {
                this.setValue(null)
            } else {
                this.setValue('');
            }
            if (this.multi) {
                this.cleared = true;
                this.allowBlank = this.origAllowBlank;
            }
        }
        // trigger event
        this.fireEvent(this.triggerEvents[0], this);
    },
    
    /*
     * is called when a trigger event is fired
     */
    onTriggerField: function(calledOnAfterRender) {
        // scope on formField
        Tine.log.info('Trigger handler called for field "' + this.name + '".');
        
        if (! this.el && calledOnAfterRender) {
            return;
        }
        if (! this.el && ! calledOnAfterRender) {
            this.on('afterrender', Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.onTriggerField.createDelegate(this, [true]));
            return;
        }
        
        var ar = this.el.parent().down('.tinebase-editmultipledialog-dirty')
            || this.el.parent().down('.tinebase-editmultipledialog-clearer');

        var originalValue = this.hasOwnProperty('startingValue') ? this.startingValue : this.originalValue,
            currentValue;
        
        if (this.isXType('datefield')) {
            currentValue = this.fullDateTime ? this.fullDateTime.format('Y-m-d H:i:s') : '';
        } else if (this.isXType('timefield')) {
            currentValue = this.fullDateTime;
        } else if (this.isXType('textarea')) {
            currentValue = this.getValue() || ''
        } else {
            currentValue = this.getValue();
        }

        Tine.log.info('Start value: "' + originalValue + '", current: "' + currentValue + '"');
        if ((Ext.encode(originalValue) != Ext.encode(currentValue)) || (this.cleared === true)) {  // if edited or cleared
            // Create or set arrow
            if (ar) {
                ar.setStyle('display','block');
            } else {
                var arrow = new Ext.Element(document.createElement('img'));
                arrow.set({
                    'src': Ext.BLANK_IMAGE_URL,
                    'class': 'tinebase-editmultipledialog-dirty',
                    'height': 5,
                    'width': 5
                });
                this.el.insertSibling(arrow);
            }
            // Set field
            this.edited = true;
            this.removeClass('tinebase-editmultipledialog-noneedit');
            
            // Set button
            if (this.multiButton) {
                this.multiButton.addClass('undo');
                this.multiButton.removeClass('hidden');
                this.multiButton.set({'ext:qtip': Ext.util.Format.htmlEncode(i18n._('Undo change for all selected records'))});
            }
        } else {    // If set back
            // Set arrow
            if (ar) {
                ar.setStyle('display','none');
            }
            // Set field
            this.edited = false;
            if (this.multi) {
                this.addClass('tinebase-editmultipledialog-noneedit');
                this.removeClass('x-form-invalid')
            }
            
            // Set button
            if (this.multiButton) {
                this.multiButton.removeClass('undo');
                this.multiButton.addClass('hidden');
                this.multiButton.set({'ext:qtip': Ext.util.Format.htmlEncode(i18n._('Delete value from all selected records'))});
            }
        }
        this.fireEvent('fieldchange');
    },
    /**
     * waits until the dialog is rendered and fetches real records by the filter on filter selection
     */
    onRecordLoad : function() {
        // fetch records from server on filterselection to get the exact difference
        if (this.isFilterSelect && this.selectionFilter) {
            this.fetchRecordsOnLoad();
        } else {
            var records = [];
            Ext.each(this.selectedRecords, function(recordData, index) {
                records.push(new this.editDialog.recordClass(recordData));
            }, this);
            this.onRecordPrepare(records);
        }
    },
    
    /**
     * find out which fields have differences
     */
    onRecordPrepare: function(records) {
        this.interRecord.multiData = [];
        Ext.each(this.editDialog.recordClass.getFieldDefinitions(), function(fieldDef) {
            var field = _.find(this.handleFields, {key: fieldDef.name});
            var refData = false;
            var multiData = {name: fieldDef.name};
            this.interRecord.multiData.push(multiData);
            Ext.each(records, function(record, index) {
                if (field && field.key === 'container_id') {
                    if (!this.interRecord.get('container_id')) {
                        this.interRecord.set('container_id', record.get('container_id'))
                    }
                    if (record.get('container_id').id !== this.interRecord.get('container_id').id) {
                        this.interRecord.set('container_id', '')
                        this.setFieldValue(field, false)
                    } else {
                        this.setFieldValue(field, true)
                    }
                } else if (field && field.type == 'relation') {
                    this.setFieldValue(field, false);
                } else {
                    // the first record of the selected is the reference
                    if (index === 0) {
                        refData = record.get(fieldDef.name);
                    }
                    if ((Ext.encode(record.get(fieldDef.name)) != Ext.encode(refData))) {
                        this.interRecord.set(fieldDef.name, '');
                        Object.assign(multiData, { equalValues: false, startValue: '' });
                        field && this.setFieldValue(field, false);
                        return false;
                    } else {
                        if (index == records.length - 1) {
                            this.interRecord.set(fieldDef.name, refData);
                            Object.assign(multiData, { equalValues: true, startValue: refData });
                            field && this.setFieldValue(field, true);
                            return true;
                        }
                    }
                }
            }, this);
        }, this);

        // TODO: grantsProperty not handled here, not needed at the moment but sometimes, perhaps.
//        var cp = this.editDialog.recordClass.getMeta('containerProperty') ? this.editDialog.recordClass.getMeta('containerProperty') : 'container_id';
//        if (this.interRecord.get(cp) !== undefined) {
//            this.interRecord.set(cp, {account_grants: {editGrant: true}});
//        }
        this.interRecord.dirty = false;
        this.interRecord.modified = {};
        
        this.editDialog.window.setTitle(String.format(i18n._('Edit {0} {1}'), this.totalRecordCount, this.editDialog.i18nRecordsName));

        Tine.log.debug('loading of the following intermediate record completed:');
        Tine.log.debug(this.interRecord);
        
        this.editDialog.updateToolbars(this.interRecord, this.editDialog.recordClass.getMeta('containerProperty'));
        if (this.editDialog.tbarItems) {
            Ext.each(this.editDialog.tbarItems, function(item) {
                if (Ext.isFunction(item.setDisabled)) item.setDisabled(true);
                item.multiEditable = false;
            });
        }
        
        // some field sanitizing
        Ext.each(this.handleFields, function(field) {
            // handle TimeFields to set original value (not possible before)
            if (field.formField.isXType('timefield')) {
                var value = this.interRecord.get(field.key);
                if (value) {
                    field.formField.setValue(Date.parseDate(value, Date.patterns.ISO8601Time));
                }
            }
            // handle DateTimeFields to set original value
            else if (field.formField.isXType('datetimefield')) {
                var value = this.interRecord.get(field.key);
                if (value) {
                    field.formField.setValue(Date.parseDate(value, Date.patterns.ISO8601Long));
                }
            }
        }, this);

        const ticketFn = (() => {
            this.editDialog.hideLoadMask();
        }).deferByTickets(this);
        const wrapTicket = ticketFn();
        this.editDialog.fireEvent('load', this.editDialog, this.editDialog.record, ticketFn);
        wrapTicket();
    },

    
    /**
     * Set field value
     * @param {Ext.form.Field} field
     * @param {Boolean} samevalue true, if value is the same of all records
     */
    setFieldValue: function(field, samevalue) {
        var ff = field.formField;
        
        ff.removeClass('x-form-empty-field');

        if (! samevalue) {  // The records does not have the same value on this field
            if (ff?.el && !ff.wrap) {
                ff.wrap = ff.el.wrap({cls: 'x-form-field-wrap x-form-field-trigger-wrap'});
            }
            ff.addClass('tinebase-editmultipledialog-noneedit');
            ff.origAllowBlank = ff.allowBlank;
            ff.allowBlank = true;
            ff.multi = true;
            
            ff.setValue('');
            ff.originalValue = '';
            ff.clearInvalid();
            Ext.QuickTips.register({
                target: ff,
                dismissDelay: 30000,
                title: i18n._('Different Values'),
                text: i18n._('This field has different values. Editing this field will overwrite the old values.'),
                width: 200
            });
            
            if (ff.isXType('checkbox')) {
                this.wrapCheckbox(ff);
            } 
        } else { // All records have the same value on this field
            if (ff.isXType('checkbox')) {
                ff.originalValue = this.interRecord.get(ff.name);
                ff.setValue(this.interRecord.get(ff.name));
            } else if (ff.isXType('tinerecordpickercombobox')) {
                var val = this.interRecord.get(field.recordKey);
                if (val) {
                    if (!ff.isXType('addressbookcontactpicker')) {
                        ff.startRecord = new ff.recordClass(val);
                    } else {
                        ff.startRecord = val;
                    }
                }
                ff.setValue(this.interRecord.get(field.recordKey));
            } else {
                ff.setValue(this.interRecord.get(field.recordKey));
            }
        }
        ff.edited = false;

        if (ff.isXType('checkbox')) {
            ff.on('check', function() {
                this.edited = (this.originalValue !== this.getValue());
            });
        }
    },
    
    /**
     * Wraps the checkbox with dirty colored span
     * @param {Ext.form.Field} checkbox
     */
    wrapCheckbox: function(checkbox) {
        if (checkbox.rendered !== true) {
            this.wrapCheckbox.defer(100, this, [checkbox]);
            return;
        }
        checkbox.getEl().wrap({tag: 'span', 'class': 'tinebase-editmultipledialog-dirtycheck'});
        checkbox.originalValue = null;
        checkbox.setValue(false);
    },
    
    findChangedFields: function() {
        this.changedFields = [];
        Ext.each(this.handleFields, function(field) {
            if (field.formField.edited === true) {
                this.changedFields.push(field);
            }
        }, this);
    },
    
    /**
     * is called when the form is submitted. only fieldvalues with edited=true are committed 
     * @return {Boolean}
     */
    onRecordUpdate: function() {
        if (!this.editDialog.isMultipleValid()) {
            Ext.MessageBox.alert(i18n._('Errors'), i18n._('Please fix the errors noted.'));
            Ext.each(this.handleFields, function(item) {
                if (item.activeError) {
                    if (!item.edited) item.activeError = null;
                }
            });
            return false;
        }
        
        this.changedHuman = '<br /><br /><ul>';
        const changes = [];
        
        // cope with added relations
        if (this.editDialog.relationsPanel) {
            Ext.each(this.editDialog.relationsPanel.getData(), function (relation) {
                const rrc = Tine.Tinebase.data.RecordMgr.get(relation['related_model']);
                const rr = new rrc(relation['related_record']);
                const modelName = rrc.getRecordName();
                const title = rr.getTitle();
                const type = relation['type'] || " ";

                relation['own_id'] = "";
                relation['related_record'] = "";
                // we have to empty the relation id, otherwise backend failed to generate relations
                relation['id'] = "";
                changes.push({name: '%add', value: Ext.encode(relation)});
    
                let createdFromPicker = false;
                
                _.each(this.editDialog.relationPickers, (picker) => {
                    if (picker.relationType === relation['type']
                        && picker.getValue() === relation['related_id']
                        && picker.fullModelName === relation['related_model']) 
                    {
                        createdFromPicker = true;
                    }
                });
                
                if (! createdFromPicker) {
                    this.changedHuman += '<li><span style="font-weight:bold">' + String.format(i18n._('Add {0} Relation'), modelName) + ':</span> ';
                    this.changedHuman += Ext.util.Format.htmlEncode(title);
                    this.changedHuman += '</li>';
                }
            }, this);
        }
        
        Ext.each(this.changedFields, function(field) {
            var ff = field.formField,
                renderer = Ext.util.Format.htmlEncode;
        
            var label = ff.fieldLabel ? ff.fieldLabel : ff.boxLabel ? ff.boxLabel : ff.ownerCt.fieldLabel;
            label = label ? label : ff.ownerCt.title;
            
            changes.push({name: (field.type === 'relation') ? field.recordKey : ff.getName(), value: ff.getValue()});

            this.changedHuman += '<li><span style="font-weight:bold">' + label + ':</span> ';
            if (ff.isXType('checkbox')) {
                renderer = Tine.Tinebase.common.booleanRenderer;
            } else if (ff.isXType('durationspinner')) {
                renderer = Tine.Tinebase.common.minutesRenderer;
            }
        
            this.changedHuman += ff.lastSelectionText ? renderer(ff.lastSelectionText) : renderer(ff.getValue());
            this.changedHuman += '</li>';
        }, this);

        const humChanges = _.map(changes, 'name');

        this.editDialog.fireEvent('multipleRecordUpdate', this, changes);

        const rc = this.editDialog.recordClass;
        _.forEach(changes, (change) => {
            if (humChanges.indexOf(change.name) < 0) {
                const label = this.editDialog.app.i18n._hidden(rc.getField(change.name).label);
                const value = Tine.widgets.grid.RendererManager.get(rc.getAppName(), rc.getPhpClassName(), change.name, 'displayPanel')(change.value);

                this.changedHuman += `<li><span style="font-weight:bold">${label}:</span> ${value}</li>`;
            }
        });
        this.changedHuman += '</ul>';
        var filter = this.selectionFilter;
        if (changes.length > 0) {
            Ext.MessageBox.confirm(i18n._('Confirm'), String.format(i18n._('Do you really want to change these {0} records?') + this.changedHuman, this.totalRecordCount),
                function(_btn) {
                if (_btn == 'yes') {
                    Ext.MessageBox.wait(i18n._('Please wait'),i18n._('Applying changes'));
                    Ext.Ajax.request({
                        url: 'index.php',
                        timeout: 3600000, // 1 hour
                        params: {
                            method: 'Tinebase.updateMultipleRecords',
                            appName: this.editDialog.recordClass.getMeta('appName'),
                            modelName: this.editDialog.recordClass.getMeta('modelName'),
                            changes: changes,
                            filter: filter
                        },
                        success: function(_result, _request) {
                            Ext.MessageBox.hide();
                            var resp = Ext.decode(_result.responseText);
                            if (resp.failcount > 0) {
                                var window = Tine.widgets.dialog.MultipleEditResultSummary.openWindow({
                                    response: _result.responseText,
                                    appName: this.app.appName,
                                    recordClass: this.editDialog.recordClass
                                });
                                window.on('close', function() {
                                    this.editDialog.fireEvent('update');
                                    this.editDialog.onCancel();
                                }, this);
                            } else {
                                this.editDialog.fireEvent('update');
                                this.editDialog.onCancel();
                            }
                        },
                        failure : function(exception) {
                            Tine.Tinebase.ExceptionHandler.handleRequestException(exception, this.onUpdateFailure, this);
                        },
                        scope: this
                    });
                } else {
                    this.editDialog.onCancel();
                }
            }, this);
        } else {
            this.editDialog.onCancel();
        }
        return false;
    },
    
    /**
     * 
     * @param {} btn
     * @param {} dialog
     */
    onUpdateFailure: function(btn, dialog) {
        this.editDialog.getForm().clear();
        this.onRecordLoad();
    },
    
    /**
     * fetch records from backend on selectionFilter
     */
    fetchRecordsOnLoad: function() {
        Tine.log.debug('Fetching additional records...');
        this.editDialog.recordProxy.searchRecords(this.selectionFilter, null, {
            scope: this,
            success: function(result) {
                this.onRecordPrepare.call(this, result.records);
            }
        });
    }
};

Ext.ComponentMgr.registerPlugin('multiple_edit_dialog', Tine.widgets.dialog.MultipleEditDialogPlugin);
