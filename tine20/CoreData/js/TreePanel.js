/**
 * Tine 2.0
 *
 * @package     CoreData
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.CoreData');

/**
 * @namespace   Tine.CoreData
 * @class       Tine.CoreData.TreePanel
 * @extends     Tine.Tinebase.Application
 * CoreData TreePanel<br>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * TODO allow to customize tree layout (remove parent nodes (/general, /applications, maybe even the application nodes)
 */
Tine.CoreData.TreePanel = function (config) {
    Ext.apply(this, config);
    this.id = 'TreePanel';
    Tine.CoreData.TreePanel.superclass.constructor.call(this);
};
Ext.extend(Tine.CoreData.TreePanel, Ext.tree.TreePanel, {

    autoScroll: true,
    border: false,

    /**
     * init this treePanel
     */
    initComponent: function () {
        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('CoreData');
        }

        var generalChildren = this.getCoreDataNodes('/general'),
            applicationChildren = this.getCoreDataNodes('/applications');

        this.root = {
            path: '/',
            cls: 'tinebase-tree-hide-collapsetool',
            text: this.app.i18n._('Core Data'),
            expanded: true,
            children: [{
                path: '/general',
                id: 'general',
                expanded: true,
                text: this.app.i18n._('General Data'),
                hidden: (generalChildren.length == 0),
                children: generalChildren
            }, {
                path: '/applications',
                id: 'applications',
                expanded: true,
                text: this.app.i18n._('Application Data'),
                children: applicationChildren
            }]
        };

        this.on('click', this.onClick, this);

        Tine.CoreData.TreePanel.superclass.initComponent.call(this);
    },

    /**
     * get core data nodes
     *
     * @param path
     * @returns Array
     */
    getCoreDataNodes: function (path) {
        if (! Tine.CoreData.registry.get('coreData')) {
            return [];
        }

        var applicationNodes = [],
            coreDataNodes = {}; // applications => [core data nodes]

        Ext.each(Tine.CoreData.registry.get('coreData')['results'], function (coreData) {
            if ((path === '/applications' && coreData.application_id.name !== 'Tinebase') ||
                (path === '/general' && coreData.application_id.name === 'Tinebase')) {

                var coreDataApp = Tine.Tinebase.appMgr.get(coreData.application_id.name);

                if (! coreDataNodes[coreData.application_id.name]) {
                    coreDataNodes[coreData.application_id.name] = [];
                    applicationNodes.push({
                        path: path + '/' + coreData.application_id.id,
                        id: coreData.application_id.id,
                        text: coreDataApp.i18n._(coreData.application_id.name),
                        attributes: coreData.application_id,
                        singleClickExpand: true
                    });
                }

                const label = coreData.label ? coreDataApp.i18n._(coreData.label) : Tine.Tinebase.data.RecordMgr.get(coreData.model)?.getModuleName();
                if (label) {
                    coreDataNodes[coreData.application_id.name].push({
                        path: path + '/' + coreData.application_id.id + '/' + coreData.id,
                        id: coreData.id,
                        // no access ? forgot to announce model?
                        text: label,
                        leaf: true,
                        attributes: coreData
                    });
                }
            }
        }, this);

        if (path === '/general') {
            return (coreDataNodes['Tinebase']) ? coreDataNodes['Tinebase'] : [];
        } else {
            Ext.each(applicationNodes, function(node) {
                node.children = coreDataNodes[node.attributes.name]
            });
            return applicationNodes;
        }
    },

    /**
     * on node click
     *
     * @param {} node
     * @param {} e
     */
    onClick: function (node, e) {
        // switch content type and set north + center panels
        if (node.attributes.attributes && node.attributes.attributes.id && node.leaf) {
            Tine.log.debug('Tine.CoreData.TreePanel::onClick');
            Tine.log.debug(node);

            var mainscreen = this.app.getMainScreen(),
                coreData = node.attributes.attributes;

            // autoregister grid
            if (! Tine.CoreData.Manager.isRegistered('grid', coreData.id)) {
                var recordClass = Tine.Tinebase.data.RecordMgr.get(coreData.model),
                    appName = recordClass ? recordClass.getMeta('appName') : null,
                    modelName = recordClass ? recordClass.getMeta('modelName') : null;

                if (appName && modelName && Tine[appName] && Tine[appName][modelName + 'GridPanel']) {
                    Tine.CoreData.Manager.registerGrid(coreData.id, Tine[appName][modelName + 'GridPanel']);
                }

            }

            mainscreen.setActiveContentType(node.attributes.attributes.id);
            mainscreen.getCenterPanel().getStore().reload();
        } else {
            return false;
        }
    }
});
