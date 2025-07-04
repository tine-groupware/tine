/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.data.Store
 * @extends Ext.util.Observable
 * <p>The Store class encapsulates a client side cache of {@link Ext.data.Record Record}
 * objects which provide input data for Components such as the {@link Ext.grid.GridPanel GridPanel},
 * the {@link Ext.form.ComboBox ComboBox}, or the {@link Ext.DataView DataView}.</p>
 * <p><u>Retrieving Data</u></p>
 * <p>A Store object may access a data object using:<div class="mdetail-params"><ul>
 * <li>{@link #proxy configured implementation} of {@link Ext.data.DataProxy DataProxy}</li>
 * <li>{@link #data} to automatically pass in data</li>
 * <li>{@link #loadData} to manually pass in data</li>
 * </ul></div></p>
 * <p><u>Reading Data</u></p>
 * <p>A Store object has no inherent knowledge of the format of the data object (it could be
 * an Array, XML, or JSON). A Store object uses an appropriate {@link #reader configured implementation}
 * of a {@link Ext.data.DataReader DataReader} to create {@link Ext.data.Record Record} instances from the data
 * object.</p>
 * <p><u>Store Types</u></p>
 * <p>There are several implementations of Store available which are customized for use with
 * a specific DataReader implementation.  Here is an example using an ArrayStore which implicitly
 * creates a reader commensurate to an Array data object.</p>
 * <pre><code>
var myStore = new Ext.data.ArrayStore({
    fields: ['fullname', 'first'],
    idIndex: 0 // id for each record will be the first element
});
 * </code></pre>
 * <p>For custom implementations create a basic {@link Ext.data.Store} configured as needed:</p>
 * <pre><code>
// create a {@link Ext.data.Record Record} constructor:
var rt = Ext.data.Record.create([
    {name: 'fullname'},
    {name: 'first'}
]);
var myStore = new Ext.data.Store({
    // explicitly create reader
    reader: new Ext.data.ArrayReader(
        {
            idIndex: 0  // id for each record will be the first element
        },
        rt // recordType
    )
});
 * </code></pre>
 * <p>Load some data into store (note the data object is an array which corresponds to the reader):</p>
 * <pre><code>
var myData = [
    [1, 'Fred Flintstone', 'Fred'],  // note that id for the record is the first element
    [2, 'Barney Rubble', 'Barney']
];
myStore.loadData(myData);
 * </code></pre>
 * <p>Records are cached and made available through accessor functions.  An example of adding
 * a record to the store:</p>
 * <pre><code>
var defaultData = {
    fullname: 'Full Name',
    first: 'First Name'
};
var recId = 100; // provide unique id for the record
var r = new myStore.recordType(defaultData, ++recId); // create new record
myStore.{@link #insert}(0, r); // insert a new record into the store (also see {@link #add})
 * </code></pre>
 * <p><u>Writing Data</u></p>
 * <p>And <b>new in Ext version 3</b>, use the new {@link Ext.data.DataWriter DataWriter} to create an automated, <a href="http://extjs.com/deploy/dev/examples/writer/writer.html">Writable Store</a>
 * along with <a href="http://extjs.com/deploy/dev/examples/restful/restful.html">RESTful features.</a>
 * @constructor
 * Creates a new Store.
 * @param {Object} config A config object containing the objects needed for the Store to access data,
 * and read the data into Records.
 * @xtype store
 */
Ext.data.Store = Ext.extend(Ext.util.Observable, {
    /**
     * @cfg {String} storeId If passed, the id to use to register with the <b>{@link Ext.StoreMgr StoreMgr}</b>.
     * <p><b>Note</b>: if a (deprecated) <tt>{@link #id}</tt> is specified it will supersede the <tt>storeId</tt>
     * assignment.</p>
     */
    /**
     * @cfg {String} url If a <tt>{@link #proxy}</tt> is not specified the <tt>url</tt> will be used to
     * implicitly configure a {@link Ext.data.HttpProxy HttpProxy} if an <tt>url</tt> is specified.
     * Typically this option, or the <code>{@link #data}</code> option will be specified.
     */
    /**
     * @cfg {Boolean/Object} autoLoad If <tt>{@link #data}</tt> is not specified, and if <tt>autoLoad</tt>
     * is <tt>true</tt> or an <tt>Object</tt>, this store's {@link #load} method is automatically called
     * after creation. If the value of <tt>autoLoad</tt> is an <tt>Object</tt>, this <tt>Object</tt> will
     * be passed to the store's {@link #load} method.
     */
    /**
     * @cfg {Ext.data.DataProxy} proxy The {@link Ext.data.DataProxy DataProxy} object which provides
     * access to a data object.  See <code>{@link #url}</code>.
     */
    /**
     * @cfg {Array} data An inline data object readable by the <code>{@link #reader}</code>.
     * Typically this option, or the <code>{@link #url}</code> option will be specified.
     */
    /**
     * @cfg {Ext.data.DataReader} reader The {@link Ext.data.DataReader Reader} object which processes the
     * data object and returns an Array of {@link Ext.data.Record} objects which are cached keyed by their
     * <b><tt>{@link Ext.data.Record#id id}</tt></b> property.
     */
    /**
     * @cfg {Ext.data.DataWriter} writer
     * <p>The {@link Ext.data.DataWriter Writer} object which processes a record object for being written
     * to the server-side database.</p>
     * <br><p>When a writer is installed into a Store the {@link #add}, {@link #remove}, and {@link #update}
     * events on the store are monitored in order to remotely {@link #createRecords create records},
     * {@link #destroyRecord destroy records}, or {@link #updateRecord update records}.</p>
     * <br><p>The proxy for this store will relay any {@link #writexception} events to this store.</p>
     * <br><p>Sample implementation:
     * <pre><code>
var writer = new {@link Ext.data.JsonWriter}({
    encode: true,
    writeAllFields: true // write all fields, not just those that changed
});

// Typical Store collecting the Proxy, Reader and Writer together.
var store = new Ext.data.Store({
    storeId: 'user',
    root: 'records',
    proxy: proxy,
    reader: reader,
    writer: writer,     // <-- plug a DataWriter into the store just as you would a Reader
    paramsAsHash: true,
    autoSave: false    // <-- false to delay executing create, update, destroy requests
                        //     until specifically told to do so.
});
     * </code></pre></p>
     */
    writer : undefined,
    /**
     * @cfg {Object} baseParams
     * <p>An object containing properties which are to be sent as parameters
     * for <i>every</i> HTTP request.</p>
     * <p>Parameters are encoded as standard HTTP parameters using {@link Ext#urlEncode}.</p>
     * <p><b>Note</b>: <code>baseParams</code> may be superseded by any <code>params</code>
     * specified in a <code>{@link #load}</code> request, see <code>{@link #load}</code>
     * for more details.</p>
     * This property may be modified after creation using the <code>{@link #setBaseParam}</code>
     * method.
     * @property
     */
    /**
     * @cfg {Object} sortInfo A config object to specify the sort order in the request of a Store's
     * {@link #load} operation.  Note that for local sorting, the <tt>direction</tt> property is
     * case-sensitive. See also {@link #remoteSort} and {@link #paramNames}.
     * For example:<pre><code>
sortInfo: {
    field: 'fieldName',
    direction: 'ASC' // or 'DESC' (case sensitive for local sorting)
}
</code></pre>
     */
    /**
     * @cfg {boolean} remoteSort <tt>true</tt> if sorting is to be handled by requesting the <tt>{@link #proxy Proxy}</tt>
     * to provide a refreshed version of the data object in sorted order, as opposed to sorting the Record cache
     * in place (defaults to <tt>false</tt>).
     * <p>If <tt>remoteSort</tt> is <tt>true</tt>, then clicking on a {@link Ext.grid.Column Grid Column}'s
     * {@link Ext.grid.Column#header header} causes the current page to be requested from the server appending
     * the following two parameters to the <b><tt>{@link #load params}</tt></b>:<div class="mdetail-params"><ul>
     * <li><b><tt>sort</tt></b> : String<p class="sub-desc">The <tt>name</tt> (as specified in the Record's
     * {@link Ext.data.Field Field definition}) of the field to sort on.</p></li>
     * <li><b><tt>dir</tt></b> : String<p class="sub-desc">The direction of the sort, 'ASC' or 'DESC' (case-sensitive).</p></li>
     * </ul></div></p>
     */
    remoteSort : false,

    /**
     * @cfg {Boolean} autoDestroy <tt>true</tt> to destroy the store when the component the store is bound
     * to is destroyed (defaults to <tt>false</tt>).
     * <p><b>Note</b>: this should be set to true when using stores that are bound to only 1 component.</p>
     */
    autoDestroy : false,

    /**
     * @cfg {Boolean} pruneModifiedRecords <tt>true</tt> to clear all modified record information each time
     * the store is loaded or when a record is removed (defaults to <tt>false</tt>). See {@link #getModifiedRecords}
     * for the accessor method to retrieve the modified records.
     */
    pruneModifiedRecords : false,

    /**
     * Contains the last options object used as the parameter to the {@link #load} method. See {@link #load}
     * for the details of what this may contain. This may be useful for accessing any params which were used
     * to load the current Record cache.
     * @property
     */
    lastOptions : null,

    /**
     * @cfg {Boolean} autoSave
     * <p>Defaults to <tt>true</tt> causing the store to automatically {@link #save} records to
     * the server when a record is modified (ie: becomes 'dirty'). Specify <tt>false</tt> to manually call {@link #save}
     * to send all modifiedRecords to the server.</p>
     * <br><p><b>Note</b>: each CRUD action will be sent as a separate request.</p>
     */
    autoSave : true,

    /**
     * @cfg {Boolean} batch
     * <p>Defaults to <tt>true</tt> (unless <code>{@link #restful}:true</code>). Multiple
     * requests for each CRUD action (CREATE, READ, UPDATE and DESTROY) will be combined
     * and sent as one transaction. Only applies when <code>{@link #autoSave}</code> is set
     * to <tt>false</tt>.</p>
     * <br><p>If Store is RESTful, the DataProxy is also RESTful, and a unique transaction is
     * generated for each record.</p>
     */
    batch : true,

    /**
     * @cfg {Boolean} restful
     * Defaults to <tt>false</tt>.  Set to <tt>true</tt> to have the Store and the set
     * Proxy operate in a RESTful manner. The store will automatically generate GET, POST,
     * PUT and DELETE requests to the server. The HTTP method used for any given CRUD
     * action is described in {@link Ext.data.Api#restActions}.  For additional information
     * see {@link Ext.data.DataProxy#restful}.
     * <p><b>Note</b>: if <code>{@link #restful}:true</code> <code>batch</code> will
     * internally be set to <tt>false</tt>.</p>
     */
    restful: false,

    /**
     * @cfg {Object} paramNames
     * <p>An object containing properties which specify the names of the paging and
     * sorting parameters passed to remote servers when loading blocks of data. By default, this
     * object takes the following form:</p><pre><code>
{
    start : 'start',  // The parameter name which specifies the start row
    limit : 'limit',  // The parameter name which specifies number of rows to return
    sort : 'sort',    // The parameter name which specifies the column to sort on
    dir : 'dir'       // The parameter name which specifies the sort direction
}
</code></pre>
     * <p>The server must produce the requested data block upon receipt of these parameter names.
     * If different parameter names are required, this property can be overriden using a configuration
     * property.</p>
     * <p>A {@link Ext.PagingToolbar PagingToolbar} bound to this Store uses this property to determine
     * the parameter names to use in its {@link #load requests}.
     */
    paramNames : undefined,

    /**
     * @cfg {Object} defaultParamNames
     * Provides the default values for the {@link #paramNames} property. To globally modify the parameters
     * for all stores, this object should be changed on the store prototype.
     */
    defaultParamNames : {
        start : 'start',
        limit : 'limit',
        sort : 'sort',
        dir : 'dir'
    },

    // private
    batchKey : '_ext_batch_',

    constructor : function(config){
        this.data = new Ext.util.MixedCollection(false);
        this.data.getKey = function(o){
            return o.id;
        };


        // temporary removed-records cache
        this.removed = [];

        if(config && config.data){
            this.inlineData = config.data;
            delete config.data;
        }

        Ext.apply(this, config);

        /**
         * See the <code>{@link #baseParams corresponding configuration option}</code>
         * for a description of this property.
         * To modify this property see <code>{@link #setBaseParam}</code>.
         * @property
         */
        this.baseParams = Ext.isObject(this.baseParams) ? this.baseParams : {};

        this.paramNames = Ext.applyIf(this.paramNames || {}, this.defaultParamNames);

        if((this.url || this.api) && !this.proxy){
            this.proxy = new Ext.data.HttpProxy({url: this.url, api: this.api});
        }
        // If Store is RESTful, so too is the DataProxy
        if (this.restful === true && this.proxy) {
            // When operating RESTfully, a unique transaction is generated for each record.
            // TODO might want to allow implemention of faux REST where batch is possible using RESTful routes only.
            this.batch = false;
            Ext.data.Api.restify(this.proxy);
        }

        if(this.reader){ // reader passed
            if(!this.recordType){
                this.recordType = this.reader.recordType;
            }
            if(this.reader.onMetaChange){
                this.reader.onMetaChange = this.reader.onMetaChange.createSequence(this.onMetaChange, this);
            }
            if (this.writer) { // writer passed
                if (this.writer instanceof(Ext.data.DataWriter) === false) {    // <-- config-object instead of instance.
                    this.writer = this.buildWriter(this.writer);
                }
                this.writer.meta = this.reader.meta;
                this.pruneModifiedRecords = true;
            }
        }

        /**
         * The {@link Ext.data.Record Record} constructor as supplied to (or created by) the
         * {@link Ext.data.DataReader Reader}. Read-only.
         * <p>If the Reader was constructed by passing in an Array of {@link Ext.data.Field} definition objects,
         * instead of a Record constructor, it will implicitly create a Record constructor from that Array (see
         * {@link Ext.data.Record}.{@link Ext.data.Record#create create} for additional details).</p>
         * <p>This property may be used to create new Records of the type held in this Store, for example:</p><pre><code>
    // create the data store
    var store = new Ext.data.ArrayStore({
        autoDestroy: true,
        fields: [
           {name: 'company'},
           {name: 'price', type: 'float'},
           {name: 'change', type: 'float'},
           {name: 'pctChange', type: 'float'},
           {name: 'lastChange', type: 'date', dateFormat: 'n/j h:ia'}
        ]
    });
    store.loadData(myData);

    // create the Grid
    var grid = new Ext.grid.EditorGridPanel({
        store: store,
        colModel: new Ext.grid.ColumnModel({
            columns: [
                {id:'company', header: 'Company', width: 160, dataIndex: 'company'},
                {header: 'Price', renderer: 'usMoney', dataIndex: 'price'},
                {header: 'Change', renderer: change, dataIndex: 'change'},
                {header: '% Change', renderer: pctChange, dataIndex: 'pctChange'},
                {header: 'Last Updated', width: 85,
                    renderer: Ext.util.Format.dateRenderer('m/d/Y'),
                    dataIndex: 'lastChange'}
            ],
            defaults: {
                sortable: true,
                width: 75
            }
        }),
        autoExpandColumn: 'company', // match the id specified in the column model
        height:350,
        width:600,
        title:'Array Grid',
        tbar: [{
            text: 'Add Record',
            handler : function(){
                var defaultData = {
                    change: 0,
                    company: 'New Company',
                    lastChange: (new Date()).clearTime(),
                    pctChange: 0,
                    price: 10
                };
                var recId = 3; // provide unique id
                var p = new store.recordType(defaultData, recId); // create new record
                grid.stopEditing();
                store.{@link #insert}(0, p); // insert a new record into the store (also see {@link #add})
                grid.startEditing(0, 0);
            }
        }]
    });
         * </code></pre>
         * @property recordType
         * @type Function
         */

        if(this.recordType){
            /**
             * A {@link Ext.util.MixedCollection MixedCollection} containing the defined {@link Ext.data.Field Field}s
             * for the {@link Ext.data.Record Records} stored in this Store. Read-only.
             * @property fields
             * @type Ext.util.MixedCollection
             */
            this.fields = this.recordType.prototype.fields;
        }
        this.modified = [];

        this.lastTransactionId = 0;

        this.options = {};

        this.addEvents(
            /**
             * @event datachanged
             * Fires when the data cache has changed in a bulk manner (e.g., it has been sorted, filtered, etc.) and a
             * widget that is using this Store as a Record cache should refresh its view.
             * @param {Store} this
             */
            'datachanged',
            /**
             * @event metachange
             * Fires when this store's reader provides new metadata (fields). This is currently only supported for JsonReaders.
             * @param {Store} this
             * @param {Object} meta The JSON metadata
             */
            'metachange',
            /**
             * @event add
             * Fires when Records have been {@link #add}ed to the Store
             * @param {Store} this
             * @param {Ext.data.Record[]} records The array of Records added
             * @param {Number} index The index at which the record(s) were added
             */
            'add',
            /**
             * @event remove
             * Fires when a Record has been {@link #remove}d from the Store
             * @param {Store} this
             * @param {Ext.data.Record} record The Record that was removed
             * @param {Number} index The index at which the record was removed
             */
            'remove',
            /**
             * @event update
             * Fires when a Record has been updated
             * @param {Store} this
             * @param {Ext.data.Record} record The Record that was updated
             * @param {String} operation The update operation being performed.  Value may be one of:
             * <pre><code>
     Ext.data.Record.EDIT
     Ext.data.Record.REJECT
     Ext.data.Record.COMMIT
             * </code></pre>
             */
            'update',
            /**
             * @event clear
             * Fires when the data cache has been cleared.
             * @param {Store} this
             * @param {Record[]} The records that were cleared.
             */
            'clear',
            /**
             * @event exception
             * <p>Fires if an exception occurs in the Proxy during a remote request.
             * This event is relayed through the corresponding {@link Ext.data.DataProxy}.
             * See {@link Ext.data.DataProxy}.{@link Ext.data.DataProxy#exception exception}
             * for additional details.
             * @param {misc} misc See {@link Ext.data.DataProxy}.{@link Ext.data.DataProxy#exception exception}
             * for description.
             */
            'exception',
            /**
             * @event beforeload
             * Fires before a request is made for a new data object.  If the beforeload handler returns
             * <tt>false</tt> the {@link #load} action will be canceled.
             * @param {Store} this
             * @param {Object} options The loading options that were specified (see {@link #load} for details)
             */
            'beforeload',
            /**
             * @event load
             * Fires after a new set of Records has been loaded.
             * @param {Store} this
             * @param {Ext.data.Record[]} records The Records that were loaded
             * @param {Object} options The loading options that were specified (see {@link #load} for details)
             */
            'load',
            /**
             * @event loadexception
             * <p>This event is <b>deprecated</b> in favor of the catch-all <b><code>{@link #exception}</code></b>
             * event instead.</p>
             * <p>This event is relayed through the corresponding {@link Ext.data.DataProxy}.
             * See {@link Ext.data.DataProxy}.{@link Ext.data.DataProxy#loadexception loadexception}
             * for additional details.
             * @param {misc} misc See {@link Ext.data.DataProxy}.{@link Ext.data.DataProxy#loadexception loadexception}
             * for description.
             */
            'loadexception',
            /**
             * @event beforewrite
             * @param {Ext.data.Store} store
             * @param {String} action [Ext.data.Api.actions.create|update|destroy]
             * @param {Record/Array[Record]} rs
             * @param {Object} options The loading options that were specified. Edit <code>options.params</code> to add Http parameters to the request.  (see {@link #save} for details)
             * @param {Object} arg The callback's arg object passed to the {@link #request} function
             */
            'beforewrite',
            /**
             * @event write
             * Fires if the server returns 200 after an Ext.data.Api.actions CRUD action.
             * Success of the action is determined in the <code>result['successProperty']</code>property (<b>NOTE</b> for RESTful stores,
             * a simple 20x response is sufficient for the actions "destroy" and "update".  The "create" action should should return 200 along with a database pk).
             * @param {Ext.data.Store} store
             * @param {String} action [Ext.data.Api.actions.create|update|destroy]
             * @param {Object} result The 'data' picked-out out of the response for convenience.
             * @param {Ext.Direct.Transaction} res
             * @param {Record/Record[]} rs Store's records, the subject(s) of the write-action
             */
            'write',
            /**
             * @event beforesave
             * Fires before a save action is called. A save encompasses destroying records, updating records and creating records.
             * @param {Ext.data.Store} store
             * @param {Object} data An object containing the data that is to be saved. The object will contain a key for each appropriate action,
             * with an array of records for each action.
             */
            'beforesave',
            /**
             * @event save
             * Fires after a save is completed. A save encompasses destroying records, updating records and creating records.
             * @param {Ext.data.Store} store
             * @param {Number} batch The identifier for the batch that was saved.
             * @param {Object} data An object containing the data that is to be saved. The object will contain a key for each appropriate action,
             * with an array of records for each action.
             */
            'save'

        );

        if(this.proxy){
            // TODO remove deprecated loadexception with ext-3.0.1
            this.relayEvents(this.proxy,  ['loadexception', 'exception']);
        }
        // With a writer set for the Store, we want to listen to add/remove events to remotely create/destroy records.
        if (this.writer) {
            this.on({
                scope: this,
                add: this.createRecords,
                remove: this.destroyRecord,
                update: this.updateRecord,
                clear: this.onClear
            });
        }

        this.sortToggle = {};
        if(this.sortField){
            this.setDefaultSort(this.sortField, this.sortDir);
        }else if(this.sortInfo){
            this.setDefaultSort(this.sortInfo.field, this.sortInfo.direction);
        }

        Ext.data.Store.superclass.constructor.call(this);

        if(this.id){
            this.storeId = this.id;
            delete this.id;
        }
        if(this.storeId){
            Ext.StoreMgr.register(this);
        }
        if(this.inlineData){
            this.loadData(this.inlineData);
            delete this.inlineData;
        }else if(this.autoLoad){
            this.load.defer(10, this, [
                typeof this.autoLoad == 'object' ?
                    this.autoLoad : undefined]);
        }
        // used internally to uniquely identify a batch
        this.batchCounter = 0;
        this.batches = {};
    },

    /**
     * builds a DataWriter instance when Store constructor is provided with a writer config-object instead of an instace.
     * @param {Object} config Writer configuration
     * @return {Ext.data.DataWriter}
     * @private
     */
    buildWriter : function(config) {
        var klass = undefined,
            type = (config.format || 'json').toLowerCase();
        switch (type) {
            case 'json':
                klass = Ext.data.JsonWriter;
                break;
            case 'xml':
                klass = Ext.data.XmlWriter;
                break;
            default:
                klass = Ext.data.JsonWriter;
        }
        return new klass(config);
    },

    /**
     * Destroys the store.
     */
    destroy : function(){
        if(!this.isDestroyed){
            if(this.storeId){
                Ext.StoreMgr.unregister(this);
            }
            this.clearData();
            this.data = null;
            Ext.destroy(this.proxy);
            this.reader = this.writer = null;
            this.purgeListeners();
            this.isDestroyed = true;
        }
    },

    /**
     * Add Records to the Store and fires the {@link #add} event.  To add Records
     * to the store from a remote source use <code>{@link #load}({add:true})</code>.
     * See also <code>{@link #recordType}</code> and <code>{@link #insert}</code>.
     * @param {Ext.data.Record[]} records An Array of Ext.data.Record objects
     * to add to the cache. See {@link #recordType}.
     */
    add : function(records){
        records = [].concat(records);
        if(records.length < 1){
            return;
        }
        for(var i = 0, len = records.length; i < len; i++){
            records[i].join(this);
        }
        var index = this.data.length;
        this.data.addAll(records);
        if(this.snapshot){
            this.snapshot.addAll(records);
        }
        this.fireEvent('add', this, records, index);
    },

    /**
     * (Local sort only) Inserts the passed Record into the Store at the index where it
     * should go based on the current sort information.
     * @param {Ext.data.Record} record
     */
    addSorted : function(record){
        const remoteSort = this.remoteSort;
        this.remoteSort = false;
        var index = this.findInsertIndex(record);
        this.remoteSort = remoteSort;
        this.insert(index, record);
    },

    /**
     * appends (number) to property to ensure uniqness
     *
     * @param {Ext.data.Record} record
     * @param {String} prop property name or property path
     */
    addUnique: function(record, prop) {
        prop = prop.match(/^data\./) ? prop : `data.${prop}`;
        let [,name, idx, ext] = String(_.get(record, prop)).match(/(.*?)(?:\s\((\d+)\))?(\..*)/) || [null, _.get(record, prop)];
        while(_.find(this.data.items, (item) => {
            if (record.data === item.data) {
                throw new Error("impossible to add unique this!")
            }
            return _.get(item, prop) === _.get(record, prop)
        })) {

            idx = idx || 0;
            _.set(record, prop, `${name} (${++idx})${ext ||''}`);
        }

        this.addSorted(record);
    },

    /**
     * replace record
     * @param {Ext.data.Record} o old record
     * @param {Ext.data.Record} n new record
     */
    replaceRecord: function(o, n) {
        const r = this.getById(o.id); // refetch record as it might be outdated in the meantime
        const idx = this.indexOf(r);
        this.remove(r);
        this.insert(idx, n);
        if (n.modified || n.dirty) {
            this.modified.push(n);
        }
    },

    /**
     * Remove Records from the Store and fires the {@link #remove} event.
     * @param {Ext.data.Record/Ext.data.Record[]} record The record object or array of records to remove from the cache.
     */
    remove : function(record){
        if(Ext.isArray(record)){
            Ext.each(record, function(r){
                this.remove(r);
            }, this);
        }
        var index = this.data.indexOf(record);
        if(index > -1){
            record.join(null);
            this.data.removeAt(index);
        }
        if(this.pruneModifiedRecords){
            this.modified.remove(record);
        }
        if(this.snapshot){
            this.snapshot.remove(record);
        }
        if(index > -1){
            this.fireEvent('remove', this, record, index);
        }
        this.removed.push(record);
    },

    /**
     * Remove a Record from the Store at the specified index. Fires the {@link #remove} event.
     * @param {Number} index The index of the record to remove.
     */
    removeAt : function(index){
        this.remove(this.getAt(index));
    },

    /**
     * Remove all Records from the Store and fires the {@link #clear} event.
     * @param {Boolean} silent [false] Defaults to <tt>false</tt>.  Set <tt>true</tt> to not fire clear event.
     */
    removeAll : function(silent){
        var items = [];
        this.each(function(rec){
            items.push(rec);
        });
        this.clearData();
        if(this.snapshot){
            this.snapshot.clear();
        }
        if(this.pruneModifiedRecords){
            this.modified = [];
        }
        if (silent !== true) {  // <-- prevents write-actions when we just want to clear a store.
            this.fireEvent('clear', this, items);
        }
    },

    // private
    onClear: function(store, records){
        Ext.each(records, function(rec, index){
            this.destroyRecord(this, rec, index);
        }, this);
    },

    /**
     * Inserts Records into the Store at the given index and fires the {@link #add} event.
     * See also <code>{@link #add}</code> and <code>{@link #addSorted}</code>.
     * @param {Number} index The start index at which to insert the passed Records.
     * @param {Ext.data.Record[]} records An Array of Ext.data.Record objects to add to the cache.
     */
    insert : function(index, records){
        records = [].concat(records);
        for(var i = 0, len = records.length; i < len; i++){
            this.data.insert(index, records[i]);
            records[i].join(this);
        }
        if(this.snapshot){
            this.snapshot.addAll(records);
        }
        this.fireEvent('add', this, records, index);
    },

    /**
     * Get the index within the cache of the passed Record.
     * @param {Ext.data.Record} record The Ext.data.Record object to find.
     * @return {Number} The index of the passed Record. Returns -1 if not found.
     */
    indexOf : function(record){
        return this.data.indexOf(record);
    },

    /**
     * Get the index within the cache of the Record with the passed id.
     * @param {String} id The id of the Record to find.
     * @return {Number} The index of the Record. Returns -1 if not found.
     */
    indexOfId : function(id){
        const index = this.data.indexOfKey(String(id));
        return index === -1 ? this.data.indexOfKey(id) : index;
    },

    /**
     * Get the Record with the specified id.
     * @param {String} id The id of the Record to find.
     * @return {Ext.data.Record} The Record with the passed id. Returns undefined if not found.
     */
    getById : function(id){
        return (this.snapshot || this.data).key(id);
    },

    /**
     * Get the Record at the specified index.
     * @param {Number} index The index of the Record to find.
     * @return {Ext.data.Record} The Record at the passed index. Returns undefined if not found.
     */
    getAt : function(index){
        return this.data.itemAt(index);
    },

    /**
     * Returns a range of Records between specified indices.
     * @param {Number} startIndex (optional) The starting index (defaults to 0)
     * @param {Number} endIndex (optional) The ending index (defaults to the last Record in the Store)
     * @return {Ext.data.Record[]} An array of Records
     */
    getRange : function(start, end){
        return this.data.getRange(start, end);
    },

    // private
    storeOptions : function(o){
        o = Ext.apply({}, o);
        delete o.callback;
        delete o.scope;
        this.lastOptions = o;
    },

    // private
    clearData: function(){
        this.data.each(function(rec) {
            rec.join(null);
        });
        this.data.clear();
    },

    /**
     * <p>Loads the Record cache from the configured <tt>{@link #proxy}</tt> using the configured <tt>{@link #reader}</tt>.</p>
     * <br><p>Notes:</p><div class="mdetail-params"><ul>
     * <li><b><u>Important</u></b>: loading is asynchronous! This call will return before the new data has been
     * loaded. To perform any post-processing where information from the load call is required, specify
     * the <tt>callback</tt> function to be called, or use a {@link Ext.util.Observable#listeners a 'load' event handler}.</li>
     * <li>If using {@link Ext.PagingToolbar remote paging}, the first load call must specify the <tt>start</tt> and <tt>limit</tt>
     * properties in the <code>options.params</code> property to establish the initial position within the
     * dataset, and the number of Records to cache on each read from the Proxy.</li>
     * <li>If using {@link #remoteSort remote sorting}, the configured <code>{@link #sortInfo}</code>
     * will be automatically included with the posted parameters according to the specified
     * <code>{@link #paramNames}</code>.</li>
     * </ul></div>
     * @param {Object} options An object containing properties which control loading options:<ul>
     * <li><b><tt>params</tt></b> :Object<div class="sub-desc"><p>An object containing properties to pass as HTTP
     * parameters to a remote data source. <b>Note</b>: <code>params</code> will override any
     * <code>{@link #baseParams}</code> of the same name.</p>
     * <p>Parameters are encoded as standard HTTP parameters using {@link Ext#urlEncode}.</p></div></li>
     * <li><b>callback</b> : Function<div class="sub-desc"><p>A function to be called after the Records
     * have been loaded. The callback is called after the load event is fired, and is passed the following arguments:<ul>
     * <li>r : Ext.data.Record[] An Array of Records loaded.</li>
     * <li>options : Options object from the load call.</li>
     * <li>success : Boolean success indicator.</li></ul></p></div></li>
     * <li><b>scope</b> : Object<div class="sub-desc"><p>Scope with which to call the callback (defaults
     * to the Store object)</p></div></li>
     * <li><b>add</b> : Boolean<div class="sub-desc"><p>Indicator to append loaded records rather than
     * replace the current cache.  <b>Note</b>: see note for <tt>{@link #loadData}</tt></p></div></li>
     * </ul>
     * @return {Boolean} If the <i>developer</i> provided <tt>{@link #beforeload}</tt> event handler returns
     * <tt>false</tt>, the load call will abort and will return <tt>false</tt>; otherwise will return <tt>true</tt>.
     */
    load : function(options) {
        options = options || {};
        this.storeOptions(options);
        if(this.sortInfo && this.remoteSort){
            var pn = this.paramNames;
            options.params = options.params || {};
            options.params[pn.sort] = this.sortInfo.field;
            options.params[pn.dir] = this.sortInfo.direction;
        }
        try {
            return this.execute('read', null, options); // <-- null represents rs.  No rs for load actions.
        } catch(e) {
            this.handleException(e);
            return false;
        }
    },

    /**
     * promisified store load
     */
    promiseLoad : function(options) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            try {
                me.load(Ext.apply(options || {}, {
                    callback: function (r, options, success) {
                        if (success) {
                            fulfill(r, options);
                        } else if (Ext.isFunction(reject)) {
                            reject(new Error(options));
                        }
                    }
                }));
            } catch (error) {
                if (Ext.isFunction(reject)) {
                    reject(new Error(options));
                }
            }
        });
    },

    nextLoad: function() {
        var me = this;
        return new Promise(function (resolve) {
            me.on('load', resolve, me, { single: true });
        });
    },
    
    /**
     * updateRecord  Should not be used directly.  This method will be called automatically if a Writer is set.
     * Listens to 'update' event.
     * @param {Object} store
     * @param {Object} record
     * @param {Object} action
     * @private
     */
    updateRecord : function(store, record, action) {
        if (action == Ext.data.Record.EDIT && this.autoSave === true && (!record.phantom || (record.phantom && record.isValid()))) {
            this.save();
        }
    },

    /**
     * Should not be used directly.  Store#add will call this automatically if a Writer is set
     * @param {Object} store
     * @param {Object} rs
     * @param {Object} index
     * @private
     */
    createRecords : function(store, rs, index) {
        for (var i = 0, len = rs.length; i < len; i++) {
            if (rs[i].phantom && rs[i].isValid()) {
                rs[i].markDirty();  // <-- Mark new records dirty
                this.modified.push(rs[i]);  // <-- add to modified
            }
        }
        if (this.autoSave === true) {
            this.save();
        }
    },

    /**
     * Destroys a record or records.  Should not be used directly.  It's called by Store#remove if a Writer is set.
     * @param {Store} this
     * @param {Ext.data.Record/Ext.data.Record[]}
     * @param {Number} index
     * @private
     */
    destroyRecord : function(store, record, index) {
        if (this.modified.indexOf(record) != -1) {  // <-- handled already if @cfg pruneModifiedRecords == true
            this.modified.remove(record);
        }
        if (!record.phantom) {
            this.removed.push(record);

            // since the record has already been removed from the store but the server request has not yet been executed,
            // must keep track of the last known index this record existed.  If a server error occurs, the record can be
            // put back into the store.  @see Store#createCallback where the record is returned when response status === false
            record.lastIndex = index;

            if (this.autoSave === true) {
                this.save();
            }
        }
    },

    /**
     * This method should generally not be used directly.  This method is called internally
     * by {@link #load}, or if a Writer is set will be called automatically when {@link #add},
     * {@link #remove}, or {@link #update} events fire.
     * @param {String} action Action name ('read', 'create', 'update', or 'destroy')
     * @param {Record/Record[]} rs
     * @param {Object} options
     * @throws Error
     * @private
     */
    execute : function(action, rs, options, /* private */ batch) {
        // blow up if action not Ext.data.CREATE, READ, UPDATE, DESTROY
        if (!Ext.data.Api.isAction(action)) {
            throw new Ext.data.Api.Error('execute', action);
        }
        // make sure options has a fresh, new params hash
        options = Ext.applyIf(options||{}, {
            params: {}
        });
        if(batch !== undefined){
            this.addToBatch(batch);
        }
        // have to separate before-events since load has a different signature than create,destroy and save events since load does not
        // include the rs (record resultset) parameter.  Capture return values from the beforeaction into doRequest flag.
        var doRequest = true;

        if (action === 'read') {
            doRequest = this.fireEvent('beforeload', this, options);
            Ext.applyIf(options.params, this.baseParams);
        }
        else {
            // if Writer is configured as listful, force single-record rs to be [{}] instead of {}
            // TODO Move listful rendering into DataWriter where the @cfg is defined.  Should be easy now.
            if (this.writer.listful === true && this.restful !== true) {
                rs = (Ext.isArray(rs)) ? rs : [rs];
            }
            // if rs has just a single record, shift it off so that Writer writes data as '{}' rather than '[{}]'
            else if (Ext.isArray(rs) && rs.length == 1) {
                rs = rs.shift();
            }
            // Write the action to options.params
            if ((doRequest = this.fireEvent('beforewrite', this, action, rs, options)) !== false) {
                this.writer.apply(options.params, this.baseParams, action, rs);
            }
        }
        if (doRequest !== false) {
            this.lastTransactionId = options.transactionId = ++this.lastTransactionId;
            // Send request to proxy.
            if (this.writer && this.proxy.url && !this.proxy.restful && !Ext.data.Api.hasUniqueUrl(this.proxy, action)) {
                options.params.xaction = action;    // <-- really old, probaby unecessary.
            }
            // Note:  Up until this point we've been dealing with 'action' as a key from Ext.data.Api.actions.
            // We'll flip it now and send the value into DataProxy#request, since it's the value which maps to
            // the user's configured DataProxy#api
            // TODO Refactor all Proxies to accept an instance of Ext.data.Request (not yet defined) instead of this looooooong list
            // of params.  This method is an artifact from Ext2.
            this.proxy.request(Ext.data.Api.actions[action], rs, options.params, this.reader, this.createCallback(action, rs, batch), this, options);
        }
        return doRequest;
    },

    /**
     * Saves all pending changes to the store.  If the commensurate Ext.data.Api.actions action is not configured, then
     * the configured <code>{@link #url}</code> will be used.
     * <pre>
     * change            url
     * ---------------   --------------------
     * removed records   Ext.data.Api.actions.destroy
     * phantom records   Ext.data.Api.actions.create
     * {@link #getModifiedRecords modified records}  Ext.data.Api.actions.update
     * </pre>
     * @TODO:  Create extensions of Error class and send associated Record with thrown exceptions.
     * e.g.:  Ext.data.DataReader.Error or Ext.data.Error or Ext.data.DataProxy.Error, etc.
     * @return {Number} batch Returns a number to uniquely identify the "batch" of saves occurring. -1 will be returned
     * if there are no items to save or the save was cancelled.
     */
    save : function() {
        if (!this.writer) {
            throw new Ext.data.Store.Error('writer-undefined');
        }

        var queue = [],
            len,
            trans,
            batch,
            data = {};
        // DESTROY:  First check for removed records.  Records in this.removed are guaranteed non-phantoms.  @see Store#remove
        if(this.removed.length){
            queue.push(['destroy', this.removed]);
        }

        // Check for modified records. Use a copy so Store#rejectChanges will work if server returns error.
        var rs = [].concat(this.getModifiedRecords());
        if(rs.length){
            // CREATE:  Next check for phantoms within rs.  splice-off and execute create.
            var phantoms = [];
            for(var i = rs.length-1; i >= 0; i--){
                if(rs[i].phantom === true){
                    var rec = rs.splice(i, 1).shift();
                    if(rec.isValid()){
                        phantoms.push(rec);
                    }
                }else if(!rs[i].isValid()){ // <-- while we're here, splice-off any !isValid real records
                    rs.splice(i,1);
                }
            }
            // If we have valid phantoms, create them...
            if(phantoms.length){
                queue.push(['create', phantoms]);
            }

            // UPDATE:  And finally, if we're still here after splicing-off phantoms and !isValid real records, update the rest...
            if(rs.length){
                queue.push(['update', rs]);
            }
        }
        len = queue.length;
        if(len){
            batch = ++this.batchCounter;
            for(var i = 0; i < len; ++i){
                trans = queue[i];
                data[trans[0]] = trans[1];
            }
            if(this.fireEvent('beforesave', this, data) !== false){
                for(var i = 0; i < len; ++i){
                    trans = queue[i];
                    this.doTransaction(trans[0], trans[1], batch);
                }
                return batch;
            }
        }
        return -1;
    },

    // private.  Simply wraps call to Store#execute in try/catch.  Defers to Store#handleException on error.  Loops if batch: false
    doTransaction : function(action, rs, batch) {
        function transaction(records) {
            try{
                this.execute(action, records, undefined, batch);
            }catch (e){
                this.handleException(e);
            }
        }
        if(this.batch === false){
            for(var i = 0, len = rs.length; i < len; i++){
                transaction.call(this, rs[i]);
            }
        }else{
            transaction.call(this, rs);
        }
    },

    // private
    addToBatch : function(batch){
        var b = this.batches,
            key = this.batchKey + batch,
            o = b[key];

        if(!o){
            b[key] = o = {
                id: batch,
                count: 0,
                data: {}
            }
        }
        ++o.count;
    },

    removeFromBatch : function(batch, action, data){
        var b = this.batches,
            key = this.batchKey + batch,
            o = b[key],
            data,
            arr;


        if(o){
            arr = o.data[action] || [];
            o.data[action] = arr.concat(data);
            if(o.count === 1){
                data = o.data;
                delete b[key];
                this.fireEvent('save', this, batch, data);
            }else{
                --o.count;
            }
        }
    },

    // @private callback-handler for remote CRUD actions
    // Do not override -- override loadRecords, onCreateRecords, onDestroyRecords and onUpdateRecords instead.
    createCallback : function(action, rs, batch) {
        var actions = Ext.data.Api.actions;
        return (action == 'read') ? this.loadRecords : function(data, response, success) {
            // calls: onCreateRecords | onUpdateRecords | onDestroyRecords
            this['on' + Ext.util.Format.capitalize(action) + 'Records'](success, rs, [].concat(data));
            // If success === false here, exception will have been called in DataProxy
            if (success === true) {
                this.fireEvent('write', this, action, data, response, rs);
            }
            this.removeFromBatch(batch, action, data);
        };
    },

    // Clears records from modified array after an exception event.
    // NOTE:  records are left marked dirty.  Do we want to commit them even though they were not updated/realized?
    // TODO remove this method?
    clearModified : function(rs) {
        if (Ext.isArray(rs)) {
            for (var n=rs.length-1;n>=0;n--) {
                this.modified.splice(this.modified.indexOf(rs[n]), 1);
            }
        } else {
            this.modified.splice(this.modified.indexOf(rs), 1);
        }
    },

    // remap record ids in MixedCollection after records have been realized.  @see Store#onCreateRecords, @see DataReader#realize
    reMap : function(record) {
        if (Ext.isArray(record)) {
            for (var i = 0, len = record.length; i < len; i++) {
                this.reMap(record[i]);
            }
        } else {
            delete this.data.map[record._phid];
            this.data.map[record.id] = record;
            var index = this.data.keys.indexOf(record._phid);
            this.data.keys.splice(index, 1, record.id);
            delete record._phid;
        }
    },

    // @protected onCreateRecord proxy callback for create action
    onCreateRecords : function(success, rs, data) {
        if (success === true) {
            try {
                this.reader.realize(rs, data);
                this.reMap(rs);
            }
            catch (e) {
                this.handleException(e);
                if (Ext.isArray(rs)) {
                    // Recurse to run back into the try {}.  DataReader#realize splices-off the rs until empty.
                    this.onCreateRecords(success, rs, data);
                }
            }
        }
    },

    // @protected, onUpdateRecords proxy callback for update action
    onUpdateRecords : function(success, rs, data) {
        if (success === true) {
            try {
                this.reader.update(rs, data);
            } catch (e) {
                this.handleException(e);
                if (Ext.isArray(rs)) {
                    // Recurse to run back into the try {}.  DataReader#update splices-off the rs until empty.
                    this.onUpdateRecords(success, rs, data);
                }
            }
        }
    },

    // @protected onDestroyRecords proxy callback for destroy action
    onDestroyRecords : function(success, rs, data) {
        // splice each rec out of this.removed
        rs = (rs instanceof Ext.data.Record) ? [rs] : [].concat(rs);
        for (var i=0,len=rs.length;i<len;i++) {
            this.removed.splice(this.removed.indexOf(rs[i]), 1);
        }
        if (success === false) {
            // put records back into store if remote destroy fails.
            // @TODO: Might want to let developer decide.
            for (i=rs.length-1;i>=0;i--) {
                this.insert(rs[i].lastIndex, rs[i]);    // <-- lastIndex set in Store#destroyRecord
            }
        }
    },

    // protected handleException.  Possibly temporary until Ext framework has an exception-handler.
    handleException : function(e) {
        throw e;
    },

    /**
     * <p>Reloads the Record cache from the configured Proxy using the configured
     * {@link Ext.data.Reader Reader} and the options from the last load operation
     * performed.</p>
     * <p><b>Note</b>: see the Important note in {@link #load}.</p>
     * @param {Object} options <p>(optional) An <tt>Object</tt> containing
     * {@link #load loading options} which may override the {@link #lastOptions options}
     * used in the last {@link #load} operation. See {@link #load} for details
     * (defaults to <tt>null</tt>, in which case the {@link #lastOptions} are
     * used).</p>
     * <br><p>To add new params to the existing params:</p><pre><code>
lastOptions = myStore.lastOptions;
Ext.apply(lastOptions.params, {
    myNewParam: true
});
myStore.reload(lastOptions);
     * </code></pre>
     */
    reload : function(options){
        this.load(Ext.applyIf(options||{}, this.lastOptions));
    },

    // private
    // Called as a callback by the Reader during a load operation.
    loadRecords : function(o, options, success){
        if (this.isDestroyed === true) {
            return;
        }
        this.options = options;

        if (options && options.add !== true
            && ((options.transactionId && this.lastTransactionId !== options.transactionId) || this.fireEvent('beforeloadrecords', o, options, success, this) === false)
        ) {
            // fire load event so loading indicator stops
            this.fireEvent('load', this, this.data.items, this.options);
            return;
        }

        if(!o || success === false){
            if(success !== false){
                this.fireEvent('load', this, [], options);
            }
            if(options.callback){
                options.callback.call(options.scope || this, [], options, false, o);
            }
            return;
        }
        var r = o.records, t = o.totalRecords || r.length;
        if(!options || options.add !== true){
            if(this.pruneModifiedRecords){
                this.modified = [];
            }
            for(var i = 0, len = r.length; i < len; i++){
                r[i].join(this);
            }
            if(this.snapshot){
                this.data = this.snapshot;
                delete this.snapshot;
            }
            this.clearData();
            this.data.addAll(r);
            this.totalLength = t;
            this.applySort();
            this.fireEvent('datachanged', this);
        }else{
            this.totalLength = Math.max(t, this.data.length+r.length);
            this.add(r);
        }
        this.fireEvent('load', this, r, options);
        if(options.callback){
            options.callback.call(options.scope || this, r, options, true);
        }
    },

    /**
     * Loads data from a passed data block and fires the {@link #load} event. A {@link Ext.data.Reader Reader}
     * which understands the format of the data must have been configured in the constructor.
     * @param {Object} data The data block from which to read the Records.  The format of the data expected
     * is dependent on the type of {@link Ext.data.Reader Reader} that is configured and should correspond to
     * that {@link Ext.data.Reader Reader}'s <tt>{@link Ext.data.Reader#readRecords}</tt> parameter.
     * @param {Boolean} append (Optional) <tt>true</tt> to append the new Records rather the default to replace
     * the existing cache.
     * <b>Note</b>: that Records in a Store are keyed by their {@link Ext.data.Record#id id}, so added Records
     * with ids which are already present in the Store will <i>replace</i> existing Records. Only Records with
     * new, unique ids will be added.
     */
    loadData : function(o, append){
        var r = this.reader.readRecords(o);
        this.loadRecords(r, {add: append}, true);
    },

    mergeData : function(o){
        const rs = this.reader.readRecords(o).records;
        rs.forEach(r => {
            this.getById(r.id)?.setData(r.data) || this.add(r);
        });
        this.data.each((r) => {
            if (! _.find(rs, {id: r.id})) {
                this.remove(r);
            }
        });
        return this;
    },

    getData : function() {
        return _.map(this.data.items, (r) => {
            return r.getData();
        })
    },

    /**
     * Gets the number of cached records.
     * <p>If using paging, this may not be the total size of the dataset. If the data object
     * used by the Reader contains the dataset size, then the {@link #getTotalCount} function returns
     * the dataset size.  <b>Note</b>: see the Important note in {@link #load}.</p>
     * @return {Number} The number of Records in the Store's cache.
     */
    getCount : function(){
        return this.data.length || 0;
    },

    /**
     * Gets the total number of records in the dataset as returned by the server.
     * <p>If using paging, for this to be accurate, the data object used by the {@link #reader Reader}
     * must contain the dataset size. For remote data sources, the value for this property
     * (<tt>totalProperty</tt> for {@link Ext.data.JsonReader JsonReader},
     * <tt>totalRecords</tt> for {@link Ext.data.XmlReader XmlReader}) shall be returned by a query on the server.
     * <b>Note</b>: see the Important note in {@link #load}.</p>
     * @return {Number} The number of Records as specified in the data object passed to the Reader
     * by the Proxy.
     * <p><b>Note</b>: this value is not updated when changing the contents of the Store locally.</p>
     */
    getTotalCount : function(){
        return this.totalLength || 0;
    },

    /**
     * Returns an object describing the current sort state of this Store.
     * @return {Object} The sort state of the Store. An object with two properties:<ul>
     * <li><b>field : String<p class="sub-desc">The name of the field by which the Records are sorted.</p></li>
     * <li><b>direction : String<p class="sub-desc">The sort order, 'ASC' or 'DESC' (case-sensitive).</p></li>
     * </ul>
     * See <tt>{@link #sortInfo}</tt> for additional details.
     */
    getSortState : function(){
        return this.sortInfo;
    },

    // private
    applySort : function(){
        if(this.sortInfo && !this.remoteSort){
            var s = this.sortInfo, f = s.field;
            this.sortData(f, s.direction);
        }
    },

    // private
    sortData : function(f, direction){
        direction = direction || 'ASC';
        var st = this.fields.get(f)?.sortType || Ext.emptyFn;
        var locale = String(Tine.Tinebase.registry.get('locale').locale).substring(0,2);
        var fn = st !== Ext.data.SortTypes.asUCString ? function(r1, r2){
            var v1 = st(r1.data[f]), v2 = st(r2.data[f]);
            return v1 > v2 ? 1 : (v1 < v2 ? -1 : 0);
        } : function(r1, r2){
            var v1 = st(r1.data[f]), v2 = st(r2.data[f]);
            return v1.localeCompare(v2,locale);
        };
        this.data.sort(direction, fn);
        if(this.snapshot && this.snapshot != this.data){
            this.snapshot.sort(direction, fn);
        }
    },

    /**
     * Sets the default sort column and order to be used by the next {@link #load} operation.
     * @param {String} fieldName The name of the field to sort by.
     * @param {String} dir (optional) The sort order, 'ASC' or 'DESC' (case-sensitive, defaults to <tt>'ASC'</tt>)
     */
    setDefaultSort : function(field, dir){
        dir = dir ? dir.toUpperCase() : 'ASC';
        this.sortInfo = {field: field, direction: dir};
        this.sortToggle[field] = dir;
    },

    /**
     * Sort the Records.
     * If remote sorting is used, the sort is performed on the server, and the cache is reloaded. If local
     * sorting is used, the cache is sorted internally. See also {@link #remoteSort} and {@link #paramNames}.
     * @param {String} fieldName The name of the field to sort by.
     * @param {String} dir (optional) The sort order, 'ASC' or 'DESC' (case-sensitive, defaults to <tt>'ASC'</tt>)
     */
    sort : function(fieldName, dir){
        var f = this.fields.get(fieldName);
        if(!f){
            return false;
        }
        if(!dir){
            if(this.sortInfo && this.sortInfo.field == f.name){ // toggle sort dir
                dir = (this.sortToggle[f.name] || 'ASC').toggle('ASC', 'DESC');
            }else{
                dir = f.sortDir;
            }
        }
        var st = (this.sortToggle) ? this.sortToggle[f.name] : null;
        var si = (this.sortInfo) ? this.sortInfo : null;

        this.sortToggle[f.name] = dir;
        this.sortInfo = {field: f.name, direction: dir};
        if(!this.remoteSort){
            this.applySort();
            this.fireEvent('datachanged', this);
        }else{
            if (!this.load(this.lastOptions)) {
                if (st) {
                    this.sortToggle[f.name] = st;
                }
                if (si) {
                    this.sortInfo = si;
                }
            }
        }
    },

    /**
     * Calls the specified function for each of the {@link Ext.data.Record Records} in the cache.
     * @param {Function} fn The function to call. The {@link Ext.data.Record Record} is passed as the first parameter.
     * Returning <tt>false</tt> aborts and exits the iteration.
     * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the function is executed.
     * Defaults to the current {@link Ext.data.Record Record} in the iteration.
     */
    each : function(fn, scope){
        this.data.each(fn, scope);
    },

    /**
     * Gets all {@link Ext.data.Record records} modified since the last commit.  Modified records are
     * persisted across load operations (e.g., during paging). <b>Note</b>: deleted records are not
     * included.  See also <tt>{@link #pruneModifiedRecords}</tt> and
     * {@link Ext.data.Record}<tt>{@link Ext.data.Record#markDirty markDirty}.</tt>.
     * @return {Ext.data.Record[]} An array of {@link Ext.data.Record Records} containing outstanding
     * modifications.  To obtain modified fields within a modified record see
     *{@link Ext.data.Record}<tt>{@link Ext.data.Record#modified modified}.</tt>.
     */
    getModifiedRecords : function(){
        return this.modified;
    },

    // private
    createFilterFn : function(property, value, anyMatch, caseSensitive){
        if(Ext.isEmpty(value, false)){
            return false;
        }
        value = this.data.createValueMatcher(value, anyMatch, caseSensitive);
        return function(r){
            return value.test(r.data[property]);
        };
    },

    /**
     * Sums the value of <tt>property</tt> for each {@link Ext.data.Record record} between <tt>start</tt>
     * and <tt>end</tt> and returns the result.
     * @param {String} property A field in each record
     * @param {Number} start (optional) The record index to start at (defaults to <tt>0</tt>)
     * @param {Number} end (optional) The last record index to include (defaults to length - 1)
     * @return {Number} The sum
     */
    sum : function(property, start, end){
        var rs = this.data.items, v = 0;
        start = start || 0;
        end = (end || end === 0) ? end : rs.length-1;

        for(var i = start; i <= end; i++){
            v += (rs[i].data[property] || 0);
        }
        return v;
    },

    /**
     * Filter the {@link Ext.data.Record records} by a specified property.
     * @param {String} field A field on your records
     * @param {String/RegExp} value Either a string that the field should begin with, or a RegExp to test
     * against the field.
     * @param {Boolean} anyMatch (optional) <tt>true</tt> to match any part not just the beginning
     * @param {Boolean} caseSensitive (optional) <tt>true</tt> for case sensitive comparison
     */
    filter : function(property, value, anyMatch, caseSensitive){
        var fn = this.createFilterFn(property, value, anyMatch, caseSensitive);
        return fn ? this.filterBy(fn) : this.clearFilter();
    },

    /**
     * Filter by a function. The specified function will be called for each
     * Record in this Store. If the function returns <tt>true</tt> the Record is included,
     * otherwise it is filtered out.
     * @param {Function} fn The function to be called. It will be passed the following parameters:<ul>
     * <li><b>record</b> : Ext.data.Record<p class="sub-desc">The {@link Ext.data.Record record}
     * to test for filtering. Access field values using {@link Ext.data.Record#get}.</p></li>
     * <li><b>id</b> : Object<p class="sub-desc">The ID of the Record passed.</p></li>
     * </ul>
     * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the function is executed. Defaults to this Store.
     */
    filterBy : function(fn, scope){
        this.snapshot = this.snapshot || this.data;
        this.data = this.queryBy(fn, scope||this);
        this.fireEvent('datachanged', this);
    },

    /**
     * Query the records by a specified property.
     * @param {String} field A field on your records
     * @param {String/RegExp} value Either a string that the field
     * should begin with, or a RegExp to test against the field.
     * @param {Boolean} anyMatch (optional) True to match any part not just the beginning
     * @param {Boolean} caseSensitive (optional) True for case sensitive comparison
     * @return {MixedCollection} Returns an Ext.util.MixedCollection of the matched records
     */
    query : function(property, value, anyMatch, caseSensitive){
        var fn = this.createFilterFn(property, value, anyMatch, caseSensitive);
        return fn ? this.queryBy(fn) : this.data.clone();
    },

    /**
     * Query the cached records in this Store using a filtering function. The specified function
     * will be called with each record in this Store. If the function returns <tt>true</tt> the record is
     * included in the results.
     * @param {Function} fn The function to be called. It will be passed the following parameters:<ul>
     * <li><b>record</b> : Ext.data.Record<p class="sub-desc">The {@link Ext.data.Record record}
     * to test for filtering. Access field values using {@link Ext.data.Record#get}.</p></li>
     * <li><b>id</b> : Object<p class="sub-desc">The ID of the Record passed.</p></li>
     * </ul>
     * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the function is executed. Defaults to this Store.
     * @return {MixedCollection} Returns an Ext.util.MixedCollection of the matched records
     **/
    queryBy : function(fn, scope){
        var data = this.snapshot || this.data;
        return data.filterBy(fn, scope||this);
    },

    /**
     * Finds the index of the first matching Record in this store by a specific field value.
     * @param {String} fieldName The name of the Record field to test.
     * @param {String/RegExp} value Either a string that the field value
     * should begin with, or a RegExp to test against the field.
     * @param {Number} startIndex (optional) The index to start searching at
     * @param {Boolean} anyMatch (optional) True to match any part of the string, not just the beginning
     * @param {Boolean} caseSensitive (optional) True for case sensitive comparison
     * @return {Number} The matched index or -1
     */
    find : function(property, value, start, anyMatch, caseSensitive){
        var fn = this.createFilterFn(property, value, anyMatch, caseSensitive);
        return fn ? this.data.findIndexBy(fn, null, start) : -1;
    },

    /**
     * Finds the index of the first matching Record in this store by a specific field value.
     * @param {String} fieldName The name of the Record field to test.
     * @param {Mixed} value The value to match the field against.
     * @param {Number} startIndex (optional) The index to start searching at
     * @return {Number} The matched index or -1
     */
    findExact: function(property, value, start){
        return this.data.findIndexBy(function(rec){
            return rec.get(property) === value;
        }, this, start);
    },

    /**
     * Find the index of the first matching Record in this Store by a function.
     * If the function returns <tt>true</tt> it is considered a match.
     * @param {Function} fn The function to be called. It will be passed the following parameters:<ul>
     * <li><b>record</b> : Ext.data.Record<p class="sub-desc">The {@link Ext.data.Record record}
     * to test for filtering. Access field values using {@link Ext.data.Record#get}.</p></li>
     * <li><b>id</b> : Object<p class="sub-desc">The ID of the Record passed.</p></li>
     * </ul>
     * @param {Object} scope (optional) The scope (<code>this</code> reference) in which the function is executed. Defaults to this Store.
     * @param {Number} startIndex (optional) The index to start searching at
     * @return {Number} The matched index or -1
     */
    findBy : function(fn, scope, start){
        return this.data.findIndexBy(fn, scope, start);
    },

    /**
     * Collects unique values for a particular dataIndex from this store.
     * @param {String} dataIndex The property to collect
     * @param {Boolean} allowNull (optional) Pass true to allow null, undefined or empty string values
     * @param {Boolean} bypassFilter (optional) Pass true to collect from all records, even ones which are filtered
     * @return {Array} An array of the unique values
     **/
    collect : function(dataIndex, allowNull, bypassFilter){
        var d = (bypassFilter === true && this.snapshot) ?
                this.snapshot.items : this.data.items;
        var v, sv, r = [], l = {};
        for(var i = 0, len = d.length; i < len; i++){
            v = d[i].data[dataIndex];
            sv = String(v);
            if((allowNull || !Ext.isEmpty(v)) && !l[sv]){
                l[sv] = true;
                r[r.length] = v;
            }
        }
        return r;
    },

    /**
     * Revert to a view of the Record cache with no filtering applied.
     * @param {Boolean} suppressEvent If <tt>true</tt> the filter is cleared silently without firing the
     * {@link #datachanged} event.
     */
    clearFilter : function(suppressEvent){
        if(this.isFiltered()){
            this.data = this.snapshot;
            delete this.snapshot;
            if(suppressEvent !== true){
                this.fireEvent('datachanged', this);
            }
        }
    },

    /**
     * Returns true if this store is currently filtered
     * @return {Boolean}
     */
    isFiltered : function(){
        return this.snapshot && this.snapshot != this.data;
    },

    // private
    afterEdit : function(record){
        if(this.modified.indexOf(record) == -1){
            this.modified.push(record);
        }
        this.fireEvent('update', this, record, Ext.data.Record.EDIT);
    },

    // private
    afterReject : function(record){
        this.modified.remove(record);
        this.fireEvent('update', this, record, Ext.data.Record.REJECT);
    },

    // private
    afterCommit : function(record){
        this.modified.remove(record);
        this.fireEvent('update', this, record, Ext.data.Record.COMMIT);
    },

    /**
     * Commit all Records with {@link #getModifiedRecords outstanding changes}. To handle updates for changes,
     * subscribe to the Store's {@link #update update event}, and perform updating when the third parameter is
     * Ext.data.Record.COMMIT.
     */
    commitChanges : function(){
        var m = this.modified.slice(0);
        this.modified = [];
        for(var i = 0, len = m.length; i < len; i++){
            m[i].commit();
        }
    },

    /**
     * {@link Ext.data.Record#reject Reject} outstanding changes on all {@link #getModifiedRecords modified records}.
     */
    rejectChanges : function(){
        var m = this.modified.slice(0);
        this.modified = [];
        for(var i = 0, len = m.length; i < len; i++){
            m[i].reject();
        }
        var m = this.removed.slice(0).reverse();
        this.removed = [];
        for(var i = 0, len = m.length; i < len; i++){
            this.insert(m[i].lastIndex||0, m[i]);
            m[i].reject();
        }
    },

    // private
    onMetaChange : function(meta){
        this.recordType = this.reader.recordType;
        this.fields = this.recordType.prototype.fields;
        delete this.snapshot;
        if(this.reader.meta.sortInfo){
            this.sortInfo = this.reader.meta.sortInfo;
        }else if(this.sortInfo  && !this.fields.get(this.sortInfo.field)){
            delete this.sortInfo;
        }
        if(this.writer){
            this.writer.meta = this.reader.meta;
        }
        this.modified = [];
        this.fireEvent('metachange', this, this.reader.meta);
    },

    // private
    findInsertIndex : function(record){
        this.suspendEvents();
        var data = this.data.clone();
        this.data.add(record);
        this.applySort();
        var index = this.data.indexOf(record);
        this.data = data;
        this.resumeEvents();
        return index;
    },

    /**
     * Set the value for a property name in this store's {@link #baseParams}.  Usage:</p><pre><code>
myStore.setBaseParam('foo', {bar:3});
</code></pre>
     * @param {String} name Name of the property to assign
     * @param {Mixed} value Value to assign the <tt>name</tt>d property
     **/
    setBaseParam : function (name, value){
        this.baseParams = this.baseParams || {};
        this.baseParams[name] = value;
    },

    getClone: function() {
        const clone = new Ext.data.Store({
           fields: this.fields,
            // data: this.data.clone().items,
           // proxy: this.proxy,
           replaceRecord: function(o, n) {
               var r = this.getById(o.id); // refetch record as it might be outdated in the meantime
               var idx = this.indexOf(r);
               this.remove(r);
               this.insert(idx, n);
           }
        });
        clone.data = this.data.clone();
        return clone;
    }
});

Ext.reg('store', Ext.data.Store);

/**
 * @class Ext.data.Store.Error
 * @extends Ext.Error
 * Store Error extension.
 * @param {String} name
 */
Ext.data.Store.Error = Ext.extend(Ext.Error, {
    name: 'Ext.data.Store'
});
Ext.apply(Ext.data.Store.Error.prototype, {
    lang: {
        'writer-undefined' : 'Attempted to execute a write-action without a DataWriter installed.'
    }
});
