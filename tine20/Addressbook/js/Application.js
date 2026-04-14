/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

/**
* @namespace   Tine.Addressbook
* @class       Tine.Addressbook.Application
* @extends     Tine.Tinebase.Application
* Addressbook Application Object <br>
*
* @author      Cornelius Weiss <c.weiss@metaways.de>
*/

Tine.Addressbook.Application = Ext.extend(Tine.Tinebase.Application, {
    
    /**
     * auto hook text i18n._('New Contact')
     */
    addButtonText: 'New Contact',
    
    /**
     * Get translated application title of the calendar application
     * 
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('Addressbook', 'Addressbooks', 1);
    },

    /** 
     * Overide get main screen to allow for feature gating
     *
     **/
    getMainScreen: function() {
        var mainscreen = Tine.Addressbook.Application.superclass.getMainScreen.call(this);

        if (!Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureListView')
            && !Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureResources')) {
            mainscreen.useModuleTreePanel = false;
        }

        return mainscreen;
    },

    registerCoreData: function() {
        Tine.CoreData.Manager.registerGrid('adb_lists', Tine.Addressbook.ListGridPanel, {
            app: this,
            initialLoadAfterRender: false
        });

        Tine.CoreData.Manager.registerGrid(
            'adb_industries',
            Tine.widgets.grid.GridPanel,
            {
                recordClass: Tine.Addressbook.Model.Industry,
                app: this,
                initialLoadAfterRender: false,
                // TODO move this to a generic place
                gridConfig: {
                    autoExpandColumn: 'name',
                    columns: [{
                        id: 'id',
                        header: this.i18n._("ID"),
                        width: 150,
                        sortable: true,
                        hidden: true
                    }, {
                        id: 'name',
                        header: this.i18n._("Name"),
                        width: 300,
                        sortable: true,
                    }, {
                        id: 'description',
                        header: this.i18n._("Description"),
                        width: 300,
                        sortable: true,
                        hidden: true
                    }]
                }
            }
        );
    }
});

/**

* @namespace   Tine.Addressbook

* @class       Tine.Addressbook.MainScreen

* @extends     Tine.widgets.MainScreen

* MainScreen of the Addressbook Application <br>

*

* @author      Cornelius Weiss <c.weiss@metaways.de>

*/
