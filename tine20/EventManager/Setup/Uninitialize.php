<?php
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     EventManager
 * @subpackage  Setup
 */
class EventManager_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializeDeleteEventContacts()
    {
        $container_id = EventManager_Config::getInstance()
            ->get(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER);
        if ($container_id) {
            Tinebase_Container::getInstance()->deleteContainer($container_id);
        }
    }
}
