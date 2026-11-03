/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.ColorManager
 * Colormanager for Coloring Calendar Events <br>
 * 
 * @constructor
 * Creates a new color manager
 * @param {Object} config
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Calendar.ColorManager = Ext.extend(Tine.Tinebase.ColorManager, {

    getStrategy: function() {
        let p = Tine.Calendar.ColorManager.colorStrategyBtn.prototype;
        let name = Ext.state.Manager.get(p.stateId, p).colorStrategy;

        return Tine.Calendar.colorStrategies[name];
    },

    getInfoBarStrategy: function() {
        let p = Tine.Calendar.ColorManager.colorStrategyBtn.prototype;
        let name = Ext.state.Manager.get(p.stateId, p).infoBarColorStrategy;
        if (! Tine.Calendar.infoBarColorStrategies[name]) {
            // fallback to default value
            name = 'response';
        }

        return Tine.Calendar.infoBarColorStrategies[name];
    },

    /**
     * hack for container only support
     * 
     * @param {Tine.Calendar.Model.Evnet} event
     * @return {Object} colorset
     */
    getColor: function(event, attendeeRecord) {
        var color = this.getStrategy().getColor(event, attendeeRecord);

        color = String(color).replace('#', '');
        return this.getSchema(color);

    }
});

Tine.Calendar.colorMgr = new Tine.Calendar.ColorManager({});

// NOTE: legacy aliases, use Tine.Tinebase.ColorManager
Tine.Calendar.ColorManager.str2dec = Tine.Tinebase.ColorManager.str2dec;
Tine.Calendar.ColorManager.compare = Tine.Tinebase.ColorManager.compare;
Tine.Calendar.ColorManager.stringToColour = Tine.Tinebase.ColorManager.stringToColour;

Tine.Calendar.ColorManager.colorStrategyBtn = Ext.extend(Ext.Button, {
    scale: 'medium',
    minWidth: 60,
    rowspan: 2,
    iconAlign: 'top',
    requiredGrant: 'readGrant',
    iconCls:'action_changecolor',
    colorStrategy: 'container',
    infoBarColorStrategy: 'response',
    useLightColors: false,
    stateful: true,
    stateId: 'cal-calpanel-color-strategy-btn',
    stateEvents: [],

    initComponent: function() {
        var _ = window.lodash,
            me = this;

        this.app = Tine.Tinebase.appMgr.get('Calendar');

        if (! this.app.featureEnabled('featureColorBy')) {
            // hide button and make sure it isn't pressed
            Tine.log.info('ColorStrategy feature is deactivated');
            this.hidden = true;
            this.stateful = false;
        }

        // init state now to render menu with state applied
        this.initState();

        Ext.getBody()[(this.useLightColors ? 'add' : 'remove') + 'Class']('cal-use-light-colors');

        this.text = this.app.i18n._('Colors');
        this.menu = {
            items: _.reduce(Tine.Calendar.colorStrategies, function(items, strategy, key) {
                return items.concat({
                    text: strategy.getName(),
                    checked: me.colorStrategy == key,
                    group: 'colorStrategy',
                    checkHandler: me.changeColorStrategy.createDelegate(me, [key])
                });
            }, [])
        };

        this.menu.items.unshift({
            text: this.app.i18n._('Use Light-Mode Colors'),
            qtip: this.app.i18n._('In dark mode event colors are inverted for better readability. If you want to see the original colors of light mode check this option.'),
            checked: me.useLightColors,
            cls: 'cal-light-mode-color-menu-item',
            checkHandler: me.changeColorMode.createDelegate(me)
        });
        
        let sidebarItems =  _.reduce(Tine.Calendar.infoBarColorStrategies, function(items, strategy, key) {
            return items.concat({
                text: strategy.getName(),
                checked: me.infoBarColorStrategy == key,
                group: 'infoBarColorStrategy',
                checkHandler: me.changeInfoBarColorStrategy.createDelegate(me, [key])
            });
        }, []);

        this.menu.items.push({
            text: this.app.i18n._('Sidebar Color'),
            menu: sidebarItems
        });

        Tine.Calendar.ColorManager.colorStrategyBtn.superclass.initComponent.apply(this, arguments);
    },

    changeColorMode: function(cmp) {
        this.useLightColors = cmp.checked;
        Ext.getBody()[(this.useLightColors ? 'add' : 'remove') + 'Class']('cal-use-light-colors');

        this.saveState();
    },

    changeColorStrategy: function(strategy) {
        this.colorStrategy = strategy;
        this.saveState();
        this.app.getMainScreen().getCenterPanel().refresh();
    },

    getState: function() {
        return {colorStrategy: this.colorStrategy, infoBarColorStrategy: this.infoBarColorStrategy, useLightColors: this.useLightColors}
    },

    changeInfoBarColorStrategy: function(strategy) {
        this.infoBarColorStrategy = strategy;
        this.saveState();
        this.app.getMainScreen().getCenterPanel().refresh();
    },
});

Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ViewBtnGrp', Tine.Calendar.ColorManager.colorStrategyBtn, 30);

