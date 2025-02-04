<?php
/**
 * @package     Saas
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Saas config class
 *
 * @package     SaasInstance
 * @subpackage  Config
 */
class SaasInstance_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    public const APP_NAME = 'SaasInstance';
    public const PRICE_PER_USER = 'pricePerUser';
    public const PRICE_PER_USER_VOLUNTEER = 'pricePerUserVolunteer';
    public const PRICE_PER_GIGABYTE = 'pricePerGigabyte';
    public const NUMBER_OF_INCLUDED_USERS = 'numberOfIncludedUsers';
    public const PACKAGE_STORAGE_INFO_TEMPLATE = 'packageStorageInfoTemplate';
    public const PACKAGE_USER_INFO_TEMPLATE = 'packageUserInfoTemplate';
    public const PACKAGE_CHANGE_USER_TYPE_INFO_TEMPLATE = 'packageChangeUserTypeInfoTemplate';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::PRICE_PER_USER                => [
            //_('Price per user')
            self::LABEL                 => 'Price per user',
            //_('Price per user of current SaasInstance package')
            self::DESCRIPTION           => 'Price per user of current SaasInstance package',
            self::TYPE                  => Tinebase_ModelConfiguration_Const::TYPE_MONEY,
            self::DEFAULT_STR           => 2.2,
        ],
        self::PRICE_PER_USER_VOLUNTEER => [
            //_('Price per volunteer user')
            self::LABEL                 => 'Price per volunteer user',
            //_('Price per volunteer user of current SaasInstance package')
            self::DESCRIPTION           => 'Price per volunteer user of current SaasInstance package',
            self::TYPE                  => Tinebase_ModelConfiguration_Const::TYPE_MONEY,
            self::DEFAULT_STR           => null,
        ],
        self::PRICE_PER_GIGABYTE                => [
            //_('Price per Gigabyte')
            self::LABEL                 => 'Price per Gigabyte',
            //_('Price per Gigabyte of current SaasInstance package')
            self::DESCRIPTION           => 'Price per Gigabyte of current SaasInstance package',
            self::TYPE                  => Tinebase_ModelConfiguration_Const::TYPE_MONEY,
            self::DEFAULT_STR           => 0.5,
        ],
        self::NUMBER_OF_INCLUDED_USERS                => [
            //_('Number of included users')
            self::LABEL                 => 'Number of included users',
            //_('Number of included users')
            self::DESCRIPTION           => 'Number of included users',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 50,
        ],
        self::PACKAGE_STORAGE_INFO_TEMPLATE                => [
            //_('Saas storage package info template')
            self::LABEL                 => 'Saas storage package info template',
            //_('The upgraded storage package info template, display when user set higher quota then current config')
            self::DESCRIPTION           => 'The upgraded storage package info template, display when user set higher quota then current config',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           =>  '<br /> Your current file system Quota limit is : {0} GB
                <br />Do you want to upgrade your subscription?<br />
                <br />Package Price : {1} Euro / {2}
                <br />Storage Price : {3} Euro / Gigabyte'
        ],
        self::PACKAGE_USER_INFO_TEMPLATE                => [
            //_('Saas user package info template')
            self::LABEL                 => 'Saas user package info template',
            //_('The upgraded user package info template, display when user set higher quota then current config')
            self::DESCRIPTION           => 'The upgraded user package info template, display when user set higher quota then current config',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           =>  '<br /> Your current user account limit is : {0} 
                <br />Do you want to upgrade your subscription?<br />
                <br />Package Price : {1} Euro / {2}'
        ],
        self::PACKAGE_CHANGE_USER_TYPE_INFO_TEMPLATE                => [
            //_('Saas change user type info template')
            self::LABEL                 => 'Saas change user type info template',
            //_('The upgraded user package info template, display when user set higher quota then current config')
            self::DESCRIPTION           => 'The change user type info template, display when user update user type in admin module',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           =>  '<br /> New user type : {0} 
                <br />Package Price : {1} Euro / {0}<br />'
        ],
    ];

    static function getProperties()
    {
        return self::$_properties;
    }

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;
}
