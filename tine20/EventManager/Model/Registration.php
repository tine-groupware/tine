<?php
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Registration extends Tinebase_Record_NewAbstract
{
    const FLD_EVENT_ID = 'eventId';
    const FLD_NAME = 'name';
    const FLD_FUNCTION = 'function';
    const FLD_SOURCE = 'source';
    const FLD_STATUS = 'status';
    const FLD_OPTIONS = 'options';
    const FLD_MEMBER_STATUS = 'memberStatus';
    const FLD_DESCRIPTION = 'description';

    const MODEL_NAME_PART = 'Registration';
    const TABLE_NAME = 'eventmanager_registration';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Registration',
        self::RECORDS_NAME              => 'Registrations', // ngettext('Registration', 'Registrations', n)
        self::DEFAULT_SORT_INFO         =>  ['field' => 'name'],
        self::TITLE_PROPERTY            => '{{name.n_fn}}',
        self::IS_METADATA_MODEL_FOR     => self::FLD_NAME,
        self::IS_DEPENDENT              => true,
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => false,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => EventManager_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [

            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_NAME      => [],
                self::FLD_OPTIONS   => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'option'      => [],
                        'record'   => []
                    ],
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_EVENT_ID         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::DISABLED              => true,
                self::CONFIG                => [
                    self::APP_NAME              => EventManager_Config::APP_NAME,
                    self::MODEL_NAME            => EventManager_Model_Event::MODEL_NAME_PART,
                ]
            ],
            self::FLD_NAME => [
                self::TYPE => self::TYPE_RECORD,
                self::LENGTH => 40,
                self::QUERY_FILTER => true,
                self::LABEL => 'Name', // _('Name')
                self::CONFIG            => [
                    self::APP_NAME => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_FUNCTION => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Function', // _('Function')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::REGISTRATION_FUNCTION,
                self::NULLABLE => true,
            ],
            self::FLD_SOURCE => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Source', // _('Source')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::REGISTRATION_SOURCE,
                self::NULLABLE => true,
            ],
            self::FLD_STATUS => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Status', // _('Status')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::REGISTRATION_STATUS,
                self::NULLABLE => true,
            ],
            self::FLD_MEMBER_STATUS => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Member Status', // _('Member Status')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::REGISTRATION_MEMBER_STATUS,
                self::NULLABLE => true,
            ],
            self::FLD_OPTIONS => [
                self::TYPE => self::TYPE_RECORDS,
                self::LABEL => 'Options', // _('Options')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                     => [
                    self::COLUMNS                     => [EventManager_Model_BookedOption::FLD_OPTION],
                ],
                self::CONFIG => [
                    self::APP_NAME                  => EventManager_Config::APP_NAME,
                    self::MODEL_NAME                => EventManager_Model_BookedOption::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => EventManager_Model_BookedOption::FLD_RECORD,
                    self::DEPENDENT_RECORDS         => true,
                ]
            ],
            self::FLD_DESCRIPTION        => [
                self::LABEL             => 'Description', //_('Description')
                self::SHY => true,
                self::TYPE          => self::TYPE_TEXT,
                self::LENGTH        => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                self::NULLABLE      => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS     => [Zend_Filter_Empty::class => null],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
