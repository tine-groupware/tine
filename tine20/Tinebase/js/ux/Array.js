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
     * taken from https://github.com/lovasoa/fast_array_intersect
     *
     * @param {Array} array1
     * @param {Array} [array2]
     * @param {Array} [...]
     */
    intersect: function() {
        const arrays = arguments
        if (arrays.length === 0) return [];
        arrays[arrays.length] = this;
        arrays.length = arrays.length+1;

        // Put the smallest array in the beginning
        for (let i=1; i<arrays.length; i++) {
            if(arrays[i].length < arrays[0].length) {
                let tmp = arrays[0];
                arrays[0] = arrays[i];
                arrays[i] = tmp;
            }
        }

        // Create a map associating each element to its current count
        const set = new Map();
        for(const elem of arrays[0]) {
            set.set(elem, 1);
        }
        for (let i=1; i<arrays.length; i++) {
            let found = 0;
            for(const elem of arrays[i]) {
                const count = set.get(elem)
                if (count === i) {
                    set.set(elem,  count + 1);
                    found++;
                }
            }
            // Stop early if an array has no element in common with the smallest
            if (found === 0) return [];
        }

        // Output only the elements that have been seen as many times as there are arrays
        return arrays[0].filter(e => {
            const count = set.get(e);
            if (count !== undefined) set.set(e, 0);
            return count === arrays.length
        });
    },

    /**
     * determine if this array contains one or more items from another array.
     * @param {array} arr the array providing items to check for items in this.
     * @return {boolean} true|false if this array contains at least one item from arr.
     */
    containsAny: function (arr) {
        let arr1 = this;
        let arr2 = arr;
        if (arr2.length < arr1.length) {
            const tmp = arr1;
            arr1 = arr2;
            arr2 = tmp;

        }

        const m = new Map();
        for(const elem of arr1) {
            m.set(elem, true);
        }

        for(const elem of arr2) {
            if(m.get(elem)) {
                return true;
            }
        }

        return false;

        // return arr.some(v => this.includes(v));
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
