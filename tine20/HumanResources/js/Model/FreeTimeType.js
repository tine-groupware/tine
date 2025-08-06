/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
/*global Ext, Tine*/

import Record from 'data/Record'

const FreeTimeType = Record.create([], {
    appName: 'HumanResources',
    modelName: 'FreeTimeType',

    getTitle() {
        const app = Tine.Tinebase.appMgr.get('HumanResources');
        return app.i18n._hidden(this.get('name'));
    },

    getAbbreviation() {
        return FreeTimeType.getAbbreviation(this.data);
    },
})

FreeTimeType.getAbbreviation = (freeTimeTypeData) => {
    const app = Tine.Tinebase.appMgr.get('HumanResources');
    const name = app.i18n._hidden(freeTimeTypeData.name);
    return name.match(/.*\[(.+)\].*/)?.[1] || freeTimeTypeData.abbreviation;
}

export default FreeTimeType