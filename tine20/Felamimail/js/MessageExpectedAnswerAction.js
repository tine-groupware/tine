/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */


import RecordEditFieldTriggerPlugin from "../../Tinebase/js/widgets/form/RecordEditFieldTriggerPlugin";

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMassDetachAction
 * @extends     Ext.Action
 */
Tine.Felamimail.MessageExpectedAnswerAction = function(config) {
    config.iconCls = 'action_file';
    config.app = Tine.Tinebase.appMgr.get('Felamimail');
    config.i18n = config.app.i18n;
    config.text = config.text ? config.text : config.i18n._('Expected Answer');
    config.menu = new Ext.menu.Menu({});
    
    Ext.apply(this, config);

    Tine.Felamimail.MessageExpectedAnswerAction.superclass.constructor.call(this, config);
    
    if (! this.initialConfig?.selectionModel && this.initialConfig?.record) {
        _.assign(this.initialConfig, {
            selections: [this.initialConfig.record],
            selectionModel: {
                getSelectionFilter: () => {
                    return [{field: 'id', operator: 'equals', value: this.initialConfig.record.id }];
                },
                getCount: () => {
                    return 1
                }
            }
        });
    }
    
    this.menu.on('beforeshow', this.showTimeMenu, this);
    this.menu.hideOnClick = false;
    this.addStaticMenuItems();
};


Ext.extend(Tine.Felamimail.MessageExpectedAnswerAction, Ext.Action, {

    /**
     * @cfg {Tinebase.data.Record} record optional instead of selectionModel (implicit fom grid)
     */
    record: null,

    initButton: function(){
        Tine.Felamimail.MessageExpectedAnswerAction.superclass.initComponent.call(this);
        this.addStaticMenuItems();
    },

    showTimeMenu: function () {
        this.menu.onShow();
    },


    addStaticMenuItems: function () {
        this.menu.addItem({
            id:'tomorrow',
            text: this.app.i18n._('Tomorrow'),
            handler: this.selectTimeOption.createDelegate(this),
            checked: false,
            group: 'expected_answer'
        });
        this.menu.addItem({
            id:'in_two_days',
            text: this.app.i18n._('In two days'),
            handler: this.selectTimeOption.createDelegate(this),
            checked: false,
            group: 'expected_answer'
        });
        this.menu.addItem({
            id:'in_a_week',
            text: this.app.i18n._('In a week'),
            handler: this.selectTimeOption.createDelegate(this),
            checked: false,
            group: 'expected_answer'
        });
        this.menu.addItem({
            id:'in_two_weeks',
            text: this.app.i18n._('In two weeks'),
            handler: this.selectTimeOption.createDelegate(this),
            checked: false,
            group: 'expected_answer'
        });
        this.menu.addItem({
            id:'in_a_month',
            text: this.app.i18n._('In a month'),
            handler: this.selectTimeOption.createDelegate(this),
            checked: false,
            group: 'expected_answer'
        });
        this.menu.addItem({
            id:'custom',
            text: this.app.i18n._('Custom'),
            iconCls: 'cal-sheet-view-type',
            checked: false,
            group: 'expected_answer',
            menu: this.dateMenu = new Ext.menu.DateMenu({
                hideOnClick: true,
                minDate: new Date(),
                listeners: {
                    scope: this,
                    'select': function (picker, date) {
                        this.answer = date;
                    }
                }
            }),
        });
    },

    selectTimeOption: function(item, e) {
        let expected_date = new Date();

        switch (item.id){
            case 'tomorrow' :
                expected_date.setDate(expected_date.getDate() + 1);
                break;
            case 'in_two_days' :
                expected_date.setDate(expected_date.getDate() + 2);
                break;
            case 'in_a_week' :
                expected_date.setDate(expected_date.getDate() + 7);
                break;
            case 'in_two_weeks' :
                expected_date.setDate(expected_date.getDate() + 14);
                break;
            case 'in_a_month' :
                expected_date.setMonth(expected_date.getMonth() + 1);
                break;
        }
        this.answer = expected_date;
    }
});
