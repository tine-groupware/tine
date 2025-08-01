/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
import {getLayoutClassByWidth, getLayoutClassByMode} from "../../../../../Tinebase/js/util/responsiveLayout";

/**
/**
 * @class Ext.grid.GridView
 * @extends Ext.util.Observable
 * <p>This class encapsulates the user interface of an {@link Ext.grid.GridPanel}.
 * Methods of this class may be used to access user interface elements to enable
 * special display effects. Do not change the DOM structure of the user interface.</p>
 * <p>This class does not provide ways to manipulate the underlying data. The data
 * model of a Grid is held in an {@link Ext.data.Store}.</p>
 * @constructor
 * @param {Object} config
 */
Ext.grid.GridView = Ext.extend(Ext.util.Observable, {
    /**
     * Override this function to apply custom CSS classes to rows during rendering.  You can also supply custom
     * parameters to the row template for the current row to customize how it is rendered using the <b>rowParams</b>
     * parameter.  This function should return the CSS class name (or empty string '' for none) that will be added
     * to the row's wrapping div.  To apply multiple class names, simply return them space-delimited within the string
     * (e.g., 'my-class another-class'). Example usage:
    <pre><code>
viewConfig: {
    forceFit: true,
    showPreview: true, // custom property
    enableRowBody: true, // required to create a second, full-width row to show expanded Record data
    getRowClass: function(record, rowIndex, rp, ds){ // rp = rowParams
        if(this.showPreview){
            rp.body = '&lt;p>'+record.data.excerpt+'&lt;/p>';
            return 'x-grid3-row-expanded';
        }
        return 'x-grid3-row-collapsed';
    }
},     
    </code></pre>
     * @param {Record} record The {@link Ext.data.Record} corresponding to the current row.
     * @param {Number} index The row index.
     * @param {Object} rowParams A config object that is passed to the row template during rendering that allows
     * customization of various aspects of a grid row.
     * <p>If {@link #enableRowBody} is configured <b><tt></tt>true</b>, then the following properties may be set
     * by this function, and will be used to render a full-width expansion row below each grid row:</p>
     * <ul>
     * <li><code>body</code> : String <div class="sub-desc">An HTML fragment to be used as the expansion row's body content (defaults to '').</div></li>
     * <li><code>bodyStyle</code> : String <div class="sub-desc">A CSS style specification that will be applied to the expansion row's &lt;tr> element. (defaults to '').</div></li>
     * </ul>
     * The following property will be passed in, and may be appended to:
     * <ul>
     * <li><code>tstyle</code> : String <div class="sub-desc">A CSS style specification that willl be applied to the &lt;table> element which encapsulates
     * both the standard grid row, and any expansion row.</div></li>
     * </ul>
     * @param {Store} store The {@link Ext.data.Store} this grid is bound to
     * @method getRowClass
     * @return {String} a CSS class name to add to the row.
     */
    /**
     * @cfg {Boolean} enableRowBody True to add a second TR element per row that can be used to provide a row body
     * that spans beneath the data row.  Use the {@link #getRowClass} method's rowParams config to customize the row body.
     */
    /**
     * @cfg {String} emptyText Default text (html tags are accepted) to display in the grid body when no rows
     * are available (defaults to ''). This value will be used to update the <tt>{@link #mainBody}</tt>:
    <pre><code>
    this.mainBody.update('&lt;div class="x-grid-empty">' + this.emptyText + '&lt;/div>');
    </code></pre>
     */
    /**
     * @cfg {Boolean} headersDisabled True to disable the grid column headers (defaults to <tt>false</tt>). 
     * Use the {@link Ext.grid.ColumnModel ColumnModel} <tt>{@link Ext.grid.ColumnModel#menuDisabled menuDisabled}</tt>
     * config to disable the <i>menu</i> for individual columns.  While this config is true the
     * following will be disabled:<div class="mdetail-params"><ul>
     * <li>clicking on header to sort</li>
     * <li>the trigger to reveal the menu.</li>
     * </ul></div>
     */
    /**
     * <p>A customized implementation of a {@link Ext.dd.DragZone DragZone} which provides default implementations
     * of the template methods of DragZone to enable dragging of the selected rows of a GridPanel.
     * See {@link Ext.grid.GridDragZone} for details.</p>
     * <p>This will <b>only</b> be present:<div class="mdetail-params"><ul>
     * <li><i>if</i> the owning GridPanel was configured with {@link Ext.grid.GridPanel#enableDragDrop enableDragDrop}: <tt>true</tt>.</li>
     * <li><i>after</i> the owning GridPanel has been rendered.</li>
     * </ul></div>
     * @property dragZone
     * @type {Ext.grid.GridDragZone}
     */
    /**
     * @cfg {Boolean} deferEmptyText True to defer <tt>{@link #emptyText}</tt> being applied until the store's
     * first load (defaults to <tt>true</tt>).
     */
    deferEmptyText : true,
    /**
     * @cfg {Number} scrollOffset The amount of space to reserve for the vertical scrollbar
     * (defaults to <tt>undefined</tt>). If an explicit value isn't specified, this will be automatically
     * calculated.
     */
    scrollOffset : undefined,
    /**
     * @cfg {Boolean} autoFill
     * Defaults to <tt>false</tt>.  Specify <tt>true</tt> to have the column widths re-proportioned
     * when the grid is <b>initially rendered</b>.  The 
     * {@link Ext.grid.Column#width initially configured width}</tt> of each column will be adjusted
     * to fit the grid width and prevent horizontal scrolling. If columns are later resized (manually
     * or programmatically), the other columns in the grid will <b>not</b> be resized to fit the grid width.
     * See <tt>{@link #forceFit}</tt> also.
     */
    autoFill : false,
    /**
     * @cfg {Boolean} forceFit
     * Defaults to <tt>false</tt>.  Specify <tt>true</tt> to have the column widths re-proportioned
     * at <b>all times</b>.  The {@link Ext.grid.Column#width initially configured width}</tt> of each
     * column will be adjusted to fit the grid width and prevent horizontal scrolling. If columns are
     * later resized (manually or programmatically), the other columns in the grid <b>will</b> be resized
     * to fit the grid width. See <tt>{@link #autoFill}</tt> also.
     */
    forceFit : false,
    /**
     * @cfg {Array} sortClasses The CSS classes applied to a header when it is sorted. (defaults to <tt>['sort-asc', 'sort-desc']</tt>)
     */
    sortClasses : ['sort-asc', 'sort-desc'],
    /**
     * @cfg {String} sortAscText The text displayed in the 'Sort Ascending' menu item (defaults to <tt>'Sort Ascending'</tt>)
     */
    sortAscText : 'Sort Ascending',
    /**
     * @cfg {String} sortDescText The text displayed in the 'Sort Descending' menu item (defaults to <tt>'Sort Descending'</tt>)
     */
    sortDescText : 'Sort Descending',
    /**
     * @cfg {String} columnsText The text displayed in the 'Columns' menu item (defaults to <tt>'Columns'</tt>)
     */
    columnsText : 'Columns',

    resizingStrategy: 'fractional',
    /**
     * @cfg {String} selectedRowClass The CSS class applied to a selected row (defaults to <tt>'x-grid3-row-selected'</tt>). An
     * example overriding the default styling:
    <pre><code>
    .x-grid3-row-selected {background-color: yellow;}
    </code></pre>
     * Note that this only controls the row, and will not do anything for the text inside it.  To style inner
     * facets (like text) use something like:
    <pre><code>
    .x-grid3-row-selected .x-grid3-cell-inner {
        color: #FFCC00;
    }
    </code></pre>
     * @type String
     */
    selectedRowClass : 'x-grid3-row-selected',

    // private
    borderWidth : 2,
    tdClass : 'x-grid3-cell',
    hdCls : 'x-grid3-hd',
    markDirty : true,

    /**
     * @cfg {Number} cellSelectorDepth The number of levels to search for cells in event delegation (defaults to <tt>4</tt>)
     */
    cellSelectorDepth : 4,
    /**
     * @cfg {Number} rowSelectorDepth The number of levels to search for rows in event delegation (defaults to <tt>10</tt>)
     */
    rowSelectorDepth : 20,
    
    /**
     * @cfg {Number} rowBodySelectorDepth The number of levels to search for row bodies in event delegation (defaults to <tt>10</tt>)
     */
    rowBodySelectorDepth : 10,

    /**
     * @cfg {String} cellSelector The selector used to find cells internally (defaults to <tt>'td.x-grid3-cell'</tt>)
     */
    cellSelector : 'td.x-grid3-cell',
    /**
     * @cfg {String} rowSelector The selector used to find rows internally (defaults to <tt>'div.x-grid3-row'</tt>)
     */
    rowSelector : 'div.x-grid3-row',
    
    /**
     * @cfg {String} rowBodySelector The selector used to find row bodies internally (defaults to <tt>'div.x-grid3-row'</tt>)
     */
    rowBodySelector : 'div.x-grid3-row-body',
    
    // private
    firstRowCls: 'x-grid3-row-first',
    lastRowCls: 'x-grid3-row-last',
    rowClsRe: /(?:^|\s+)x-grid3-row-(first|last|alt)(?:\s+|$)/g,

    constructor : function(config){
        Ext.apply(this, config);
	    // These events are only used internally by the grid components
	    this.addEvents(
	        /**
	         * @event beforerowremoved
	         * Internal UI Event. Fired before a row is removed.
	         * @param {Ext.grid.GridView} view
	         * @param {Number} rowIndex The index of the row to be removed.
	         * @param {Ext.data.Record} record The Record to be removed
	         */
	        'beforerowremoved',
	        /**
	         * @event beforerowsinserted
	         * Internal UI Event. Fired before rows are inserted.
	         * @param {Ext.grid.GridView} view
	         * @param {Number} firstRow The index of the first row to be inserted.
	         * @param {Number} lastRow The index of the last row to be inserted.
	         */
	        'beforerowsinserted',
	        /**
	         * @event beforerefresh
	         * Internal UI Event. Fired before the view is refreshed.
	         * @param {Ext.grid.GridView} view
	         */
	        'beforerefresh',
	        /**
	         * @event rowremoved
	         * Internal UI Event. Fired after a row is removed.
	         * @param {Ext.grid.GridView} view
	         * @param {Number} rowIndex The index of the row that was removed.
	         * @param {Ext.data.Record} record The Record that was removed
	         */
	        'rowremoved',
	        /**
	         * @event rowsinserted
	         * Internal UI Event. Fired after rows are inserted.
	         * @param {Ext.grid.GridView} view
	         * @param {Number} firstRow The index of the first inserted.
	         * @param {Number} lastRow The index of the last row inserted.
	         */
	        'rowsinserted',
	        /**
	         * @event rowupdated
	         * Internal UI Event. Fired after a row has been updated.
	         * @param {Ext.grid.GridView} view
	         * @param {Number} firstRow The index of the row updated.
	         * @param {Ext.data.record} record The Record backing the row updated.
	         */
	        'rowupdated',
	        /**
	         * @event refresh
	         * Internal UI Event. Fired after the GridView's body has been refreshed.
	         * @param {Ext.grid.GridView} view
	         */
	        'refresh'
	    );
	    Ext.grid.GridView.superclass.constructor.call(this);    
    },

    /* -------------------------------- UI Specific ----------------------------- */

    // private
    initTemplates : function(){
        var ts = this.templates || {};
        if(!ts.master){
            ts.master = new Ext.Template(
                    '<div class="x-grid3" hidefocus="true">',
                        '<div class="x-grid3-viewport">',
                            '<div class="x-grid3-header"><div class="x-grid3-header-inner"><div class="x-grid3-header-offset" style="{ostyle}">{header}</div></div><div class="x-clear"></div></div>',
                            '<div class="x-grid3-scroller"><div class="x-grid3-body" style="{bstyle}">{body}</div><a href="#" class="x-grid3-focus" tabIndex="-1"></a></div>',
                        '</div>',
                        '<div class="x-grid3-resize-marker">&#160;</div>',
                        '<div class="x-grid3-resize-proxy">&#160;</div>',
                    '</div>'
                    );
        }

        if(!ts.header){
            ts.header = new Ext.Template(
                    '<table border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
                    '<thead><tr class="x-grid3-hd-row">{cells}</tr></thead>',
                    '</table>'
                    );
        }

        if(!ts.hcell){
            ts.hcell = new Ext.Template(
                    '<td class="x-grid3-hd x-grid3-cell x-grid3-td-{id} {css}" style="{style}"><div {tooltip} {attr} class="x-grid3-hd-inner x-grid3-hd-{id}" unselectable="on" style="{istyle}">', this.grid.enableHdMenu ? '<a class="x-grid3-hd-btn" href="#"></a>' : '',
                    '{value}<img class="x-grid3-sort-icon" src="', Ext.BLANK_IMAGE_URL, '" />',
                    '</div></td>'
                    );
        }

        if(!ts.body){
            ts.body = new Ext.Template('{rows}');
        }

        if(!ts.row){
            ts.row = new Ext.Template(
                    '<div class="x-grid3-row {alt}" style="{tstyle}"><table class="x-grid3-row-table" border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
                    '<tbody><tr>{cells}</tr>',
                    (this.enableRowBody ? '<tr class="x-grid3-row-body-tr" style="{bodyStyle}"><td colspan="{cols}" class="x-grid3-body-cell" tabIndex="0" hidefocus="on"><div class="x-grid3-row-body">{body}</div></td></tr>' : ''),
                    '</tbody></table></div>'
                    );
        }

        if(!ts.cell){
            ts.cell = new Ext.Template(
                    '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} {css}" style="{style}" tabIndex="0" {cellAttr}>',
                    '<div class="x-grid3-cell-inner x-grid3-col-{id}" unselectable="on" {attr}>{value}</div>',
                    '</td>'
                    );
        }

        for(var k in ts){
            var t = ts[k];
            if(t && Ext.isFunction(t.compile) && !t.compiled){
                t.disableFormats = true;
                t.compile();
            }
        }

        this.templates = ts;
        this.colRe = new RegExp('x-grid3-td-([^\\s]+)', '');
    },

    // private
    fly : function(el){
        if(!this._flyweight){
            this._flyweight = new Ext.Element.Flyweight(document.body);
        }
        this._flyweight.dom = el;
        return this._flyweight;
    },

    // private
    getEditorParent : function(){
        return this.scroller.dom;
    },

    // private
    initElements : function(){
        var E = Ext.Element;

        var el = this.grid.getGridEl().dom.firstChild;
        var cs = el.childNodes;

        this.el = new E(el);

        this.mainWrap = new E(cs[0]);
        this.mainHd = new E(this.mainWrap.dom.firstChild);
        this.mainHd.setDisplayed(!this.grid.hideHeaders);

        this.innerHd = this.mainHd.dom.firstChild;
        this.scroller = new E(this.mainWrap.dom.childNodes[1]);
        if(this.forceFit){
            this.scroller.setStyle('overflow-x', 'auto');
        }
        /**
         * <i>Read-only</i>. The GridView's body Element which encapsulates all rows in the Grid.
         * This {@link Ext.Element Element} is only available after the GridPanel has been rendered.
         * @type Ext.Element
         * @property mainBody
         */
        this.mainBody = new E(this.scroller.dom.firstChild);

        this.focusEl = new E(this.scroller.dom.childNodes[1]);
        this.focusEl.swallowEvent('click', true);

        this.resizeMarker = new E(cs[1]);
        this.resizeProxy = new E(cs[2]);
    },

    // private
    getRows : function(){
        return this.hasRows() ? this.mainBody.dom.childNodes : [];
    },

    // finder methods, used with delegation

    // private
    findCell : function(el){
        if(!el){
            return false;
        }
        return this.fly(el).findParent(this.cellSelector, this.cellSelectorDepth);
    },

    /**
     * <p>Return the index of the grid column which contains the passed HTMLElement.</p>
     * See also {@link #findRowIndex}
     * @param {HTMLElement} el The target element
     * @return {Number} The column index, or <b>false</b> if the target element is not within a row of this GridView.
     */
    findCellIndex : function(el, requiredCls){
        var cell = this.findCell(el);
        if(cell && (!requiredCls || this.fly(cell).hasClass(requiredCls))){
            return this.getCellIndex(cell);
        }
        return false;
    },

    // private
    getCellIndex : function(el){
        if(el){
            var m = el.className.match(this.colRe);
            if(m && m[1]){
                return this.cm.getIndexById(m[1]);
            }
        }
        return false;
    },

    // private
    findHeaderCell : function(el){
        var cell = this.findCell(el);
        return cell && this.fly(cell).hasClass(this.hdCls) ? cell : null;
    },

    // private
    findHeaderIndex : function(el){
        return this.findCellIndex(el, this.hdCls);
    },

    /**
     * Return the HtmlElement representing the grid row which contains the passed element.
     * @param {HTMLElement} el The target HTMLElement
     * @return {HTMLElement} The row element, or null if the target element is not within a row of this GridView.
     */
    findRow : function(el){
        if(!el){
            return false;
        }
        return this.fly(el).findParent(this.rowSelector, this.rowSelectorDepth);
    },

    /**
     * <p>Return the index of the grid row which contains the passed HTMLElement.</p>
     * See also {@link #findCellIndex}
     * @param {HTMLElement} el The target HTMLElement
     * @return {Number} The row index, or <b>false</b> if the target element is not within a row of this GridView.
     */
    findRowIndex : function(el){
        var r = this.findRow(el);
        return r ? r.rowIndex : false;
    },
    
    /**
     * Return the HtmlElement representing the grid row body which contains the passed element.
     * @param {HTMLElement} el The target HTMLElement
     * @return {HTMLElement} The row body element, or null if the target element is not within a row body of this GridView.
     */
    findRowBody : function(el){
        if(!el){
            return false;
        }
        return this.fly(el).findParent(this.rowBodySelector, this.rowBodySelectorDepth);
    },

    // getter methods for fetching elements dynamically in the grid

    /**
     * Return the <tt>&lt;div></tt> HtmlElement which represents a Grid row for the specified index.
     * @param {Number} index The row index
     * @return {HtmlElement} The div element.
     */
    getRow : function(row){
        return this.getRows()[row];
    },

    /**
     * Returns the grid's <tt>&lt;td></tt> HtmlElement at the specified coordinates.
     * @param {Number} row The row index in which to find the cell.
     * @param {Number} col The column index of the cell.
     * @return {HtmlElement} The td at the specified coordinates.
     */
    getCell : function(row, col){
        return this.getRow(row).getElementsByTagName('td')[col];
    },

    /**
     * Return the <tt>&lt;td></tt> HtmlElement which represents the Grid's header cell for the specified column index.
     * @param {Number} index The column index
     * @return {HtmlElement} The td element.
     */
    getHeaderCell : function(index){
      return this.mainHd.dom.getElementsByTagName('td')[index];
    },

    // manipulating elements

    // private - use getRowClass to apply custom row classes
    addRowClass : function(row, cls){
        var r = this.getRow(row);
        if(r){
            this.fly(r).addClass(cls);
        }
    },

    // private
    removeRowClass : function(row, cls){
        var r = this.getRow(row);
        if(r){
            this.fly(r).removeClass(cls);
        }
    },

    // private
    removeRow : function(row){
        Ext.removeNode(this.getRow(row));
        this.syncFocusEl(row);
    },
    
    // private
    removeRows : function(firstRow, lastRow){
        var bd = this.mainBody.dom;
        for(var rowIndex = firstRow; rowIndex <= lastRow; rowIndex++){
            Ext.removeNode(bd.childNodes[firstRow]);
        }
        this.syncFocusEl(firstRow);
    },

    // scrolling stuff

    // private
    getScrollState : function(){
        var sb = this.scroller.dom;
        return {left: sb.scrollLeft, top: sb.scrollTop};
    },

    // private
    restoreScroll : function(state){
        var sb = this.scroller.dom;
        sb.scrollLeft = state.left;
        sb.scrollTop = state.top;
    },

    /**
     * Scrolls the grid to the top
     */
    scrollToTop : function(){
        this.scroller.dom.scrollTop = 0;
        this.scroller.dom.scrollLeft = 0;
    },

    // private
    syncScroll : function(){
      this.syncHeaderScroll();
      var mb = this.scroller.dom;
        this.grid.fireEvent('bodyscroll', mb.scrollLeft, mb.scrollTop);
    },

    // private
    syncHeaderScroll : function(){
        var mb = this.scroller.dom;
        this.innerHd.scrollLeft = mb.scrollLeft;
        this.innerHd.scrollLeft = mb.scrollLeft; // second time for IE (1/2 time first fails, other browsers ignore)
    },

    // private
    updateSortIcon : function(col, dir){
        const sc = this.sortClasses;
        const hds = this.mainHd.select('td').removeClass(sc);
        if (hds.item(col))  hds.item(col).addClass(sc[dir === 'DESC' ? 1 : 0]);
    },

    // private
    updateAllColumnWidths : function(){
        const tw = this.getTotalWidth();
        this.innerHd.firstChild.style.width = this.getOffsetWidth();
        this.innerHd.firstChild.firstChild.style.width = tw;
        this.mainBody.dom.style.width = tw;
        const ws = [];
        
        this.cm.config.forEach((col, idx) => {
            ws[idx] = this.getColumnWidth(idx);
            this.updateColumnStyle(idx, {'width': ws[idx]})
        })
        
        this.onAllColumnWidthsUpdated(ws, tw);
    },

    // private
    updateColumnWidth : function(col, width){
        const tw = this.getTotalWidth();
        this.innerHd.firstChild.style.width = this.getOffsetWidth();
        this.innerHd.firstChild.firstChild.style.width = tw;
        this.mainBody.dom.style.width = tw;
        const w = this.getColumnWidth(col);
        this.updateColumnStyle(col, {'width': w});
        this.onColumnWidthUpdated(col, w, tw);
    },

    // private
    updateColumnHidden : function(col, hidden){
        const tw = this.getTotalWidth();
        this.innerHd.firstChild.style.width = this.getOffsetWidth();
        this.innerHd.firstChild.firstChild.style.width = tw;
        this.mainBody.dom.style.width = tw;
        
        this.updateColumnStyle(col, {'display': hidden ? 'none' : ''});
        this.onColumnHiddenUpdated(col, hidden, tw);
        delete this.lastViewWidth; // force recalc
        this.layout();
    },

    // private
    doRender : function(cs, rs, ds, startRow, colCount, stripe){
        var ts = this.templates, ct = ts.cell, rt = ts.row, last = colCount-1;
        var tstyle = 'width:'+this.getTotalWidth()+';';
        // buffers
        var buf = [], cb, c, p = {}, rp = {tstyle: tstyle}, r;
        for(var j = 0, len = rs.length; j < len; j++){
            r = rs[j]; cb = [];
            var rowIndex = (j+startRow);
            for(var i = 0; i < colCount; i++){
                c = cs[i];
                p.id = c.id;
                p.css = i === 0 ? 'x-grid3-cell-first ' : (i == last ? 'x-grid3-cell-last ' : '');
                p.attr = p.cellAttr = '';
                p.value = c.renderer.call(c.scope, r.data[c.name], p, r, rowIndex, i, ds);
                p.style = c.style;
                if(Ext.isEmpty(p.value)){
                    p.value = '&#160;';
                }
                if(this.markDirty && r.dirty && Ext.isDefined(r.modified[c.name])){
                    p.css += ' x-grid3-dirty-cell';
                }
                cb[cb.length] = ct.apply(p);
            }
            var alt = [];
            if(stripe && ((rowIndex+1) % 2 === 0)){
                alt[0] = 'x-grid3-row-alt';
            }
            if(r.dirty){
                alt[1] = ' x-grid3-dirty-row';
            }
            rp.cols = colCount;
            if(this.getRowClass){
                alt[2] = this.getRowClass(r, rowIndex, rp, ds);
            }
            rp.alt = alt.join(' ');
            rp.cells = cb.join('');
            buf[buf.length] =  rt.apply(rp);
        }
        return buf.join('');
    },

    // private
    processRows : function(startRow, skipStripe){
        if(!this.ds || this.ds.getCount() < 1){
            return;
        }
        var rows = this.getRows(),
            len = rows.length,
            i, r;
            
        skipStripe = skipStripe || !this.grid.stripeRows;
        startRow = startRow || 0;
        for(i = 0; i<len; i++) {
            r = rows[i];
            if(r) {
                r.rowIndex = i;
                if(!skipStripe){
                    r.className = r.className.replace(this.rowClsRe, ' ');
                    if ((i + 1) % 2 === 0){
                        r.className += ' x-grid3-row-alt';
                    }
                }   
            }          
        }
        // add first/last-row classes
        if (!rows[0]) return;
        if(startRow === 0){
            Ext.fly(rows[0]).addClass(this.firstRowCls);
        }
        Ext.fly(rows[rows.length - 1]).addClass(this.lastRowCls);
    },

    afterRender : function(){
        if(!this.ds || !this.cm){
            return;
        }
        this.mainBody.dom.innerHTML = this.renderRows() || '&#160;';
        this.processRows(0, true);

        if(this.deferEmptyText !== true){
            this.applyEmptyText();
        }
        this.grid.fireEvent('viewready', this.grid);
    },

    // private
    renderUI : function(){

        var header = this.renderHeaders();
        var body = this.templates.body.apply({rows:'&#160;'});


        var html = this.templates.master.apply({
            body: body,
            header: header,
            ostyle: 'width:'+this.getOffsetWidth()+';',
            bstyle: 'width:'+this.getTotalWidth()+';'
        });

        var g = this.grid;

        g.getGridEl().dom.innerHTML = html;

        this.initElements();

        // get mousedowns early
        Ext.fly(this.innerHd).on('click', this.handleHdDown, this);
        this.mainHd.on({
            scope: this,
            mouseover: this.handleHdOver,
            mouseout: this.handleHdOut,
            mousemove: this.handleHdMove
        });

        this.scroller.on('scroll', this.syncScroll,  this);
        if(g.enableColumnResize !== false){
            this.splitZone = new Ext.grid.GridView.SplitDragZone(g, this.mainHd.dom);
        }

        if(g.enableColumnMove){
            this.columnDrag = new Ext.grid.GridView.ColumnDragZone(g, this.innerHd);
            this.columnDrop = new Ext.grid.HeaderDropZone(g, this.mainHd.dom);
        }

        if(g.enableHdMenu !== false){
            this.hmenu = new Ext.menu.Menu({id: g.id + '-hctx'});
            this.hmenu.add(
                {itemId:'asc', text: this.sortAscText, cls: 'xg-hmenu-sort-asc'},
                {itemId:'desc', text: this.sortDescText, cls: 'xg-hmenu-sort-desc'}
            );
            if(g.enableColumnHide !== false){
                this.colMenu = new Ext.menu.Menu({id:g.id + '-hcols-menu'});
                this.colMenu.on({
                    scope: this,
                    beforeshow: this.beforeColMenuShow,
                    itemclick: this.handleHdMenuClick
                });
                this.hmenu.add('-', {
                    itemId:'columns',
                    hideOnClick: false,
                    text: this.columnsText,
                    menu: this.colMenu,
                    iconCls: 'x-cols-icon'
                });
            }
            this.hmenu.on('itemclick', this.handleHdMenuClick, this);
        }

        if(g.trackMouseOver){
            this.mainBody.on({
                scope: this,
                mouseover: this.onRowOver,
                mouseout: this.onRowOut
            });
        }

        if(g.enableDragDrop || g.enableDrag){
            this.dragZone = new Ext.grid.GridDragZone(g, {
                ddGroup : g.ddGroup || 'GridDD'
            });
        }

        this.updateHeaderSortState();

    },
    
    // private
    processEvent: Ext.emptyFn,

    // private
    layout : function(){
        if(!this.mainBody){
            return; // not rendered
        }
        const g = this.grid;
        const c = g.getGridEl();
        if (!g.hasOwnProperty('enableResponsive') && g.ownerCt.enableResponsive) {
            g.enableResponsive = g.ownerCt.enableResponsive;
        }

        if (g.enableResponsive) g.autoHeight = g.ownerCt.autoHeight

        const csize = c.getSize(true);
        const vw = csize.width;
        
        if (this.mainHd) {
            const mode = this.getResponsiveMode();
            this.mainHd.setDisplayed(mode.name !== 'oneColumn' && !g.hideHeaders);
        }
        if(!g.hideHeaders && (vw < 20 || csize.height < 20)){ // display: none?
            return;
        }
        
        if(g.autoHeight){
            this.scroller.dom.style.overflow = 'visible';
            if(Ext.isWebKit){
                this.scroller.dom.style.position = 'static';
            }
            if (g.enableResponsive) {
                this.el.setSize(csize.width, 'auto');
                this.scroller.setSize(vw, 'auto');
            }
        }else{
            this.el.setSize(csize.width, csize.height);

            var hdHeight = this.mainHd.getHeight();
            var vh = csize.height - (hdHeight);

            this.scroller.setSize(vw, vh);
            if(this.innerHd){
                this.innerHd.style.width = (vw)+'px';
            }
        }
        
        if(this.forceFit){
            this.fitColumns(false, false);
            
            this.lastViewWidth = vw;
        }else {
            this.autoExpand();
            this.syncHeaderScroll();
        }
        this.onLayout(vw, vh);
    },
    
    resolveStateIdResponsiveMode(stateId) {
        if (!stateId) return null;
        const modeName = this.getResponsiveMode().name;
        if (modeName) {
            if (!this.latestMode) {
                stateId = `${stateId}_${modeName}`;
            } else {
                if (this.latestMode !== modeName) {
                    stateId = stateId.replace(`_${this.latestMode}`, `_${modeName}`);
                }
                if (!stateId.includes(`_${modeName}`)) {
                    stateId = `${stateId}_${modeName}`;
                }
            }
            this.latestMode = modeName;
        }
        return stateId;
    },
    
    getResponsiveMode() {
        let result = {
            level: -1, 
            name: null
        };
        if (this.disableResponsiveLayout) return result;
        let width = this.grid?.getWidth?.() ?? 0;
        if (width === 0 && this.grid?.lastSize) {
            width = this.grid.lastSize.width;
        }
        if (width === 0) return result;
        if (this.cm.config.length === 1) this.responsiveMode = 'oneColumn';
        if (!this.responsiveMode) this.responsiveMode = 'auto';
        
        if (this.responsiveMode === 'auto') {
            result = getLayoutClassByWidth(width, this.cm.config);
        } else {
            result = getLayoutClassByMode(this.responsiveMode, this.cm.config);
        }
        return result;
    },
    
    setResponsiveMode(mode) {
        if (this.disableResponsiveLayout) return;
        this.responsiveMode = mode;
    },
    
    updateColumnStyle(col, styles) {
        const tw = this.getTotalWidth();
        _.each(styles, (value, key) => {
            const hd = this.getHeaderCell(col);
            hd.style[key] = value;
            const ns = this.getRows();
            ns.forEach((row) => {
                row.style.width = tw;
                if(row.firstChild){
                    row.firstChild.style.width = tw;
                    row.firstChild.rows[0].childNodes[col].style[key] = value;
                }
            })
        })
    },

    // template functions for subclasses and plugins
    // these functions include precalculated values
    onLayout : function(vw, vh){
        // do nothing
    },

    onColumnWidthUpdated : function(col, w, tw){
        //template method
    },

    onAllColumnWidthsUpdated : function(ws, tw){
        //template method
    },

    onColumnHiddenUpdated : function(col, hidden, tw){
        // template method
    },

    updateColumnText : function(col, text){
        // template method
    },

    afterMove : function(colIndex){
        // template method
    },

    /* ----------------------------------- Core Specific -------------------------------------------*/
    // private
    init : function(grid){
        this.grid = grid;
        this.disableResponsiveLayout = this.grid.disableResponsiveLayout ?? false;
        this.initTemplates();
        this.initData(grid.store, grid.colModel);
        this.initUI(grid);
    },

    // private
    getColumnId : function(index){
      return this.cm.getColumnId(index);
    },
    
    // private 
    getOffsetWidth : function() {
        return (this.cm.getTotalWidth() + this.getScrollOffset()) + 'px';
    },
    
    getScrollOffset: function(){
        return Ext.num(this.scrollOffset, Ext.getScrollBarWidth());
    },

    // private
    renderHeaders : function(){
        var cm = this.cm, 
            ts = this.templates,
            ct = ts.hcell,
            cb = [], 
            p = {},
            len = cm.getColumnCount(),
            last = len - 1;
        
        for(var i = 0; i < len; i++){
            p.id = cm.getColumnId(i);
            p.value = cm.getColumnHeader(i) || '';
            p.style = this.getColumnStyle(i, true);
            p.tooltip = this.getColumnTooltip(i);
            p.css = i === 0 ? 'x-grid3-cell-first ' : (i == last ? 'x-grid3-cell-last ' : '');
            if(cm.config[i].align == 'right'){
                p.istyle = 'padding-right:16px';
            } else {
                delete p.istyle;
            }
            cb[cb.length] = ct.apply(p);
        }
        return ts.header.apply({cells: cb.join(''), tstyle:'width:'+this.getTotalWidth()+';'});
    },

    // private
    getColumnTooltip : function(i){
        var tt = this.cm.getColumnTooltip(i);
        if(tt){
            if(Ext.QuickTips.isEnabled()){
                return 'ext:qtip="'+tt+'"';
            }else{
                return 'title="'+tt+'"';
            }
        }
        return '';
    },

    // private
    beforeUpdate : function(){
        this.grid.stopEditing(true);
    },

    // private
    updateHeaders : function(){
        this.innerHd.firstChild.innerHTML = this.renderHeaders();
        this.innerHd.firstChild.style.width = this.getOffsetWidth();
        this.innerHd.firstChild.firstChild.style.width = this.getTotalWidth();
    },

    /**
     * Focuses the specified row.
     * @param {Number} row The row index
     */
    focusRow : function(row){
        this.focusCell(row, 0, false);
    },

    /**
     * Focuses the specified cell.
     * @param {Number} row The row index
     * @param {Number} col The column index
     */
    focusCell : function(row, col, hscroll){
        this.syncFocusEl(this.ensureVisible(row, col, hscroll));
        if(Ext.isGecko){
            this.focusEl.focus();
        }else{
            this.focusEl.focus.defer(1, this.focusEl);
        }
    },

    resolveCell : function(row, col, hscroll){
        if(!Ext.isNumber(row)){
            row = row.rowIndex;
        }
        if(!this.ds){
            return null;
        }
        if(row < 0 || row >= this.ds.getCount()){
            return null;
        }
        col = (col !== undefined ? col : 0);

        var rowEl = this.getRow(row),
            cm = this.cm,
            colCount = cm.getColumnCount(),
            cellEl;
        if(!(hscroll === false && col === 0)){
            while(col < colCount && cm.isHidden(col) && cm.config[col].id !== 'responsive'){
                col++;
            }
            cellEl = this.getCell(row, col);
        }

        return {row: rowEl, cell: cellEl};
    },

    getResolvedXY : function(resolved){
        if(!resolved){
            return null;
        }
        var s = this.scroller.dom, c = resolved.cell, r = resolved.row;
        return c ? Ext.fly(c).getXY() : [this.el.getX(), Ext.fly(r).getY()];
    },

    syncFocusEl : function(row, col, hscroll){
        var xy = row;
        if(!Ext.isArray(xy)){
            row = Math.min(row, Math.max(0, this.getRows().length-1));
            xy = this.getResolvedXY(this.resolveCell(row, col, hscroll));
        }
        this.focusEl.setXY(xy||this.scroller.getXY());
    },

    ensureVisible : function(row, col, hscroll){
        var resolved = this.resolveCell(row, col, hscroll);
        if(!resolved || !resolved.row){
            return;
        }

        var rowEl = resolved.row, 
            cellEl = resolved.cell,
            c = this.scroller.dom,
            ctop = 0,
            p = rowEl, 
            stop = this.el.dom;
            
        while(p && p != stop){
            ctop += p.offsetTop;
            p = p.offsetParent;
        }
        
        ctop -= this.mainHd.dom.offsetHeight;
        stop = parseInt(c.scrollTop, 10);
        
        var cbot = ctop + rowEl.offsetHeight,
            ch = c.clientHeight,
            sbot = stop + ch;
        

        if(ctop < stop){
          c.scrollTop = ctop;
        }else if(cbot > sbot){
            c.scrollTop = cbot-ch;
        }

        if(hscroll !== false){
            var cleft = parseInt(cellEl.offsetLeft, 10);
            var cright = cleft + cellEl.offsetWidth;

            var sleft = parseInt(c.scrollLeft, 10);
            var sright = sleft + c.clientWidth;
            if(cleft < sleft){
                c.scrollLeft = cleft;
            }else if(cright > sright){
                c.scrollLeft = cright-c.clientWidth;
            }
        }
        return this.getResolvedXY(resolved);
    },

    // private
    insertRows : function(dm, firstRow, lastRow, isUpdate){
        var last = dm.getCount() - 1;
        if(!isUpdate && firstRow === 0 && lastRow >= last){
	    this.fireEvent('beforerowsinserted', this, firstRow, lastRow);
            this.refresh();
	    this.fireEvent('rowsinserted', this, firstRow, lastRow);
        }else{
            if(!isUpdate){
                this.fireEvent('beforerowsinserted', this, firstRow, lastRow);
            }
            var html = this.renderRows(firstRow, lastRow),
                before = this.getRow(firstRow);
            if(before){
                if(firstRow === 0){
                    Ext.fly(this.getRow(0)).removeClass(this.firstRowCls);
                }
                Ext.DomHelper.insertHtml('beforeBegin', before, html);
            }else{
                var r = this.getRow(last - 1);
                if(r){
                    Ext.fly(r).removeClass(this.lastRowCls);
                }
                Ext.DomHelper.insertHtml('beforeEnd', this.mainBody.dom, html);
            }
            if(!isUpdate){
                this.fireEvent('rowsinserted', this, firstRow, lastRow);
                this.processRows(firstRow);
            }else if(firstRow === 0 || firstRow >= last){
                //ensure first/last row is kept after an update.
                Ext.fly(this.getRow(firstRow)).addClass(firstRow === 0 ? this.firstRowCls : this.lastRowCls);
            }
        }
        this.syncFocusEl(firstRow);
    },

    // private
    deleteRows : function(dm, firstRow, lastRow){
        if(dm.getRowCount()<1){
            this.refresh();
        }else{
            this.fireEvent('beforerowsdeleted', this, firstRow, lastRow);

            this.removeRows(firstRow, lastRow);

            this.processRows(firstRow);
            this.fireEvent('rowsdeleted', this, firstRow, lastRow);
        }
    },

    // private
    getColumnStyle : function(col, isHeader){
        let style = !isHeader ? (this.cm.config[col].css || '') : '';
        style += 'width:'+this.getColumnWidth(col)+';';
        let hidden = this.cm.config[col]?.hidden || this.cm.config[col].id === 'responsive';
        if (this.getResponsiveMode().name === 'oneColumn') {
            hidden = this.cm.config[col].id !== 'responsive';
        }
        if (hidden){
            style += 'display:none;';
        }
        var align = this.cm.config[col].align;
        if(align){
            style += 'text-align:'+align+';';
        }
        return style;
    },

    // private
    getColumnWidth : function(col){
        var w = this.cm.getColumnWidth(col);
        if(Ext.isNumber(w)){
            return (Ext.isBorderBox || (Ext.isWebKit && !Ext.isSafari2) ? w : (w - this.borderWidth > 0 ? w - this.borderWidth : 0)) + 'px';
        }
        return w;
    },

    // private
    getTotalWidth : function(){
        if (this.grid.hideHeaders) return (this.cm.getTotalWidth() + this.getScrollOffset()) +'px';
        return this.cm.getTotalWidth()+'px';
    },
    
    fitColumns : function(preventRefresh, onlyExpand, omitColumn){
        if (!this.mainBody || !this.grid.viewReady) return;
        const cm = this.cm;
        const widthCurrentVisibleTotal = cm.getTotalWidth(false);
        if (!widthCurrentVisibleTotal) return;
        const colIdxOmitColumn = Ext.isNumber(omitColumn) ? omitColumn : -1;
        const isOmitColumnValid = omitColumn > -1;
        const widthResizedGrid = this.grid.getGridEl().getWidth(true) - this.getScrollOffset();
        let widthExtra = widthCurrentVisibleTotal - widthResizedGrid;
        // not initialized, so don't screw up the default widths
        const colCountVisible = cm.getColumnCount(true);
        if (widthResizedGrid < 20 * colCountVisible) return;
        // handle neighbours resizing first
        if (this.resizingStrategy === 'neighbours' && isOmitColumnValid) {
            const next = cm.config.indexOf(_.find(cm.config, (c, i) => { return i > colIdxOmitColumn && !c.hidden; }));
            if (next) {
                cm.setColumnWidth(next, cm.getColumnWidth(next) - widthExtra, false);
                widthExtra = 0; 
            }
        }
        const currentGridStateId = this.resolveStateIdResponsiveMode(this.grid?.stateId);
        const currentGridState = Ext.state.Manager.get(currentGridStateId);
        const isStateIdChanged = !!(!this.latestGridStateId && currentGridStateId)
            || !!((this.latestGridStateId && currentGridStateId) && (this.latestGridStateId !== currentGridStateId));
        this.latestGridStateId = currentGridStateId;
        
        if (isStateIdChanged) {
            if (this.grid) {
                this.grid.stateId = this.latestGridStateId;
                if (currentGridState) {
                    this.grid.applyState(currentGridState, true);
                    this.updateHeaders();
                    this.updateHeaderSortState();
                }
            }
            const mode = this.getResponsiveMode();
            cm.config.forEach((col, idx) => {
                col.initialConfig = col.initialConfig || {... col};
                col.index = idx;
                const refConfig = currentGridState?.columns?.[idx] ?? col.initialConfig;
                // set custom field
                col.useManualWidth = refConfig.useManualWidth ?? false;
                
                // reset grid state if stateId changed, make sure column config is based on current stateId
                cm.setColumnWidth(col.index, refConfig?.width ?? this.grid.minColumnWidth, true);
                let hidden = refConfig?.hidden ?? false;
                if (mode.level > -1) {
                    if (mode.name === 'oneColumn') {
                        hidden = col.id !== 'responsive';
                    } else {
                        //handle auto mode and strict mode
                        if (!refConfig) {
                            const responsiveLevel = col?.responsiveLevel ?? 'big';
                            const colModeClass = getLayoutClassByMode(responsiveLevel, this.cm.config);
                            if (col?.responsiveLevel || !hidden) hidden = colModeClass.level > mode.level;
                        }
                        if (col.id === 'responsive') hidden = true;
                    }
                    this.updateColumnStyle(col.index, {'display': hidden ? 'none' : ''});
                }
                cm.setHidden(col.index, hidden, true);

            });
            // handle sorting too
            const sortInfo = currentGridState?.sort;
            if (sortInfo && JSON.stringify(this.grid.store.sortInfo) !== JSON.stringify(sortInfo)) {
                this.grid.store.sort(sortInfo.field, sortInfo.direction);
            }
            this.fitColumns(preventRefresh, onlyExpand, omitColumn);
        }

        // collect all visible columns from existing config
        const colsToResolve = [];
        
        cm.config.forEach((col, idx) => {
            if (!cm.isHidden(idx)) {
                if (idx > colIdxOmitColumn) colsToResolve.push(col);
            }
        });
        if (!colsToResolve.length) return;
        
        // handle columns fractional resizing
        const widthToResolve = colsToResolve.reduce((acc, col) => {return acc + col.width;}, 0);
        const colIdxDefaultAutoExpand = this.autoExpand && this.grid.autoExpandColumn ? cm.getIndexById(this.grid.autoExpandColumn) : -1;
        const fraction = isOmitColumnValid
            ? (widthToResolve - widthExtra) / widthToResolve
            : widthResizedGrid / widthToResolve;
        
        colsToResolve.forEach((col) => {
            const idx = _.findIndex(cm.config, (c) => { return col.id === c.id; })
            const width = !cm.isFitable(idx) ? col.width : col.width * fraction;
            let widthResolved = Math.max(this.grid.minColumnWidth, Math.floor(width));
            if (colIdxDefaultAutoExpand < 0 && !isOmitColumnValid && col.width && widthToResolve <= widthResizedGrid) {
                widthResolved = Math.max(col.width, width);
            }
            if (col.useManualWidth && col.width !== col.minWidth) {
                widthResolved = col.width;
            }

            cm.setColumnWidth(idx, Math.floor(widthResolved), true);
        });
        
        // resolve auto expand column index, it should not be on the left hand side
        const widthUpdatedTotalVisibleCols = cm.getTotalWidth(false);
        const diff = widthResizedGrid - widthUpdatedTotalVisibleCols;
        let colIdxResolvedAutoExpand = colIdxDefaultAutoExpand;
        let colAutoExpand = null;
        let isDefaultAutoExpandColReachLimit = false;
        if (colIdxResolvedAutoExpand > -1) {
            colAutoExpand = cm.config[colIdxResolvedAutoExpand];
            isDefaultAutoExpandColReachLimit = (diff > 0 && colAutoExpand.maxWidth === colAutoExpand.width)
                || (diff < 0 && colAutoExpand.width === colAutoExpand.minWidth);
        }
        if (isOmitColumnValid 
            || colIdxDefaultAutoExpand <= colIdxOmitColumn 
            || colAutoExpand?.useManualWidth
            || (colAutoExpand && cm.isHidden(colIdxResolvedAutoExpand))
            || isDefaultAutoExpandColReachLimit
        ) {
            let colIdxLastResizeable =  -1;
            cm.config.forEach((col, idx) => {
                if (!cm.isHidden(idx) && !cm.isFixed(idx) && idx > colIdxOmitColumn) {
                    colIdxLastResizeable = idx;
                }
            });
            colIdxResolvedAutoExpand = colIdxLastResizeable;
        }
        // resized columns might not fit the total width , we should make sure they are equal
        if (colIdxResolvedAutoExpand >= 0 && fraction !== 1) {
            const width = cm.getColumnWidth(colIdxResolvedAutoExpand);
            cm.setColumnWidth(colIdxResolvedAutoExpand, Math.max(this.grid.minColumnWidth,  width + diff), true);
        }
        
        if (this.grid?.stateId && this.grid.stateId === this.latestGridStateId) {
            this.grid.saveState();
        }
        
        if (preventRefresh !== true) this.updateAllColumnWidths();
        
        // browser might draw x-scrollbars when y-scrollbars where shown
        this.scroller.setStyle('overflow-x', diff < 0 ? 'auto' : 'hidden');
        return true;
    },

    // private
    autoExpand : function(preventUpdate){
        var g = this.grid, cm = this.cm;
        if(!this.userResized && g.autoExpandColumn){
            var tw = cm.getTotalWidth(false);
            var aw = this.grid.getGridEl().getWidth(true)-this.getScrollOffset();
            if(tw != aw){
                var ci = cm.getIndexById(g.autoExpandColumn);
                var currentWidth = cm.getColumnWidth(ci);
                var cw = Math.min(Math.max(((aw-tw)+currentWidth), g.autoExpandMin), g.autoExpandMax);
                if(cw != currentWidth && ci >= 0){
                    cm.setColumnWidth(ci, cw, true);
                    if(preventUpdate !== true){
                        this.updateColumnWidth(ci, cw);
                    }
                }
            }
        }
    },

    // private
    getColumnData : function(){
        // build a map for all the columns
        var cs = [], cm = this.cm, colCount = cm.getColumnCount();
        for(var i = 0; i < colCount; i++){
            var name = cm.getDataIndex(i);
            cs[i] = {
                name : (!Ext.isDefined(name) ? this.ds.fields.get(i).name : name),
                renderer : cm.getRenderer(i),
                scope: cm.getRendererScope(i),
                id : cm.getColumnId(i),
                style : this.getColumnStyle(i)
            };
        }
        return cs;
    },

    // private
    renderRows : function(startRow, endRow){
        // pull in all the crap needed to render rows
        var g = this.grid, cm = g.colModel, ds = g.store, stripe = g.stripeRows;
        var colCount = cm.getColumnCount();

        if(ds.getCount() < 1){
            return '';
        }

        var cs = this.getColumnData();

        startRow = startRow || 0;
        endRow = !Ext.isDefined(endRow) ? ds.getCount()-1 : endRow;

        // records to render
        var rs = ds.getRange(startRow, endRow);

        return this.doRender(cs, rs, ds, startRow, colCount, stripe);
    },

    // private
    renderBody : function(){
        var markup = this.renderRows() || '&#160;';
        return this.templates.body.apply({rows: markup});
    },

    // private
    refreshRow : function(record){
        var ds = this.ds, index;
        if(Ext.isNumber(record)){
            index = record;
            record = ds.getAt(index);
            if(!record){
                return;
            }
        }else{
            index = ds.indexOf(record);
            if(index < 0){
                return;
            }
        }
        this.insertRows(ds, index, index, true);
        this.getRow(index).rowIndex = index;
        this.onRemove(ds, record, index+1, true);
        this.fireEvent('rowupdated', this, index, record);
    },

    /**
     * Refreshs the grid UI
     * @param {Boolean} headersToo (optional) True to also refresh the headers
     */
    refresh : function(headersToo){
        if (!this.grid) {
            return;
        }
        
        this.fireEvent('beforerefresh', this);
        if (this.grid.activeEditor?.record) {
            this.grid.activeEditor.record.store = this.grid.store;
        } else {
            this.grid.stopEditing(true);
        }

        var result = this.renderBody();
        this.mainBody.update(result).setWidth(this.getTotalWidth());
        if(headersToo === true){
            this.updateHeaders();
            this.updateHeaderSortState();
        }
        this.processRows(0, true);
        this.layout();
        this.applyEmptyText();
        this.fireEvent('refresh', this);
    },

    // private
    applyEmptyText : function(){
        if(this.emptyText && !this.hasRows()){
            this.mainBody.update('<div class="x-grid-empty">' + this.emptyText + '</div>');
        }
    },

    // private
    updateHeaderSortState : function(){
        var state = this.ds.getSortState();
        if(!state || !this.grid){
            return;
        }
        if(!this.sortState || (this.sortState.field != state.field || this.sortState.direction != state.direction)){
            this.grid.fireEvent('sortchange', this.grid, state);
        }
        this.sortState = state;
        var sortColumn = this.cm.findColumnIndex(state.field);
        if(sortColumn != -1){
            var sortDir = state.direction;
            this.updateSortIcon(sortColumn, sortDir);
        }
    },

    // private
    clearHeaderSortState : function(){
        if(!this.sortState){
            return;
        }
        this.grid.fireEvent('sortchange', this.grid, null);
        this.mainHd.select('td').removeClass(this.sortClasses);
        delete this.sortState;
    },

    // private
    destroy : function(){
        if(this.colMenu){
            Ext.menu.MenuMgr.unregister(this.colMenu);
            this.colMenu.destroy();
            delete this.colMenu;
        }
        if(this.hmenu){
            Ext.menu.MenuMgr.unregister(this.hmenu);
            this.hmenu.destroy();
            delete this.hmenu;
        }

        this.initData(null, null);
        this.purgeListeners();
        Ext.fly(this.innerHd).un("click", this.handleHdDown, this);

        if(this.grid.enableColumnMove){
            Ext.destroy(
                this.columnDrag.el,
                this.columnDrag.proxy.ghost,
                this.columnDrag.proxy.el,
                this.columnDrop.el,
                this.columnDrop.proxyTop,
                this.columnDrop.proxyBottom,
                this.columnDrag.dragData.ddel,
                this.columnDrag.dragData.header
            );
            if (this.columnDrag.proxy.anim) {
                Ext.destroy(this.columnDrag.proxy.anim);
            }
            delete this.columnDrag.proxy.ghost;
            delete this.columnDrag.dragData.ddel;
            delete this.columnDrag.dragData.header;
            this.columnDrag.destroy();
            delete Ext.dd.DDM.locationCache[this.columnDrag.id];
            delete this.columnDrag._domRef;

            delete this.columnDrop.proxyTop;
            delete this.columnDrop.proxyBottom;
            this.columnDrop.destroy();
            delete Ext.dd.DDM.locationCache["gridHeader" + this.grid.getGridEl().id];
            delete this.columnDrop._domRef;
            delete Ext.dd.DDM.ids[this.columnDrop.ddGroup];
        }

        if (this.splitZone){ // enableColumnResize
            this.splitZone.destroy();
            delete this.splitZone._domRef;
            delete Ext.dd.DDM.ids["gridSplitters" + this.grid.getGridEl().id];
        }

        Ext.fly(this.innerHd).removeAllListeners();
        Ext.removeNode(this.innerHd);
        delete this.innerHd;

        Ext.destroy(
            this.el,
            this.mainWrap,
            this.mainHd,
            this.scroller,
            this.mainBody,
            this.focusEl,
            this.resizeMarker,
            this.resizeProxy,
            this.activeHdBtn,
            this.dragZone,
            this.splitZone,
            this._flyweight
        );

        delete this.grid.container;

        if(this.dragZone){
            this.dragZone.destroy();
        }

        Ext.dd.DDM.currentTarget = null;
        delete Ext.dd.DDM.locationCache[this.grid.getGridEl().id];

        Ext.EventManager.removeResizeListener(this.onWindowResize, this);
    },

    // private
    onDenyColumnHide : function(){

    },

    // private
    render : function(){
        if(this.autoFill){
            var ct = this.grid.ownerCt;
            if (ct && ct.getLayout()){
                ct.on('afterlayout', function(){ 
                    this.fitColumns(true, true);
                    this.updateHeaders();
                    this.updateHeaderSortState();
                }, this, {single: true}); 
            }else{ 
                this.fitColumns(true, true); 
            }
        }else if(this.forceFit){
            this.fitColumns(true, false);
        }else if(this.grid.autoExpandColumn){
            this.autoExpand(true);
        }

        this.renderUI();
    },

    /* --------------------------------- Model Events and Handlers --------------------------------*/
    // private
    initData : function(ds, cm){
        if(this.ds){
            this.ds.un('load', this.onLoad, this);
            this.ds.un('datachanged', this.onDataChange, this);
            this.ds.un('add', this.onAdd, this);
            this.ds.un('remove', this.onRemove, this);
            this.ds.un('update', this.onUpdate, this);
            this.ds.un('clear', this.onClear, this);
            if(this.ds !== ds && this.ds.autoDestroy){
                this.ds.destroy();
            }
        }
        if(ds){
            ds.on({
                scope: this,
                load: this.onLoad,
                datachanged: this.onDataChange,
                add: this.onAdd,
                remove: this.onRemove,
                update: this.onUpdate,
                clear: this.onClear
            });
        }
        this.ds = ds;

        if(this.cm){
            this.cm.un('configchange', this.onColConfigChange, this);
            this.cm.un('widthchange', this.onColWidthChange, this);
            this.cm.un('headerchange', this.onHeaderChange, this);
            this.cm.un('hiddenchange', this.onHiddenChange, this);
            this.cm.un('columnmoved', this.onColumnMove, this);
        }
        if(cm){
            delete this.lastViewWidth;
            cm.on({
                scope: this,
                configchange: this.onColConfigChange,
                widthchange: this.onColWidthChange,
                headerchange: this.onHeaderChange,
                hiddenchange: this.onHiddenChange,
                columnmoved: this.onColumnMove
            });
            const hasResponsive = cm.config.find(c => { return c.id === 'responsive';});
            if (!hasResponsive && !this.disableResponsiveLayout) {
                const source = this.grid?.parentScope ?? this.grid;
                cm.config.push({
                    id: 'responsive',
                    header: " Title ",
                    sortable: false,
                    hidden: true,
                    renderer: source && Ext.isFunction(source.oneColumnRenderer) ?
                        source.oneColumnRenderer.createDelegate(this) :
                        this.oneColumnRenderer.createDelegate(this)
                });
                cm.setConfig(cm.config, true);
            }
        }
        this.cm = cm;
    },

    /**
     * responsive content Renderer
     *
     * @param {Object} metadata
     * @param {Folder|Account} record
     * @return {String}
     */
    oneColumnRenderer: function(recordId, metadata, record, rowIndex, colIndex, store) {
        if (this?.grid?.stateId) {
            const stateIdDefault = this.grid.stateId;
            if (!Ext.state.Manager.get(stateIdDefault)) this.grid.saveState();
        }
        const block =  document.createElement('div');
        block.className = 'responsive-title';

        if (Ext.isFunction(record.getTitle)) {
            const row =  document.createElement('div');
            row.className = 'responsive-grid-row';
            
            const titleEl = document.createElement('div');
            titleEl.className = 'responsive-grid-text-medium';
            titleEl.innerHTML = Tine.Tinebase.EncodingHelper.encode(record.getTitle());
            
            row.appendChild(titleEl);
            block.appendChild(row);
        } else {
            const metaDataCopy = {...metadata};
            
            this.cm.config.forEach((col, idx) => {
                //idx = stateStored?.columns ? _.findIndex(stateStored.columns, {id: col.id}) : idx;
                const hidden = col?.hidden;
                if (col.dataIndex !== 'responsive' && !hidden) {
                    const row =  document.createElement('div');
                    row.className = 'responsive-grid-row';
                    
                    const row1Left = document.createElement('div');
                    row1Left.className = 'responsive-grid-row-left';
                    
                    const headerEl = document.createElement('div');
                    headerEl.innerHTML = col.header;
                    headerEl.className = 'responsive-grid-text-small';
                    headerEl.style.fontWeight = 'bold';
                    row1Left.appendChild(headerEl);
                    
                    const row1Right =  document.createElement('div');
                    row1Right.className = 'responsive-grid-row-right';
                    const renderer = this.cm.getRenderer(idx);
                    const content = renderer.call(col.scope, record.data[col.dataIndex], metaDataCopy, record, rowIndex, colIndex, store);
                    
                    const contentEl = document.createElement('div');
                    contentEl.innerHTML = content;
                    row1Right.appendChild(contentEl);
                    
                    if (!content) row.innerHTML = record.data[col.dataIndex] ?? '';
                    row.appendChild(row1Left);
                    row.appendChild(row1Right);
                    block.appendChild(row);
                }
            })
        }
        return  block.outerHTML;
    },

    // private
    onDataChange : function(){
        this.refresh(true);
        this.updateHeaderSortState();
        this.syncFocusEl(0);
    },

    // private
    onClear : function(){
        this.refresh();
        this.syncFocusEl(0);
    },

    // private
    onUpdate : function(ds, record){
        this.refreshRow(record);
    },

    // private
    onAdd : function(ds, records, index){
	
        this.insertRows(ds, index, index + (records.length-1));
    },

    // private
    onRemove : function(ds, record, index, isUpdate){
        if(isUpdate !== true){
            this.fireEvent('beforerowremoved', this, index, record);
        }
        this.removeRow(index);
        if(isUpdate !== true){
            this.processRows(index);
            this.applyEmptyText();
            this.fireEvent('rowremoved', this, index, record);
        }
    },

    // private
    onLoad : function(){
        this.scrollToTop.defer(Ext.isGecko ? 1 : 0, this);
    },

    // private
    onColWidthChange : function(cm, col, width){
        this.updateColumnWidth(col, width);
    },

    // private
    onHeaderChange : function(cm, col, text){
        this.updateHeaders();
    },

    // private
    onHiddenChange : function(cm, col, hidden){
        this.updateColumnHidden(col, hidden);
    },

    // private
    onColumnMove : function(cm, oldIndex, newIndex){
        this.indexMap = null;
        var s = this.getScrollState();
        this.refresh(true);
        this.restoreScroll(s);
        this.afterMove(newIndex);
        this.grid.fireEvent('columnmove', oldIndex, newIndex);
    },

    // private
    onColConfigChange : function(){
        delete this.lastViewWidth;
        this.indexMap = null;
        this.refresh(true);
    },

    /* -------------------- UI Events and Handlers ------------------------------ */
    // private
    initUI : function(grid){
        grid.on('headerclick', this.onHeaderClick, this);
    },

    // private
    initEvents : function(){
    },

    // private
    onHeaderClick : function(g, index){
        if(this.headersDisabled || !this.cm.isSortable(index)){
            return;
        }
        g.stopEditing(true);
        g.store.sort(this.cm.getDataIndex(index));
    },

    // private
    onRowOver : function(e, t){
        var row;
        if((row = this.findRowIndex(t)) !== false){
            this.addRowClass(row, 'x-grid3-row-over');
        }
    },

    // private
    onRowOut : function(e, t){
        var row;
        if((row = this.findRowIndex(t)) !== false && !e.within(this.getRow(row), true)){
            this.removeRowClass(row, 'x-grid3-row-over');
        }
    },

    // private
    handleWheel : function(e){
        e.stopPropagation();
    },

    // private
    onRowSelect : function(row){
        this.addRowClass(row, this.selectedRowClass);
    },

    // private
    onRowDeselect : function(row){
        this.removeRowClass(row, this.selectedRowClass);
    },

    // private
    onCellSelect : function(row, col){
        var cell = this.getCell(row, col);
        if(cell){
            this.fly(cell).addClass('x-grid3-cell-selected');
        }
    },

    // private
    onCellDeselect : function(row, col){
        var cell = this.getCell(row, col);
        if(cell){
            this.fly(cell).removeClass('x-grid3-cell-selected');
        }
    },

    // private
    onColumnSplitterMoved : function(i, w){
        this.userResized = true;
        const cm = this.grid.colModel;
        const oldWidth = cm.getColumnWidth(i);
        cm.setColumnWidth(i, w, true, true);
        const newWidth = cm.getColumnWidth(i);

        if (oldWidth === newWidth) return;
        
        if(this.forceFit){
            this.fitColumns(true, false, i);
            this.updateAllColumnWidths();
        }else{
            this.updateColumnWidth(i, w);
            this.syncHeaderScroll();
        }

        this.grid.fireEvent('columnresize', i, w);
    },

    // private
    handleHdMenuClick : function(item){
        var index = this.hdCtxIndex,
            cm = this.cm, 
            ds = this.ds,
            id = item.getItemId();
        switch(id){
            case 'asc':
                ds.sort(cm.getDataIndex(index), 'ASC');
                break;
            case 'desc':
                ds.sort(cm.getDataIndex(index), 'DESC');
                break;
            default:
                index = cm.getIndexById(id.substr(4));
                if(index !== -1){
                    if(item.checked && cm.getColumnsBy(this.isHideableColumn, this).length <= 1){
                        this.onDenyColumnHide();
                        return false;
                    }
                    cm.setHidden(index, item.checked);
                }
        }
        return true;
    },

    // private
    isHideableColumn : function(c){
        return !c.hidden && !c.fixed;
    },

    // private
    beforeColMenuShow : function(){
        var cm = this.cm,  colCount = cm.getColumnCount();
        this.colMenu.removeAll();
        for(var i = 0; i < colCount; i++){
            if(cm.config[i].fixed !== true && cm.config[i].hideable !== false && cm.config[i].id !== 'responsive'){
                this.colMenu.add(new Ext.menu.CheckItem({
                    itemId: 'col-'+cm.getColumnId(i),
                    text: cm.getColumnHeader(i),
                    checked: !cm.isHidden(i),
                    hideOnClick:false,
                    disabled: cm.config[i].hideable === false
                }));
            }
        }
    },

    // private
    handleHdDown : function(e, t){
        if(Ext.fly(t).hasClass('x-grid3-hd-btn')){
            e.stopEvent();
            var hd = this.findHeaderCell(t);
            Ext.fly(hd).addClass('x-grid3-hd-menu-open');
            var index = this.getCellIndex(hd);
            this.hdCtxIndex = index;
            var ms = this.hmenu.items, cm = this.cm;
            ms.get('asc').setDisabled(!cm.isSortable(index));
            ms.get('desc').setDisabled(!cm.isSortable(index));
            this.hmenu.on('hide', function(){
                Ext.fly(hd).removeClass('x-grid3-hd-menu-open');
            }, this, {single:true});
            this.hmenu.show(t, 'tl-bl?');
        }
    },

    // private
    handleHdOver : function(e, t){
        var hd = this.findHeaderCell(t);
        if(hd && !this.headersDisabled){
            this.activeHdRef = t;
            this.activeHdIndex = this.getCellIndex(hd);
            var fly = this.fly(hd);
            this.activeHdRegion = fly.getRegion();
            if(!this.cm.isMenuDisabled(this.activeHdIndex)){
                fly.addClass('x-grid3-hd-over');
                this.activeHdBtn = fly.child('.x-grid3-hd-btn');
                if(this.activeHdBtn){
                    this.activeHdBtn.dom.style.height = (hd.firstChild.offsetHeight-1)+'px';
                }
            }
        }
    },

    // private
    handleHdMove : function(e, t){
        var hd = this.findHeaderCell(this.activeHdRef);
        if(hd && !this.headersDisabled){
            var hw = this.splitHandleWidth || 5,
                r = this.activeHdRegion,
                x = e.getPageX(),
                ss = hd.style,
                cur = '';
            if(this.grid.enableColumnResize !== false){
                if(x - r.left <= hw && this.cm.isResizable(this.activeHdIndex-1)){
                    cur = Ext.isAir ? 'move' : Ext.isWebKit ? 'e-resize' : 'col-resize'; // col-resize not always supported
                }else if(r.right - x <= (!this.activeHdBtn ? hw : 2) && this.cm.isResizable(this.activeHdIndex)){
                    cur = Ext.isAir ? 'move' : Ext.isWebKit ? 'w-resize' : 'col-resize';
                }
            }
            ss.cursor = cur;
        }
    },

    // private
    handleHdOut : function(e, t){
        var hd = this.findHeaderCell(t);
        if(hd && (!Ext.isIE || !e.within(hd, true))){
            this.activeHdRef = null;
            this.fly(hd).removeClass('x-grid3-hd-over');
            hd.style.cursor = '';
        }
    },

    // private
    hasRows : function(){
        var fc = this.mainBody.dom.firstChild;
        return fc && fc.nodeType == 1 && fc.className != 'x-grid-empty';
    },

    // back compat
    bind : function(d, c){
        this.initData(d, c);
    }
});


