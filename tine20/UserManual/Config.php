<?php
/**
 * Tine 2.0
 *
 * @package     UserManual
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * UserManual config class
 *
 * @package     UserManual
 * @subpackage  Config
 *
 */
class UserManual_Config extends Tinebase_Config_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<UserManual_Config> */
    use Tinebase_Controller_SingletonTrait;

    const APP_NAME = 'UserManual';

    const HELP_BASE_URL = 'helpBaseUrl';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::HELP_BASE_URL        => [
            //_('Help Base URL')
            self::LABEL                 => 'Help Base URL',
            //_('Base url of the user manual')
            self::DESCRIPTION           => 'Base url of the user manual',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => 'https://docs.local.tine-dev.de',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
    ];

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
