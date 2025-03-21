<?php
/**
 * @package     GDPR
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * GDPR config class
 * 
 * @package     GDPR
 * @subpackage  Config
 */
class GDPR_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'GDPR';

    /**
     * Is data provenance for ADB contacts mandatory
     * 
     * @var string
     */
    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY = 'dataProvenanceADBContactMandatory';

    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES     = 'yes';
    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY_NO      = 'no';
    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT = 'default';

    /**
     * The default data provenance for ADB contacts
     *
     * @var string
     */
    const DEFAULT_ADB_CONTACT_DATA_PROVENANCE = 'defaultADBContactDataProvenance';

    const LANGUAGES_AVAILABLE = 'languagesAvailable';
    const SUBSCRIPTION_CONTAINER_ID = 'subscriptionContainerId';
    const JWT_SECRET = 'jwtSecret';
    const TEMPLATE_PATH = 'templatePath';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY => [
                                        //_('Data provenance for ADB contacts mandatory')
            self::LABEL                 => 'Data provenance for ADB contacts mandatory',
            //_('Whether the data provenance for ADB contacts is mandatory, not mandatory or empty provenances will be set to a default')
            self::DESCRIPTION           => 'Sets whether the data provenance for ADB contacts is mandatory or not.',
            self::TYPE                  => self::TYPE_KEYFIELD,
            self::OPTIONS               => [
                'records'                   => [
                    ['id' => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES,       'value' => 'yes'], //_('yes')
                    ['id' => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_NO,        'value' => 'no'], //_('no')
                    ['id' => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT,   'value' => 'default'], //_('default')
                ],
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT,
        ],
        self::DEFAULT_ADB_CONTACT_DATA_PROVENANCE => [
            //_('Default data provenance for ADB contacts')
            self::LABEL                 => 'Default data provenance for ADB contacts',
            //_('Default data provenance for ADB contacts')
            self::DESCRIPTION           => 'Default data provenance for ADB contacts',
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => GDPR_Config::APP_NAME,
                self::MODEL_NAME            => GDPR_Model_DataProvenance::MODEL_NAME_PART,
            ],
            // @TODO: remove TYPE_RECORD_CONTROLLER and derive it from modelName!
            self::TYPE_RECORD_CONTROLLER=> GDPR_Controller_DataProvenance::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => '',
        ],
        self::LANGUAGES_AVAILABLE => [
            self::LABEL                 => 'Languages Available', //_('Languages Available')
            self::DESCRIPTION           => 'List of the language in which the multilingual texts are laid out.', //_('List of the language in which the multilingual texts are laid out.')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            'localeTranslationList'     => 'Language',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 'de', 'value' => 'German'],
                    ['id' => 'en', 'value' => 'English'],
                ],
                self::DEFAULT_STR           => 'en',
            ],
        ],
        self::SUBSCRIPTION_CONTAINER_ID => [
            self::LABEL                 => 'Subscription container id', //_('Subscription container id')
            self::DESCRIPTION           => 'Subscription container id in Addressbook.', //_('Subscription container id in Addressbook.')
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
        ],
        self::JWT_SECRET => [
            //_('GDPR registration secret string')
            self::LABEL                 => 'GDPR registration secret string',
            //_('GDPR jwt secret string corresponding to GDPR JWT_SECRET environment variable')
            self::DESCRIPTION           => 'GDPR jwt secret string corresponding to GDPR JWT_SECRET environment variable',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
        ],
        self::TEMPLATE_PATH => [
            //_('GDPR template path')
            self::LABEL                 => 'GDPR template path',
            //_('GDPR template path')
            self::DESCRIPTION           => 'GDPR template path',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
        ],
    ];
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
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
