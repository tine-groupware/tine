/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import SyncedCache from '../../data/SyncedCache';

const NAME = 'Tinebase.usableTags';

/**
 * all tags the current user may use (with their contexts), kept up to date via broadcasthub
 */
SyncedCache.define(NAME, () => ({
    models: ['Tinebase_Model_Tag'],
    loader: async () => (await Tine.Tinebase.getUsableTags()).results || [],
}), { preload: true });

const getTagsCache = () => SyncedCache.get(NAME);

/**
 * filter tags by usage context
 *
 * @param {Array} tags tag data with usage_contexts
 * @param {Object} context
 *  - appId {String} id of the application
 *  - model {String} php class name of the model
 *  - personalTags {Boolean} user may use personal tags in the application
 * @return {Array}
 */
const filterUsableTags = (tags, { appId, model, personalTags }) => {
    return (tags || []).filter((tag) => {
        if (tag.type === 'personal' && !personalTags) return false;
        return (tag.usage_contexts || []).some((context) => {
            return String(context.application_id) === '0'
                || (context.application_id === appId && (!context.model || context.model === model));
        });
    });
};

/**
 * @param {Tine.Tinebase.data.Record} recordClass
 * @return {Array|null} tags usable for records of the given class, null if tags are not loaded yet
 */
const getUsableTags = (recordClass) => {
    const tags = getTagsCache().getData();
    if (!tags) return null;

    const appName = recordClass.getMeta('appName');
    return filterUsableTags(tags, {
        appId: Tine.Tinebase.appMgr.get(appName)?.id,
        model: recordClass.getPhpClassName(),
        personalTags: !!Tine.Tinebase.common.hasRight('use_personal_tags', appName),
    });
};

export { getTagsCache, getUsableTags, filterUsableTags };
