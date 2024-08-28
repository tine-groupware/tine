/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.Viewport
 * @extends Ext.Container
 * <p>A specialized container representing the viewable application area (the browser viewport).</p>
 * <p>The Viewport renders itself to the document body, and automatically sizes itself to the size of
 * the browser viewport and manages window resizing. There may only be one Viewport created
 * in a page. Inner layouts are available by virtue of the fact that all {@link Ext.Panel Panel}s
 * added to the Viewport, either through its {@link #items}, or through the items, or the {@link #add}
 * method of any of its child Panels may themselves have a layout.</p>
 * <p>The Viewport does not provide scrolling, so child Panels within the Viewport should provide
 * for scrolling if needed using the {@link #autoScroll} config.</p>
 * <p>An example showing a classic application border layout:</p><pre><code>
new Ext.Viewport({
    layout: 'border',
    items: [{
        region: 'north',
        html: '&lt;h1 class="x-panel-header">Page Title&lt;/h1>',
        autoHeight: true,
        border: false,
        margins: '0 0 5 0'
    }, {
        region: 'west',
        collapsible: true,
        title: 'Navigation',
        width: 200
        // the west region might typically utilize a {@link Ext.tree.TreePanel TreePanel} or a Panel with {@link Ext.layout.AccordionLayout Accordion layout}
    }, {
        region: 'south',
        title: 'Title for Panel',
        collapsible: true,
        html: 'Information goes here',
        split: true,
        height: 100,
        minHeight: 100
    }, {
        region: 'east',
        title: 'Title for the Grid Panel',
        collapsible: true,
        split: true,
        width: 200,
        xtype: 'grid',
        // remaining grid configuration not shown ...
        // notice that the GridPanel is added directly as the region
        // it is not "overnested" inside another Panel
    }, {
        region: 'center',
        xtype: 'tabpanel', // TabPanel itself has no title
        items: {
            title: 'Default Tab',
            html: 'The first tab\'s content. Others may be added dynamically'
        }
    }]
});
</code></pre>
 * @constructor
 * Create a new Viewport
 * @param {Object} config The config object
 * @xtype viewport
 */
Ext.Viewport = Ext.extend(Ext.Container, {
    /*
     * Privatize config options which, if used, would interfere with the
     * correct operation of the Viewport as the sole manager of the
     * layout of the document body.
     */
    /**
     * @cfg {Mixed} applyTo @hide
     */
    /**
     * @cfg {Boolean} allowDomMove @hide
     */
    /**
     * @cfg {Boolean} hideParent @hide
     */
    /**
     * @cfg {Mixed} renderTo @hide
     */
    /**
     * @cfg {Boolean} hideParent @hide
     */
    /**
     * @cfg {Number} height @hide
     */
    /**
     * @cfg {Number} width @hide
     */
    /**
     * @cfg {Boolean} autoHeight @hide
     */
    /**
     * @cfg {Boolean} autoWidth @hide
     */
    /**
     * @cfg {Boolean} deferHeight @hide
     */
    /**
     * @cfg {Boolean} monitorResize @hide
     */

    initComponent : function() {
        Ext.Viewport.superclass.initComponent.call(this);
        document.getElementsByTagName('html')[0].className += ' x-viewport';
        this.el = Ext.getBody();
        this.el.setHeight = Ext.emptyFn;
        this.el.setWidth = Ext.emptyFn;
        this.el.setSize = Ext.emptyFn;
        this.el.dom.scroll = 'no';
        this.allowDomMove = false;
        this.autoWidth = true;
        this.autoHeight = true;
        Ext.EventManager.onWindowResize(this.fireResize, this);
        this.renderTo = this.el;
        this.colorSchemeAction = new Ext.Action({
            menu: [{
                text: 'Follow System', // _('Follow System')
                checked: (['dark', 'light'].indexOf(Ext.util.Cookies.get('color-scheme')) < 0),
                group: 'color-scheme',
                checkHandler: this.setColorScheme.createDelegate(this, ['auto']),
                _name: 'auto'
            }, {
                text: 'Dark Mode', // _('Dark Mode')
                checked: Ext.util.Cookies.get('color-scheme') === 'dark',
                group: 'color-scheme',
                checkHandler: this.setColorScheme.createDelegate(this, ['dark']),
                _name: 'dark'
            }, {
                text: 'Light Mode', // _('Light Mode')
                checked: Ext.util.Cookies.get('color-scheme') === 'light',
                group: 'color-scheme',
                checkHandler: this.setColorScheme.createDelegate(this, ['light']),
                _name: 'light'
            }],
            getActiveColorScheme: () => Ext.util.Cookies.get('color-scheme') || 'auto',
            listeners: {
                afterrender: () => {
                    this.colorSchemeAction.items[0].menu.items.each((item) => {
                        item.setText(i18n._hidden(item.text));
                    })
                }
            }
        });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', this.onColorSchemeChange);
        this.onColorSchemeChange();
    },

    fireResize : function(w, h){
        this.fireEvent('resize', this, w, h, w, h);
    },
    onColorSchemeChange : function(){
        const system = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const preference = Ext.util.Cookies.get('color-scheme') || 'auto';
        const mode = ['dark', 'light'].indexOf(preference) >= 0 ? preference : system;
        const jobs = mode === 'dark' ? ['light','dark'] : ['dark','light'];
        Ext.getBody().removeClass(`${jobs[0]}-mode`);
        Ext.getBody().addClass(`${jobs[1]}-mode`);
        this.colorSchemeAction.setIconClass(`color-scheme-${mode}`);
    },
    setColorScheme : function(schema) {
        Ext.util.Cookies.set('color-scheme', schema, new Date().add(Date.YEAR, 100));
        this.onColorSchemeChange();
    },
});
Ext.reg('viewport', Ext.Viewport);
