<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Christian Feitl <c.feitl@metaways.de>
 * @copyright    Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Admin_Export_Group_Csv extends Tinebase_Export_CsvNew
{
    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        parent::_resolveRecords($_records);
        foreach ($_records as $group) {
            $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($group->getId(), Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP);
            $roles = Tinebase_Acl_Roles::getInstance()->getMultiple($roleMemberships)->name;
            $group->roles = implode(",", $roles);
        }
    }
}
