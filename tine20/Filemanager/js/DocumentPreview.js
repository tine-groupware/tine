/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Filemanager');

Tine.Filemanager.DocumentPreview = Ext.extend(Ext.Panel, {
    /**
     * Node record to preview
     */
    record: null,

    /**
     * filemanager
     */
    app: null,

    /**
     * App which triggered this action
     */
    initialApp: null,

    /**
     * Layout
     */
    layout: 'fit',

    initComponent: function () {
        this.addEvents(
            /**
             * Fires if no preview is available. Later it should be used to be fired if the browser is not able to load images.
             */
            'noPreviewAvailable'
        );

        this.on('noPreviewAvailable', this.onNoPreviewAvailable, this);
        this.on('keydown', this.onKeydown, this);

        if (!this.app) {
            this.app = Tine.Tinebase.appMgr.get('Filemanager');
        }

        this.afterIsRendered().then(() => {
            this.el.on('contextmenu', (e) => {
                e.stopEvent();
                return false;
            })
        });

        Tine.Filemanager.DocumentPreview.superclass.initComponent.apply(this, arguments);

        if (!this.record) {
            this.fireEvent('noPreviewAvailable');
            return;
        }

        this.loadPreview();
    },

    loadPreview: function () {
        let me = this,
            recordClass = this.record.constructor,
            records = [];

        // attachments preview
        this.isAttachment = this.record.get('path').match(/^\/records/);
        if (! recordClass.hasField('preview_count') && recordClass.hasField('attachments')) {
            _.each(this.record.get('attachments'), function(attachmentData) {
                records.push(new Tine.Tinebase.Model.Tree_Node(attachmentData));
            });
        } else {
            records.push(this.record);
        }

        if (! records.length) {
            this.fireEvent('noPreviewAvailable');
            return;
        }

        me.add(this.previewContainer = new Ext.Panel({
            layout: 'anchor',
            bodyStyle: 'overflow-y: scroll;'
        }));

        this.afterIsRendered().then(async () => {
            const isRendered = records.map((record) => {
                return me.addPreviewPanelForRecord(me, record);
            });

            await Promise.all(isRendered);
        });
    },


    addPreviewPanelForRecord: async function (me, record) {
        const path = record.get('path');
        const revision = record.get('revision');
        const contenttype = record.get('contenttype');
        const urls = [];
        const isTempFile = !!_.get(record, 'json.input');
        const previewCount = record.get('preview_count');

        const generatePreviewUrl = (previewNumber) => {
            const baseParams = {
                frontend: 'http',
                _type: 'previews',
                _num: previewNumber,
            };

            if (isTempFile) {
                return Ext.urlEncode({
                    ...baseParams,
                    method: 'Tinebase.downloadPreviewByTempFile',
                    _tempFileId: record.json.tempFile.id,
                }, Tine.Tinebase.tineInit.requestUrl + '?');
            } else {
                return Ext.urlEncode({
                    ...baseParams,
                    method: 'Tinebase.downloadPreview',
                    _path: path,
                    _appId: me.initialApp ? me.initialApp.id : me.app.id,
                    _revision: revision,
                }, Tine.Tinebase.tineInit.requestUrl + '?');
            }
        };

        if (record.get('type') === 'folder') {
            this.fireEvent('noPreviewAvailable');
            return Promise.resolve();
        }

        if (isTempFile) {
            this.loadMask = new Ext.LoadMask(this.el, {msg: i18n._('Loading'), msgCls: 'x-mask-loading'});
            this.loadMask.show();

            await Tine.Tinebase.getPreviewsFromTempFile(record.json.tempFile.id)
                .then((result) => {
                    if (result === 0) {
                        this.fireEvent('noPreviewAvailable');
                    }
                    record.set('preview_count', result);
                })
                .catch((e) => {})
            this.loadMask.hide();
        } else if (!+previewCount) {
            urls.push(generatePreviewUrl(0));
        }

        _.range(previewCount).forEach((previewNumber) => {
            urls.push(generatePreviewUrl(previewNumber));
        });

        const isFetched = urls.map((url) => {
            return new Promise(async (resolve) => {
                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-TINE20-PREVIEWSERVICE-SYNC': true,
                        }
                    })
                    const data = await response.blob().then(
                        blob => new Promise((resolve, reject) => {
                            resolve(URL.createObjectURL(blob));
                        })
                    )
                    resolve(data);
                } catch (e) {
                    resolve('');
                }
            });
        });
        const results = await Promise.all(isFetched);

        const isRendered = results.map((data) => {
            return new Promise((resolve) => {
                me.previewContainer.add({
                    html: `<img style="width: 100%;" src="${data}" /><div class="filemanager-quicklook-protect" />`,
                    xtype: 'panel',
                    cls: 'dark-reverse',
                    frame: true,
                    border: true,
                });
            });
        });

        me.doLayout();
        return Promise.all(isRendered);
    },

    onKeydown: function(e) {
        const key = e.getKey();
        if (key.constrain(33, 36) === key) {
            const scrollEl = this.previewContainer.el.child('.x-panel-body');
            if (key < 35) {
                scrollEl.scroll(key === e.PAGE_UP ? 'up' : 'down', Math.round(scrollEl.getHeight()*0.9), true);
            } else {
                scrollEl.scrollTo('top', key === e.HOME ? 0 : scrollEl.dom.scrollHeight);
            }
        }
    },

    /**
     * Fires if no previews are available
     */
    onNoPreviewAvailable: function () {
        var me = this;
        me.afterIsRendered().then(function() {
            let text = '';
            let contenttype =  me.record.get('contenttype');
            let iconCls = me.record.get('type') === 'folder' ? 'mime-icon-folder' :
                contenttype ? Tine.Tinebase.common.getMimeIconCls(contenttype) : 'mime-icon-file';

            if (!Tine.Tinebase.configManager.get('filesystem').createPreviews) {
                text = '<b>' + me.app.i18n._('Sorry, Tine 2.0 would have liked to show you the contents of the file.') + '</b><br/><br/>' +
                    me.app.i18n._('This is possible for .doc, .jpg, .pdf and other file formats.') + '<br/>' +
                    '<a href="https://www.tine20.com/kontakt/" target="_blank">' +
                    me.app.i18n._('Interested? Then let us know!') + '</a><br/>' +
                    me.app.i18n._('We would be happy to make you a non-binding offer.');
            } else if (String(contenttype).match(/^vnd\.adobe\.partial-upload.*/)) {
                const [, final] = contenttype.match(/final_type=(.+)$/);
                iconCls = final ? Tine.Tinebase.common.getMimeIconCls(final) : 'mime-icon-file';
                text = '<b>' + me.app.i18n._('This file has no content. The upload has either failed or is not yet complete.') + '</b>';
            } else if (!contenttype) { // how to get all previewable types?
                text = '<b>' + me.app.i18n._('No preview available.') + '</b>';
            } else {
                text = '<b>' + me.app.i18n._('No preview available yet - Please try again in a few minutes.') + '</b>';
            }

            me.add({
                border: false,
                layout: 'vbox',
                layoutConfig: {
                    align: 'stretch'
                },
                items: [{
                    html: text,
                    frame: true,
                    border: true
                }, {
                    border: false,
                    flex: 1,
                    xtype: 'container',
                    cls: iconCls,
                    style: 'background-repeat: no-repeat; background-position: center; background-size: contain;'
                }]
            });

            me.doLayout();
        });
    }
});
