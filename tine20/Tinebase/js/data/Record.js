/*
 * Tine 2.0
 * 
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const { apply, extend, override, isPrimitive, isArray, isString } = require("Ext/core/core/Ext");
const { emptyFn, copyTo } = require("Ext/core/Ext-more");
const { lowerFirst, get, set, find, forEach, isFunction, isObject, indexOf, map, difference, compact, bind, toUpper, isUndefined } = require('lodash');
const ExtRecord = require("Ext/data/Record");
const MixedCollection = require("Ext/util/MixedCollection");
const Field = require("Ext/data/DataField");
const JsonReader = require("Ext/data/JsonReader");
const recordMgr = require("./RecordMgr");
const { assertComparable } = require("common")
import log from "ux/Log.js"

// getTitle() dependenciesn - use dynamic includes? (also twig)
// - Tine.Tinebase.appMgr
// - Tine.Tinebase.data.TitleRendererManager
// - Tine.Tinebase.widgets.keyfield

// @see https://github.com/ericmorand/twing/issues/332
// #if typeof window !== "undefined"
import getTwingEnv from "twingEnv.es6";
// #endif

const ISO8601Long = 'Y-m-d H:i:s';
const ISO8601Time = 'H:i:s';

const Record = function(data, id) {
    this.constructor.init();
    if (id || id === 0) {
        this.id = id;
        if (!data[this.idProperty]) {
            data[this.idProperty] = this.id
        }
    } else if (data[this.idProperty]) {
        this.id = data[this.idProperty];
    } else {
        this.id = ++ExtRecord.AUTO_ID;
    }
    this.data = data;
    this.initData();
    this.ctime = new Date().getTime();
};

/**
 * @namespace Tine.Tinebase.data
 * @class     Record
 * @extends   Ext.data.Record
 * 
 * Baseclass of Tine 2.0 models
 */
