/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import QuickTags from './QuickTags';
import { getStyle, getQtip } from './tagStyle';
import './TagsMenu';

Ext.ns('Tine.widgets.tags');

/**
 * "Tags" row for context menus
 *
 * - shows the tags of the sub menu (pinned, attached, recent) as icons, attached tags in full color
 * - a click on an icon toggles the tag and closes the menu
 * - opens the TagsMenu as sub menu
 *
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMenuItem
 * @extends     Ext.menu.Item
 */
Tine.widgets.tags.TagsMenuItem = Ext.extend(Ext.menu.Item, {
    /**
     * @cfg {Object} menuConfig config of Tine.widgets.tags.TagsMenu (recordClass, mode, selectionModel, getRecords, ...)
     */
    menuConfig: null,

    iconCls: 'action_tag',
    cls: 'tw-tags-menu-item',
    requiredGrant: 'editGrant',
    allowMultiple: true,

    initComponent() {
        this.text = this.text || i18n._('Tags');
        this.baseText = this.text;
        this.menu = new Tine.widgets.tags.TagsMenu(this.menuConfig);
        this.offUsableTagsChange = this.menu.quickTags.onUsableTagsChange(() => this.onUsableTagsChange());

        Tine.widgets.tags.TagsMenuItem.superclass.initComponent.call(this);

        this.on('click', this.onItemClick, this);
    },

    onRender(ct, position) {
        Tine.widgets.tags.TagsMenuItem.superclass.onRender.call(this, ct, position);

        // context menus are cached -> refresh pins on each show
        this.ownerCt?.on('beforeshow', this.onBeforeOwnerShow, this);
        this.refreshPins();
    },

    /**
     * @cfg {Number} maxIcons
     */
    maxIcons: 12,

    /**
     * NOTE: usable tags (pins and recents might refer to deleted tags) are kept up to date by the tags cache,
     *       they are (re)loaded here only, if they are not loaded or outdated (e.g. no broadcasthub)
     */
    onBeforeOwnerShow() {
        this.refreshPins();
        if (!this.menu.quickTags.hasUsableTags()) {
            this.addClass('tw-tags-menu-item-loading');
            this.menu.quickTags.loadUsableTags().catch(Ext.emptyFn).then(() => {
                if (this.isDestroyed) return;
                this.removeClass('tw-tags-menu-item-loading');
                this.onUsableTagsChange();
            });
        }
    },

    onUsableTagsChange() {
        if (this.ownerCt?.isVisible() && this.menu.getMenuTagIds() !== this.iconTagIds) {
            this.refreshPins();
        }
    },

    onDestroy() {
        this.offUsableTagsChange?.();
        Tine.widgets.tags.TagsMenuItem.superclass.onDestroy.call(this);
    },

    refreshPins() {
        const records = this.menu.getTagRecords();
        const counts = QuickTags.getTagCounts(records);
        const { pinned, others } = this.menu.getMenuTags(counts);
        this.iconTags = pinned.concat(others).slice(0, this.maxIcons);
        this.iconTagIds = this.menu.getMenuTagIds();
        const pins = this.iconTags.map((tag) => {
            const state = this.menu.isFilterSelect() ? 'none' : QuickTags.getState(records, tag, counts);
            const cls = `tw-tags-menu-item-pin tw-tags-menu-item-pin-${{ all: 'full', some: 'part', none: 'none' }[state]} dark-reverse`;
            return `<span class="${cls}" data-tag-id="${Ext.util.Format.htmlEncode(tag.id)}" style="${getStyle(tag)}" ext:qtip="${getQtip(tag)}"></span>`;
        });

        const text = this.baseText + (pins.length ? `<span class="tw-tags-menu-item-pins">${pins.join('')}</span>` : '');
        if (this.rendered) {
            this.setText(text);
        } else {
            this.text = text;
        }
    },

    onItemClick(item, e) {
        const pin = e.getTarget('.tw-tags-menu-item-pin');
        if (!pin) return;

        const tag = _.find(this.iconTags, { id: pin.getAttribute('data-tag-id') });
        if (tag && !this.disabled) {
            this.menu.toggleTag(tag);
        }
        this.parentMenu?.hide(true);
        return false;
    }
});

Ext.reg('tw-tagsmenuitem', Tine.widgets.tags.TagsMenuItem);
