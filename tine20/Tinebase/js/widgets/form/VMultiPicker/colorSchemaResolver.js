/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * creates a function which returns the colorset of a picker record
 * for cross records (isMetadataModelFor) the colorset of the mapped record is returned
 *
 * @param {object} options
 * @param {string} [options.isMetadataModelFor] field of the mapped record
 * @param {function} options.toRecord converts mapped record data into a record
 * @param {function} options.getRecordSchema returns colorset or null for a record
 * @return {function(record): (object|null)}
 */
export const createColorSchemaResolver = ({ isMetadataModelFor, toRecord, getRecordSchema }) => {
  return (record) => {
    let colorRecord = record
    if (isMetadataModelFor) {
      const data = record.get(isMetadataModelFor)
      colorRecord = !data || data.get ? data : toRecord(data)
    }
    return colorRecord ? getRecordSchema(colorRecord) : null
  }
}

export default createColorSchemaResolver
