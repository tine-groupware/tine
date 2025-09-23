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
class EventManager_Model_OptionsRule extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OptionsRule';
    public const FLD_REF_OPTION_FIELD = 'ref_option_field';
    public const FLD_CRITERIA = 'criteria';
    public const FLD_VALUE = 'value';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::RECORD_NAME               => 'Option rule',
        self::RECORDS_NAME              => 'Option rules', // ngettext('Option rule', 'Option rules', n)
        self::TITLE_PROPERTY            => self::FLD_CRITERIA,
        self::MODLOG_ACTIVE             => false,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => EventManager_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_REF_OPTION_FIELD => [],
            ],
        ],

        self::FIELDS => [
            self::FLD_REF_OPTION_FIELD      => [
                self::LABEL                     => 'Event Option', // _('Event Option')
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::CONFIG                    => [
                    self::APP_NAME                  => EventManager_Config::APP_NAME,
                    self::MODEL_NAME                => EventManager_Model_Option::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::QUERY_FILTER              => true,
                self::NAME                      => EventManager_Model_Option::FLD_NAME_OPTION,
                self::NULLABLE                  => true,
            ],
            self::FLD_CRITERIA              => [
                self::LABEL                     => 'Criteria', // _('Criteria')
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::DEFAULT_VAL               => 1,
                self::NAME                      => EventManager_Config::CRITERIA_TYPE,
                self::NULLABLE                  => true,
            ],
            self::FLD_VALUE                 => [
                self::LABEL                     => 'Value', // _('Value')
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
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
