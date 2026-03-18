<?php
/**
 * @package     GDPR
 * @subpackage  Config
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (https://www.metaways.de)
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
    const MANAGE_CONSENT_EMAIL_TEMPLATE = 'manageConsentEmailTemplate';
    const ENABLE_PUBLIC_PAGES = 'enablePublicPages';
    const DATA_PROTECTION_OFFICER = 'dataProtectionOfficer';
    const DATA_PROTECTION_AUTHORITY = 'dataProtectionAuthority';
    const HOSTING_PROVIDER = 'hostingProvider';
    const INSTALLATION_RESPONSIBLE = 'installationResponsible';
    const LOG_RETENTION_PERIOD = 'logRetentionPeriod';
    const BACKUP_RETENTION_PERIOD = 'backupRetebtuibPeriod';
    const COMMERCIAL_REGISTRY = 'commercialRegistry';
    const VAT_ID = 'vatId';


    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY => [
                                        //_('Data provenance is mandatory for ADB contacts')
            self::LABEL                 => 'Data provenance is mandatory for ADB contacts',
            //_('Specifies whether data sources for ADB contacts are mandatory, optional, or if empty values should be set to a default.')
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
            //_('Default data source for ADB contacts')
            self::LABEL                 => 'Default data source for ADB contacts',
            //_('Default data source for ADB contacts')
            self::DESCRIPTION           => 'Default data source for ADB contacts',
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

        self::MANAGE_CONSENT_EMAIL_TEMPLATE => [
            //_('Manage consent Email template')
            self::LABEL                 => 'Manage consent Email template',
            //_('Manage consent Email template')
            self::DESCRIPTION           => 'Manage consent Email template',
            self::TYPE                  => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
            self::DEFAULT_STR           => [
                'de' => '<a href="{{manageconstentlink}}">Abmelden</a>',
                'en' => '<a href="{{manageconstentlink}}">Unsubscribe</a>'
            ],
        ],
        self::LANGUAGES_AVAILABLE => [
            self::LABEL                 => 'Available Languages', //_('Available Languages')
            self::DESCRIPTION           => 'List of the languages in which multilingual texts are available.', //_('List of the languages in which multilingual texts are available.')
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
            self::LABEL                 => 'Subscription container ID', //_('Subscription container ID')
            self::DESCRIPTION           => 'Subscription container ID in Addressbook.', //_('Subscription container ID in Addressbook.')
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
        ],
        self::JWT_SECRET => [
            //_('GDPR registration secret string')
            self::LABEL                 => 'GDPR registration secret string',
            //_('The GDPR JWT secret string corresponding to the GDPR JWT_SECRET environment variable.')
            self::DESCRIPTION           => 'The GDPR JWT secret string',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => true,
            self::RANDOMIZEIFEMPTY      => true,
        ],
        self::TEMPLATE_PATH => [
            //_('GDPR template path')
            self::LABEL                 => 'GDPR template path',
            //_('GDPR template path')
            self::DESCRIPTION           => 'GDPR template path',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
        ],
        self::ENABLE_PUBLIC_PAGES => [
            //_('Enable public pages')
            self::LABEL                 => 'Enable public pages',
            //_('Enable public pages like terms and conditions, imprint, representative information.')
            self::DESCRIPTION           => 'Enable public pages like terms and conditions, imprint, representative information.',
            self::TYPE                  => self::TYPE_BOOL,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => false,
        ],
        self::DATA_PROTECTION_OFFICER => [
            self::LABEL                 => 'Data protection officer', //_('Data protection officer')
            self::DESCRIPTION           => 'Contact of the responsible data protection officer.', //_('Contact of the responsible data protection officer.')
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => Addressbook_Config::APP_NAME,
                self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_NAME_PART,
            ],
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE => true,
        ],
        self::DATA_PROTECTION_AUTHORITY => [
            self::LABEL                 => 'Data protection authority', //_('Data protection authority')
            self::DESCRIPTION           => 'Data protection authority.', //_('Data protection authority.')
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => Addressbook_Config::APP_NAME,
                self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_NAME_PART,
            ],
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE => true,
        ],
        self::HOSTING_PROVIDER => [
            self::LABEL                 => 'Hosting provider', //_('Hosting provider')
            self::DESCRIPTION           => 'Contact of the hosting provider (if installation is not hosted in-house)', //_('Contact of the hosting provider (if installation is not hosted in-house)')
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => Addressbook_Config::APP_NAME,
                self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_NAME_PART,
            ],
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE      => true,
        ],
        self::INSTALLATION_RESPONSIBLE => [
            self::LABEL                 => 'Legally responsible', //_('Legally responsible')
            self::DESCRIPTION           => 'Legal person responsible for this installation', //_('Legal person responsible for this installation')
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => Addressbook_Config::APP_NAME,
                self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_NAME_PART,
            ],
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE      => true,
        ],
        self::LOG_RETENTION_PERIOD => [
            self::LABEL                 => 'Retention period for log files', //_('Retention period for log files')
            self::DESCRIPTION           => 'Retention period for log files in hours.', //_('Retention period for log files in hours.')
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 24,
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE      => true,
        ],
        self::BACKUP_RETENTION_PERIOD => [
            self::LABEL                 => 'Backup retention period', //_('Backup retention period')
            self::DESCRIPTION           => 'Backup retention period in days.', //_('Backup retention period in days.')
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 21,
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE      => true,
        ],
        self::COMMERCIAL_REGISTRY  => [
            self::LABEL                 => 'Commercial registry', //_('Commercial registry')
            self::DESCRIPTION           => 'Competent commercial register and commercial register number', //_('Competent commercial register and commercial register number')
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE      => true,
        ],
        self::VAT_ID => [
            self::LABEL                 => 'Vat Id', //_('Vat Id')
            self::DESCRIPTION           => 'Vat Id', //_('Vat Id')
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
            self::SETBYADMINMODULE      => true,
            self::EXPOSETOTEMPLATE      => true,
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