// private
// This is a support class used internally by the Grid components
Ext.grid.GridView.SplitDragZone = function(grid, hd){
    this.grid = grid;
    this.view = grid.getView();
    this.marker = this.view.resizeMarker;
    this.proxy = this.view.resizeProxy;
    Ext.grid.GridView.SplitDragZone.superclass.constructor.call(this, hd,
        'gridSplitters' + this.grid.getGridEl().id, {
        dragElId : Ext.id(this.proxy.dom), resizeFrame:false
    });
    this.scroll = false;
    this.hw = this.view.splitHandleWidth || 5;
};
Ext.extend(Ext.grid.GridView.SplitDragZone, Ext.dd.DDProxy, {

    b4StartDrag : function(x, y){
        this.view.headersDisabled = true;
        var h = this.view.mainWrap.getHeight();
        this.marker.setHeight(h);
        this.marker.show();
        this.marker.alignTo(this.view.getHeaderCell(this.cellIndex), 'tl-tl', [-2, 0]);
        this.proxy.setHeight(h);
        var w = this.cm.getColumnWidth(this.cellIndex);
        var minw = Math.max(w-this.grid.minColumnWidth, 0);
        this.resetConstraints();
        this.setXConstraint(minw, 1000);
        this.setYConstraint(0, 0);
        this.minX = x - minw;
        this.maxX = x + 1000;
        this.startPos = x;
        Ext.dd.DDProxy.prototype.b4StartDrag.call(this, x, y);
    },
    
    allowHeaderDrag : function(e){
        return true;
    },


    handleMouseDown : function(e){
        var t = this.view.findHeaderCell(e.getTarget());
        if(t && this.allowHeaderDrag(e)){
            var xy = this.view.fly(t).getXY(), x = xy[0], y = xy[1];
            var exy = e.getXY(), ex = exy[0];
            var w = t.offsetWidth, adjust = false;
            if((ex - x) <= this.hw){
                adjust = -1;
            }else if((x+w) - ex <= this.hw){
                adjust = 0;
            }
            if(adjust !== false){
                this.cm = this.grid.colModel;
                var ci = this.view.getCellIndex(t);
                if(adjust == -1){
                  if (ci + adjust < 0) {
                    return;
                  }
                    while(this.cm.isHidden(ci+adjust)){
                        --adjust;
                        if(ci+adjust < 0){
                            return;
                        }
                    }
                }
                this.cellIndex = ci+adjust;
                this.split = t.dom;
                if(this.cm.isResizable(this.cellIndex) && !this.cm.isFixed(this.cellIndex)){
                    Ext.grid.GridView.SplitDragZone.superclass.handleMouseDown.apply(this, arguments);
                }
            }else if(this.view.columnDrag){
                this.view.columnDrag.callHandleMouseDown(e);
            }
        }
    },

    endDrag : function(e){
        this.marker.hide();
        var v = this.view;
        var endX = Math.max(this.minX, e.getPageX());
        var diff = endX - this.startPos;
        v.onColumnSplitterMoved(this.cellIndex, this.cm.getColumnWidth(this.cellIndex)+diff);
        setTimeout(function(){
            v.headersDisabled = false;
        }, 50);
    },

    autoOffset : function(){
        this.setDelta(0,0);
    }
});
