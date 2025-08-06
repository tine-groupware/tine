Ext.ux.ItemRegistry.registerItem('EventManager-Event-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    frame: true,
    requiredGrant: 'editGrant',
    layout: 'fit',

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

        var gridPanel = this.gridPanel = new Tine.Filemanager.NodeGridPanel({
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
            /*defaultFilters: [
                {field: 'query', operator: 'contains', value: ''},
                {field: 'path', operator: 'equals', value: '/shared/Veranstaltungen/'}
            ],*/
            displaySelectionHelper: false
        });

        gridPanel.pagingToolbar.insert(11, gridPanel.action_goUpFolder);

        gridPanel.getStore().on('load', (store, rs, options) => {
            const path = _.get(_.filter(_.get(store, 'reader.jsonData.filter'), {field: 'path'}), '[0].value.path');
            if (path !== _.find(this.getGridPanel().defaultFilters, { field: 'path'}).value) {
                store.insert(0, Tine.Tinebase.data.Record.setFromJson({
                    type: 'folder',
                    name: '..',
                    path: Tine.Filemanager.Model.Node.dirname(path),
                    id: '..'
                }, Tine.Filemanager.Model.Node));
            }

            gridPanel.action_goUpFolder.setHidden(path === this.initialPath.replace(/[0-9A-Z#]+\/$/, ''));
        }, this);

        gridPanel.getGrid().reconfigure(gridPanel.getStore(), this.getColumnModel());

        // Hide filter toolbar
        gridPanel.filterToolbar.hide();

        return gridPanel;
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

    onRecordLoad: async function(editDialog, record) {
        const pathFilter = _.find(this.getGridPanel().defaultFilters, { field: 'path'});
        const path = record.data.xprops?.filemanagerPath;

        this.initialPath = pathFilter.value = Tine.Filemanager.Model.Node.sanitize(path) || '/shared/Veranstaltungen/';
        this.getGridPanel().filterToolbar.filterStore.each(function (filter) {
            var field = filter.get('field');
            if (field === 'path') {
                filter.set('value', path);
                return false;
            }
        }, this);

    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {

    },

    setOwnerCt: function(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case record is already loaded
        if (! this.setOwnerCt.initialOnRecordLoad) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }

    }
}), 10);

