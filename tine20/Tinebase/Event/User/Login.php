<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * event class for account login / password availability
 *
 * @package     Tinebas
 * @subpackage  Event
 */
class Tinebase_Event_User_Login extends Tinebase_Event_Abstract
{
    public Tinebase_Model_FullUser $user;
    public ?string $password = null;
}
