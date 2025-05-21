/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

Tine.Calendar.RrulePanel = Ext.extend(Ext.Panel, {
    
    /**
     * @static
     */
    wkdays: ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'],
    /**
     * @property
     */    
    activeRuleCard: null,
    
    /**
     * the event edit dialog (parent)
     * @type Tine.Calendar.EventEditDialog
     */
    eventEditDialog: null,

    layout: 'fit',
    frame: true,
    canonicalName: 'RecurrenceConfig',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.title = this.app.i18n._('Recurrences');

        this.defaults = {
            border: false
        };
        
        this.NONEcard = new Ext.Panel({
            freq: 'NONE',
            html: this.app.i18n._('No recurring rule defined')
        });
        this.NONEcard.setRule = Ext.emptyFn;
        this.NONEcard.fillDefaults = Ext.emptyFn;
        this.NONEcard.getRule = function() {
            return null;
        };
        this.NONEcard.isValid = function() {
            return true;
        };
        
        this.DAILYcard = new Tine.Calendar.RrulePanel.DAILYcard({rrulePanel: this});
        this.WEEKLYcard = new Tine.Calendar.RrulePanel.WEEKLYcard({rrulePanel: this});
        this.MONTHLYcard = new Tine.Calendar.RrulePanel.MONTHLYcard({rrulePanel: this});
        this.YEARLYcard = new Tine.Calendar.RrulePanel.YEARLYcard({rrulePanel: this});
        this.INDIVIDUALcard = new Tine.Calendar.RrulePanel.INDIVIDUALcard({rrulePanel: this});

        this.ruleCards = new Ext.Panel({
            layout: 'card',
            baseCls: 'ux-arrowcollapse',
            cls: 'ux-arrowcollapse-plain',
            collapsible: true,
            collapsed: false,
            activeItem: 0,
            listeners: {
                scope: this,
                collapse: this.doLayout,
                expand: this.doLayout
            },
            //style: 'padding: 10px 0 0 20px;',
            title: this.app.i18n._('Details'),
            items: [
                this.NONEcard,
                this.DAILYcard,
                this.WEEKLYcard,
                this.MONTHLYcard,
                this.YEARLYcard,
                this.INDIVIDUALcard
            ]
        });

        this.idPrefix = Ext.id();
        
        this.tbar = [{
            id: this.idPrefix + 'tglbtn' + 'NONE',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
            text: this.app.i18n._('None'),
            handler: this.onFreqChange.createDelegate(this, ['NONE']),
            toggleGroup: this.idPrefix + 'freqtglgroup'
        }, {
            id: this.idPrefix + 'tglbtn' + 'DAILY',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
            text: this.app.i18n._('Daily'),
            handler: this.onFreqChange.createDelegate(this, ['DAILY']),
            toggleGroup: this.idPrefix + 'freqtglgroup'
        }, {
            id: this.idPrefix + 'tglbtn' + 'WEEKLY',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
            text: this.app.i18n._('Weekly'),
            handler: this.onFreqChange.createDelegate(this, ['WEEKLY']),
            toggleGroup: this.idPrefix + 'freqtglgroup'
        }, {
            id: this.idPrefix + 'tglbtn' + 'MONTHLY',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
            text: this.app.i18n._('Monthly'),
            handler: this.onFreqChange.createDelegate(this, ['MONTHLY']),
            toggleGroup: this.idPrefix + 'freqtglgroup'
        }, {
            id: this.idPrefix + 'tglbtn' + 'YEARLY',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
            text: this.app.i18n._('Yearly'),
            handler: this.onFreqChange.createDelegate(this, ['YEARLY']),
            toggleGroup: this.idPrefix + 'freqtglgroup'
        },{
            id: this.idPrefix + 'tglbtn' + 'INDIVIDUAL',
            xtype: 'tbbtnlockedtoggle',
            enableToggle: true,
            text: this.app.i18n._('Individual'),
            handler: this.onFreqChange.createDelegate(this, ['INDIVIDUAL']),
            toggleGroup: this.idPrefix + 'freqtglgroup',
        }];

        this.items = [
            this.ruleCards
        ];

        this.eventEditDialog.on('dtStartChange', function(jsonData) {
            const data = Ext.decode(jsonData),
                dtstart = Date.parseDate(data.newValue, Date.patterns.ISO8601Long);

            this.initRrule(dtstart);
        }, this);
        Tine.Calendar.RrulePanel.superclass.initComponent.call(this);
    },

    initRrule: function(dtstart) {
        if (Ext.isDate(dtstart)) {
            const byday = Tine.Calendar.RrulePanel.prototype.wkdays[dtstart.format('w')];
            const bymonthday = dtstart.format('j');
            const bymonth = dtstart.format('n');

            this.WEEKLYcard.setRule({
                interval: 1,
                byday: byday
            });
            this.MONTHLYcard.setRule({
                interval: 1,
                byday: '1' + byday,
                bymonthday: bymonthday
            });
            this.YEARLYcard.setRule({
                byday: '1' + byday,
                bymonthday: bymonthday,
                bymonth: bymonth
            });
        }
    },

    isValid: function() {
        return this.activeRuleCard.isValid(this.record);
    },
    
    onFreqChange: async function (freq) {
        this.ruleCards.layout.setActiveItem(this[freq + 'card']);
        this.ruleCards.layout.layout();
        this.activeRuleCard = this[freq + 'card'];
    },

    /**
     * disable contents not panel
     */
    setDisabled: function(v) {
        this.getTopToolbar().items.each(function(item) {
            item.setDisabled(v);
        }, this);
    },
    
    onRecordLoad: async function (record) {
        this.record = record;
        this.rrule = this.record.get('rrule');
        this.initRrule(this.record.get('dtstart'));

        const freq = this.rrule && this.rrule.freq ? this.rrule.freq : 'NONE';

        if (freq !== 'INDIVIDUAL' && !this.record.get('editGrant') || this.record.isRecurException() || this.record.hasPoll()) {
            this.setDisabled(false);
        }

        const freqBtn = Ext.getCmp(this.idPrefix + 'tglbtn' + freq);
        freqBtn.toggle(true);

        this.activeRuleCard = this[freq + 'card'];
        this.ruleCards.activeItem = this.activeRuleCard;

        this.activeRuleCard.setRule(this.rrule);

        this.constrains = this.record.get('rrule_constraints');
        if (this.constrains) {
            const constrainsValue = this.constrains[0].value;
            if (constrainsValue && this.activeRuleCard.constrains) {
                this.activeRuleCard.constrains.setValue(constrainsValue);
            }
        }

        if (this.record.isRecurException()) {
            this.items.each(function (item) {
                item.setDisabled(freq !== 'INDIVIDUAL');
            }, this);
            this.ruleCards.collapsed = false;

            this.NONEcard.html = this.app.i18n._("Exceptions of reccuring events can't have recurrences themselves.");
        }

        if (this.activeRuleCard.freq === 'INDIVIDUAL') {
            await Tine.Calendar.getEventExceptions(this.record.get('id'))
                .then((response) => {
                    this.INDIVIDUALcard.exceptionGrid.store.loadData(response.results);
                });
        }
    },
    
    onRecordUpdate: function(record) {
        const rendered = _.get(this, 'activeRuleCard.rendered', false);
        const rrule = rendered ? this.activeRuleCard.getRule() : this.rrule;
        
        if (rrule && (!this.rrule || !this.record.data.creation_time || (rrule.freq === 'INDIVIDUAL' && this.rrule.count !== rrule.count))) {
            // mark as new rule to avoid series confirm dlg
            rrule.newrule = true;
        }

        record.set('rrule', '');
        record.set('rrule', rrule);
        record.set('rrule_constraints', '');

        if (! rendered) {
            record.set('rrule_constraints', this.constrains);
        } else if (this.activeRuleCard.constrains)  {
            const constrainsValue = this.activeRuleCard.constrains.getValue();
            if (constrainsValue && constrainsValue.length) {
                record.set('rrule_constraints', [{field: 'container_id', operator: 'in', value: constrainsValue}]);
            }
        }
    }
});

