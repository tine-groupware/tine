/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { getTagsCache, getUsableTags as getUsableTagsOfModel } from './TagsCache';

const TAG_FIELDS = ['id', 'name', 'description', 'color', 'type'];

const getData = (tag) => {
    return tag && typeof tag.beginEdit === 'function' ? tag.data : tag;
};

// tags used right now (e.g. created), which are not in the tags cache yet: id => {tag, ts}
const rememberedTags = {};
const REMEMBER_TTL = 60000;

const getId = (tag) => {
    return _.isString(tag) ? tag : getData(tag)?.id;
};

/**
 * quick tags of a model: pinned and recently used tags of the current user
 *
 * - pins are stored per user and model in state
 * - recents are stored per user and application (RecentsManager)
 */
export default class QuickTags {
    /**
     * @param {Object} config
     *  - recordClass {Tine.Tinebase.data.Record} model the tags are used for
     */
    constructor(config) {
        this.recordClass = config.recordClass;
        this.appName = this.recordClass.getMeta('appName');
        this.pinsStateId = ['tinebase-tags-pins', this.appName, this.recordClass.getMeta('modelName')].join('-').toLowerCase();
        this.recentsManager = new Tine.Tinebase.RecentsManager({
            recordClass: Tine.Tinebase.Model.Tag,
            domain: this.appName,
            maxRecents: 20
        });
    }

    /**
     * tags the current user may use for the model (tags cache, kept up to date via broadcasthub)
     * NOTE: pins and recents might refer to deleted tags or tags not usable for the model
     *
     * @return {Array|null} null if not loaded yet
     */
    getUsableTags() {
        const tags = getUsableTagsOfModel(this.recordClass);
        if (!tags) return null;

        const now = Date.now();
        const remembered = _.filter(rememberedTags, (entry, id) => {
            const keep = now - entry.ts < REMEMBER_TTL && !_.find(tags, { id });
            if (!keep) delete rememberedTags[id];
            return keep;
        });
        return tags.concat(_.map(remembered, 'tag'));
    }

    /**
     * @return {Boolean} usable tags are loaded and up to date
     */
    hasUsableTags() {
        return getTagsCache().isFresh();
    }

    /**
     * @return {Promise} resolves when usable tags are loaded and up to date
     */
    loadUsableTags() {
        return getTagsCache().load();
    }

    /**
     * @param {Function} fn called when usable tags changed
     * @return {Function} unsubscribe
     */
    onUsableTagsChange(fn) {
        return getTagsCache().onChange(fn);
    }

    /**
     * filter tags to the usable ones (with up to date data)
     * NOTE: tags are not filtered as long as usable tags are not loaded
     */
    filterAvailable(tags) {
        const usable = this.getUsableTags();
        if (!usable) return tags;
        return tags.map((tag) => _.find(usable, { id: getId(tag) })).filter(Boolean);
    }

    /**
     * tag is used (e.g. created right now) -> it is usable, even if it is not in the tags cache yet
     */
    rememberAvailable(tagData) {
        if (tagData?.id && !_.find(getTagsCache().getData() || [], { id: tagData.id })) {
            rememberedTags[tagData.id] = { tag: tagData, ts: Date.now() };
            getTagsCache().invalidate();
        }
    }

    /**
     * tags might have been created or changed (e.g. in dialogs)
     * NOTE: needed without broadcasthub only, but makes changes visible faster
     */
    static invalidateAvailableTags() {
        getTagsCache().invalidate();
    }

    /**
     * @return {Array} tag data of pinned tags
     */
    getPinnedTags() {
        const pins = Ext.state.Manager.get(this.pinsStateId);
        return this.filterAvailable(_.isArray(pins) ? pins : []);
    }

    isPinned(tag) {
        return !!_.find(this.getPinnedTags(), { id: getId(tag) });
    }

    setPinned(tag, pinned) {
        const tagData = _.pick(getData(tag), TAG_FIELDS);
        const state = Ext.state.Manager.get(this.pinsStateId);
        const pins = this.filterAvailable(_.isArray(state) ? state : []).filter((t) => t.id !== tagData.id)
            .map((t) => _.pick(t, TAG_FIELDS));
        if (pinned) {
            this.rememberAvailable(tagData);
            pins.push(tagData);
        }
        Ext.state.Manager.set(this.pinsStateId, pins);
    }

    togglePin(tag) {
        this.setPinned(tag, !this.isPinned(tag));
    }

    /**
     * @return {Array} tag data of recently used tags (most recent first)
     */
    getRecentTags() {
        return this.filterAvailable(_.map(_.sortBy(this.recentsManager.getRecentRecords(), (r) => -(r.dtSelect || 0)), 'data'));
    }

    addRecent(tag) {
        this.rememberAvailable(_.pick(getData(tag), TAG_FIELDS));
        this.recentsManager.addRecentRecord(new Tine.Tinebase.Model.Tag(_.pick(getData(tag), TAG_FIELDS)));
    }

    /**
     * count usage of tags in given records
     *
     * @param {Array} records
     * @return {Object} tagId => {tag: tagData, count: n}
     */
    static getTagCounts(records) {
        return _.reduce(records, (counts, record) => {
            _.each(record.get('tags') || [], (tag) => {
                const tagData = getData(tag);
                if (!tagData?.id) return;
                counts[tagData.id] = counts[tagData.id] || { tag: tagData, count: 0 };
                counts[tagData.id].count++;
            });
            return counts;
        }, {});
    }

    /**
     * get state of a tag for given records
     *
     * @return {String} all|some|none
     */
    static getState(records, tag, counts = null) {
        const count = _.get(counts || QuickTags.getTagCounts(records), `${getId(tag)}.count`, 0);
        return !count ? 'none' : (count >= records.length ? 'all' : 'some');
    }

    /**
     * toggle tag in records (local only)
     * - set tag in all records, if not all records have the tag
     * - remove tag from all records otherwise
     *
     * @return {Boolean} attached
     */
    static toggleLocal(records, tag, options = {}) {
        const tagData = _.pick(getData(tag), TAG_FIELDS);
        const attach = options.attach ?? QuickTags.getState(records, tagData) !== 'all';
        _.each(records, (record) => {
            const tags = _.map(record.get('tags') || [], getData).filter((t) => t?.id !== tagData.id);
            if (attach) {
                tags.push(tagData);
            }
            if (options.committed) {
                // tags are saved already: update data without modification (e.g. grids would save modified records)
                record.data.tags = tags;
                record.store?.fireEvent('update', record.store, record, Ext.data.Record.COMMIT);
            } else {
                // NOTE: Ext compares values as strings, which is not reliable for arrays of objects
                record.beginEdit();
                record.set('tags', '');
                record.set('tags', tags);
                record.endEdit();
            }
        });
        return attach;
    }
}
