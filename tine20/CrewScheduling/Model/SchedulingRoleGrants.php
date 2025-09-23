<?php
/**
 * class to handle grants
 *
 * @package     CrewScheduling
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 */

/**
 * defines scheduling sole grants
 *
 * @package     CrewScheduling
 * @subpackage  Record
 *  */
class CrewScheduling_Model_SchedulingRoleGrants extends Tinebase_Model_Grants
{
    public const MODEL_NAME_PART    = 'SchedulingRoleGrants';


    public const MANAGE_POLL = 'managePollGrant';
    public const SEND_EMAILS = 'sendEmailsGrant';
    public const ASSIGN_ATTENDEE = 'assignAttendeeGrant';
    public const RECEIVE_NOTIFICATIONS = 'receiveNotificationsGrant';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = CrewScheduling_Config::APP_NAME;

    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return [
            self::GRANT_READ,
            self::SEND_EMAILS,
            self::MANAGE_POLL,
            self::ASSIGN_ATTENDEE,
//            self::RECEIVE_NOTIFICATIONS,
            self::GRANT_ADMIN,
        ];
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function getAllGrantsMC(): array
    {
        return [
            self::SEND_EMAILS    => [
                self::LABEL         => 'Send Emails', // _('Send Emails')
                self::DESCRIPTION   => 'The grant to send emails to role members.', // _('The grant to send emails to role members.')
            ],
            self::MANAGE_POLL    => [
                self::LABEL         => 'Manage Polls', // _('Manage Polls')
                self::DESCRIPTION   => 'The grant to manage polls and send poll mails to possible attendee and role members.', // _('The grant to create polls and send poll mails to possible attendee and role members.')
            ],
            self::ASSIGN_ATTENDEE => [
                self::LABEL         => 'Assign Attendee',  // _('Assign Attendee')
                // NOTE: CS only - no integration to calendar api's! ignores calendar acls!
                self::DESCRIPTION   => 'The grant to add attendee of this role to events in Crew Scheduling Application.',  // _('The grant to add attendee of this role to events in Crew Scheduling Application.')
            ],
//            self::RECEIVE_NOTIFICATIONS => [
//                self::LABEL         => 'Receive Notifications',  // _('Receive Notifications')
//                self::DESCRIPTION   => 'The grant to receive notifications.',  // _('The grant to receive notifications.')
//            ],
//            self::GRANT_ADMIN     => [
//                self::LABEL         => 'Manage Role',  // _('Receive Notifications')
//                self::DESCRIPTION   => 'The grant to manage the role itself.', // _('The grant to manage the role itself.')
//            ],
        ];
    }

    public function setFromArray(array &$_data)
    {
        if (isset($_data[self::GRANT_ADMIN]) && $_data[self::GRANT_ADMIN]) {
            foreach (static::getAllGrants() as $grant) {
                $_data[$grant] = true;
            }
        } else {
            if (isset($_data[self::MANAGE_POLL]) && $_data[self::MANAGE_POLL]) {
                $_data[self::SEND_EMAILS] = true;
            }
        }

        parent::setFromArray($_data);
    }

    public static function getPersonalGrants($_accountId, $_additionalGrants = array())
    {
        $result = parent::getPersonalGrants($_accountId, $_additionalGrants);
        $result->addRecord(new static([
            'account_id'     => Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
            'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            self::GRANT_ADMIN => true,
        ], true));
        $result->addRecord(new static([
            'account_id'     => '0',
            'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            self::GRANT_READ => true,
        ], true));
        return $result;
    }
}
