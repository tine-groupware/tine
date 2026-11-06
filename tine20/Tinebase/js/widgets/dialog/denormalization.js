/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * returns the names of the locally changed fields of the copy
 * NOTE: the copy gets a new id when it is created from the original
 *
 * @param {Tine.Tinebase.data.Record} copy
 * @return {String[]}
 */
const getLocalChanges = (copy) => {
    return Object.keys(copy.modified || {}).filter((field) => ![copy.idProperty, 'original_id'].includes(field));
};

/**
 * takes the changes of an updated original into its (denormalized) copy
 *
 * NOTE: the server updates the persistent copy itself, a copy of a new (unsaved) context record exists in the client only
 * NOTE: local changes of the copy are tracked in copy.modified, the user is asked whether to keep them
 *
 * @param {Tine.Tinebase.data.Record} copy - copy before the original got updated
 * @param {Object|String} originalData - data of the updated original
 * @param {Tine.Tinebase.data.Record} denormalizationRecordClass
 * @param {Tine.Tinebase.Application} app
 * @return {Promise<Tine.Tinebase.data.Record>} updated copy (of the class of the given copy)
 */
const takeOriginalChanges = async (copy, originalData, denormalizationRecordClass, app) => {
    const recordClass = copy.constructor;
    const originalId = copy.get('original_id') || copy.json?.original_id;
    let updated = null;

    try {
        const persistentCopy = await denormalizationRecordClass.getProxy().promiseLoadRecord(copy.id);
        if (persistentCopy.getMTime() > copy.getMTime()) {
            updated = recordClass.setFromJson(persistentCopy.getData());
        }
    } catch (e) {/* no api -> record was not updated */}

    if (!updated) {
        // take data from updated original
        updated = recordClass.setFromJson(originalData);
        updated.setId(copy.id);
    }
    updated.json.original_id = updated.data.original_id = originalId;

    const localChanges = getLocalChanges(copy);
    if (localChanges.length) {
        if (await Ext.MessageBox.show({
            icon: Ext.MessageBox.QUESTION,
            buttons: Ext.MessageBox.YESNO,
            title: app.formatMessage('Discard changes?'),
            msg: app.formatMessage('Do you want to overwrite the local changes with the data from the original record?'),
        }) === 'no') {
            localChanges.forEach((field) => {
                updated.set(field, copy.get(field));
            });
        }
    }

    return updated;
};

export { getLocalChanges, takeOriginalChanges };
