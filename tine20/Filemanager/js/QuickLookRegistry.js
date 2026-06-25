/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import * as async from 'async'

Ext.ns('Tine.Filemanager');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.QuickLookRegistry
 * @singleton
 *
 */
Tine.Filemanager.QuickLookRegistry = function() {
    return {
        negotiationDefaults: {
            prio: 50,
            label: null, // defaults to number
            iconCls: null
        },

        /**
         * registers a handler by xtype and config
         * each contentType can have multiple handlers, the one with the highest prio is used. other handlers with lower prio can be selected by the user.
         * prio can be changed on runtime with the negotiate function (static function of the registered class)
         *
         * @param {String} contentType
         * @param {String} xtype panel xtype
         * @param {Object} negotiationConfig
         */
        registerContentType: function(contentType, xtype, negotiationConfig) {
            this.register('contentType', contentType, xtype, negotiationConfig);
        },

        /**
         * registers a handler
         *
         * @param {String} extension
         * @param {String} xtype panel xtype
         * @param {Object} negotiationConfig
         */
        registerExtension: function(extension, xtype, negotiationConfig) {
            this.register('extension', extension, xtype, negotiationConfig);
        },

        /**
         * registers a handler
         *
         * @param {String} type
         * @param {String} key
         * @param {String} value
         * @param {Object} negotiationConfig
         */
        register: function(type, key, value, negotiationConfig) {
            this.initItems();
            const id = {[type]: key, xtype: value}
            if (! _.find(this.items, id)) {
                this.items.push(Object.assign({}, negotiationConfig, id))
            }
            Tine.Filemanager.registry.set('quickLookRegistry', this.items);
        },

        /**
         * returns a xtype for a contentType
         *
         * @param {String} contentType
         * @param {Tinebase.FileLocation} fileLocation
         * @return {String} xtype
         */
        getByContentType: async function(contentType, fileLocation) {
            return this.get('contentType', contentType, fileLocation);
        },

        /**
         * returns a xtype
         *
         * @param {String} extension
         * @param {Tinebase.FileLocation} fileLocation
         * @return {String} xtype
         */
        getByExtension: async function(extension, fileLocation) {
            return this.get('extension', extension, fileLocation);
        },

        /**
         * returns ordered registrations for a key
         *
         * @param {String} type
         * @param {String} key
         * @param {Tinebase.FileLocation} fileLocation
         * @return {config}[] registrations
         */
        get: async function(type, key, fileLocation) {
            this.initItems();
            return _.each(_.orderBy(await async.reduce(_.filter(this.items, {[type]: key}), [], async (memo, item) => {
                const Panel = _.get(Ext, `ComponentMgr.types['${item.xtype}']`)
                const defaults = _.each(this.negotiationDefaults, (value, key) => item[key] = _.get(Panel, key, value))
                const negotiationConfig = await _.get(Panel, `negotiate`)?.(fileLocation, item) || {}
                const config = Object.assign({...item}, defaults, negotiationConfig)
                return memo.concat(config.prio > 0 ? config : [])
            }), ['prio'], ['desc']), (item, idx) => item.label = item.label || String(idx));
        },

        /**
         * checks if an extension item has been registered already
         *
         * @param {String} extension
         * @param {String} xtype panel xtype
         */
        hasExtension: function(extension) {
            return this.has('extension', extension);
        },

        /**
         * checks if an item has been registered already
         *
         * @param {String} contentType
         * @return {Bool}
         */
        hasContentType: function(contentType) {
            return this.has('contentType', contentType);
        },

        /**
         * checks if an item has been registered already
         *
         * @param {String} type
         * @param {String} key
         * @return {Bool}
         */
        has: function(type, key) {
            this.initItems();
            return !!_.filter(this.items, {[type]: key}).length;
        },

        /**
         * fetch items from Filemanager registry
         */
        initItems: function() {
            if (! this.items) {
                this.items = Tine.Filemanager.registry.get('quickLookRegistry') || [];
            }
        }
    }
}();