/**
 * Color Strategies Registry
 * 
 * @type Object
 */
Tine.Calendar.colorStrategies = {};
Tine.Calendar.colorStrategies['container'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Color by Calendar');
    },
    getColor: function(event, attendeeRecord) {
        var container = null,
            color = null,
            schema = null;

        if (! Ext.isFunction(event.get)) {
            // tree comes with containers only
            container = event;
        } else {
            
            container = event.get('container_id');
            
            // take displayContainer if user has no access to origin
            if (Ext.isPrimitive(container)) {
                container = event.getDisplayContainer();
            }
        }

        if (null === container) {
            return;
        }

        return String(container.color).replace('#', '');
    }
};

Tine.Calendar.colorStrategies['displayContainer'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Color by Attendee Calendar');
    },
    getColor: function(event, attendeeRecord) {
        var container = null,
            color = null;

        if (attendeeRecord) {
            container = attendeeRecord.get('displaycontainer_id');
            color = container ? String(container.color).replace('#', '') : 'C0C0C0';

        }  else {
            color = 'C0C0C0' // light gray
        }

        return color;
    }
};

Tine.Calendar.colorStrategies['tag'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Color by Tags');
    },
    getColor: function(event, attendeeRecord) {
        var color = 'C0C0C0',
            tags = event.get('tags'),
            tag = Ext.isArray(tags) ? tags[0] : null;

        if (tag) {
            color = tag.color;
        }

        return color;
    }
};

Tine.Calendar.colorStrategies['requiredAttendee'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Color by Attendee Role');
    },
    getColor: function(event, attendeeRecord) {
        var color, role;

        if (attendeeRecord) {
            role = attendeeRecord.get('role');
            
            const roleStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeRoles');
            const roleRecord = roleStore.getById(role);

            color = roleRecord ? roleRecord.get('color') : role === 'REQ' ? 'FF0000' : '00FF00';
        } else {
            color = 'C0C0C0' // light gray
        }

        return color;
    }
};

/**
 * Info Color Bar Strategies Registry
 *
 * @type Object
 */
Tine.Calendar.infoBarColorStrategies = {};

Tine.Calendar.infoBarColorStrategies['response'] = {
    getName: function () {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Colors by response');
    },
    getColors: function (event, attendeeRecord) {
        let attendees = event.get('attendee');
        let colorMapping = {
            'ACCEPTED': '00CC00',
            'DECLINED': 'FF0000',
            'TENTATIVE': 'FFDD00'
        }
        let colors = [];
        _.forEach(attendees, function (att) {
            colors.push(colorMapping[att.status] ?? 'C0C0C0');
        });

        return colors;
    }
};

Tine.Calendar.infoBarColorStrategies['tag'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Colors by tags');
    },
    getColors: function(event, attendeeRecord) {
        let tags = event.get('tags');

        let colors = [];
        _.forEach(tags, function (tag) {
            if (tag.color) {
                colors.push(tag.color);
            }
        });

        return colors;
    }
};

Tine.Calendar.infoBarColorStrategies['attendees'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Colors by attendee');
    },
    getColors: function(event, attendeeRecord) {
        let attendees = event.get('attendee');

        let colors = [];
        _.forEach(attendees, function (att) {
            if (att.user_type !== 'user' && att.user_type !== 'groupmember') {
                return;
            }
            let container = att.displaycontainer_id;
            let color = container ? String(container.color).replace('#', '') : 'C0C0C0';
            colors.push(color);
        });

        return colors;
    }
};

Tine.Calendar.infoBarColorStrategies['resource'] = {
    getName: function() {
        return Tine.Tinebase.appMgr.get('Calendar').i18n._('Colors by resource');
    },
    getColors: function(event, attendeeRecord) {
        let attendees = event.get('attendee');

        let colors = [];
        _.forEach(attendees, function (att) {
            if (att.user_type !== 'resource') {
                return;
            }
            let user = att.user_id;
            let color = user ? String(user.color).replace('#', '') : 'C0C0C0';
            colors.push(color);
        });

        return colors;
    }
};
