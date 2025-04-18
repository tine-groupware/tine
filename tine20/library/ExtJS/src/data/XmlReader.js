/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.data.XmlReader
 * @extends Ext.data.DataReader
 * <p>Data reader class to create an Array of {@link Ext.data.Record} objects from an XML document
 * based on mappings in a provided {@link Ext.data.Record} constructor.</p>
 * <p><b>Note</b>: that in order for the browser to parse a returned XML document, the Content-Type
 * header in the HTTP response must be set to "text/xml" or "application/xml".</p>
 * <p>Example code:</p>
 * <pre><code>
var Employee = Ext.data.Record.create([
   {name: 'name', mapping: 'name'},     // "mapping" property not needed if it is the same as "name"
   {name: 'occupation'}                 // This field will use "occupation" as the mapping.
]);
var myReader = new Ext.data.XmlReader({
   totalProperty: "results", // The element which contains the total dataset size (optional)
   record: "row",           // The repeated element which contains row information
   idProperty: "id"         // The element within the row that provides an ID for the record (optional)
   messageProperty: "msg"   // The element within the response that provides a user-feedback message (optional)
}, Employee);
</code></pre>
 * <p>
 * This would consume an XML file like this:
 * <pre><code>
&lt;?xml version="1.0" encoding="UTF-8"?>
&lt;dataset>
 &lt;results>2&lt;/results>
 &lt;row>
   &lt;id>1&lt;/id>
   &lt;name>Bill&lt;/name>
   &lt;occupation>Gardener&lt;/occupation>
 &lt;/row>
 &lt;row>
   &lt;id>2&lt;/id>
   &lt;name>Ben&lt;/name>
   &lt;occupation>Horticulturalist&lt;/occupation>
 &lt;/row>