Tine.Calendar.RrulePanel.AbstractCard = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'form',
    labelAlign: 'side',
    autoHeight: true,
    
    getRule: function() {
        const rrule = {
            freq    : this.freq,
            interval: this.interval.getValue()
        };
        
        if (this.untilRadio.checked) {
            rrule.until = this.until.getRawValue();
            rrule.until = rrule.until ? Date.parseDate(rrule.until, this.until.format) : null;
            
            
            if (Ext.isDate(rrule.until)) {
                // make sure, last reccurance is included
                rrule.until = rrule.until.clearTime(true).add(Date.HOUR, 24).add(Date.SECOND, -1).format(Date.patterns.ISO8601Long);
            }
        } else {
            rrule.count = this.count.getValue() || 1;
        }
            
        
        return rrule;
    },
    
    onAfterUnitTriggerClick: function() {
        if (! this.until.getValue()) {
            const dtstart = this.rrulePanel.record.get('dtstart');
            this.until.menu.picker.setValue(dtstart);
        }
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.limitId = Ext.id();
        
        this.untilRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at'), 
            name          : this.limitId + 'LimitRadioGroup', 
            inputValue    : 'UNTIL',
            checked       : true,
            listeners     : {
                check: this.onLimitRadioCheck.createDelegate(this)
            }
        });
        
        this.until = new Ext.form.DateField({
            requiredGrant : 'editGrant',
            width         : 100,
            ownerCt       : this,
            emptyText     : this.app.i18n._('never'),
            onTriggerClick: Ext.form.DateField.prototype.onTriggerClick.createSequence(this.onAfterUnitTriggerClick, this),
            listeners: {
                scope: this,
                // so dumb!
                render: function(f) {f.wrap.setWidth.defer(100, f.wrap, [f.initialConfig.width]);}
            }
        });
        
        const countStringParts = this.app.i18n._('after {0} occurrences').split('{0}'),
            countBeforeString = countStringParts[0],
            countAfterString = countStringParts[1];
        
        this.countRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : countBeforeString, 
            name          : this.limitId + 'LimitRadioGroup', 
            inputValue    : 'COUNT',
            listeners     : {
                check: this.onLimitRadioCheck.createDelegate(this)
            }
        });
        
        this.count = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            width         : 40,
            minValue      : 1,
            disabled      : true,
            allowDecimals : false,
            allowBlank    : false
        });
        
        const intervalPars = this.intervalString.split('{0}');
        const intervalBeforeString = intervalPars[0];
        const intervalAfterString = intervalPars[1];
        
        this.interval = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            //fieldLabel    : this.intervalBeforeString,
            minValue      : 1,
            allowBlank    : false,
            value         : 1,
            width         : 40
        });
        
        if (! this.items) {
            this.items = [];
        }
        
        if (this.freq !== 'YEARLY') {
            this.items = [{
                layout: 'column',
                items: [{
                    width: 70,
                    html: intervalBeforeString
                },
                    this.interval,
                {
                    style: 'padding-top: 2px;',
                    html: intervalAfterString
                }]
            }].concat(this.items);
        }

        this.constrains = new Tine.widgets.container.FilterModelMultipleValueField({
        //this.constrains = new Tine.widgets.container.SelectionComboBox({
            app: this.app,
            allowBlank: true,
            width: 260,
            listWidth: 200,
            allowNodeSelect: true,
            recordClass: Tine.Calendar.Model.Event
        });

        if (this.app.featureEnabled('featureRecurExcept')) {
            this.items = this.items.concat([{
                layout: 'hbox',
                //style: 'padding-top: 2px;',
                items: [
                    {
                        xtype: 'label',
                        style: 'padding-top: 2px;',
                        width: 70,
                        text: this.app.i18n._('Except')
                    },
                    {
                        // @IDEA: this could be a combo later
                        // - if one attendee is busy
                        // - if organizer is busy
                        // - resources are busy
                        // - ...
                        xtype: 'label',
                        style: 'padding-top: 2px;',
                        width: 200,
                        text: this.app.i18n._('during events in the calendars')
                    },
                    {
                        xtype: 'label',
                        width: 260,
                        id: this.limitId + 'constraints'
                    }
                ]
            }]);
        };

        this.items = this.items.concat({
            layout: 'form',
            html: '<div style="padding-top: 5px;">' + this.app.i18n._('End') + '</div>' +
                    '<div style="position: relative;">' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.limitId + 'untilRadio"></td>' +
                            '<td width="100" id="' + this.limitId + 'until"></td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.limitId + 'countRadio"></td>' +
                            '<td class="x-column-inner .x-form-field" width="40" id="' + this.limitId + 'count"></td>' +
                            '<td width="40" style="padding-left: 5px" >' + countAfterString + '</td>' +
                         '</tr></table>' +
                    '</div>' +
                '</div>',
                listeners: {
                   scope: this,
                   render: this.onLimitRender
                }
        });
        
        Tine.Calendar.RrulePanel.AbstractCard.superclass.initComponent.call(this);
    },
    
    onLimitRender: function() {
        const untilradioel = Ext.get(this.limitId + 'untilRadio');
        const untilel = Ext.get(this.limitId + 'until');
        
        const countradioel = Ext.get(this.limitId + 'countRadio');
        const countel = Ext.get(this.limitId + 'count');
        
        if (! (untilradioel && countradioel)) {
            return this.onLimitRender.defer(100, this, arguments);
        }
        
        this.untilRadio.render(untilradioel);
        this.until.render(untilel);
        this.until.wrap.setWidth(80);
        
        this.countRadio.render(countradioel);
        this.count.render(countel);

        if (this.app.featureEnabled('featureRecurExcept')) {
            this.constrains.render(Ext.get(this.limitId + 'constraints'));
            this.constrains.wrap.setWidth(260);
        }
    },
    
    onLimitRadioCheck: function(radio, checked) {
        switch(radio.inputValue) {
            case 'UNTIL':
                this.count.setDisabled(checked);
                if (checked) this.count.setValue(null);
                break;
            case 'COUNT':
                this.until.setDisabled(checked);
                if (checked) this.until.setValue(null);
                break;
        }
    },
    
    isValid: function(record) {
        const until = this.until?.getValue() || null;
        const freq = this.freq;
        
        if (Ext.isDate(until) && Ext.isDate(record.get('dtstart'))) {
            if (until.getTime() < record.get('dtstart').getTime()) {
                this.until.markInvalid(this.app.i18n._('Until has to be after event start'));
                return false;
            }
        } 
        
        if (Ext.isDate(record.get('dtend')) && Ext.isDate(record.get('dtstart'))) {
            var dayDifference = (record.get('dtend').getTime() - record.get('dtstart').getTime()) / 1000 / 60 / 60 / 24,
                dtendField = this.rrulePanel.eventEditDialog.getForm().findField('dtend');
            
            if(freq === 'DAILY' && dayDifference >= 1) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            } else if(freq === 'WEEKLY' && dayDifference >= 7) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            } else if(freq === 'MONTHLY' && dayDifference >= 28) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            } else if(freq === 'YEARLY' && dayDifference >= 365) {
                dtendField.markInvalid(this.app.i18n._('The event is longer than the recurring interval'));
                return false;
            }
        }
        
        return true;
    },
    
    setRule: function(rrule) {
        if (!this.interval) return;
        this.interval.setValue(rrule.interval || 1);
        this.until.value = Date.parseDate(rrule.until, Date.patterns.ISO8601Long);
        
        if (rrule.count) {
            this.count.value = rrule.count;
                
            this.untilRadio.setValue(false);
            this.countRadio.setValue(true);
            this.onLimitRadioCheck(this.untilRadio, false);
            this.onLimitRadioCheck(this.countRadio, true);
        }
    },
});

