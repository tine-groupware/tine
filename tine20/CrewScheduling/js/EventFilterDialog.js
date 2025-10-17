/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const EventFilterDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    /**
     * @cfg {Tine.CrewScheduling.EventMembersGrid} membersGrid
     */
    membersGrid: null,
    /**
     * @cfg {Array} filters
     */
    filters: null,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('CrewScheduling')

        this.recordClass = Tine.Calendar.Model.Event;

        this.window.setTitle(this.app.i18n._('Change Crew Scheduling Event Filter'));

        this.filterPanel = new Tine.widgets.grid.FilterPanel(Ext.apply({
            app: Tine.Tinebase.appMgr.get('Calendar'),
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel(),
            defaultFilter: 'query',
            useQuickFilter: false,
            allowSaving: true,
            showSearchButton: false,
            filters: this.defaultFilters || [],
            onSaveFilter: () => {
                this.favoritesPanel.saveFilter()
            },
            getAllFilterData: function() {
                // return data from filterPanel only
                return this.getValue()
            }
        }, this.filterToolbarConfig));

        this.filterPanel.on('afterrender', () => {
            this.filterPanel.setValue(this.filters);
        }, this, {buffer: 200})

        // NOTE: we don't bind store but wait for OK btn to start search as
        // - after each eventStore load capabilities needs to be recalculated
        // - need to deal with dismissChanges?
        // this.filterPanel.init(this.membersGrid)

        this.favoritesPanel = new Tine.widgets.persistentfilter.PickerPanel({
            app: this.app,
            contentType: 'cs-event-member-grid',
            filterModel: 'Calendar_Model_EventFilter',
            // recordClass: this.recordClass,
            rootVisible: true,
            filterToolbar: this.filterPanel,
            onFilterSelect: (favorite) => {
                this.filterPanel.setValue(favorite.get('filters'))
            }
        });

        this.items = [{
                layout: 'hbox',
                layoutConfig: {
                    align: 'stretch',
                },
                border: false,
                items: [Object.assign(this.favoritesPanel, {
                        width: 200,
                    }), {
                        // NOTE: without wrapping we end up in a layout loop
                        flex: 1,
                        autoHeight: true,
                        border: false,
                        items: [this.filterPanel]
                    }
                ]
            }
        ]

        return this.supr().initComponent.call(this)
    },

    getEventData: function(eventName) {
        if (eventName === 'apply') {
            return {
                filter: this.filterPanel.getValue()
            }
        }
    }
})

Tine.CrewScheduling.EventFilterDialog = EventFilterDialog

EventFilterDialog.openWindow = (config) => {
    return Tine.WindowFactory.getWindow({
        width: 1200,
        height: 400,
        name: 'CrewScheduling.EventFilterDialog',
        contentPanelConstructor: 'Tine.CrewScheduling.EventFilterDialog',
        contentPanelConstructorConfig: config
    })
}

export default EventFilterDialog