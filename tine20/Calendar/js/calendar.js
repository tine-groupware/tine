/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('../styles/Calendar.scss');
require('./Application');
require('./MainScreen');
require('./Printer/Base');
require('./AddressbookGridPanelHook');
require('./ParallelEventsRegistry');
require('./SearchCombo');
require('./Model');
require('./Model/ResourceGrants');
require('./AdminPanel');
require('./CalendarPanel');
require('./AbstractView');
require('./EventUI');
require('./DaysViewEventUI');
require('./MonthViewEventUI');
require('./EventSelectionModel');
require('./DaysView');
require('./MonthView');
require('./YearView');
require('./GridView');
require('./TimelineLabel');
require('./TimelineViewEventUI');
require('./TimelineView');
require('./TimelinePanel');
require('./ExceptionHandler');
require('./PagingToolbar');
require('./EventDetailsPanel');
require('./MainScreenCenterPanel');
require('./EventContextAttendeesItem');
require('./WestPanel');
require('./EventEditDialog');
require('./AttendeeGridPanel');
require('./AttendeeFilterGrid');
require('./AttendeeFilterModel');
require('./TreePanel');
require('./PerspectiveCombo');
require('./ColorManager');
require('./RrulePanel');
require('./ResourcesGridPanel');
require('./ResourceEditDialog');
require('./ContactEventsGridPanel');
require('./iMIPDetailsPanel');
require('./CalDAVContainerPropertiesHookField');
require('./AddToEventPanel');
require('./calendarRenderer');
require('./ImportDialog');
require('./CalendarPanelSplitPlugin');
require('./RruleFilter');
require('./WeekdayFilter');
require('./EventFinderOptionsDialog');
require('./floorplan/src/openViewBtn');
require('./ViewInCalendarDialog');
require('./AttendeeCombo');
require('./SyncContainerConfigEditDialog');

/**
 * generic tags menu in event context menu, changes are saved when the menu gets closed
 */
Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ContextMenu', function(config) {
    const app = Tine.Tinebase.appMgr.get('Calendar');
    const centerPanel = app.getMainScreen().getCenterPanel();
    const event = config.event;
    let changed = false;
    let item = null;

    const save = () => {
        if (!changed) return;
        changed = false;
        centerPanel.getCalendarPanel(centerPanel.activeView).getView().editing = event;
        centerPanel.onUpdateEvent(event, false, 'update');
    };

    item = new Tine.widgets.tags.TagsMenuItem({
        hidden: centerPanel.isActiveView('Grid') || !(event && event.get('editGrant')
            && app.featureEnabled('featureExtendedEventContextActions')),
        requiredGrant: null,
        menuConfig: {
            recordClass: Tine.Calendar.Model.Event,
            mode: 'local',
            getRecords: () => event ? [event] : [],
            onLocalChange: () => {
                changed = true;
                // e.g. tags added via dialog after menu got closed
                if (!item.ownerCt?.isVisible()) save();
            }
        },
        listeners: {
            render: () => item.ownerCt.on('hide', save)
        }
    });

    return item;
}, 130);

