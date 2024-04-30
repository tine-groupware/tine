<?php
/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de>
 * @copyright    Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 */
class EventManager_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    protected $_applicationName = EventManager_Config::APP_NAME;

}
