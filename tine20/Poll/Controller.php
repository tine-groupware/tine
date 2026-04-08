<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * main controller for Poll
 *
 * @package     Poll
 * @subpackage  Controller
 */
class Poll_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the instance of the singleton
     *
     * @var Poll_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct()
    {
        $this->_applicationName = 'Poll';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Poll_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Poll_Controller;
        }

        return self::$_instance;
    }
}
