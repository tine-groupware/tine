<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * event class for deleted accounts
 *
 * @package     Tinebase
 */
class Tinebase_Event_User_DeleteAccount extends Tinebase_Event_Abstract
{
    /**
     * the account to be deleted
     *
     * @var Tinebase_Model_FullUser
     */
    public $account;

    /**
     * additional text
     *
     * @var string $additionalText
     */
    public string $additionalText;

    /**
     * delete personal containers
     *
     * @var boolean
     */
    protected $_deletePersonalContainers = false;

    /**
     * delete personal folders
     *
     * @var boolean
     */
    protected $_deletePersonalFolders = false;

    /**
     * delete email accounts
     *
     * @var boolean
     */
    protected $_deleteEmailAccounts = false;

    /**
     * keep account as contact in the addressbook (which addressbook?)
     *
     * @var boolean
     */
    protected $_keepAsContact = false;

    /**
     * keep accounts organizer events as external events in the calendar
     *
     * @var boolean
     */
    protected $_keepOrganizerEvents = false;

    /**
     * keep accounts calender event attendee as external attendee
     *
     * @var boolean
     */
    protected $_keepAttendeeEvents = false;

    public function deletePersonalContainers()
    {
        return $this->_deletePersonalContainers;
    }

    public function deletePersonalFolders()
    {
        return $this->_deletePersonalFolders;
    }

    public function deleteEmailAccounts()
    {
        return $this->_deleteEmailAccounts;
    }

    public function keepAsContact()
    {
        return $this->_keepAsContact;
    }

    public function keepOrganizerEvents()
    {
        return $this->_keepOrganizerEvents;
    }

    public function keepAttendeeEvents()
    {
        return $this->_keepAttendeeEvents;
    }
}