Tine.Calendar.RrulePanel.DAILYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'DAILY',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Day');
        
        Tine.Calendar.RrulePanel.DAILYcard.superclass.initComponent.call(this);
    }
});

Tine.Calendar.RrulePanel.WEEKLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'WEEKLY',
    
    getRule: function() {
        const rrule = Tine.Calendar.RrulePanel.WEEKLYcard.superclass.getRule.call(this);
        
        const bydayArray = [];
        this.byday.items.each(function(cb) {
            if (cb.checked) {
                bydayArray.push(cb.name);
            }
        }, this);
        
        rrule.byday = bydayArray.join();
        if (! rrule.byday) {
            rrule.byday = this.byDayValue;
        }
        
        rrule.wkst = this.wkst || Tine.Calendar.RrulePanel.prototype.wkdays[Ext.DatePicker.prototype.startDay];
        
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Week at');
        
        const bydayItems = [];
        let i = 0, d;
        for (; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            bydayItems.push({
                boxLabel: Date.dayNames[d],
                name: Tine.Calendar.RrulePanel.prototype.wkdays[d]
            })
        }
        
        this.byday = new Ext.form.CheckboxGroup({
            requiredGrant : 'editGrant',
            style: 'padding-top: 5px;',
            hideLabel: true,
            items: bydayItems
        });
        
        this.items = [this.byday];
        
        Tine.Calendar.RrulePanel.WEEKLYcard.superclass.initComponent.call(this);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.WEEKLYcard.superclass.setRule.call(this, rrule);
        this.wkst = rrule.wkst;
        
        if (rrule.byday) {
            this.byDayValue = rrule.byday;
            
            const bydayArray = rrule.byday.split(',');
            
            if (Ext.isArray(this.byday.items)) {
                // on initialisation items are not renderd
                Ext.each(this.byday.items, function(cb) {
                    cb.checked = bydayArray.indexOf(cb.name) !== -1
                }, this);
            } else {
                // after items are rendered
                this.byday.items.each(function(cb) {
                    cb.setValue(bydayArray.indexOf(cb.name) !== -1);
                }, this);
            }
        }
    }
});