extend(Record, ExtRecord, {
    /**
     * @cfg {String} appName
     * internal/untranslated app name (required)
     */
    appName: null,
    /**
     * @cfg {String} modelName
     * name of the model/record  (required)
     */
    modelName: null,
    /**
     * @cfg {String} idProperty
     * property of the id of the record
     */
    idProperty: 'id',
    /**
     * @cfg {String} titleProperty
     * property of the title attibute, used in generic getTitle function  (required)
     */
    titleProperty: null,
    /**
     * @cfg {String} recordName
     * untranslated record/item name
     */
    recordName: 'record',
    /**
     * @cfg {String} recordName
     * untranslated records/items (plural) name
     */
    recordsName: 'records',
    /**
     * @cfg {String} grantsPath
     * path (see _.get() to find grants
     */
    grantsPath: null,
    /**
     * @cfg {String} containerProperty
     * name of the container property
     */
    containerProperty: null,
    /**
     * @cfg {String} containerName
     * untranslated container name
     */
    containerName: 'container',
    /**
     * @cfg {string} containerName
     * untranslated name of container (plural)
     */
    containersName: 'containers',
    /**
     * @cfg: {bool} copyNoAppendTitle
     * don't append "(copy)" string to title when record is copied
     */
    copyNoAppendTitle: null,
    /**
     * default filter
     * @type {string}
     */
    defaultFilter: null,
    
    cfExp: /^#(.+)/,

    /**
     * template fn called when record is instanciated
     */
    initData: emptyFn,

    /**
     * Get the value of the {@link Field#name named field}.
     * @param {String} name The {@link Field#name name of the field} to get the value of.
     * @return {Object} The value of the field.
     */
    get: function(name) {
        var cfName = String(name).match(this.cfExp);
        
        if (cfName) {
            return this.data.customfields ? this.data.customfields[cfName[1]] : null;
        }
        
        return this.data[name];
    },
    
    /**
     * Set the value of the {@link Field#name named field}.
     * @param {String} name The {@link Field#name name of the field} to get the value of.
     * @return {Object} this.
     */
    set : function(name, value) {
        var encode = isPrimitive(value) ? String : JSON.stringify,
            current = this.get(name),
            cfName;
            
        if (current !== null && encode(current) == encode(value)) {
            return this;
        }
        this.dirty = true;
        if (!this.modified) {
            this.modified = {};
        }
        if (this.modified[name] === undefined) {
            this.modified[name] = current;
        }
        if (encode(value) == encode(this.modified[name])) {
            delete this.modified[name];
        }
        if (Object.keys(this.modified).length === 0) {
            this.dirty = false;
        }
        if (cfName = String(name).match(this.cfExp)) {
            var oldValueJSON = JSON.stringify(this.get('customfields') || {}),
                valueObject = JSON.parse(oldValueJSON);

            assertComparable(valueObject);
            valueObject[cfName[1]] = value;

            if (JSON.stringify(valueObject) != oldValueJSON) {
                this.set('customfields', valueObject);
            }
        } else {
            this.data[name] = value;
        }
        
        if (!this.editing) {
            this.afterEdit();
        }

        return this;
    },

    getData: function() {
        return set(ExtRecord.prototype.getData.call(this), '__meta.recordClass', `${this.appName}.${this.modelName}`);
    },
    /**
     * returns title of this record
     * 
     * @return {String}
     */
    getTitle: function(options) {
        var _ = window.lodash,
            me = this;

        const template = Tine.Tinebase.appMgr.get(this.appName).getRegistry().get('preferences')?.get(`${lowerFirst(this.modelName)}TitleTemplate`);
        if (template) {
            this.titleProperty = template;
        }

        if (Tine.Tinebase.data.TitleRendererManager.has(this.appName, this.modelName)) {
            return Tine.Tinebase.data.TitleRendererManager.get(this.appName, this.modelName)(this);
        } else if (String(this.titleProperty).match(/[{ ]/)) {
            if (! this.constructor.titleTwing) {
                var twingEnv = getTwingEnv();
                var loader = twingEnv.getLoader();

                loader.setTemplate(
                    this.constructor.getPhpClassName() + 'Title',
                    Tine.Tinebase.appMgr.get(this.appName).i18n._hidden(this.titleProperty)
                );

                this.constructor.titleTwing = twingEnv;
            }

            return this.constructor.titleTwing.renderProxy(this.constructor.getPhpClassName() + 'Title', Object.assign({record: this}, this.data));
        } else if (get(this.fields.get(this.titleProperty), 'fieldDefinition.config.specialType') === 'localizedString') {
            // const keyFieldDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(this.constructor, this.titleProperty);
            const languagesAvailableDef = get(this.constructor.getModelConfiguration(), 'languagesAvailable')
            const keyFieldDef = Tine.Tinebase.widgets.keyfield.getDefinition(get(languagesAvailableDef, 'config.appName', this.appName), languagesAvailableDef.name)
            let language = options?.language || keyFieldDef.default;
            const value = this.get(this.titleProperty);
            const preferredLanguage = Tine.Tinebase.registry.get('preferences')?.get('locale');
            if (preferredLanguage !== 'auto') {
                language = preferredLanguage;
            }
            return get(find(value, { language }), 'text', '') || find(value, (r) => {return r.text})?.text || i18n._('Translation not found')
        } else {
            var s = this.titleProperty ? this.titleProperty.split('.') : [null];
            return (s.length > 0 && this.get(s[0]) && this.get(s[0])[s[1]]) ? this.get(s[0])[s[1]] : s[0] ? this.get(this.titleProperty) : '';
        }
    },
    /**
     * returns the id of the record
     */
    getId: function() {
        return this.get(this.idProperty ? this.idProperty : 'id');
    },

    /**
     * sets the id of the record
     *
     * @param {String} id
     */
    setId: function(id) {
        this.id = id;
        return this.set(this.idProperty ? this.idProperty : 'id', id);
    },

    /**
     * converts data to String
     * 
     * @return {String}
     */
    toString: function() {
        return JSON.stringify(this.data);
    },
    
    toJSON: function() {
        return this.data;
    },
    
    /**
     * returns true if given record obsoletes this one
     * 
     * - returns false if record has no modlog properties to make 
     *   handling of updated records work in the grid panel
     * @see 0009464: user grid does not refresh after ctx menu action
     * 
     * @param {Record} record
     * @return {Boolean}
     */
    isObsoletedBy: function(record) {
        if (record.modelName !== this.modelName || record.getId() !== this.getId()) {
            throw new Error('Records could not be compared');
        }
        
        if (this.constructor.hasField('seq') && record.get('seq') != this.get('seq')) {
            return record.get('seq') > this.get('seq');
        }
        
        return record.getMTime() > this.getMTime();
    },

    getMTime: function() {
        return this.data.last_modified_time || this.data.creation_time;
    },

    /**
     * update complete record with data from given record
     *
     * @param record
     */
    update: function(record) {
        record = get(record, 'data', false) ? record :
            Record.setFromJson(record, this.constructor);

        this.beginEdit();
        this.fields.each((field) => {
            let newValue = assertComparable(record.get(field.name));
            assertComparable(get(this, 'data.' + field.name));
            this.set(field.name, newValue);
        }, this);
        this.endEdit();
    },
    
    resolveForeignRecords: async function(fields) {
        const fieldDefs = this.constructor.getModelConfiguration().fields;
        const pms = [];
        
        forEach(this.constructor.getModelConfiguration().fields, (def, fieldName) => {
            if (fields && indexOf(fields, fieldName) < 0) return;
            
            let value = this.get(fieldName);
            if (get(def, 'type') === 'record' && value && ! isFunction(get(value, 'beginEdit'))) {
                const recordClass = recordMgr.get(def.config.appName, def.config.modelName);
                if (! recordClass) return;
                
                if (String(value)[0] === '{' || isObject(value)) {
                    this.set(fieldName, Record.setFromJson(value, recordClass));
                } else {
                    const proxy = new Tine.Tinebase.data.RecordProxy({
                        recordClass: recordClass
                    });
                    pms.push(proxy.promiseLoadRecord(value)
                        .then((record) => {
                            this.set(fieldName, record);
                        }));
                }
            }
        });
        
        await Promise.all(pms);
    },

    copy: function(newId) {
        const data = this.getData();
        data[this.idProperty] = isUndefined(newId) ? data[this.idProperty] : newId;
        const copy = Record.setFromJson(data, this.constructor);
        forEach(this.data, (v, k) => {
            if (isFunction(get(v, 'copy'))) {
                copy.data[k] = v.copy();
            }
        });
        forEach(this.__metaFields, (m) => {
            if (this.hasOwnProperty(m)) {
                copy[m] = this[m];
            }
        });
        difference(Object.keys(data), this.fields.keys, ["__meta"]).forEach((k) => {
            copy.data[k] = data[k];
        });
        copy.modified = [];
        forEach(this.modified, (v, k) => {
            copy.modified[k] = isFunction(get(v, 'copy')) ? v.copy() : this.modified[k];
        });
        return copy;
    }
});

/**
 * type mapping
 * @type {Object}
 */
const types = {
    'date':     'date',
    'datetime': 'date',
    'datetime_separated_date': 'date',
    'datetime_separated_time': 'date',
    'time':     'date',
    'string':   'string',
    'localizedString': 'string',
    'stringAutocomplete': 'string',
    'text':     'string',
    'fulltext':     'string',
    'boolean':  'bool',
    'integer':  'int',
    'bigint':   'int',
    'numberableInt': 'int',
    'float':    'float',
    'money':    'float'
};

/**
 * returns the field
 *
 * @param {Object} fieldDefinition
 * @return {Object}
 */
const getFieldFromModelConfig = function(fieldDefinition, key) {
    // default type is auto
    var field = {
        name: key,
        fieldDefinition: fieldDefinition
    };

    if (fieldDefinition.type) {
        // add pre defined type
        field.type = types[fieldDefinition.type];
        switch (fieldDefinition.type) {
            case 'datetime_separated_date':
            case 'datetime':
            case 'date':
                field.dateFormat = ISO8601Long;
                break;
            case 'time':
                field.dateFormat = ISO8601Time;
                break;
            case 'record':
            case 'records':
                field.type = fieldDefinition.config.appName + '.' + fieldDefinition.config.modelName;
                field.getRecordClass = function() {
                    return recordMgr.get(field.type);
                }
                break;
        }
        if (get(fieldDefinition, 'validators.allowEmpty') === false) {
            field.allowBlank = false;
        }

        // NOTE (from Ext docs): The default value used when a Record is being created by a Reader when the item referenced by the mapping does not exist in the data object (i.e. undefined). (defaults to "")
        // -> applies to existing records (on server). we don't have a concept for new (phantom) records yet!
        if (['attachments', 'records', 'relations', 'alarms', 'notes'].indexOf(fieldDefinition.type) >= 0
            || (fieldDefinition.nullable)) {
            field.defaultValue = null;
        }

        // NOTE: this field default is meant for doctrine on server
        if (fieldDefinition.hasOwnProperty('default')) {
            field.defaultValue = fieldDefinition['default'];
        }

        if (fieldDefinition.hasOwnProperty('validators')) {
            if (fieldDefinition['validators']['default'] || fieldDefinition['validators']['Zend_Filter_Empty']) {
                // @TODO evaluate inputFilters. inputFilters run on server when the key ist set in the data whereas validators always run
                field.defaultValue = fieldDefinition['validators']['default'] || fieldDefinition['validators']['Zend_Filter_Empty'];
            }
        }

        let defaultFromConfig = get(fieldDefinition, 'config.defaultFromConfig');
        defaultFromConfig = defaultFromConfig ? Tine.Tinebase.configManager.get(defaultFromConfig.config, defaultFromConfig.appName) : null;
        if (defaultFromConfig) {
            field.defaultValue = defaultFromConfig
        }

        // php for [className, functionName]
        if (get(field.defaultValue, '[0].length') === 2) {
            if (field.defaultValue[1] === 'Tinebase_Record_RecordSet') {
                field.defaultValue = field.defaultValue[3];
            } else if (field.defaultValue[0][1] === 'generateUID') {
                field.defaultValue = bind(Record.generateUID, _, field.defaultValue[1] || 40);
            }
        }

        if ((toUpper(get(fieldDefinition, `config.storage`)) === 'JSON' || get(fieldDefinition, `config.persistent`) === true)
            && !isArray(field.defaultValue) && String(field.defaultValue).match(/^[\[{]/)) {
            // NOTE: Server can't handle this properly, see {tine20/vendor/zendframework/zendframework1/library/Zend/Filter/Input.php:998}
            field.defaultValue = JSON.parse(field.defaultValue);
        }
        if (!field.defaultValue && ['int', 'float'].indexOf(field.type) >= 0 && !fieldDefinition.nullable) {
            field.defaultValue = 0;
        }
        if (fieldDefinition.type === 'dynamicRecord' && !fieldDefinition.nullable && !field.defaultValue) {
            field.defaultValue = {};
        }
        if (['record', 'dynamicRecord'].indexOf(fieldDefinition.type) >=0 && JSON.stringify(field.defaultValue) === '[]') {
            field.defaultValue = {};
        }

        // allow overwriting date pattern in model
        if (fieldDefinition.hasOwnProperty('dateFormat')) {
            field.dateFormat = fieldDefinition.dateFormat;
        }

        if (fieldDefinition.hasOwnProperty('label')) {
            field.label = fieldDefinition.label;
        }
    }

    // TODO: create field registry, add fields here
    return field;
};

Record.convertModelConfig = function(modelConfig) {
    const modelArray = map(modelConfig.fieldKeys, key => {
        let fieldDefinition = modelConfig.fields[key];

        if (fieldDefinition.type === 'virtual') {
            fieldDefinition = fieldDefinition.config || {};
        }

        return getFieldFromModelConfig(fieldDefinition, key);
    })

    forEach(modelConfig.virtualFields, field => {
        modelArray.push(getFieldFromModelConfig(field, field.key));
    })

    const recordConfig = copyTo({modelConfiguration: modelConfig}, modelConfig,
        'idProperty,defaultFilter,appName,modelName,recordName,recordsName,titleProperty,' +
        'containerProperty,containerName,containersName,group,copyOmitFields,copyNoAppendTitle');

    return { modelArray, recordConfig }
}

/**
 * Generate a constructor for a specific Record layout.
 * 
 * @param {Array} def see {@link Ext.data.Record#create}
 * @param {Object} meta information see {@link Record}
 * 
 * <br>usage:<br>
<b>IMPORTANT: the ngettext comments are required for the translation system!</b>
<pre><code>
var TopicRecord = Record.create([
    {name: 'summary', mapping: 'topic_title'},
    {name: 'details', mapping: 'username'}
], {
    appName: 'Tasks',
    modelName: 'Task',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Task', 'Tasks', n);
    recordName: 'Task',
    recordsName: 'Tasks',
    containerProperty: 'container_id',
    // ngettext('to do list', 'to do lists', n);
    containerName: 'to do list',
    containesrName: 'to do lists'
});
</code></pre>
 * @static
 */
Record.create = function(o, meta) {
    var f = extend(Record, {});
    var p = f.prototype;
    apply(p, meta);

    f.getField = function(name) {
        f.init();
        return p.fields.get(name);
    };
    f.getMeta = function(name) {
        f.init();
        var value = null;
        switch (name) {
            case ('phpClassName'):
                value = p.appName + '_Model_' + p.modelName;
                break;
            default:
                value = p[name];
        }
        return value;
    };
    // NOTE: gets overwritten with Record.getDefaultData (see below) for autogenerated models in appStarter
    f.getDefaultData = function() {
        return {};
    };
    f.getDefaultData.isEmptyDefault = true;

    f.getFieldDefinitions = function() {
        f.init();
        return p.fields.items;
    };
    f.getFieldNames = function() {
        f.init();
        if (! p.fieldsarray) {
            var arr = p.fieldsarray = [];
            forEach(p.fields.items, function(item) {arr.push(item.name);});
        }
        return p.fieldsarray;
    };
    f.hasField = function(n) {
        f.init();
        return p.fields.indexOfKey(n) >= 0;
    };
    f.getDataFields = function() {
        f.init();
        const systemFields = map(Record.genericFields, 'name')
            .concat(f.getMeta('idProperty'))
            .concat(p.modelConfiguration?.hasUserNotes ? [] : 'notes')
            .concat(p.modelConfiguration?.delegateAclField && p.grantsPath ? String(p.grantsPath).replace(/^data\./, '') : []);
        return difference(p.modelConfiguration?.fieldKeys, systemFields);
    };
    f.getRecordName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.recordName, p.recordsName, 1);
    };
    f.getModuleName = function () {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        if (p.modelConfiguration && p.modelConfiguration.moduleName) {
            return i18n._(p.modelConfiguration.moduleName);
        }

        return p.moduleName ? i18n._(p.moduleName) : f.getRecordsName();
    };
    f.getRecordsName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.recordName, p.recordsName, 50);
    };
    f.getRecordGender = function () {
        f.init();
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n,
            msgId = 'GENDER_' + p.recordName,
            gender = i18n._hidden(msgId);
        
        return gender !== msgId ? gender : 'other';
    };
    f.getContainerGender = function () {
        f.init();
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n,
            msgId = 'GENDER_' + p.containerName,
            gender = i18n._hidden(msgId);

        return gender !== msgId ? gender : 'other';
    };
    f.getContainerName = function() {
        f.init();
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.containerName, p.containersName, 1);
    };
    f.getContainersName = function() {
        f.init();
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n.n_(p.containerName, p.containersName, 50);
    };
    f.getAppName = function() {
        var app = Tine.Tinebase.appMgr.get(p.appName),
            i18n = app && app.i18n ? app.i18n : window.i18n;

        return i18n._(p.appName);
    };
    f.getIconCls = function() {
        return 'ApplicationIconCls ' + p.appName + 'IconCls ' + p.appName + p.modelName;
    };
    /**
     * returns the php class name of the record itself or by the application(name) and model(name)
     * @param {mixed} app       the application instance or the application name or the record class
     * @param {mixed} model     the model name
     * @return {String} php class name
     */
    f.getPhpClassName = function(app, model) {
        f.init();
        // without arguments the php class name of the this is returned
        if (!app && !model) {
            return f.getMeta('phpClassName');
        }
        // if var app is a record class, the getMeta method is called
        if (isFunction(app.getMeta)) {
            return app.getMeta('phpClassName');
        }

        var appName = (isObject(app) && app.hasOwnProperty('name')) ? app.name : app;
        return appName + '_Model_' + model;
    };
    f.getModelConfiguration = function() {
        return p.modelConfiguration;
    };
    f.setModelConfiguration = function(modelConfiguration) {
        p.modelConfiguration = modelConfiguration;
        return this;
    };
    f.getProxy = function() {
        return get(window, `Tine.${p.appName}.${p.modelName.toLowerCase()}Backend`);
    };
    ['clone', 'generateUID'].forEach(function(method) {
        f[method] = bind(Record[method], Record);
    });
    f.setFromJson = function(json) {
        return Record.setFromJson(json, p);
    };

    f.init = function(o, meta) {
        if (p.fields || p.isInitialized) return;
        if (! (o && o.length)) {
            // lazy init
            const modelConfig = f.getModelConfiguration() || Tine[p.appName].registry.get('models')[p.modelName];
            const { modelArray, recordConfig} = Record.convertModelConfig(modelConfig)
            o = modelArray; meta = recordConfig;
        }

        apply(p, meta);
        p.fields = new MixedCollection(false, function(field) {
            return field.name;
        });
        p.fields.get = function(name) {
            const cfName = String(name).match(p.cfExp);
            if (cfName) {
                return {
                    name,
                    sortDir: 'DESC'
                };
            } else {
                return MixedCollection.prototype.get.apply(this, arguments);
            }
        };

        for(var i = 0, len = o.length; i < len; i++) {
            if (o[i]['name'] == meta.containerProperty && meta.allowBlankContainer === false) {
                o[i]['allowBlank'] = false;
            }
            p.fields.add(new Field(o[i]));
        }

        // sanitize containerProperty label
        var containerProperty = f.getMeta('containerProperty');
        if (containerProperty) {
            var field = p.fields.get(containerProperty);
            if (field) {
                field.label = p.containerName;
            }
        }
        if (!p.grantsPath) {
            p.grantsPath = 'data' + (containerProperty ? ('.' + containerProperty) : '') + '.account_grants';
        }

        p.isInitialized = true;
    }

    recordMgr.add(f);

    if (o.length) {
        f.init(o, meta)
    }

    return f;
};

