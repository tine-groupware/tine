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
                 '<table cellspacing="0" role="presentation" class="x-toolbar-ct"><tbody><tr><td class="x-toolbar-left" align="' + align + '"><table role="presentation"cellspacing="0"><tbody><tr class="x-toolbar-left-row"></tr></tbody></table></td><td class="x-toolbar-right" align="right"><table role="presentation"cellspacing="0" class="x-toolbar-right-ct"><tbody><tr><td><table role="presentation"cellspacing="0"><tbody><tr class="x-toolbar-right-row"></tr></tbody></table></td><td><table role="presentation"cellspacing="0"><tbody><tr class="x-toolbar-extras-row"></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table>');
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
        const td = document.createElement('td');
        td.className='x-toolbar-cell';
        side.insertBefore(td, side.childNodes[pos]||null);
        return td;
    },

    getItemWidth : function(c){
        return c.hidden ? (c.xtbWidth || 0) : c.getPositionEl().dom.parentNode.offsetWidth;
    },

    fitToSize : function(toolbarEl){
        const containerWidth = toolbarEl.dom.clientWidth,
            lastContainerWidth = this.lastWidth || 0,
            contentWidth = toolbarEl.dom.firstChild.offsetWidth,
            clipWidth = containerWidth - this.triggerWidth;

        if (containerWidth === 0) return;
        this.lastWidth = containerWidth;

        if (contentWidth > containerWidth || (this.hiddens && containerWidth >= lastContainerWidth)) {
            this.resolveByPriority(clipWidth);
        }

        if (this.hiddens && !this.enableResponsive) {
            this.initMore();
            if (!this.lastOverflow) {
                this.container.fireEvent('overflowchange', this.container, true);
                this.lastOverflow = true;
            }
        } else if (this.more) {
            this.clearMenu();
            this.more.destroy();
            delete this.more;
            if (this.lastOverflow) {
                this.container.fireEvent('overflowchange', this.container, false);
                this.lastOverflow = false;
            }
        }
    },

    resolveByPriority(clipWidth) {
        const toolbarItems = this.container.items.items;
        const itemCount = toolbarItems.length;
        const itemsByPriority = {};

        for (let i = 0; i < itemCount; i++) {
            const item = toolbarItems[i];
            let priority = item.displayPriority ?? 0;

            if (!item.hasOwnProperty('displayPriority') && item.el?.dom.classList.contains('xtb-sep') && i !== itemCount - 1) {
                priority = toolbarItems[i + 1].displayPriority ?? 0;
            }

            if (!itemsByPriority[priority]) itemsByPriority[priority] = [];
            itemsByPriority[priority].push(item);

            this.resetDisplay(item);
        }

        const sortedPriorities = Object.keys(itemsByPriority).sort((a, b) => b - a);

        let usedWidth = 0;
        for (const priority of sortedPriorities) {
            for (const item of itemsByPriority[priority]) {
                if (item.isFill) continue;
                usedWidth = this.resolveItem(item, clipWidth, usedWidth, this);
            }
        }
    },

    resetDisplay(item) {
        if (!item?.el?.dom) return;
        item.el.dom.style.display = '';

        if (this.isOverflowGroup(item)) {
            item.items.items.forEach(child => this.resetDisplay(child));
        }
    },

    isOverflowGroup(item) {
        return item.xtype === 'buttongroup'
            && item.items?.items?.length
            && item.layout instanceof Ext.layout.ToolbarLayout
            && item.layout !== this;
    },

    resolveItem(item, clipWidth, usedWidth, owner) {
        if (this.isOverflowGroup(item)) {
            const groupOwner = item.layout;
            for (const child of item.items.items) {
                if (child.isFill) continue;
                usedWidth = this.resolveItem(child, clipWidth, usedWidth, groupOwner);
            }
            this.syncGroupMoreMenu(groupOwner);
            return usedWidth;
        }

        usedWidth += this.getItemWidth(item);

        if (usedWidth > clipWidth) {
            if (!item.xtbHidden) this.setItemHidden(owner, item, true);
        } else if (item.xtbHidden) {
            this.setItemHidden(owner, item, false);
        }
        return usedWidth;
    },

    setItemHidden(owner, item, hidden) {
        const cell = item.getPositionEl?.().dom.parentNode;
        if (!cell) return;

        item.xtbHidden = hidden;
        item.hidden = hidden;

        if (hidden) {
            item.xtbWidth = cell.offsetWidth;
            cell.style.display = 'none';
            (owner.hiddens = owner.hiddens || []).push(item);
        } else {
            cell.style.display = '';
            if (owner.hiddens) {
                owner.hiddens.remove(item);
                if (owner.hiddens.length < 1) delete owner.hiddens;
            }
        }
    },

    syncGroupMoreMenu(groupOwner) {
        if (groupOwner.hiddens && !groupOwner.more) {
            groupOwner.initMore();
        } else if (!groupOwner.hiddens && groupOwner.more) {
            groupOwner.clearMenu();
            groupOwner.more.destroy();
            delete groupOwner.more;
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
        var h = this.hiddens,
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
        if(this.more) return;

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

        const referenceItem = this.hiddens && this.hiddens[0];
        const isRightRow = referenceItem?.el?.dom && this.rightTr?.contains(referenceItem.el.dom);

        var td = this.insertCell(this.more, isRightRow ? this.extrasTr : this.leftTr, 100);
        this.more.render(td);
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