Tine.Calendar.RrulePanel.MONTHLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'MONTHLY',
    
    getRule: function() {
        const rrule = Tine.Calendar.RrulePanel.MONTHLYcard.superclass.getRule.call(this);
        
        if (this.bydayRadio.checked) {
            rrule.byday = this.wkNumber.getValue() + this.wkDay.getValue();
        } else {
            rrule.bymonthday = this.bymonthdayday.getValue();
        }
        
        return rrule;
    },
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Month');
        
        this.idPrefix = Ext.id();
        
        this.bydayRadio = new Ext.form.Radio({
            hideLabel: true,
            boxLabel: this.app.i18n._('at the'), 
            name: this.idPrefix + 'byRadioGroup', 
            inputValue: 'BYDAY',
            checked: true,
            listeners: {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });

        this.wkNumber = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width: 80,
            listWidth: 80,
            triggerAction : 'all',
            hideLabel     : true,
            value         : 1,
            editable      : false,
            mode          : 'local',
            store         : [
                [1,  this.app.i18n._('first')  ],
                [2,  this.app.i18n._('second') ],
                [3,  this.app.i18n._('third')  ],
                [4,  this.app.i18n._('fourth') ],
                [5,  this.app.i18n._('fifth')  ],
                [-1, this.app.i18n._('last')   ]
            ]
        });
        
        const wkdayItems = [];
        let i = 0, d;
        for (; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            Tine.Calendar.RrulePanel.prototype.wkdays[d];
            wkdayItems.push([Tine.Calendar.RrulePanel.prototype.wkdays[d], Date.dayNames[d]]);
        }
        
        this.wkDay = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 100,
            listWidth     : 100,
            triggerAction : 'all',
            hideLabel     : true,
            value         : Tine.Calendar.RrulePanel.prototype.wkdays[Ext.DatePicker.prototype.startDay],
            editable      : false,
            mode          : 'local',
            store         : wkdayItems,
        });
        
        this.bymonthdayRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at the'), 
            name          : this.idPrefix + 'byRadioGroup', 
            inputValue    : 'BYMONTHDAY',
            listeners     : {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });
        
        this.bymonthdayday = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            hideLabel     : true,
            width         : 40,
            value         : 1,
            disabled      : true
        });
        
        this.items = [{
            html: '<div style="padding-top: 5px;">' + 
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td style="position: relative;" width="65" id="' + this.idPrefix + 'bydayradio"></td>' +
                            '<td width="100" id="' + this.idPrefix + 'bydaywknumber"></td>' +
                            '<td width="110" id="' + this.idPrefix + 'bydaywkday"></td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.idPrefix + 'bymonthdayradio"></td>' +
                            '<td width="40" id="' + this.idPrefix + 'bymonthdayday" class="bymonthdayday"></td>' +
                            '<td>.</td>' +
                         '</tr></table>' +
                    '</div>' +
                '</div>',
            listeners: {
                scope: this,
                render: this.onByRender
            }
        }];
        
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.initComponent.call(this);
    },

    onByRadioCheck: function(radio, checked) {
        switch(radio.inputValue) {
            case 'BYDAY':
                this.bymonthdayday.setDisabled(checked);
                break;
            case 'BYMONTHDAY':
                this.wkNumber.setDisabled(checked);
                this.wkDay.setDisabled(checked);
                break;
        }
    },
    
    onByRender: function() {
        const bybayradioel = Ext.get(this.idPrefix + 'bydayradio');
        const bybaywknumberel = Ext.get(this.idPrefix + 'bydaywknumber');
        const bybaywkdayel = Ext.get(this.idPrefix + 'bydaywkday');
        
        const bymonthdayradioel = Ext.get(this.idPrefix + 'bymonthdayradio');
        const bymonthdaydayel = Ext.get(this.idPrefix + 'bymonthdayday');
        
        if (! (bybayradioel && bymonthdayradioel)) {
            return this.onByRender.defer(100, this, arguments);
        }
        
        this.bydayRadio.render(bybayradioel);
        this.wkNumber.render(bybaywknumberel);
        this.wkNumber.wrap.setWidth(80);
        this.wkDay.render(bybaywkdayel);
        this.wkDay.wrap.setWidth(100);
        
        this.bymonthdayRadio.render(bymonthdayradioel);
        this.bymonthdayday.render(bymonthdaydayel);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.setRule.call(this, rrule);
        
        if (rrule.byday) {
            this.bydayRadio.setValue(true);
            this.bymonthdayRadio.setValue(false);
            this.onByRadioCheck(this.bydayRadio, true);
            this.onByRadioCheck(this.bymonthdayRadio, false);
            
            const parts = rrule.byday.match(/([\-\d]{1,2})([A-Z]{2})/);
            this.wkNumber.setValue(parts[1]);
            this.wkDay.setValue(parts[2]);
            
        }
        
        if (rrule.bymonthday) {
            this.bydayRadio.setValue(false);
            this.bymonthdayRadio.setValue(true);
            this.onByRadioCheck(this.bydayRadio, false);
            this.onByRadioCheck(this.bymonthdayRadio, true);
            
            this.bymonthdayday.setValue(rrule.bymonthday);
        }

    }
    
});