Record.generateUID = function(length) {
    length = length || 40;
        
    var s = '0123456789abcdef',
        uuid = new Array(length);
    for(var i=0; i<length; i++) {
        uuid[i] = s.charAt(Math.ceil(Math.random() *15));
    }
    return uuid.join('');
};

Record.getDefaultData = function(recordClass, defaults) {
    var modelConfig = recordClass.getModelConfiguration(),
        appName = modelConfig.appName,
        modelName = modelConfig.modelName;
    
    // if default data is empty, it will be resolved to an array
    if (isArray(modelConfig.defaultData)) {
        modelConfig.defaultData = {};
    }
    
    // get default data from fields config
    const dd = Record.setFromJson(JSON.stringify(modelConfig.defaultData), recordClass).getData();
    delete dd[recordClass.getMeta('idProperty')];
    delete dd['__meta'];

    // find container by selection or use defaultContainer by registry
    if (modelConfig.containerProperty &&! modelConfig.extendsContainer) {
        if (! dd.hasOwnProperty(modelConfig.containerProperty)) {
            var app = Tine.Tinebase.appMgr.get(appName),
                registry = app.getRegistry(),
                ctp = app.getMainScreen().getWestPanel().getContainerTreePanel();

            var container = (ctp && isFunction(ctp.getDefaultContainer) ? ctp.getDefaultContainer() : null)
                || (registry ? registry.get("default" + modelName + "Container") : null);

            if (container) {
                dd[modelConfig.containerProperty] = container;
            }
        }
    }

    // @TODO: use grants model and set all grants to true for new records
    dd['account_grants'] = {'adminGrant': true};

    // NOTE: ui config overwrites db config
    forEach(modelConfig.fields, (config, name) => {
        const fieldDefault = get(config, 'uiconfig.default', this);
        if (fieldDefault !== this) {
            dd[name] = fieldDefault;
        }
    });

    return Object.assign(dd, defaults);
};

