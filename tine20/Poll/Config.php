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
 * Poll config class
 *
 * @package     Poll
 * @subpackage  Config
 *
 */
class Poll_Config extends Tinebase_Config_Abstract
{
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        // TODO replace ExampleApp with Poll if this is to be used
	/*
        self::STATUS => array(
            //_('Status Available')
            self::LABEL              => 'Status',
            //_('Possible status. Please note that additional status might impact other ExampleApplication systems on export or syncronisation.')
            self::DESCRIPTION        => 'Possible status. Please note that additional status might impact other ExampleApplication systems on export or syncronisation.',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => array('recordModel' => ExampleApplication_Model_Status::class),
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => array(
                'records' => array(
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/icon-set/icon_ok.svg',     'system' => true), //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/icon-set/icon_stop.svg',   'system' => true), //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/icon-set/icon_reload.svg', 'system' => true), //_('In process')
                ),
                self::DEFAULT_STR => 'IN-PROCESS'
            )
         ),
	 */
    );

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Poll';



    const STATUS = 'status';
    /**
     * holds the instance of the singleton
     *
     * @var Poll_Config
     *
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * Returns instance of Poll_Config
     *
     * @return Poll_Config
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
