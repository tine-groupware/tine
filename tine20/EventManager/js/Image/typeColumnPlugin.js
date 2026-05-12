/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

const ImageTypeColumnPlugin = {
    init(cmp) {
        this.cmp = cmp;
        this.cmp.store.on('remove', this.removeImageRecord, this);

        cmp.colModel.config.unshift(new Ext.grid.Column({
            resizable: true,
            id: 'source',
            width: 100,
            header: i18n._('Source'),
            renderer: (v, c, r) => {
                const image = this.findImageRecord(r);
                return Tine.widgets.grid.RendererManager.get('EventManager', 'ImageMetadata', 'source')(image?.source);
            },
            editor: Tine.widgets.form.FieldManager.get('EventManager', 'ImageMetadata', 'source', 'propertyGrid', {
                allowBlank: false
            })
        }));

        cmp.colModel.config.unshift(new Ext.grid.Column({
            resizable: true,
            id: 'sort',
            width: 60,
            header: i18n._('Sort'),
            renderer: (v, c, r) => {
                const image = this.findImageRecord(r);
                return Tine.widgets.grid.RendererManager.get('EventManager', 'ImageMetadata', 'sort')(image?.sort);
            },
            editor: Tine.widgets.form.FieldManager.get('EventManager', 'ImageMetadata', 'sort', 'propertyGrid', {
                allowBlank: true,
                minValue: 0,
                value: 1
            })
        }));

        cmp.colModel.config.unshift(new Ext.grid.Column({
            resizable: true,
            id: 'consent',
            width: 80,
            header: i18n._('Consent'),
            renderer: (v, c, r) => {
                const image = this.findImageRecord(r);
                return Tine.widgets.grid.RendererManager.get('EventManager', 'ImageMetadata', 'consent')(image?.consent);
            },
            editor: Tine.widgets.form.FieldManager.get('EventManager', 'ImageMetadata', 'consent', 'propertyGrid', {
                allowBlank: true,
            })
        }));

        cmp.on('afteredit', (editEvent) => {
            const columnIndex = editEvent.column;
            const columnId = this.cmp.colModel.config[columnIndex]?.id;

            if (['source', 'sort', 'consent'].indexOf(columnId) >= 0) {
                let imageRecord = this.findImageRecord(editEvent.record);

                // Create new image record if it doesn't exist
                if (!imageRecord) {
                    imageRecord = {
                        id: Tine.Tinebase.data.Record.generateUID(),
                        node_id: editEvent.record.id,
                        task: this.cmp.editDialog.record.id,
                        consent: false,
                        source: '',
                        sort: 1,
                    };

                    const images = this.cmp.editDialog.record.get('images') || [];
                    images.push(imageRecord);
                    this.cmp.editDialog.record.set('images', images);
                }

                // Update the specific field
                switch (columnId) {
                    case 'source':
                        imageRecord.source = editEvent.value;
                        break;
                    case 'sort':
                        imageRecord.sort = parseInt(editEvent.value) || 0;
                        break;
                    case 'consent':
                        imageRecord.consent = Boolean(editEvent.value);
                        break;
                }

                this.cmp.view.refresh();
            }
        }, this);
    },

    findImageRecord(attachmentRecord) {
        const images = this.cmp.editDialog.record.get('images') || [];
        return _.find(images, { node_id: attachmentRecord.id });
    },

    removeImageRecord(attachmentRecord) {
        const images = this.cmp.editDialog.record.get('images') || [];
        _.remove(images, { node_id: attachmentRecord.id });
        this.cmp.editDialog.record.set('images', images);
    }
};

Ext.ux.pluginRegistry.register('/EventManager/EditDialog/Event/AttachmentsGrid', ImageTypeColumnPlugin);