Tine.Calendar.RrulePanel.YEARLYcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    
    freq: 'YEARLY',
    
    getRule: function() {
        const rrule = Tine.Calendar.RrulePanel.MONTHLYcard.superclass.getRule.call(this);
        
        if (this.bydayRadio.checked) {
            rrule.byday = this.wkNumber.getValue() + this.wkDay.getValue();
        } else {
            rrule.bymonthday = this.bymonthdayday.getValue();
        }
        
        rrule.bymonth = this.bymonth.getValue();
        return rrule;
    },
    
    initComponent: function() {
        let i;
        let d;
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.intervalString = this.app.i18n._('Every {0}. Year');
        
        this.idPrefix = Ext.id();
        
        this.bydayRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at the'), 
            name          : this.idPrefix + 'byRadioGroup', 
            inputValue    : 'BYDAY',
            listeners     : {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });

        this.wkNumber = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 80,
            listWidth     : 80,
            triggerAction : 'all',
            hideLabel     : true,
            value         : 1,
            editable      : false,
            mode          : 'local',
            disabled      : true,
            store         : [
                [1,  this.app.i18n._('first')  ],
                [2,  this.app.i18n._('second') ],
                [3,  this.app.i18n._('third')  ],
                [4,  this.app.i18n._('fourth') ],
                [-1, this.app.i18n._('last')   ]
            ]
        });
        
        const wkdayItems = [];
        for (; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7
            Tine.Calendar.RrulePanel.prototype.wkdays[d];
            wkdayItems.push([Tine.Calendar.RrulePanel.prototype.wkdays[d], Date.dayNames[d]]);
        }
        
        this.wkDay = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 100,
            listWidth     : 100,
            triggerAction : 'all',
            hideLabel     : true,
            value         : Tine.Calendar.RrulePanel.prototype.wkdays[Ext.DatePicker.prototype.startDay],
            editable      : false,
            mode          : 'local',
            store         : wkdayItems,
            disabled      : true
        });
        
        this.bymonthdayRadio = new Ext.form.Radio({
            requiredGrant : 'editGrant',
            hideLabel     : true,
            boxLabel      : this.app.i18n._('at the'), 
            name          : this.idPrefix + 'byRadioGroup', 
            inputValue    : 'BYMONTHDAY',
            checked       : true,
            listeners     : {
                check: this.onByRadioCheck.createDelegate(this)
            }
        });
        
        this.bymonthdayday = new Ext.form.NumberField({
            requiredGrant : 'editGrant',
            style         : 'text-align:right;',
            hideLabel     : true,
            width         : 40,
            value         : 1
        });
        
        const monthItems = [];
        for (i = 0; i<Date.monthNames.length; i++) {
            monthItems.push([i+1, Date.monthNames[i]]);
        }
        
        this.bymonth = new Ext.form.ComboBox({
            requiredGrant : 'editGrant',
            width         : 100,
            listWidth     : 100,
            triggerAction : 'all',
            hideLabel     : true,
            value         : 1,
            editable      : false,
            mode          : 'local',
            store         : monthItems
        });
        
        this.items = [{
            html: '<div style="padding-top: 5px;">' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td style="position: relative;" width="65" id="' + this.idPrefix + 'bydayradio"></td>' +
                            '<td width="100" id="' + this.idPrefix + 'bydaywknumber"></td>' +
                            '<td width="110" id="' + this.idPrefix + 'bydaywkday"></td>' +
                            //'<td style="padding-left: 10px">' + this.app.i18n._('of') + '</td>' +
                        '</tr></table>' +
                    '</div>' +
                    '<div style="position: relative;">' +
                        '<table><tr>' +
                            '<td width="65" id="' + this.idPrefix + 'bymonthdayradio"></td>' +
                            '<td width="40" id="' + this.idPrefix + 'bymonthdayday" class="bymonthdayday"></td>' +
                            '<td>.</td>' +
                            '<td width="15" style="padding-left: 37px">' + this.app.i18n._('of') + '</td>' +
                            '<td width="100" id="' + this.idPrefix + 'bymonth"></td>' +
                         '</tr></table>' +
                    '</div>' +
                '</div>',
            listeners: {
                scope: this,
                render: this.onByRender
            }
        }];
        Tine.Calendar.RrulePanel.YEARLYcard.superclass.initComponent.call(this);
    },
    
    onByRadioCheck: function(radio, checked) {
        switch(radio.inputValue) {
            case 'BYDAY':
                this.bymonthdayday.setDisabled(checked);
                break;
            case 'BYMONTHDAY':
                this.wkNumber.setDisabled(checked);
                this.wkDay.setDisabled(checked);
                break;
        }
    },
    
    onByRender: function() {
        const bybayradioel = Ext.get(this.idPrefix + 'bydayradio');
        const bybaywknumberel = Ext.get(this.idPrefix + 'bydaywknumber');
        const bybaywkdayel = Ext.get(this.idPrefix + 'bydaywkday');
        
        const bymonthdayradioel = Ext.get(this.idPrefix + 'bymonthdayradio');
        const bymonthdaydayel = Ext.get(this.idPrefix + 'bymonthdayday');
        
        const bymonthel = Ext.get(this.idPrefix + 'bymonth');
        
        if (! (bybayradioel && bymonthdayradioel)) {
            return this.onByRender.defer(100, this, arguments);
        }
        
        this.bydayRadio.render(bybayradioel);
        this.wkNumber.render(bybaywknumberel);
        this.wkNumber.wrap.setWidth(80);
        this.wkDay.render(bybaywkdayel);
        this.wkDay.wrap.setWidth(100);
        
        this.bymonthdayRadio.render(bymonthdayradioel);
        this.bymonthdayday.render(bymonthdaydayel);
        
        this.bymonth.render(bymonthel);
        this.bymonth.wrap.setWidth(100);
    },
    
    setRule: function(rrule) {
        Tine.Calendar.RrulePanel.MONTHLYcard.superclass.setRule.call(this, rrule);
        
        if (rrule.byday) {
            this.bydayRadio.setValue(true);
            this.bymonthdayRadio.setValue(false);
            this.onByRadioCheck(this.bydayRadio, true);
            this.onByRadioCheck(this.bymonthdayRadio, false);
            
            var parts = rrule.byday.match(/([\-\d]{1,2})([A-Z]{2})/);
            this.wkNumber.setValue(parts[1]);
            this.wkDay.setValue(parts[2]);
            
        }
        
        if (rrule.bymonthday) {
            this.bydayRadio.setValue(false);
            this.bymonthdayRadio.setValue(true);
            this.onByRadioCheck(this.bydayRadio, false);
            this.onByRadioCheck(this.bymonthdayRadio, true);
            
            this.bymonthdayday.setValue(rrule.bymonthday);
        }
        
        this.bymonth.setValue(rrule.bymonth);

    }
});


