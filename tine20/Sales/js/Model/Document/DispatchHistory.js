/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const Mixin = {
    getGroupName: function(withStatus) {
        const transportName = Tine.Tinebase.data.RecordMgr.get(this.get('dispatch_transport')).getRecordName()
        //@TODO add state icon from last Record once type is a keyField and withSatus is true
        return `${transportName} - ${this.get('dispatch_report')}`;
    }
}

Ext.ns('Tine.Sales.Model');
Tine.Sales.Model.Document_DispatchHistoryMixin = Mixin

export default Mixin
