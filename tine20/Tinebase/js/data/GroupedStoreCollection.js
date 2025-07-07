/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import recordMgr from './RecordMgr'
import Record from './Record'
import asString from "../../../Tinebase/js/ux/asString"

Ext.ns('Tine.Tinebase.data');

/**
 * @namespace   Tine.Tinebase.data
 * @class       Tine.Tinebase.data.GroupedStoreCollection
 * @extends     Ext.util.MixedCollection
 *
 * grouping store collection
 *
 * automatically manages group stores
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 * @param {Object} config
 */
Tine.Tinebase.data.GroupedStoreCollection = function(config) {
    this.fixedGroups = [];
    Ext.apply(this, config);
    Tine.Tinebase.data.GroupedStoreCollection.superclass.constructor.call(this);

    this.store.on('beforeload', this.onStoreBeforeLoad, this);
    this.store.on('load', this.onStoreLoad, this);
    this.store.on('add', this.onStoreAdd, this);
    this.store.on('update', this.onStoreUpdate, this);
    this.store.on('remove', this.onStoreRemove, this);

    if (this.group) {
        this.applyGrouping();
    }
};

Ext.extend(Tine.Tinebase.data.GroupedStoreCollection, Ext.util.MixedCollection, {
    /**
     * @cfg {Ext.data.Store} store
     */
    store: null,

    /**
     * @cfg {String|Function} group
     */
    group: '',

    /**
     * @cfg {Array} fixedGroups
     * if present, this is the fixed set of groups
     */
    fixedGroups: null,

    /**
     * @cfg {Bool} groupOnLoad
     * apply grouping when store is loaded
     * NOTE: when disabled, grouping must be triggered manually
     */
    groupOnLoad: true,

    applyGrouping: function() {
        if (this.fixedGroups.length) {
            this.setFixedGroups(this.fixedGroups)
        }

        this.groupRecords(this.store.getRange(), false);
    },

    groupBy: function(group) {
        this.group = group;
        this.applyGrouping();
    },

    groupRecords: async function(rs, append) {
        // put data into groups
        var groups = [];
        var records = [];
        await [].concat(rs).asyncForEach(async (r) => {
            var groupNames = await this.getGroupNames(r);

            Ext.each(groupNames, function(groupName) {
                var idx = groups.indexOf(groupName);
                if (idx < 0) {
                    groups.push(groupName);
                    records.push([r.copy()]);
                } else {
                    records[idx].push(r.copy());
                }
            });
        }, this);

        // collection housekeeping
        if (! this.fixedGroups.length) {
            Ext.each(this.keys.concat(), function (groupName) {
                if (groups.indexOf(groupName) < 0) {
                    this.removeKey(groupName);
                }
            }, this);
        }

        if (! append) {
            // clear stores which have no longer data
            this.eachKey(function (groupName) {
                if (groups.indexOf(groupName) < 0) {
                    var store = this.getCloneStore(groupName);
                    store.loadRecords({records: []}, {add: append}, true);
                }
            }, this);
        }

        Ext.each(groups, function(groupName, idx) {
            var store = this.getCloneStore(groupName);
            // do we need a beforeload event here?
            store.loadRecords({records: records[idx]}, {add: append}, true);
        }, this);
    },

    setFixedGroups: async function(groupNames) {
        groupNames = await this.sanitizeGroupNames(groupNames);

        this.fixedGroups = groupNames;
        if (groupNames.length) {
            this.setGroups(groupNames);
        }
        this.groupRecords(this.store.getRange(), false);
    },

    // add/delete cloneStores
    setGroups: function(groupNames) {
        Ext.each(this.keys.concat(), function(groupName) {
            if (groupNames.indexOf(groupName) < 0) {
                this.removeKey(groupName);
            }
        }, this);

        Ext.each(groupNames, function(groupName, idx) {
            var store = this.get(groupName);
            if (! store) {
                store = this.createCloneStore();
                this.addSorted(groupName, store);
            }
        }, this);
    },

    /**
     * gets groups of given record
     *
     * @param {Ext.data.record} record
     * @returns {Array}
     */
    getGroupNames: async function(record) {
        var _ = window.lodash,
            groupNames = Ext.isFunction(this.group) ? this.group(record) : record.get(this.group);

        if (! Ext.isArray(groupNames)) {
            groupNames = [groupNames];
        }

        groupNames = groupNames.map((groupName) => {
            if (_.isObject(groupName) && ! _.isFunction(groupName.getTitle) && _.isString(this.group)) {
                let recordClass;
                if(this.group.match(/^#/)) {
                    const conf = Tine.widgets.customfields.ConfigManager.getConfig(record.constructor.getMeta('appName'), record.constructor.getMeta('modelName'), this.group.replace('#', ''));
                    recordClass = recordMgr.get(_.get(conf, 'data.definition.recordConfig.value.records'));
                } else {
                    const conf = _.get(record.constructor.getField(this.group), 'fieldDefinition.config');
                    recordClass = recordMgr.get(_.get(conf, 'appName'), _.get(conf, 'modelName'))
                }

                if (recordClass) {
                    return Record.setFromJson(groupName, recordClass)
                }
            }
            return groupName;
        })
        groupNames = await this.sanitizeGroupNames(groupNames);

        if (this.fixedGroups.length) {
            groupNames = _.intersection(groupNames, this.fixedGroups);
        }

        return groupNames;
    },

    sanitizeGroupNames: async function(groupNames) {
        groupNames = await Promise.all(groupNames.map((groupName) => {
            if (! _.isObject(groupName)) return groupName;
            if (_.isFunction(groupName.getTitle)) return asString(groupName.getTitle());
            if (_.isString(this.group) && this.store.recordClass) return Tine.widgets.grid.RendererManager.get(
                this.store.recordClass.getMeta('appName'),
                this.store.recordClass.getMeta('modelName'),
                this.group
            )(groupName).asString();
        }));

        if (_.remove(groupNames, (v) => {return [null, undefined, false, Infinity, NaN].indexOf(v) >= 0}).length) {
            groupNames.push('');
        }

        return groupNames
    },

    onStoreBeforeLoad: function(store, options) {
        var ret = true;
        this.eachKey(function(groupName) {
            var store = this.get(groupName);
            ret = ret && store.fireEvent('beforeload', store, options);
        }, this);

        return ret;
    },

    onStoreLoad: function() {
        if (this.groupOnLoad) {
            this.applyGrouping();
        }
    },

    onStoreAdd: async function (store, records, index) {
        const suspendCloneStoreEvents = this.suspendCloneStoreEvents;
        this.suspendCloneStoreEvents = true;

        await [].concat(records).asyncForEach(async (record) => {
            var groupNames = await this.getGroupNames(record);

            Ext.each(groupNames, function (groupName) {
                var store = this.get(groupName),
                    existingRecord = store && record.id != 0 ? store.getById(record.id) : null;

                // NOTE: record might be existing as it was added to a cloneStore
                if (existingRecord) {
                    this.getCloneStore(groupName).replaceRecord(existingRecord, record.copy());
                } else {
                    this.getCloneStore(groupName).add([record.copy()]);
                }
            }, this);
        });

        this.suspendCloneStoreEvents = suspendCloneStoreEvents;
    },

    onStoreUpdate: async function(store, record, operation) {
        const suspendCloneStoreEvents = this.suspendCloneStoreEvents;
        this.suspendCloneStoreEvents = true;

        var groupNames = await this.getGroupNames(record);
        this.eachKey(function(groupName) {
            var store = this.get(groupName),
                existingRecord = store.getById(record.id);

            if (existingRecord) {
                if (groupNames.indexOf(groupName) < 0) {
                    store.remove(existingRecord);
                } else {
                    store.replaceRecord(existingRecord, record.copy());
                }

            } else {
                store.add([record.copy()]);
            }
            groupNames.remove(groupName);
        }, this);

        Ext.each(groupNames, function(groupName) {
            var store = this.getCloneStore(groupName);
            store.add(record.copy());
        }, this);

        this.suspendCloneStoreEvents = suspendCloneStoreEvents;
    },

    onStoreRemove: function(store, record, index) {
        const suspendCloneStoreEvents = this.suspendCloneStoreEvents;
        this.suspendCloneStoreEvents = true;

        this.eachKey(function(groupName) {
            var store = this.get(groupName),
                existingRecord = store.getById(record.id);

            if (existingRecord) {
                store.remove(existingRecord);
            }

        }, this);

        this.suspendCloneStoreEvents = suspendCloneStoreEvents;
    },

    getCloneStore: function(groupName) {
        var store = this.get(groupName);
        if (! store && !this.fixedGroups.length) {
            store = this.createCloneStore();
            this.addSorted(groupName, store);
        }

        return store;
    },

    addSorted: function(groupName, store) {
        var idx = this.length;

        if (this.sortFn) {
            var items = [store].concat(this.items);
            items.sort(this.sortFn);
            idx = items.indexOf(store);
        }

        this.insert(idx, groupName, store);
    },

    createCloneStore: function() {
        var clone = new Ext.data.Store({
            fields: this.store.fields,
            // load: this.mainStore.load.createDelegate(this.mainStore),
            // proxy: this.store.proxy,
            replaceRecord: function(o, n) {
                var r = this.getById(o.id); // refetch record as it might be outdated in the meantime
                var idx = this.indexOf(r);
                this.remove(r);
                this.insert(idx, n);
            }
        });

        clone.on('add', this.onCloneStoreAdd, this);
        clone.on('update', this.onCloneStoreUpdate, this);
        clone.on('remove', this.onCloneStoreRemove, this);
        return clone;
    },

    onCloneStoreAdd: function(eventStore, rs) {
        if (this.suspendCloneStoreEvents) return;

        const method = this.store.remoteSort ? 'add' : 'addSorted';
        Ext.each(rs, function(r) {
            this.store[method](r.copy());
        }, this);
    },

    onCloneStoreUpdate: function(eventStore, r) {
        if (this.suspendCloneStoreEvents) return;

        var existingRecord = this.store.getById(r.id);

        if (existingRecord) {
            this.store.replaceRecord(existingRecord, r.copy());
        }
    },

    onCloneStoreRemove: function(store, r) {
        if (this.suspendCloneStoreEvents) return;

        var existingRecord = this.store.getById(r.id);

        if (existingRecord) {
            this.store.remove(existingRecord);
        }
    }
});
