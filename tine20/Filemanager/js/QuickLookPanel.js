/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FileLocation from 'Model/FileLocation'

Ext.ns('Tine.Filemanager');

require('Filemanager/js/QuickLookRegistry');
require('Filemanager/js/DocumentPreview');
require('Filemanager/js/QuickLookMediaPanel');
require('Filemanager/js/QuickLookHTMLPanel');
require('Filemanager/js/QuickLookTextPanel');
require('Filemanager/js/QuickLookImagePanel');
require('Filemanager/js/QuickLookObjectPanel');

Tine.Filemanager.QuickLookPanel = Ext.extend(Ext.Panel, {

    /**
     * Node record to preview
     */
    record: null,

    /**
     * filemanager
     */
    app: null,

    /**
     * App which triggered this action (passed to preview panel)
     */
    initialApp: null,

    /**
     * window cache for preview panels
     */
    cardPanelsCache: {},

    /**
     * @type Tine.Filemanager.QuickLookRegistry
     */
    registry: null,

    /**
     * Layout
     */
    layout: 'fit', // hfit?

    /**
     * @type SelectionModel
     */
    sm: null,

    border: false,

    requiredGrant: 'downloadGrant',

    /**
     * init panel
     */
    initComponent: function () {
        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.registry = Tine.Filemanager.QuickLookRegistry;

        this.selBtns = new Ext.ButtonGroup({
            hidden: true,
        })

        this.tbar = new Ext.Toolbar({
            items: [this.selBtns, {
                xtype: 'tbfill',
                order: 50
            }, Tine.Filemanager.nodeActionsMgr.get('download', {
                hidden: this.record.constructor.getPhpClassName() === 'Felamimail_Model_Message'
            }) ],
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tine-Filemanager-QuicklookPanel'
            }],
        });

        this.actionUpdater = new Tine.widgets.ActionUpdater({
            evalGrants: true
        });

        this.actionUpdater.addActions(this.tbar.items);

        if (this.tbar.items.getCount() < 2) {
            this.tbar.hide();
        }

        this.action_close = new Ext.Action({
            text: this.app.i18n._('Close'),
            minWidth: 70,
            scope: this,
            handler: this.onClose,
            iconCls: 'action_cancel'
        });

        this.items = [{
            ref: 'cardPanel',
            xtype: 'panel',
            layout: 'card',
            activeItem: 0,
            frame: false,
            border: false,
            items: [{keep: true, html: '<div class="tine-viewport-waitcycle">&nbsp;</div>'}]
        }];

        this.fbar = ['->', this.action_close];

        Ext.getBody().on('keydown', function (e) {
            if (e.getTarget('input')) {
                return;
            }
            
            switch (e.getKey()) {
                case e.SPACE:
                case e.ESC:
                    this.onClose();
                    break;
                case e.DOWN:
                case e.UP:
                case e.LEFT:
                case e.RIGHT:
                    this.onNavigate(e);
                    break;
                default:
                    this.cardPanel.layout.activeItem.fireEvent('keydown', e);
                    break;
            }
        }, this);

        Tine.Filemanager.QuickLookPanel.superclass.initComponent.apply(this, arguments);

        var me = this;
        this.afterIsRendered().then(function () {
            me.loadPreviewPanel();
        });
    },

    /**
     * fetch/manage preview panels for records by content-type
     */
    loadPreviewPanel: async function (idx) {
        let previewPanel = null;

        await this.handleAttachments();

        this.window.setTitle(this.record.get('name'));
        const cacheKey = this.record.get('id') + (idx || '');

        if (this.cardPanelsCache[cacheKey]) {
            previewPanel = this.cardPanel.get(this.cardPanelsCache[cacheKey]);
        } else {
            const fileExtension = Tine.Filemanager.Model.Node.getExtension(this.record.get('name'));
            const contentType = this.record.get('contenttype');
            const fileLocation = FileLocation.create(this.record);

            let previewPanelXtype = '';
            let previewPanelTypes;

            if (this.registry.hasContentType(contentType)) {
                previewPanelTypes = await this.registry.getByContentType(contentType, fileLocation);
            } else if (this.registry.hasExtension(fileExtension)) {
                previewPanelTypes = await this.registry.getByExtension(fileExtension, fileLocation);
            }
            previewPanelXtype = _.get(previewPanelTypes, `[${idx || 0}].xtype`)

            //const useOriginalSizeLimit = 30 * 1024 * 1024; // @TODO have pref or conf, but do we really need this for native browser preview ?
            const isTempFile = !!_.get(this.record, 'json.input');
            const isAttachment = fileLocation.get('model_name') === "Tinebase_Model_FileLocation_RecordAttachment";
            const hasRequiredGrant = this.requiredGrant && !isAttachment ? _.get(this.record, `data.account_grants.${this.requiredGrant}`, false) : true;
            const protectedContentTypes = [
                'txt', 'rtf', 'odt', 'ods', 'odp', 'doc', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx', 'pdf'
            ];
            const previewFromDocumentServer = !previewPanelXtype || (!isTempFile && !hasRequiredGrant && protectedContentTypes.find((type) => {return fileExtension === type}));

            if (previewFromDocumentServer) {
                // use default secured doc preview panel
                previewPanel = new Tine.Filemanager.DocumentPreview({
                    initialApp: this.initialApp,
                    record: this.record
                });
            } else {
                const url = Tine.Filemanager.Model.Node.getDownloadUrl(
                    this.record,
                    this.record.get('revision'),
                    (isTempFile || hasRequiredGrant ) ? 'attachment' : 'inline'
                );

                previewPanel = Ext.create({
                    xtype: previewPanelXtype,
                    initialApp: this.initialApp,
                    nodeRecord: this.record,
                    contentType,
                    url
                });
            }

            previewPanel.previewPanelTypes = hasRequiredGrant ? previewPanelTypes : [] // forced DocumentPreview (image)
            this.cardPanelsCache[cacheKey] = previewPanel.id;
            this.cardPanel.add(previewPanel);
        }

        this.selBtns.removeAll();
        if (previewPanel.previewPanelTypes.length > 1) {
            this.selBtns.add(previewPanel.previewPanelTypes.map((previewPanelType, idx) => {
                return {
                    text: previewPanelType.label,
                    iconCls: previewPanelType.iconCls,
                    pressed: previewPanelType.xtype === previewPanel.xtype,
                    handler: () => {
                        this.loadPreviewPanel(idx);
                    }
                }
            }))
        }
        this.selBtns.setVisible(previewPanel.previewPanelTypes.length > 1)
        this.selBtns.doLayout();

        this.actionUpdater.each(action => action.previewPanel = previewPanel);
        this.actionUpdater.updateActions([this.record]);
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.cardPanel, previewPanel, true);
    },

    async handleAttachments() {
        // cope with attachments
        if (this.record.constructor.hasField('attachments')) {
            this.attachments = _.map(this.record.get('attachments'), (attachment)=> {
                return new Tine.Tinebase.Model.Tree_Node(attachment);
            });
            this.record = this.attachments[0] || new Tine.Tinebase.Model.Tree_Node({name: ''});
        }
    },

    /**
     * navigate previews
     *
     * @param e
     */
    onNavigate: function(e) {
        const key = e.getKey();
        if ([e.LEFT, e.RIGHT].indexOf(key) >= 0) {
            return this.onNavigateAttachment(key === e.LEFT ? -1 : +1);
        }

        if (this.sm) {
            switch (key) {
                case e.DOWN:
                    this.sm.selectNext();
                    break;
                case e.UP:
                    this.sm.selectPrevious();
                    break;
                default:
                    break;
            }

            if (this.sm.getSelected() !== this.record) {
                this.record = this.sm.getSelected();
                this.loadPreviewPanel();
                _.defer(() => { this.doLayout() });
            }
        }
    },

    onNavigateAttachment(dir) {
        if (this.attachments?.length > 1) {
            const caches = this.attachments.map(r => r?.cache ?? r);
            this.record = caches[((caches.indexOf(this.record) || this.attachments.length) + dir)%this.attachments.length];
            this.loadPreviewPanel();
        }
    },
    
    /**
     * @private
     */
    onClose: function(){
        this.fireEvent('cancel');
        this.purgeListeners();
        this.window.close();
    }
});

Tine.Filemanager.QuickLookPanel.openWindow = function (config) {
    const windowNamePrefix = config.windowNamePrefix ?? 'QuickLookPanel_'
    const id = config.recordId ?? config.record?.id ?? 0;
    return Tine.WindowFactory.getWindow({
        width: (screen.height * 0.8) / Math.sqrt(2), // DIN A4 and so on
        height: screen.height * 0.8,
        name: `${windowNamePrefix}${id}`,
        contentPanelConstructor: 'Tine.Filemanager.QuickLookPanel',
        contentPanelConstructorConfig: config,
        modal: false,
    });
};
