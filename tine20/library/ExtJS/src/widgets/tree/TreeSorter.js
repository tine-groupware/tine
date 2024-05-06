/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.tree.TreeSorter
 * Provides sorting of nodes in a {@link Ext.tree.TreePanel}.  The TreeSorter automatically monitors events on the
 * associated TreePanel that might affect the tree's sort order (beforechildrenrendered, append, insert and textchange).
 * Example usage:<br />
 * <pre><code>
new Ext.tree.TreeSorter(myTree, {
    folderSort: true,
    dir: "desc",
    sortType: function(node) {
        // sort by a custom, typed attribute:
        return parseInt(node.id, 10);
    }
});
</code></pre>
 * @constructor
 * @param {TreePanel} tree
 * @param {Object} config
 */
Ext.tree.TreeSorter = function(tree, config){
    /**
     * @cfg {Boolean} folderSort True to sort leaf nodes under non-leaf nodes (defaults to false)
     */
    /**
     * @cfg {String} property The named attribute on the node to sort by (defaults to "text").  Note that this
     * property is only used if no {@link #sortType} function is specified, otherwise it is ignored.
     */
    /**
     * @cfg {String} dir The direction to sort ("asc" or "desc," case-insensitive, defaults to "asc")
     */
    /**
     * @cfg {String} leafAttr The attribute used to determine leaf nodes when {@link #folderSort} = true (defaults to "leaf")
     */
    /**
     * @cfg {Boolean} caseSensitive true for case-sensitive sort (defaults to false)
     */
    /**
     * @cfg {Function} sortType A custom "casting" function used to convert node values before sorting.  The function
     * will be called with a single parameter (the {@link Ext.tree.TreeNode} being evaluated) and is expected to return
     * the node's sort value cast to the specific data type required for sorting.  This could be used, for example, when
     * a node's text (or other attribute) should be sorted as a date or numeric value.  See the class description for
     * example usage.  Note that if a sortType is specified, any {@link #property} config will be ignored.
     */

    Ext.apply(this, config);
    tree.on("beforechildrenrendered", this.doSort, this);
    tree.on("append", this.updateSort, this);
    tree.on("insert", this.updateSort, this);
    tree.on("textchange", this.updateSortParent, this);

    const dsc = this.dir && this.dir.toLowerCase() === "desc";
    let p = this.property || "text";
    const sortType = this.sortType;
    const fs = this.folderSort;
    const cs = this.caseSensitive === true;
    const leafAttr = this.leafAttr || 'leaf';
    let priorityList = this.priorityList || [];
    const priorityProperty = this.priorityProperty;
    const locale = String(Tine.Tinebase.registry.get('locale').locale).substring(0,2) || 'en';

    this.sortFn = function(n1, n2){
        if(fs) {
            if(n1.attributes[leafAttr] && !n2.attributes[leafAttr]) return 1;
            if(!n1.attributes[leafAttr] && n2.attributes[leafAttr]) return -1;
        }
        p = priorityProperty || p;
        const n1Data = !cs && typeof n1.attributes[p] === 'string' ? n1.attributes[p].toUpperCase() : n1.attributes[p];
        const n2Data = !cs && typeof n2.attributes[p] === 'string' ? n2.attributes[p].toUpperCase() : n2.attributes[p];
        
        const node1Priority = priorityList.includes(n1Data);
        const node2Priority = priorityList.includes(n2Data);
        
        if (priorityProperty && (node1Priority || node2Priority)) {
            if (!cs) priorityList = priorityList.map((item) => {return item.toUpperCase();});
            if(node1Priority && !node2Priority) return -1;
            if(!node1Priority && node2Priority) return +1;
            if (node1Priority && node2Priority) return priorityList.indexOf(n1Data) > priorityList.indexOf(n2Data) ? +1 : -1;
        } else {
            const v1 = sortType ? sortType(n1) : n1Data;
            const v2 = sortType ? sortType(n2) : n2Data;
            
            if (Ext.isString(v1) && Ext.isString(v2)) return v1.localeCompare(v2, locale);

            if (v1 < v2){
                return dsc ? +1 : -1;
            } else if (v1 > v2){
                return dsc ? -1 : +1;
            } else {
                return 0;
            }
        }
    };
};

Ext.tree.TreeSorter.prototype = {
    doSort : function(node){
        node.sort(this.sortFn);
    },

    compareNodes : function(n1, n2){
        return (n1.text.toUpperCase() > n2.text.toUpperCase() ? 1 : -1);
    },

    updateSort : function(tree, node){
        if(node.childrenRendered){
            this.doSort.defer(1, this, [node]);
        }
    },

    updateSortParent : function(node){
        var p = node.parentNode;
        if(p && p.childrenRendered){
            this.doSort.defer(1, this, [p]);
        }
    }
};
