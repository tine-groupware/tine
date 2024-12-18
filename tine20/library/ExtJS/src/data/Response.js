/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.data.Response
 * A generic response class to normalize response-handling internally to the framework.
 */
const Response = function(params) {
    Object.assign(this, params);
};
Response.prototype = {
    /**
     * @cfg {String} action {@link Ext.data.Api#actions}
     */
    action: undefined,
    /**
     * @cfg {Boolean} success
     */
    success : undefined,
    /**
     * @cfg {String} message
     */
    message : undefined,
    /**
     * @cfg {Array/Object} data
     */
    data: undefined,
    /**
     * @cfg {Object} raw The raw response returned from server-code
     */
    raw: undefined,
    /**
     * @cfg {Ext.data.Record/Ext.data.Record[]} records related to the Request action
     */
    records: undefined
};

module.exports = Response;