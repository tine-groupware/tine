<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Tinebase
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * event class for removed group member
 *
 * @package     Tinebase
 */
class Tinebase_Event_RemoveGroupMember extends Tinebase_Event_Abstract
{
    /**
     * the group id
     *
     * @var string
     */
    public $groupId;

    /**
     * the user id
     *
     * @var string
     */
    public $userId;
}
