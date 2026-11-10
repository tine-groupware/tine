/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import QuickTags from './QuickTags';
import tagStyle, { label } from './tagStyle';

Ext.ns('Tine.widgets.tags');

Tine.widgets.tags.tagStyle = tagStyle;
Tine.widgets.tags.QuickTags = QuickTags;

/**
 * menu to toggle tags of records
 *
 * - pinned tags first, then attached and recently used tags, then actions
 * - mode remote: changes are saved directly (main screen grids)
 * - mode local: changes are set to the records only (record fields, saved with the main record)
 *
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMenu
 * @extends     Ext.menu.Menu
 */
Tine.widgets.tags.TagsMenu = Ext.extend(Ext.menu.Menu, {
    /**
     * @cfg {Tine.Tinebase.data.Record|String} recordClass of the records to tag
     */
    recordClass: null,

    /**
     * @cfg {String} mode remote|local
     */
    mode: 'remote',

    /**
     * @cfg {Ext.grid.AbstractSelectionModel} selectionModel to get records (and filter in remote mode) from
     */
    selectionModel: null,

    /**
     * @cfg {Function} getRecords (optional) custom fn to get the records to tag
     */
    getRecords: null,

    /**
     * @cfg {Function} onLocalChange (mode local) called with (records, tagData, attached) after tags got changed
     */
    onLocalChange: Ext.emptyFn,

    /**
     * @cfg {Function} onRemoteUpdate (mode remote) called with (records, {reload}) after tags got saved
     *  reload: records are not updated (e.g. changes via dialogs)
     */
    onRemoteUpdate: Ext.emptyFn,

    /**
     * @cfg {Boolean} readOnly
     */
    readOnly: false,

    /**
     * @cfg {Number} maxOtherTags max number of not pinned tags (attached or recently used)
     */
    maxOtherTags: 10,

    cls: 'tw-tags-menu',
    enableScrolling: false,

    initComponent() {
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);
        this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        this.quickTags = new QuickTags({ recordClass: this.recordClass });
        // usable tags (pins and recents might refer to deleted tags) are kept up to date by the tags cache
        this.offUsableTagsChange = this.quickTags.onUsableTagsChange(() => this.onUsableTagsChange());
        this.items = [];

        Tine.widgets.tags.TagsMenu.superclass.initComponent.call(this);

        this.on('beforeshow', this.onBeforeMenuShow, this);
        this.on('show', this.onMenuShow, this);
        this.on('show', this.blockOuterTips, this);
        this.on('hide', this.unblockOuterTips, this);
    },

    /**
     * tooltips of elements outside of menus (e.g. tag icons of the grid cell the menu was opened from)
     * would cover the menu -> not shown while the menu is visible
     */
    blockOuterTips() {
        const tip = Ext.QuickTips.getQuickTip();
        if (!tip || this.outerTipsBlocker) return;
        this.outerTipsBlocker = (quickTip) => {
            const target = quickTip.activeTarget?.el;
            return !target || !!Ext.fly(target).up('.x-menu');
        };
        tip.on('beforeshow', this.outerTipsBlocker);
        if (tip.isVisible() && !this.outerTipsBlocker(tip)) {
            tip.hide();
        }
    },

    unblockOuterTips() {
        if (this.outerTipsBlocker) {
            Ext.QuickTips.getQuickTip()?.un('beforeshow', this.outerTipsBlocker);
            delete this.outerTipsBlocker;
        }
    },

    /**
     * @return {Array} records to tag
     */
    getTagRecords() {
        if (_.isFunction(this.getRecords)) {
            return this.getRecords() || [];
        }
        return this.selectionModel ? this.selectionModel.getSelections() : [];
    },

    isFilterSelect() {
        return this.mode === 'remote' && !!this.selectionModel?.isFilterSelect;
    },

    /**
     * get the quick tags of the menu
     *
     * @return {Object} { pinned: [tagData], others: [tagData] }
     */
    getMenuTags(counts = QuickTags.getTagCounts(this.getTagRecords())) {
        const pinned = this.quickTags.getPinnedTags().map((tag) => counts[tag.id]?.tag || tag);
        const isPinned = (tag) => !!_.find(pinned, { id: tag.id });
        const attached = _.sortBy(_.map(counts, 'tag'), 'name').filter((tag) => !isPinned(tag));
        const recents = this.quickTags.getRecentTags().filter((tag) => !isPinned(tag) && !counts[tag.id]);
        const others = attached.concat(recents.slice(0, Math.max(0, this.maxOtherTags - attached.length)));

        return { pinned, others };
    },

    /**
     * NOTE: usable tags are loaded on boot and kept up to date (broadcasthub)
     *       they are (re)loaded here only, if they are not loaded or outdated (e.g. no broadcasthub)
     */
    onBeforeMenuShow() {
        this.refresh();
        this.menuTagIds = this.getMenuTagIds();
        if (!this.quickTags.hasUsableTags()) {
            this.addClass('tw-tags-menu-loading');
            this.quickTags.loadUsableTags().catch(Ext.emptyFn).then(() => {
                if (this.isDestroyed) return;
                this.removeClass('tw-tags-menu-loading');
                this.onUsableTagsChange();
            });
        }
    },

    /**
     * usable tags changed (e.g. tag deleted or created by someone else)
     */
    onUsableTagsChange() {
        if (this.isVisible() && this.getMenuTagIds() !== this.menuTagIds) {
            this.refresh();
            this.menuTagIds = this.getMenuTagIds();
        }
    },

    onDestroy() {
        this.offUsableTagsChange?.();
        this.unblockOuterTips();
        Tine.widgets.tags.TagsMenu.superclass.onDestroy.call(this);
    },

    getMenuTagIds() {
        const { pinned, others } = this.getMenuTags();
        return _.map(pinned, 'id').join(',') + '|' + _.map(others, 'id').join(',');
    },

    /**
     * (re)build menu items
     */
    refresh() {
        const records = this.getTagRecords();
        const counts = QuickTags.getTagCounts(records);
        const { pinned, others } = this.getMenuTags(counts);
        const items = [];

        pinned.forEach((tag) => items.push(this.createTagItem(tag, records, counts, true)));
        others.forEach((tag) => items.push(this.createTagItem(tag, records, counts, false)));
        if (items.length) items.push('-');
        items.push(...this.getActionItems());

        this.removeAll(true);
        this.add(...items);
        // registered items e.g. help (UserManual)
        new Ext.ux.ItemRegistry({ key: 'Tinebase-TagsMenu' }).init(this);
        if (this.rendered) {
            this.doLayout(false, true);
        }
    },

    createTagItem(tag, records, counts, pinned) {
        const filterSelect = this.isFilterSelect();
        const count = counts[tag.id]?.count || 0;
        const state = filterSelect ? 'none' : QuickTags.getState(records, tag, counts);
        const labelState = { all: 'full', some: 'part', none: 'none' }[state];
        const pinTip = pinned ? i18n._('Unpin tag') : i18n._('Pin tag');

        // NOTE: state 'some' is shown by css (tw-tags-menu-tag-some), menu is rebuilt after each change
        return new Ext.menu.CheckItem({
            tagData: tag,
            tagState: state,
            checked: state === 'all',
            hideOnClick: false,
            disabled: this.readOnly,
            cls: `tw-tags-menu-tag tw-tags-menu-tag-${state}` + (pinned ? ' tw-tags-menu-pinned' : ''),
            text: label(tag, { state: labelState, count: state === 'some' ? `${count}/${records.length}` : '' })
                + `<span class="tw-tags-menu-pin" ext:qtip="${pinTip}"></span>`,
            handler: (item, e) => {
                // NOTE: CheckItem toggled itself already -> refresh restores / updates the state
                //       defer: the item is destroyed by refresh, ext is still handling its click
                const isPin = !!(e && e.getTarget('.tw-tags-menu-pin'));
                _.defer(() => {
                    if (isPin) {
                        this.quickTags.togglePin(tag);
                        this.refresh();
                    } else {
                        this.toggleTag(tag);
                    }
                });
            }
        });
    },

    getActionItems() {
        const items = [];

        if (!this.massAttachAction) {
            this.massAttachAction = new Tine.widgets.tags.TagsMassAttachAction({
                app: this.app,
                text: i18n._('Add Tags') + '…',
                recordClass: this.recordClass,
                selectionModel: this.selectionModel,
                mode: this.mode,
                onLocalOk: (tags) => {
                    const records = this.getTagRecords();
                    tags.forEach((tag) => {
                        if (QuickTags.getState(records, tag) !== 'all') {
                            QuickTags.toggleLocal(records, tag);
                        }
                        this.quickTags.addRecent(tag);
                    });
                    QuickTags.invalidateAvailableTags();
                    this.onLocalChange(records);
                },
                updateHandler: (tags) => {
                    // NOTE: tags might have been created in the dialog, records are not updated -> reload
                    QuickTags.invalidateAvailableTags();
                    _.filter(tags, 'id').forEach((tag) => this.quickTags.addRecent(tag));
                    this.onRemoteUpdate(this.getTagRecords(), { reload: true });
                }
            });
        }
        items.push(new Ext.menu.Item(Object.assign(Ext.apply({}, this.massAttachAction.initialConfig), {
            disabled: this.readOnly
        })));

        // NOTE: in local mode all attached tags are listed in the menu -> no need for the detach dialog
        if (this.mode === 'remote') {
            if (!this.massDetachAction) {
                this.massDetachAction = new Tine.widgets.tags.TagsMassDetachAction({
                    app: this.app,
                    text: i18n._('Detach tag(s)') + '…',
                    recordClass: this.recordClass,
                    selectionModel: this.selectionModel,
                    updateHandler: () => {
                        this.onRemoteUpdate(this.getTagRecords(), { reload: true });
                    }
                });
            }
            items.push(new Ext.menu.Item(Object.assign(Ext.apply({}, this.massDetachAction.initialConfig), {
                disabled: this.readOnly
            })));
        }

        return items;
    },

    /**
     * toggle tag of current records
     * - attach to all records if not all records have the tag
     * - detach from all records otherwise
     */
    async toggleTag(tag) {
        const records = this.getTagRecords();
        if (this.readOnly || (!records.length && !this.isFilterSelect())) return;

        this.quickTags.addRecent(tag);

        if (this.mode === 'local') {
            const attached = QuickTags.toggleLocal(records, tag);
            this.onLocalChange(records, tag, attached);
        } else {
            await this.toggleRemote(records, tag);
        }

        if (this.isVisible()) {
            this.refresh();
        }
    },

    async toggleRemote(records, tag) {
        const attach = this.isFilterSelect() || QuickTags.getState(records, tag) !== 'all';
        const filterName = `${this.recordClass.getMeta('appName')}_Model_${this.recordClass.getMeta('modelName')}Filter`;
        const filter = this.selectionModel?.getSelectionFilter ? this.selectionModel.getSelectionFilter() : [
            { field: 'id', operator: 'in', value: _.map(records, (r) => r.getId()) }
        ];

        try {
            await new Promise((resolve, reject) => {
                Ext.Ajax.request({
                    timeout: 1800000, // 30 minutes
                    params: attach ? {
                        method: 'Tinebase.attachTagToMultipleRecords',
                        filterData: filter,
                        filterName: filterName,
                        tag: tag
                    } : {
                        method: 'Tinebase.detachTagsFromMultipleRecords',
                        filterData: filter,
                        filterName: filterName,
                        tag: [tag.id]
                    },
                    success: resolve,
                    failure: (response, options) => {
                        Tine.Tinebase.ExceptionHandler.handleRequestException(response, options);
                        reject(response);
                    }
                });
            });
        } catch (e) {
            return;
        }

        // reflect changes in records without reloading the grid
        if (!this.isFilterSelect()) {
            QuickTags.toggleLocal(records, tag, { attach, committed: true });
        }
        this.onRemoteUpdate(records);
    },

    /**
     * toggle pinned tags with keys 1-9
     */
    onMenuShow() {
        if (!this.keyMap) {
            this.keyMap = new Ext.KeyMap(this.el, {
                key: '123456789',
                fn: (key, e) => {
                    const pinned = this.quickTags.getPinnedTags();
                    const tag = pinned[key - 49];
                    if (tag) {
                        e.stopEvent();
                        this.toggleTag(tag);
                    }
                }
            });
        }
    }
});

Ext.reg('tw-tagsmenu', Tine.widgets.tags.TagsMenu);
