Object.assign(Array.prototype, {
    /**
     * Returns an object with ids of records to delete, to create or to update
     *
     * @param {Array} toCompare Array to compare with
     * @return object An object with sub array properties 'toDelete', 'toCreate' and 'toUpdate'
     */
    getMigration: function(toCompare) {
        return {
            toDelete: this.diff(toCompare),
            toCreate: toCompare.diff(this),
            toUpdate: this.intersect(toCompare)
        };
    },
    
    /**
     * Returns an array containing all the entries from this array that are not present in any of the other arrays.
     * 
     * @param {Array} array1
     * @param {Array} [array2]
     * @param {Array} [...]
     */
    diff: function() {
        var allItems = [],
            diffs = [];
        
        // create an array containing all items of all args
        for (var i=0; i<arguments.length; i++) {
            allItems = allItems.concat(arguments[i]);
        }
        
        // check which item is not present in all args
        this.forEach((item) => {
            if (allItems.indexOf(item) < 0) {
                diffs.push(item);
            }
        });
        
        
        return diffs;
    },
    
    /**
     * simple map fn
     * 
     * @param {Function} fn
     */
    map: function(fn) {
        var map = [];
        this.forEach((v, i) => {
            map.push(fn.call(this, v, i));
        });
        
        return map;
    },
    
    /**
     * returns an array containing all the values of this array that are present in all the arguments.
     * 
     * @param {Array} array1
     * @param {Array} [array2]
     * @param {Array} [...]
     */
    intersect: function() {
        var allItems = [],
            intersect = [];
        
        // create an array containing all items of all args
        for (var i=0; i<arguments.length; i++) {
            allItems = allItems.concat(arguments[i]);
        }
        
        // check which item is not present in all args
        this.forEach((item) => {
            if (allItems.indexOf(item) >= 0) {
                intersect.push(item);
            }
        });
        
        
        return intersect;
    },
    
    /**
     * Creates a copy of this Array, filtered to contain only unique values.
     * @return {Array} The new Array containing unique values.
     */
    unique: function() {
        return Ext.unique(this);
    },

    /**
     * async version of forEach
     * @param callback
     * @return {Promise<void>}
     */
    asyncForEach: async function (callback) {
        for (let index = 0; index < this.length; index++) {
            await callback(this[index], index, this);
        }
    }
});
