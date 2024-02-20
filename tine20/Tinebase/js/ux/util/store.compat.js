/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * compatibility plugin for store to replace Ext.util.MixedCollection in registry & preferences
 */
;(function(store, _) {
    var _set = _.set,
        _remove = _.remove,
        _clear = _.clear,
        _get = _.get,
        _on = _.storeAPI.on,
        _isPrefExp = /\.preferences$/;

    _.stringify = function(d) {
        return d === undefined || typeof d === "function" ? d+'' : Ext.encode(d);
    };
    
    _.parse = function(s) {
        // if it doesn't parse, return as is
        try{ return Ext.decode(s); }catch(e){ return s; }
    };

    _.containsKey = function(key) {
        if (key && key.match(_isPrefExp)) {
            var parts = key.split('.');
            return !! Tine[parts[parts.length-3]]['preferences'];
        }

        return this.has(key);
    };

    _.get = function(area, key) {
        if (key && key.match(_isPrefExp)) {
            var parts = key.split('.');
            return Tine[parts[parts.length-3]]['preferences'];
        }
        return _get.apply(this, arguments);
    };

    /**
     * NOTE as localStorage don't sends events for own window, we need to
     * intercept store write options
     */
    _.set = function(area, key, string) {
        try {
            const oldValue = this.get(area, key);
            const ret = _set.apply(this, arguments);
            this.fireEvent(key, oldValue, string);
            return ret;
        } catch (e) {
            const storageSizeData = getLocalStorageSize();
            if (e instanceof DOMException && (
                e.name === 'QuotaExceededError' ||
                e.name === 'NS_ERROR_DOM_QUOTA_REACHED'
            )) {
                // Handle quota exceeded error
                console.error('LocalStorage quota exceeded');
                console.log(storageSizeData);
                
                localStorage.clear();
            } else {
                console.error('Error storing data:', e);
            }
            return e;
        }
    };

    _.remove = function(area, key) {
        var oldValue = this.get(area, key),
            ret = _remove.apply(this, arguments);

        this.fireEvent(key, oldValue, undefined);

        return ret;
    };

    _.clear = function(area) {
        // we come here without on clear without namespace only
        // hence no need to implement now
    };

    /**
     * fire a simulated storage event
     */
    _.fireEvent = function(key, oldValue, newValue) {
        if (Ext.isIE8 || Ext.isIE9) {
            // IEs fire same window events on their own
            return;
        }

        var event;
        if (document.createEvent) {
            event = document.createEvent('StorageEvent');
            event.initStorageEvent('storage', true, true, key, oldValue, newValue, window.location.href, window.localStorage);

            return dispatchEvent(event);
        } else {
            // IE < 9?
            event = document.createEventObject();
            event.eventType = "storage";
            //event.eventName = "storage";
            event.key = key;
            event.newValue = newValue;
            event.oldValue = oldValue;

            return document.fireEvent("onstorage", event);
        }
    };

    /**
     * NOTE: in Ext.util.MixedCollection you can't register for specific keys
     *       so we add this capability as a fours parameter
     * NOTE: we only support the replace event yet and do no mapping computations
     */
    _.fn('on', function(event, fn, scope, key) {

        // Ext.util.MixedCollection
        if (['clear','add','replace','remove','sort'].indexOf(event) >= 0) {
            if (event != 'replace') {
                throw new Ext.Error('event ' + event + ' not implemented in store.compat');
            }

            return _on.call(this, key, function(e) {
                if (!key || key == e.key) {
                    if (e.oldValue != e.newValue) {
                        return fn.call(scope||window, e.key, e.oldValue, e.newValue);
                    }
                }
            });
        }

        // fallback
        return _on.call(this, event, fn);
    });

    _.fn('add', _.storeAPI.set);
    _.fn('replace', _.storeAPI.set);
    _.fn('containsKey', _.containsKey);
    
    const getLocalStorageSize = () => {
        let total = 0;
        const bigItems = [];
        for (let x in localStorage) {
            // Value is multiplied by 2 due to data being stored in `utf-16` format, which requires twice the space.
            const amount = (localStorage[x].length * 2) / 1024 / 1024;
            if (!isNaN(amount) && localStorage.hasOwnProperty(x)) {
                // console.log(x, localStorage.getItem(x), amount);
                total += amount;
                if (amount > 0.5) bigItems.push({key: x, size: amount});
            }
        }
        return {
            total: total.toFixed(2),
            bigItems: bigItems
        };
    };

})(window.store, window.store._);
