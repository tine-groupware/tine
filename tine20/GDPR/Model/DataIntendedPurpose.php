<?php
/**
 * class to hold DataIntendedPurpose data
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold DataIntendedPurpose data
 *
 * @package     GDPR
 * @subpackage  Model
 */
class GDPR_Model_DataIntendedPurpose extends Tinebase_Record_NewAbstract
{
    public const TABLE_NAME = 'gdpr_dataintendedpurposes';
    public const MODEL_NAME_PART = 'DataIntendedPurpose';
    public const FLD_NAME = 'name';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_IS_SELF_REGISTRATION = 'is_self_registration';
    public const FLD_IS_SELF_SERVICE = 'is_self_service';
    public const FLD_URL = 'url';
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 2,
        self::MODLOG_ACTIVE => true,
        
        self::APP_NAME => GDPR_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        
        self::RECORD_NAME => 'Data intended purpose',
        self::RECORDS_NAME => 'Data intended purposes', // ngettext('Data intended purpose', 'Data intended purposes', n)
        self::TITLE_PROPERTY => self::FLD_NAME,
        
        self::HAS_RELATIONS => false,
        self::HAS_CUSTOM_FIELDS => false,
        self::HAS_NOTES => false,
        self::HAS_TAGS => false,
        self::HAS_ATTACHMENTS => false,
        
        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,
        
        self::SINGULAR_CONTAINER_MODE => false,
        self::HAS_PERSONAL_CONTAINER => false,

        'copyEditAction' => true,
        'multipleEdit' => false,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [],
        ],

        self::LANGUAGES_AVAILABLE => [
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => GDPR_Config::LANGUAGES_AVAILABLE,
            self::CONFIG => [
                self::APP_NAME => GDPR_Config::APP_NAME,
            ],
        ],
        
        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE => self::TYPE_LOCALIZED_STRING,
                self::CONFIG => [
                    self::TYPE => self::TYPE_STRING,
                    self::LENGTH => 255,
                ],
                self::QUERY_FILTER => true,
                self::LABEL => 'Data intended purpose', // _('Data intended purpose')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ]
            ],
            self::FLD_DESCRIPTION => [
                self::TYPE => self::TYPE_LOCALIZED_STRING,
                self::CONFIG => [
                    self::TYPE => self::TYPE_FULLTEXT,
                ],
                self::QUERY_FILTER => true,
                self::LABEL => 'Description', // _('Description')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_IS_SELF_REGISTRATION => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::NULLABLE => true,
                self::LABEL => 'Hide from self registration', // _('Hide from self registration')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false
                ],
            ],
            self::FLD_IS_SELF_SERVICE => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::NULLABLE => true,
                self::LABEL => 'Hide from self service', // _('Hide from self service')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false
                ],
            ],
            self::FLD_URL => [
                self::LABEL                     => 'Registration link', // _('Registration link')
                self::TYPE                      => self::TYPE_VIRTUAL,
                self::SPECIAL_TYPE              => self::SPECIAL_TYPE_URL,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false
                ],
                self::UI_CONFIG     => [
                    self::READ_ONLY             => true,
                    'plugins'                   => [
                        'ux.fieldclipboardplugin'
                    ],
                ]
            ],
        ]
    ];

    public function isReplicable()
    {
        return true;
    }
}