Tine.Calendar.RrulePanel.INDIVIDUALcard = Ext.extend(Tine.Calendar.RrulePanel.AbstractCard, {
    freq: 'INDIVIDUAL',
    getRule: function() {
        const exceptionCount = this.exceptionGrid.store.getCount();
        if (exceptionCount === 0) return null;
        return {
            freq: this.freq,
            interval: 1,
            count: exceptionCount + 1,
        };
    },
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.idPrefix = Ext.id();

        this.exceptionGrid = new Tine.widgets.grid.QuickaddGridPanel({
            title: this.app.i18n._('Individual Dates'),
            border: false,
            frame: false,
            autoExpandColumn: 'info',
            quickaddMandatory: 'dtstart',
            recordClass: Tine.Calendar.Model.Event,
            deleteOnServer: true,
            height: 200,
            store: new Tine.Tinebase.data.RecordStore({
                readOnly: true,
                autoLoad: false,
                recordClass: Tine.Calendar.Model.Event,
                proxy: Tine.Calendar.backend,
                pruneModifiedRecords: true,
                getModifiedRecords: function () {
                    return window.lodash.filter(this.modified, {dirty: true});
                },
                sortInfo: {
                    field: 'dtstart',
                    direction: 'ASC'
                }
            }),
            resetAllOnNew: false,
            cm: new Ext.grid.ColumnModel([{
                id: 'dtstart',
                header: this.app.i18n._('Date'),
                width: 200,
                hideable: false,
                sortable: true,
                renderer: this.dateRenderer.createDelegate(this),
                quickaddField: new Ext.ux.form.DateTimeField({
                    resetOnNew: false,
                    value: new Date().clearTime(),
                }),
                editor: new Ext.ux.form.DateTimeField({
                    allowBlank: false
                })
            }, {
                id: 'summary',
                header: this.app.i18n._('Summary'),
                minWidth: 100,
                hideable: false,
                sortable: true,
                editor: new Ext.form.TextField({allowBlank: true}),
            }, {
                id: 'info',
                header: this.app.i18n._('Info'),
                hideable: false,
                sortable: true,
                renderer: this.infoRenderer.createDelegate(this)
            }]),
        });

        this.exceptionGrid.on('beforeaddrecord', async (individualEvent) => {
            if (this.rrulePanel.activeRuleCard.freq !== 'INDIVIDUAL') return false;
            if (!this.rrulePanel.record?.data?.id && !this.rrulePanel.activeRuleCard.getRule()) {
                const proxy = this.rrulePanel.eventEditDialog.recordClass.getProxy();
                this.rrulePanel.record = await proxy.promiseSaveRecord(this.rrulePanel.record);
                this.rrulePanel.eventEditDialog.record = this.rrulePanel.record;
            }
            const rrule = this.rrulePanel.activeRuleCard.getRule() || {
                freq: 'INDIVIDUAL',
                interval: 1,
                count: 2
            };
            const exception = this.createIndividualEvent(individualEvent, rrule);

            await Tine.Calendar.createRecurException(exception, false, false)
                .then((result) => {
                    this.rrulePanel.eventEditDialog.loadRecord('remote');
                })
                .catch((e) => {})
        });

        this.items = [
            this.exceptionGrid
        ]
        Tine.Calendar.RrulePanel.AbstractCard.superclass.initComponent.call(this);
    },

    dateRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        return value.format('l') + ', ' + Tine.Tinebase.common.dateTimeRenderer(value);
    },

    infoRenderer: function(value, metaData, record, rowIndex, colIndex, store) {
        var _ = window.lodash,
            attendeeStatus = _.groupBy(_.get(record, 'data.attendee', {}), 'status'),
            statusStore = store = Tine.Tinebase.widgets.keyfield.StoreMgr.get(this.app, 'attendeeStatus'),
            renderer = Tine.Tinebase.widgets.keyfield.Renderer.get(this.app, 'attendeeStatus');

        return _.map(_.reverse([].concat(Tine.Calendar.PollPanel.prototype.statusWeightMap)), function(statusId) {
            var count = _.get(attendeeStatus, statusId, []).length;

            return '<span class="cal-pollpanel-alternativeevents-statustoken">' +
                renderer(statusId) +
                '<span class="cal-pollpanel-alternativeevents-statuscount">' + count + '</span>' +
                '</span>';
        }).join('');
    },

    createIndividualEvent(individualEvent, rrule) {
        const baseEvent = this.rrulePanel.record;
        const exception = Tine.Tinebase.data.Record.setFromJson(baseEvent.data, Tine.Calendar.Model.Event);
        const duration = (baseEvent.get('dtend').getTime() - baseEvent.get('dtstart').getTime()) / 60000;

        exception.set('base_event_id', individualEvent.get('base_event_id') || baseEvent.get('id'));
        exception.set('id', '');
        exception.set('recurid', '');
        exception.set('dtstart', individualEvent.get('dtstart'));
        exception.set('dtend', individualEvent.get('dtstart').clone().add(Date.MINUTE, duration));
        exception.set('summary', individualEvent.get('summary') || baseEvent.get('summary'));
        exception.set('rrule', rrule);

        return exception;
    },
});