&lt;/dataset>
</code></pre>
 * @cfg {String} totalProperty The DomQuery path from which to retrieve the total number of records
 * in the dataset. This is only needed if the whole dataset is not passed in one go, but is being
 * paged from the remote server.
 * @cfg {String} record The DomQuery path to the repeated element which contains record information.
 * @cfg {String} record The DomQuery path to the repeated element which contains record information.
 * @cfg {String} successProperty The DomQuery path to the success attribute used by forms.
 * @cfg {String} idPath The DomQuery path relative from the record element to the element that contains
 * a record identifier value.
 * @constructor
 * Create a new XmlReader.
 * @param {Object} meta Metadata configuration options
 * @param {Object} recordType Either an Array of field definition objects as passed to
 * {@link Ext.data.Record#create}, or a Record constructor object created using {@link Ext.data.Record#create}.
 */
Ext.data.XmlReader = function(meta, recordType){
    meta = meta || {};

    // backwards compat, convert idPath or id / success
    Ext.applyIf(meta, {
        idProperty: meta.idProperty || meta.idPath || meta.id,
        successProperty: meta.successProperty || meta.success
    });

    Ext.data.XmlReader.superclass.constructor.call(this, meta, recordType || meta.fields);
};
Ext.extend(Ext.data.XmlReader, Ext.data.DataReader, {
    readerType: 'xml',

    /**
     * This method is only used by a DataProxy which has retrieved data from a remote server.
     * @param {Object} response The XHR object which contains the parsed XML document.  The response is expected
     * to contain a property called <tt>responseXML</tt> which refers to an XML document object.
     * @return {Object} records A data block which is used by an {@link Ext.data.Store} as
     * a cache of Ext.data.Records.
     */
    read : function(response){
        var doc = response.responseXML;
        if(!doc) {
            throw {message: "XmlReader.read: XML Document not available"};
        }
        return this.readRecords(doc);
    },

    /**
     * Create a data block containing Ext.data.Records from an XML document.
     * @param {Object} doc A parsed XML document.
     * @return {Object} records A data block which is used by an {@link Ext.data.Store} as
     * a cache of Ext.data.Records.
     */
    readRecords : function(doc){
        /**
         * After any data loads/reads, the raw XML Document is available for further custom processing.
         * @type XMLDocument
         */
        this.xmlData = doc;

        var root    = doc.documentElement || doc,
            q       = Ext.DomQuery,
            totalRecords = 0,
            success = true;

        if(this.meta.totalProperty){
            totalRecords = this.getTotal(root, 0);
        }
        if(this.meta.successProperty){
            success = this.getSuccess(root);
        }

        var records = this.extractData(q.select(this.meta.record, root), true); // <-- true to return Ext.data.Record[]

        // TODO return Ext.data.Response instance.  @see #readResponse
        return {
            success : success,
            records : records,
            totalRecords : totalRecords || records.length
        };
    },

    /**
     * Decode a json response from server.
     * @param {String} action [{@link Ext.data.Api#actions} create|read|update|destroy]
     * @param {Object} response HTTP Response object from browser.
     * @return {Ext.data.Response} response Returns an instance of {@link Ext.data.Response}
     */
    readResponse : function(action, response) {
        var q   = Ext.DomQuery,
        doc     = response.responseXML;

        // create general Response instance.
        var res = new Ext.data.Response({
            action: action,
            success : this.getSuccess(doc),
            message: this.getMessage(doc),
            data: this.extractData(q.select(this.meta.record, doc) || q.select(this.meta.root, doc), false),
            raw: doc
        });

        if (Ext.isEmpty(res.success)) {
            throw new Ext.data.DataReader.Error('successProperty-response', this.meta.successProperty);
        }

        // Create actions from a response having status 200 must return pk
        if (action === Ext.data.Api.actions.create) {
            var def = Ext.isDefined(res.data);
            if (def && Ext.isEmpty(res.data)) {
                throw new Ext.data.JsonReader.Error('root-empty', this.meta.root);
            }
            else if (!def) {
                throw new Ext.data.JsonReader.Error('root-undefined-response', this.meta.root);
            }
        }
        return res;
    },

    getSuccess : function() {
        return true;
    },

    /**
     * build response-data extractor functions.
     * @private
     * @ignore
     */
    buildExtractors : function() {
        if(this.ef){
            return;
        }
        var s       = this.meta,
            Record  = this.recordType,
            f       = Record.prototype.fields,
            fi      = f.items,
            fl      = f.length;

        if(s.totalProperty) {
            this.getTotal = this.createAccessor(s.totalProperty);
        }
        if(s.successProperty) {
            this.getSuccess = this.createAccessor(s.successProperty);
        }
        if (s.messageProperty) {
            this.getMessage = this.createAccessor(s.messageProperty);
        }
        this.getRoot = function(res) {
            return (!Ext.isEmpty(res[this.meta.record])) ? res[this.meta.record] : res[this.meta.root];
        }
        if (s.idPath || s.idProperty) {
            var g = this.createAccessor(s.idPath || s.idProperty);
            this.getId = function(rec) {
                var id = g(rec) || rec.id;
                return (id === undefined || id === '') ? null : id;
            };
        } else {
            this.getId = function(){return null;};
        }
        var ef = [];
        for(var i = 0; i < fl; i++){
            f = fi[i];
            var map = (f.mapping !== undefined && f.mapping !== null) ? f.mapping : f.name;
            ef.push(this.createAccessor(map));
        }
        this.ef = ef;
    },

    /**
     * Creates a function to return some particular key of data from a response.
     * @param {String} key
     * @return {Function}
     * @private
     * @ignore
     */
    createAccessor : function(){
        var q = Ext.DomQuery;
        return function(key) {
            switch(key) {
                case this.meta.totalProperty:
                    return function(root, def){
                        return q.selectNumber(key, root, def);
                    }
                    break;
                case this.meta.successProperty:
                    return function(root, def) {
                        var sv = q.selectValue(key, root, true);
                        var success = sv !== false && sv !== 'false';
                        return success;
                    }
                    break;
                default:
                    return function(root, def) {
                        return q.selectValue(key, root, def);
                    }
                    break;
            }
        };
    }(),

    /**
     * extracts values and type-casts a row of data from server, extracted by #extractData
     * @param {Hash} data
     * @param {Ext.data.Field[]} items
     * @param {Number} len
     * @private
     * @ignore
     */
    extractValues : function(data, items, len) {
        var f, values = {};
        for(var j = 0; j < len; j++){
            f = items[j];
            var v = this.ef[j](data);
            values[f.name] = f.convert((v !== undefined) ? v : f.defaultValue, data);
        }
        return values;
    }
});