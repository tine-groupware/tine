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
    const AUTODETECT_VERSION_PATH = 'autodetectVersionPath';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;

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
            self::DEFAULT_STR           => 'https://docs.tine-groupware.de/',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::AUTODETECT_VERSION_PATH => [
            //_('Autodetect Version Path')
            self::LABEL                 => 'Autodetect Version Path',
            //_('Autodetect Version Path')
            self::DESCRIPTION           => 'Autodetect Version Path',
            self::TYPE                  => self::TYPE_BOOL,
            self::DEFAULT_STR           => true,
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

    /**
     * @throws Tinebase_Exception_InvalidArgument
     * @see Tinebase_Frontend_Http_SinglePageApplication::getHeaders()
     */
    public function registerCspSources(): void
    {
        $url = $this->get(self::HELP_BASE_URL);

        if (!empty($url)) {
            $parsed = parse_url(rtrim($url, '/'));
            if ($parsed) {
                $origin = $parsed['scheme'] . '://' . $parsed['host']
                    . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                Tinebase_Frontend_Http_CspRegistry::getInstance()->addSource('frame-src', $origin);
                Tinebase_Frontend_Http_CspRegistry::getInstance()->addSource('connect-src', $origin);
            }
        }
    }
}
