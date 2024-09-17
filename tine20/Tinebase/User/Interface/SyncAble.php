<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * syncable user backend interface
 *
 * @package     Tinebase
 * @subpackage  User
 */
interface Tinebase_User_Interface_SyncAble
{
    /**
     * get user by login name
     *
     * @param   string $_property
     * @param   string $_accountId
     * @param   string $_accountClass
     * @return Tinebase_Model_User the user object
     */
    public function getUserByPropertyFromSyncBackend($_property, $_accountId, $_accountClass = 'Tinebase_Model_User');

    /**
     * get list of users
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @param string $_accountClass the type of subclass for the Tinebase_Record_RecordSet to return
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_User
     */
    public function getUsersFromSyncBackend($_filter = NULL, $_sort = NULL, $_dir = 'ASC', $_start = NULL, $_limit = NULL, $_accountClass = 'Tinebase_Model_User');
    
    /**
     * update user status (enabled or disabled)
     *
     * @param   mixed   $_accountId
     * @param   string  $_status
     */
    public function setStatusInSyncBackend($_accountId, $_status);

    /**
     * sets/unsets expiry date in ldap backend
     *
     * expiryDate is the number of days since Jan 1, 1970
     *
     * @param   mixed      $_accountId
     * @param   Tinebase_DateTime  $_expiryDate
     */
    public function setExpiryDateInSyncBackend($_accountId, $_expiryDate);
    
    /**
     * add an user
     * 
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser
     */
    public function addUserToSyncBackend(Tinebase_Model_FullUser $_user);

    /**
     * updates an existing user
     *
     * @todo check required objectclasses?
     *
     * @param Tinebase_Model_FullUser $_account
     * @return Tinebase_Model_FullUser
     */
    public function updateUserInSyncBackend(Tinebase_Model_FullUser $_account);

    public function isReadOnlyUser(string|int|null $userId): bool;

    public function setPasswordInSyncBackend(Tinebase_Model_FullUser $user, string $_password, bool $_encrypt = true, bool $_mustChange = false): void;
    
    /**
     * delete an user in ldap backend
     *
     * @param Tinebase_Model_User|string|int $_userId
     */
    public function deleteUserInSyncBackend($_userId);
    
    /**
     * return contact information for user
     *
     * @param  Tinebase_Model_FullUser    $_user
     * @param  Addressbook_Model_Contact  $_contact
     */
    public function updateContactFromSyncBackend(Tinebase_Model_FullUser $_user, Addressbook_Model_Contact $_contact);

    /**
     * update contact data(first name, last name, ...) of user
     * 
     * @param Addressbook_Model_Contact $_contact
     */
    public function updateContactInSyncBackend($_contact);

    public function setUserAsWriteGroupMember(string $userId, bool $value = true): void;
}
