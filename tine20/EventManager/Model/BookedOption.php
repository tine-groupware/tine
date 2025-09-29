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
class EventManager_Model_BookedOption extends Tinebase_Record_NewAbstract
{
    public const FLD_OPTION = 'option';
    public const FLD_SELECTION_CONFIG = 'selection_config';
    public const FLD_SELECTION_CONFIG_CLASS = 'selection_config_class';

    public const MODEL_NAME_PART = 'BookedOption';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                  => EventManager_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::TITLE_PROPERTY            => '{{option.option_config_class}}',

        self::RECORD_NAME               => 'Booked Option', // gettext('GENDER_Booked Option')
        self::RECORDS_NAME              => 'Booked Options', // ngettext('Booked Option', 'Booked Options', n)

        self::FIELDS => [
            self::FLD_OPTION        => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => EventManager_Config::APP_NAME,
                    self::MODEL_NAME        => EventManager_Model_Option::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL             => 'Event Option', // _('Event Option')
                self::QUERY_FILTER      => true,
                self::NULLABLE          => true,
            ],
            self::FLD_SELECTION_CONFIG_CLASS    => [
                self::TYPE                          => self::TYPE_MODEL,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        EventManager_Model_Selections_Checkbox::class,
                        EventManager_Model_Selections_File::class,
                        EventManager_Model_Selections_TextInput::class,
                    ],
                ],
                self::ALLOW_CAMEL_CASE              => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_SELECTION_CONFIG      => [
                self::LABEL                     => 'Selection Config', // _('Selection Config')
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_SELECTION_CONFIG_CLASS,
                    self::PERSISTENT                => true,
                    self::SET_DEFAULT_INSTANCE      => true,
                ],
                self::ALLOW_CAMEL_CASE          => true,
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
