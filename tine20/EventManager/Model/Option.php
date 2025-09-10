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
 * Model for metadata of files
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Option extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Option';
    public const TABLE_NAME = 'eventmanager_option';
    public const FLD_EVENT_ID = 'event_id';
    public const FLD_NAME_OPTION = 'name_option';
    public const FLD_OPTION_CONFIG = 'option_config';
    public const FLD_OPTION_CONFIG_CLASS = 'option_config_class';
    public const FLD_DISPLAY = 'display';
    public const FLD_OPTION_REQUIRED = 'option_required';
    public const FLD_GROUP = 'group';
    public const FLD_LEVEL = 'level';
    public const FLD_SORTING = 'sorting';
    public const FLD_OPTION_RULE = 'option_rule';
    public const FLD_RULE_TYPE = 'rule_type';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Option',
        self::RECORDS_NAME              => 'Options', // ngettext('Option', 'Options', n)
        self::TITLE_PROPERTY            => self::FLD_NAME_OPTION,
        self::IS_DEPENDENT              => true,
        self::HAS_RELATIONS             => true,
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
            self::NAME                  => self::TABLE_NAME,
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_OPTION_RULE => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        EventManager_Model_OptionsRule::FLD_REF_OPTION_FIELD => [],
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
                ],
                self::ALLOW_CAMEL_CASE      => true,
                self::NULLABLE              => true,
            ],
            self::FLD_NAME_OPTION       => [
                self::LABEL                 => 'Name', // _('Name')
                self::TYPE                  => self::TYPE_STRING,
                self::NULLABLE              => true,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_OPTION_CONFIG_CLASS   => [
                self::LABEL                     => 'Option Config Class', // _('Option Config Class')
                self::TYPE                      => self::TYPE_MODEL,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS      => [
                        EventManager_Model_TextOption::class,
                        EventManager_Model_CheckboxOption::class,
                        EventManager_Model_FileOption::class,
                        EventManager_Model_TextInputOption::class,
                    ],
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::ALLOW_CAMEL_CASE          => true,
                self::NULLABLE                  => true,
            ],
            self::FLD_OPTION_CONFIG           => [
                self::LABEL                     => 'Option Config', // _('Option Config')
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_OPTION_CONFIG_CLASS,
                    self::PERSISTENT                => true,
                ],
                self::SHY                       => true,
                self::ALLOW_CAMEL_CASE          => true,
                self::NULLABLE                  => true,
            ],
            self::FLD_DISPLAY       => [
                self::TYPE              => self::TYPE_KEY_FIELD,
                self::LABEL             => 'Display', // _('Display')
                self::DEFAULT_VAL       => 1,
                self::NAME              => EventManager_Config::DISPLAY_TYPE,
                self::NULLABLE          => true,
            ],
            self::FLD_OPTION_REQUIRED       => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::LABEL                     => 'Option required', // _('Option required')
                self::DEFAULT_VAL               => 1,
                self::NAME                      => EventManager_Config::OPTION_REQUIRED_TYPE,
                self::NULLABLE                  => true,
            ],
            self::FLD_SORTING       => [
                self::TYPE              => self::TYPE_FLOAT,
                self::LABEL             => 'Display Sorting', // _('Display Sorting')
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS     => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_LEVEL         => [
                self::TYPE              => self::TYPE_KEY_FIELD,
                self::LABEL             => 'Level', // _('Level')
                self::DEFAULT_VAL       => 1,
                self::NAME              => EventManager_Config::OPTION_LEVEL,
                self::NULLABLE          => true,
            ],
            self::FLD_GROUP         => [
                self::TYPE              => self::TYPE_STRING_AUTOCOMPLETE,
                self::LABEL             => 'Group', // _('Group')
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DESCRIPTION => 'If you want multiple options as a group 
                    so that only one option of the group can be chosen, give them the same group name.
                    Otherwise leave this field empty',
                // _('If you want multiple options as a group so that only one option of the group can be chosen, give them the same group name. Otherwise leave this field empty')
            ],
            self::FLD_OPTION_RULE       => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::LABEL                 => 'Option rule', // _('Option rule')
                self::CONFIG                => [
                    self::APP_NAME              => EventManager_Config::APP_NAME,
                    self::MODEL_NAME            => EventManager_Model_OptionsRule::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS     => true,
                    self::REF_ID_FIELD          => EventManager_Model_OptionsRule::FLD_REF_OPTION_FIELD,
                    self::STORAGE               => self::TYPE_JSON,
                ],
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE                  => true,
                self::UI_CONFIG                 => [
                    self::COLUMNS                   => [
                        EventManager_Model_OptionsRule::FLD_REF_OPTION_FIELD,
                        EventManager_Model_OptionsRule::FLD_CRITERIA,
                        EventManager_Model_OptionsRule::FLD_VALUE,
                    ],
                ],
            ],
            self::FLD_RULE_TYPE         => [
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::DEFAULT_VAL           => 1,
                self::NAME                  => EventManager_Config::RULE_TYPE,
                self::NULLABLE              => true,
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
