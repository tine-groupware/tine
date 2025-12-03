<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Admin
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * event class for newly added group member
 *
 * @package     Admin
 */
class Tinebase_Event_AddGroupMember extends Tinebase_Event_Abstract
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
