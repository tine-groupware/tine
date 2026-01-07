/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.layout.ToolbarLayout
 * @extends Ext.layout.ContainerLayout
 * Layout manager implicitly used by Ext.Toolbar.
 */
Ext.layout.ToolbarLayout = Ext.extend(Ext.layout.ContainerLayout, {
    monitorResize : true,
    triggerWidth : 18,
    lastOverflow : false,

    noItemsMenuText : '<div class="x-toolbar-no-items">(None)</div>',

    // private
    onLayout : function(ct, target){
        if(!this.leftTr){
            var align = ct.buttonAlign == 'center' ? 'center' : 'left';
            target.addClass('x-toolbar-layout-ct');
            target.insertHtml('beforeEnd',
                 '<table cellspacing="0" class="x-toolbar-ct"><tbody><tr><td class="x-toolbar-left" align="' + align + '"><table cellspacing="0"><tbody><tr class="x-toolbar-left-row"></tr></tbody></table></td><td class="x-toolbar-right" align="right"><table cellspacing="0" class="x-toolbar-right-ct"><tbody><tr><td><table cellspacing="0"><tbody><tr class="x-toolbar-right-row"></tr></tbody></table></td><td><table cellspacing="0"><tbody><tr class="x-toolbar-extras-row"></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table>');
            this.leftTr = target.child('tr.x-toolbar-left-row', true);
            this.rightTr = target.child('tr.x-toolbar-right-row', true);
            this.extrasTr = target.child('tr.x-toolbar-extras-row', true);
        }

        var side = ct.buttonAlign == 'right' ? this.rightTr : this.leftTr,
            pos = 0,
            items = ct.items.items;

        for(var i = 0, len = items.length, c; i < len; i++, pos++) {
            c = items[i];
            if(c.isFill){
                side = this.rightTr;
                pos = -1;
            }else if(!c.rendered){
                c.render(this.insertCell(c, side, pos));
            }else{
                if(!c.xtbHidden && !this.isValidParent(c, side.childNodes[pos])){
                    var td = this.insertCell(c, side, pos);
                    td.appendChild(c.getPositionEl().dom);
                    c.container = Ext.get(td);
                }
            }
        }
        //strip extra empty cells
        this.cleanup(this.leftTr);
        this.cleanup(this.rightTr);
        this.cleanup(this.extrasTr);
        this.fitToSize(target);
    },

    cleanup : function(row){
        var cn = row.childNodes, i, c;
        for(i = cn.length-1; i >= 0 && (c = cn[i]); i--){
            if(!c.firstChild){
                row.removeChild(c);
            }
        }
    },

    insertCell : function(c, side, pos){
        var td = document.createElement('td');
        td.className='x-toolbar-cell';
        side.insertBefore(td, side.childNodes[pos]||null);
        return td;
    },

    hideItem : function(item){
        var h = (this.hiddens = this.hiddens || []);
        h.push(item);
        item.xtbHidden = true;
        item.xtbWidth = item.getPositionEl().dom.parentNode.offsetWidth;
        item.hide();
    },

    unhideItem : function(item){
        item.show();
        item.xtbHidden = false;
        this.hiddens.remove(item);
        if(this.hiddens.length < 1){
            delete this.hiddens;
        }
    },

    getItemWidth : function(c){
        return c.hidden ? (c.xtbWidth || 0) : c.getPositionEl().dom.parentNode.offsetWidth;
    },

    fitToSize : function(t){
        // 2026-01-06 - cw - this is not used right?
        // but it prevents more buttons.
        // one problem is, that setWidth does not shrink buttons
        // it might be an idea to shorten/extend the text (char by char)
        // we might support a shortText or remove text and have tip only
        if(false && this.container.enableOverflow === false && !this.enableResponsive){
            const items = this.container.items.items;
            const w = t.dom.clientWidth;
            const autoShrinkItems = items.filter(el => el.autoShrink);
            if (autoShrinkItems.length) {
                let loopWidth = 0,
                    unshrunkWidth = 0;
                for (let i = 0; i < items.length; i++){
                    const c = items[i];
                    if (!c.isFill){
                        loopWidth += this.getItemWidth(c);
                        unshrunkWidth += c.originalWidth ?? c.width ?? this.getItemWidth(c);
                    }
                }
                if (loopWidth - w > 10) {
                    for (let j = 0; j < autoShrinkItems.length; j++){
                        const c = autoShrinkItems[j];
                        const cw = this.getItemWidth(c);
                        c.originalWidth ??= c.width ?? cw
                        c.setWidth(cw - 10);
                    }
                    this.fitToSize(t);
                } else {
                    if ((10 < unshrunkWidth - loopWidth) && (Math.abs(w - loopWidth) > 10)) {
                        let refit = false;
                        for (let j = 0; j < autoShrinkItems.length; j++){
                            c = autoShrinkItems[j];
                            const cw = this.getItemWidth(c);
                            c.originalWidth ??= c.width ?? cw;
                            if (cw >= c.originalWidth) continue
                            c.setWidth(cw + 10)
                            refit = true
                        }
                        if (refit) this.fitToSize(t);
                    }
                }
            }
            return;
        }
        var w = t.dom.clientWidth,
            lw = this.lastWidth || 0,
            iw = t.dom.firstChild.offsetWidth,
            clipWidth = w - this.triggerWidth,
            hideIndex = -1;

        this.lastWidth = w;

        if(iw > w || (this.hiddens && w >= lw)){
            var i, items = this.container.items.items,
                len = items.length, c,
                loopWidth = 0;

            const itemsDict = {}
            for (i=0; i<len; i++){
                c = items[i];
                let totalVisibleWidth = 0;
                let priority = c.displayPriority ?? 0;
                if (!c.hasOwnProperty('displayPriority') && c.el?.dom.classList.contains('xtb-sep') && i !== len - 1) {
                    priority = items[i+1].displayPriority;
                }
                if (!itemsDict[priority]) itemsDict[priority] = [];
                itemsDict[priority].push(c);
                if (!c?.el?.dom) continue;
                c.el.dom.style.display = '';
                const width = c.el.dom.offsetWidth ?? 0;
                totalVisibleWidth += width;
            }

            const sortedItems = Object.keys(itemsDict).sort(function(a,b){return b-a});

            for (let i=0; i<sortedItems.length; i++) {
                const items = itemsDict[sortedItems[i]];
                for (let j=0; j<items.length; j++){
                    const c = items[j];
                    if (!c.isFill) {
                        loopWidth += this.getItemWidth(items[j]);
                        if(loopWidth > clipWidth){
                            if(!(c.hidden || c.xtbHidden)){
                                this.hideItem(c);
                            }
                        }else if(c.xtbHidden){
                            this.unhideItem(c);
                        }
                    }
                }
            }
        }
        if(this.hiddens && !this.enableResponsive){
            this.initMore();
            if(!this.lastOverflow){
                this.container.fireEvent('overflowchange', this.container, true);
                this.lastOverflow = true;
            }
        }else if(this.more){
            this.clearMenu();
            this.more.destroy();
            delete this.more;
            if(this.lastOverflow){
                this.container.fireEvent('overflowchange', this.container, false);
                this.lastOverflow = false;
            }
        }
    },

    createMenuConfig : function(c, hideOnClick){
        var cfg = Ext.apply({}, c.initialConfig),
            group = c.toggleGroup;

        Ext.apply(cfg, {
            text: c.overflowText || c.text,
            iconCls: c.iconCls,
            icon: c.icon,
            itemId: c.itemId,
            disabled: c.disabled,
            handler: c.handler,
            scope: c.scope,
            menu: c.menu,
            hideOnClick: hideOnClick
        });
        if(group || c.enableToggle){
            Ext.apply(cfg, {
                group: group,
                checked: c.pressed,
                listeners: {
                    checkchange: function(item, checked){
                        c.toggle(checked);
                    }
                }
            });
        }
        delete cfg.ownerCt;
        delete cfg.xtype;
        delete cfg.id;
        return cfg;
    },

    // private
    addComponentToMenu : function(m, c){
        if(c instanceof Ext.Toolbar.Separator){
            m.add('-');
        }else if(Ext.isFunction(c.isXType)){
            if(c.isXType('splitbutton')){
                m.add(this.createMenuConfig(c, true));
            }else if(c.isXType('button')){
                m.add(this.createMenuConfig(c, !c.menu));
            }else if(c.isXType('buttongroup')){
                c.items.each(function(item){
                     this.addComponentToMenu(m, item);
                }, this);
            }
        }
    },

    clearMenu : function(){
        var m = this.moreMenu;
        if(m && m.items){
            m.items.each(function(item){
                delete item.menu;
            });
        }
    },

    // private
    beforeMoreShow : function(m){
        var h = this.container.items.items,
            len = h.length,
            c,
            prev,
            needsSep = function(group, item){
                return group.isXType('buttongroup') && !(item instanceof Ext.Toolbar.Separator);
            };

        this.clearMenu();
        m.removeAll();
        for(var i = 0; i < len; i++){
            c = h[i];
            if(c.xtbHidden || m.showAll){
                if(prev && (needsSep(c, prev) || needsSep(prev, c))){
                    m.add('-');
                }
                this.addComponentToMenu(m, c);
                prev = c;
            }
        }
        // put something so the menu isn't empty
        // if no compatible items found
        if(m.items.length < 1){
            m.add(this.noItemsMenuText);
        }
    },

    initMore : function(){
        if(!this.more){
            this.moreMenu = new Ext.menu.Menu({
                ownerCt : this.container,
                listeners: {
                    beforeshow: this.beforeMoreShow,
                    scope: this
                }

            });
            this.more = new Ext.Button({
                iconCls : 'x-toolbar-more-icon',
                cls     : 'x-toolbar-more',
                menu    : this.moreMenu,
                ownerCt : this.container
            });
            var td = this.insertCell(this.more, this.extrasTr, 100);
            this.more.render(td);
        }
    },

    destroy : function(){
        Ext.destroy(this.more, this.moreMenu);
        delete this.leftTr;
        delete this.rightTr;
        delete this.extrasTr;
        Ext.layout.ToolbarLayout.superclass.destroy.call(this);
    }
});

Ext.Container.LAYOUTS.toolbar = Ext.layout.ToolbarLayout;