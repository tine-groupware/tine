/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.CrewScheduling');

require('../css/memberSelectionPanel.css');
require('./MemberToken');

/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.MemberSelectionPanel
 * @extends     Ext.Panel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.CrewScheduling.MemberSelectionPanel = Ext.extend(Ext.Panel, {
    /**
     * @property {Tine.Tinebase.Application} app
     */
    app: null,
    /**
     * @property {Ext.DataView} dataView
     */
    dataView: null,
    /**
     * @property {Tine.CrewScheduling.MemberToken} memberToken
     */
    memberToken: null,
    /**
     * @property {Array} filterCells
     */
    filterCells: null,

    /** private **/
    layout:'fit',
    border: false,
    canonicalName: 'MemberSelection',

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('CrewScheduling');

        this.filterCells = [];

        this.filterField = new Ext.ux.form.ClearableTextField({
            width: 'auto',
            emptyText: this.app.i18n._('Filter by name'),
            enableKeyEvents: true
        });
        this.filterField.on('keypress', this.onFilterChange, this, {buffer: 50});
        this.filterField.on('specialkey', this.onFilterChange, this, {buffer: 50});
        this.filterField.on('change', this.onFilterChange, this, {buffer: 50});

        this.tbar = [this.filterField, '->', {
            tooltip: this.app.i18n._('Filter Attendee'),
            scope: this,
            handler: this.onFilterChange,
            iconCls: 'cs-start-filter'
        }];

        this.memberToken = new Tine.CrewScheduling.MemberToken();
        Object.assign(this.memberToken, this.memberTokenConfig || {});

        this.dataView = new Ext.DataView({
            autoScroll: true,
            multiSelect: true,
            overClass:'x-view-over',
            itemSelector:'table.cs-member-token',
            emptyText: this.app.i18n._('No users to display'),
            tpl: this.memberToken,
            prepareData : this.memberToken.prepareData
        });
        this.items = [this.dataView];

        Tine.CrewScheduling.MemberSelectionPanel.superclass.initComponent.call(this);
    },

    updateMemberCounts: function(memberCounts) {
        var _ = window.lodash,
            el = this.getEl();

        Ext.fly(el).select('td.cs-count').update('0');

        _.each(memberCounts, function(count, key) {
            Ext.fly(el).select('table[tine-cs-token-id*=' + key + '] td.cs-count').update(count);
        });

        this.fireEvent('updateMemberCount', this, memberCounts);
    },

    setFilterCells: function(filterCells) {
        if (JSON.stringify(filterCells) != JSON.stringify(this.filterCells)) {
            this.filterCells = filterCells;
            this.filterStore(this.filterField.getValue(), this.filterCells);
        }
    },

    onFilterChange: function(field, value, oldValue) {
        this.filterStore(this.filterField.getValue(), this.filterCells);
    },

    filterStore: function(queryString, filterCells) {

        if (! (queryString || filterCells.length) ) {
            this.store.clearFilter();
            return;
        }
        
        var _ = window.lodash,
            queryParts = queryString ? String(queryString).trim().split(' ') : null,
            search = '',
            regExp;

        Ext.each(queryParts, function(queryPart, idx) {
            search += (search ? '|(' :'(') + _.escapeRegExp(queryPart) + ')';
        });

        regExp = new RegExp(search,'gi');

        this.store.filterBy(function(member) {
            return !! _.get(member, 'data.user_id.n_fileas', '').match(regExp) &&
                (filterCells.length ?
                    _.get(member, 'data.user_id.possibleUsages', []).containsAny(filterCells) :
                    true);
        }, this);
    },



    onResize : function(adjWidth, adjHeight, rawWidth, rawHeight){
        Tine.CrewScheduling.MemberSelectionPanel.superclass.onResize.apply(this, arguments);

        this.filterField.setWidth(adjWidth-23);
    }
});