/**
 * create record from json string
 *
 * @param {String} json
 * @param {Record} recordClass
 * @returns {Record}
 */
Record.setFromJson = function(json, recordClass) {
    recordClass = recordMgr.get(recordClass);
    if (!recordClass) return null;
    var jsonReader = new JsonReader({
        id: recordClass.idProperty,
        root: 'results',
        totalProperty: 'totalcount'
    }, recordClass);

    try {
        var recordData = {
                results: compact([
                    isString(json) ? JSON.parse(json) : json
                ])
            },
            data = jsonReader.readRecords(recordData),
            record = data.records[0];
    } catch (e) {
        log.warn('Exception in setFromJson:');
        log.warn(e);
    }

    let recordId = get(record, 'data.' + get(record, 'idProperty'));
    if (!recordId && [0, '0'].indexOf(recordId) < 0 ) {
        recordId = Record.generateUID();
    }

    if (! record) {
        record = new recordClass({}, recordId);
    }
    record.setId(recordId);
    // 2025-01-15 - cweiss - NOTE: we can't commit here as we would loose __meta modified
    //                             i have no idea why i commited here, maybe just because of the id?
    //                             let's solve it better the next time!
    // record.commit();

    return record;
};

/**
 * returns a clone of given record (in current window context)
 *
 * @param {Record} record
 * @return {Record}
 */
Record.clone = function(record) {
    const data = JSON.stringify(record.getData());
    const recordClass = record.constructor.getPhpClassName()

    return Record.setFromJson(data, recordClass);
}

/**
 * @type {Array}
 *
 * modlog Fields
 */

Record.modlogFields = [
    { name: 'creation_time',      type: 'date', dateFormat: ISO8601Long, omitDuplicateResolving: true },
    { name: 'created_by',                                                              omitDuplicateResolving: true },
    { name: 'last_modified_time', type: 'date', dateFormat: ISO8601Long, omitDuplicateResolving: true },
    { name: 'last_modified_by',                                                        omitDuplicateResolving: true },
    { name: 'is_deleted',         type: 'boolean',                                     omitDuplicateResolving: true },
    { name: 'deleted_time',       type: 'date', dateFormat: ISO8601Long, omitDuplicateResolving: true },
    { name: 'deleted_by',                                                              omitDuplicateResolving: true },
    { name: 'seq',                                                                     omitDuplicateResolving: true }
];

/**
 * @type {Array}
 * generic Record fields
 */
Record.genericFields = Record.modlogFields.concat([
    { name: 'container_id', header: 'Container',                                       omitDuplicateResolving: false}
]);


export default Record
