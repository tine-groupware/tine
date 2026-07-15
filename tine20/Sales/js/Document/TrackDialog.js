/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import asString from "../../../Tinebase/js/ux/asString";
import * as async from 'async'

Tine.Sales.Document.TrackDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    width: 800,
    height: 600,


    document: null,
    app: null,
    layout: 'fit',
    border: false,
    frame: false,
    buttonAlign: null,

    cyNodesCollapsed: true,

    initComponent () {
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.documentModel);
        this.record = Tine.Tinebase.data.Record.setFromJson(this.document, this.recordClass);

        (async () => {
            const title = await asString(this.record.getTitle());
            this.window.setTitle(this.app.formatMessage('Trac Document { title }', { title }));
        })();


        this.cyImport = Promise.all([
            import(/* webpackChunkName: "Tinebase/js/cytoscape" */ 'cytoscape'),
            import(/* webpackChunkName: "Tinebase/js/cytoscape" */ 'cytoscape-dagre')
        ])
        this.cyPanel = new Ext.Panel({
            tbar: [this.refresh = new Ext.Toolbar.Button({
                tooltip: Ext.PagingToolbar.prototype.refreshText,
                overflowText: Ext.PagingToolbar.prototype.refreshText,
                iconCls: 'x-tbar-loading',
                handler: this.doRefresh,
                scope: this
            }), '-', this.cyToggleCollapseButton = new Ext.Button({
                enableToggle: true,
                text: this.app.i18n._('Expand Positions'),
                iconCls: 'SalesTracExpandPositions',
                toggleHandler: this.cyToggleCollapse,
                scope: this
            })],
            layout: 'fit',
            border: false,
            getStore: function() {
                return me.store;
            },
            getView: function() {
                return view;
            },
            getSelectionModel: function() {
                return {
                    getSelections: function() {
                        return this.selectedNode;
                    }
                };
            }
        });

        this.items = [{
            layout: 'fit',
            // align: 'stretch',
            // pack: 'start',
            border: false,
            // autoScroll: true,
            items: [
                this.cyPanel,
            ]
        }];

        const cy = Promise.all([
            this.cyPanel.afterIsRendered(),
            this.cyImport
        ]).then((values) => {
            const cytoscape = values[1][0].default
            const dagre = values[1][1].default
            cytoscape.use(dagre);

            this.renderCy(cytoscape);
        })

        Promise.all([cy, Tine.Sales.trackDocument(this.documentModel, this.record.id)]).then((values) => {
            this.tracData = values[1];
            this.onTracDataLoad();
        });
        Tine.Sales.Document.TrackDialog.superclass.initComponent.call(this);
    },

    renderCy (cytoscape) {
        const darkMode = window.document.body.classList.contains('dark-mode');
        const bgColor = window.getComputedStyle(this.cyPanel.body.dom).backgroundColor;

        this.cy = cytoscape({
            container: this.cyPanel.body.dom,
            elements: [],
            boxSelectionEnabled: false,
            autounselectify: true,
            style: [
                {
                    selector: 'node',
                    css: {
                        'shape': 'rectangle',
                        'height': 20,
                        'width': 20,
                        'background-position-x': 0,
                        'background-position-y': 0,
                        'border-color': '#ffffff',
                        // 'background-opacity': 0,
                        // 'border-width': 3,
                        // 'border-opacity': 0,
                        'background-color': darkMode ? '#ddd' : '#ffffff',
                        'label': 'data(name)',
                        'background-image': this.createImage,
                        // 'background-fit': 'contain',
                        // 'overlay-padding': '10',

                        'text-valign': 'center',
                        'text-halign': 'right',
                        'text-margin-x': 4,
                        'text-background-color': bgColor,
                        'text-background-opacity': 1,
                        'text-background-shape': 'roundrectangle',
                        'text-background-padding': '3px'

                    }
                },
                {
                    selector: ':parent',
                    css: {
                        'padding-top': '30px',
                        'font-weight': 'bold',
                        'height': 40,
                        'width': 40,
                        // 'text-margin-x': '-100px',
                        'text-margin-y': '30px',
                        'shape': 'roundrectangle',
                        'border-width': 3,
                        'border-opacity': 1,
                        'text-valign': 'top',
                        'text-halign': 'center',
                        'text-margin-x': 4,
                        'text-background-color': bgColor,
                        'text-background-opacity': 1,
                        'text-background-shape': 'roundrectangle',
                        'text-background-padding': '3px'
                    }
                },
                { selector: '.reversed', style: { 'color': darkMode ? '#888' : '#aaa', 'text-opacity': 0.6, 'opacity': 0.5 } },
                {
                    selector: 'edge',
                    css: {
                        // 'label': 'data(type)',
                        'curve-style': 'bezier',
                        'target-arrow-shape': 'triangle',
                        'width': 4,
                        'line-color': darkMode ? '#888': '#ddd',
                        'target-arrow-color': darkMode ? '#888' :'#ddd'
                    }
                }
            ]
        });

        this.cy.on('tap', _.bind(this.onTap, this));
    },

    createImage (ele) {
        const modelName = ele.data('modelName');
        if (! modelName) return

        const iconCls = '.' + modelName.replace('_Model_', '');
        const cssRule = Ext.util.CSS.getRule(iconCls);
        const iconData = cssRule.style.backgroundImage;
        const iconImage = new Image();
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const url = (data) => {return data};

        const size = ele.data('collapsed') ? 20 : 40;
        canvas.width = size; // + tags.length * 18;
        canvas.height = size;

        iconImage.src = eval(iconData);
        ctx.drawImage(iconImage, 0, 0, size, size);

        return 'url("' + canvas.toDataURL() + '")';
    },

    onTap (evt) {
        var evtTarget = evt.target;

        // for some reason cy internal selection model does not work :-(
        // -> might have to do with autounselectify opton :-)
        if (this.selectedNode) {
            this.selectedNode.style({
                'overlay-opacity': '0'
            });
        }

        if (evtTarget?.isNode()) {
            const node = evtTarget;
            const [modelName, id] = node.data('id').split('-');

            this.selectedNode = node;
            this.selectedNode.style({
                'overlay-opacity': '0.2'
            });

            if (Ext.isDate(this.onTapLastTime) && this.onTapLastTime.getElapsed() < 500) {
                const editDialog = Tine.widgets.dialog.EditDialog.getConstructor(modelName);
                editDialog.openWindow({ recordId: id, record: { id } });
            }
            this.onTapLastTime = new Date();
        }
    },

    async onTracDataLoad() {
        // docuemnt or position based?
        // -> start with document
        // console.error(tracData);
        const elements = {nodes: [], edges: []};
        await async.forEach(this.tracData, (async (dynamicRecordWrapper) => {
            const {model_name: modelName, record: recordData} = dynamicRecordWrapper;
            const document = Tine.Tinebase.data.Record.setFromJson(recordData, Tine.Tinebase.data.RecordMgr.get(modelName));
            const isReversed = document.get('reversed_status') !== 'notReversed';

            const documentId = `${modelName}-${document.id}`;
            const title = await asString(document.get('document_number'));
            elements.nodes.push({
                data: {id: documentId, name: title, modelName, collapsed: this.cyNodesCollapsed },
                classes: [modelName, isReversed ? 'reversed' : ''].join(' ').trim()
            });

            if (this.cyNodesCollapsed) {
                _.forEach(document.get('precursor_documents'), (precursorDynamicRecordWrapper) => {
                    const {model_name: precursorModelName, record: precursorRecordId} = precursorDynamicRecordWrapper;
                    const precursorDocumentId = `${precursorModelName}-${precursorRecordId}`;
                    elements.edges.push({
                        data: {id: `${precursorDocumentId}-${documentId}`, source: precursorDocumentId, target: documentId}
                    });
                })
            } else {
                _.forEach(document.get('positions'), (positionData) => {
                    const positionModelName = modelName.replace(/_Document_/, '_DocumentPosition_');
                    const positionId = `${positionModelName}-${positionData.id}`;
                    elements.nodes.push({data: {id: positionId, name: `${positionData.pos_number} ${positionData.title}`, parent: `${modelName}-${document.id}`} });
                    if (positionData.precursor_position) {
                        const precursorId = `${positionData.precursor_position_model}-${positionData.precursor_position}`;
                        elements.edges.push({ data: {id: `${precursorId}-${positionId}`, source: precursorId, target: positionId}});
                    }
                });
            }
        }));

        this.cy.startBatch();
        this.cy.remove('*');
        this.cy.add(elements);
        this.cy.endBatch();
        this.cy.layout({
            name: 'dagre',
            rankDir: 'LR',
            nodeSep: 30,
            rankSep: 80,
            nodeDimensionsIncludeLabels: true,
            animate: true
        }).run();
    },

    async doRefresh() {
        this.refresh.disable();
        this.tracData = await Tine.Sales.trackDocument(this.documentModel, this.record.id);
        await this.onTracDataLoad();
        this.refresh.enable();
    },

    cyToggleCollapse() {
        const pressed = this.cyToggleCollapseButton.pressed;
        this.cyNodesCollapsed = !pressed;
        this.onTracDataLoad();
    }
});

Tine.Sales.Document.TrackDialog.openWindow = function(config) {
    return Tine.WindowFactory.getWindow({
        // width: 400,
        // height: 300,
        name: `Tine.Sales.Document.TrackDialog.${config.document.id}`,
        contentPanelConstructor: 'Tine.Sales.Document.TrackDialog',
        contentPanelConstructorConfig: config,
    });

};
