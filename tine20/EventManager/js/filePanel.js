
/*
 * tine Groupware
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.ux.ItemRegistry.registerItem('EventManager-Event-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    frame: true,
    requiredGrant: 'editGrant',
    layout: 'fit',
    basePath: '/shared/Veranstaltungen/',

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('EventManager');
        this.title = this.app.i18n._('Files');

        this.items = [
            this.getGridPanel()
        ];

        this.tbar = this.getGridPanel().getActionToolbar();

        this.supr().initComponent.call(this);
    },

    getGridPanel: function () {
        if (this.gridPanel) {
            return this.gridPanel;
        }

        const gridPanel = this.gridPanel = new Tine.Filemanager.NodeGridPanel({
            app: Tine.Tinebase.appMgr.get('Filemanager'),
            height: 200,
            width: 200,
            border: false,
            frame: false,
            readOnly: this.hasOwnProperty('readOnly') ? this.readOnly : false,
            // onRowDblClick: Tine.Filemanager.NodeGridPanel.prototype.onRowDblClick.createInterceptor(this.onNodeDblClick, this),
            enableDD: false,
            enableDrag: false,
            hasQuickSearchFilterToolbarPlugin: false,
            stateIdSuffix: '-EventManager-Datei-Panel',
            displaySelectionHelper: false
        });

        gridPanel.pagingToolbar.insert(11, gridPanel.action_goUpFolder);

        gridPanel.getStore().on('load', (store) => {
            const path = _.get(_.filter(_.get(store, 'reader.jsonData.filter'), {field: 'path'}), '[0].value.path');

            if (path && path !== '/') {
                store.insert(0, Tine.Tinebase.data.Record.setFromJson({
                    type: 'folder',
                    name: '..',
                    path: Tine.Filemanager.Model.Node.dirname(path),
                    id: '..'
                }, Tine.Filemanager.Model.Node));
            }

            gridPanel.action_goUpFolder.setHidden(!path || path === '/');
        }, this);

        gridPanel.getGrid().reconfigure(gridPanel.getStore(), this.getColumnModel());

        // Hide filter toolbar
        gridPanel.filterToolbar.hide();

        return gridPanel;
    },

    getEventNames: function (record) {
        const value = record.get('name');
        const names = Array.isArray(value)
            ? value.map(t => t?.text)
            : [value];

        return _.uniq(names
            .map(n => String(n || '').replace(/\//g, '-').trim())
            .filter(Boolean));
    },

    onRecordLoad: async function (editDialog, record) {
        if (!record) return;

        let path = this.basePath;
        const folderName = await this.findEventFolder(this.basePath, this.getEventNames(record));
        if (folderName) {
            path = `${this.basePath}${folderName}/`;
        }

        this.initialPath = Tine.Filemanager.Model.Node.sanitize(path);
        this.loadPath(this.initialPath);
    },

    findEventFolder: async function (parentPath, names) {
        if (!names.length) return null;
        try {
            const result = await Tine.Filemanager.searchNodes([
                {field: 'path', operator: 'equals', value: parentPath}
            ], {limit: 500});

            const folders = _.filter(result?.results, {type: 'folder'});
            // prefer the order of the translations (e.g. user's language first)
            for (const name of names) {
                const match = _.find(folders, f => f.name.toLowerCase() === name.toLowerCase());
                if (match) return match.name;
            }
            return null;
        } catch (e) {
            return false;
        }
    },

    loadPath: function (path) {
        const gridPanel = this.getGridPanel();
        const grid = gridPanel.getGrid();

        gridPanel.filterToolbar.setValue([
            {field: 'query', operator: 'contains', value: ''},
            {field: 'path', operator: 'equals', value: path}
        ]);

        if (grid.viewReady) {
            gridPanel.getStore().load();
        } else if (!this.pendingInitialLoad) {
            this.pendingInitialLoad = true;
            grid.on('viewready', () => {
                this.pendingInitialLoad = false;
                gridPanel.getStore().load();
            }, this, {single: true});
        }
    },

    setOwnerCt: function (ct) {
        this.ownerCt = ct;

        if (!this.editDialog) {
            this.editDialog = this.findParentBy(c => c instanceof Tine.widgets.dialog.EditDialog);
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        if (!this.initialOnRecordLoadDone) {
            this.initialOnRecordLoadDone = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }
    },

    getColumnModel: function () {
        var columns = [Object.assign(_.find(this.gridPanel.customColumnData, {id: 'name'}), {
            header: this.app.i18n._("Name"),
            dataIndex: 'name',
            width: 70,
        }), {
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 40,
            sortable: true,
            dataIndex: 'size',
            renderer: Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, true], 3)
        }, {
            id: 'contenttype',
            header: this.app.i18n._("Content type"),
            width: 50,
            sortable: true,
            dataIndex: 'contenttype',
            renderer: function (value, metadata, record) {

                var app = Tine.Tinebase.appMgr.get('Filemanager');
                if (record.data.type === 'folder') {
                    return app.i18n._("Folder");
                } else {
                    return value;
                }
            }
        }, {
            id: 'creation_time',
            header: this.app.i18n._("Creation Time"),
            width: 100,
            sortable: true,
            dataIndex: 'creation_time',
            renderer: Tine.Tinebase.common.dateTimeRenderer,
            hidden: true
        },{
            id: 'last_modified_time',
            header: this.app.i18n._("Last Modified Time"),
            width: 100,
            sortable: true,
            dataIndex: 'last_modified_time',
            hidden: false,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        }];

        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: columns
        });
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {

    },

}), 10);

