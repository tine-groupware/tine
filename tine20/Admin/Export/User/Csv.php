<?php
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de>
 * @copyright    Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Admin_Export_User_Csv extends Tinebase_Export_CsvNew
{
    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        parent::_resolveRecords($_records);
        foreach ($_records as $user) {
            $groupMemberships = Tinebase_Group::getInstance()->getGroupMemberships($user->getId());
            $groups = Tinebase_Group::getInstance()->getMultiple($groupMemberships)->name;
            $user->groups = implode(",", $groups);
            $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user->accountId);
            $roles = Tinebase_Acl_Roles::getInstance()->getMultiple($roleMemberships)->name;
            $user->roles = implode(",", $roles);
            $user->password_must_change = (int) $user->password_must_change;
        }
    }
}
