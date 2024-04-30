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
    const FLD_OPTION = 'option';
    const FLD_RECORD = 'record';

    const MODEL_NAME_PART = 'BookedOption';
    const TABLE_NAME = 'eventmanager_booked_option';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Booked Option',
        self::RECORDS_NAME              => 'Booked Options', // ngettext('Booked Option', 'Booked Options', n)
        self::TITLE_PROPERTY            => self::FLD_OPTION,
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
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
            self::NAME      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS   => [
                self::FLD_OPTION       => [
                    self::COLUMNS           => [self::FLD_OPTION, self::FLD_RECORD],
                ],
                self::FLD_RECORD                => [
                    self::COLUMNS           => [self::FLD_RECORD, self::FLD_OPTION],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'option_fk' => [
                    'targetEntity' => EventManager_Model_Option::class,
                    'fieldName' => self::FLD_OPTION,
                    'joinColumns' => [[
                        'name' => self::FLD_OPTION,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'option'      => [],
                'record'       => []
            ],
        ],

        self::FIELDS => [
            self::FLD_OPTION      => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => EventManager_Config::APP_NAME,
                    self::MODEL_NAME        => EventManager_Model_Option::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Event Option', // _('Event Option')
                self::QUERY_FILTER      => true,
            ],
            self::FLD_RECORD            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => EventManager_Config::APP_NAME,
                    self::MODEL_NAME        => EventManager_Model_Registration::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Registration', // _('Registration')
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
