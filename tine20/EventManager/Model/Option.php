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
    const FLD_EVENT_ID = 'eventId';
    const FLD_NAME = 'name';
    const FLD_PRICE = 'price';
    const FLD_BOOKED_PLACES = 'bookedPlaces';
    const FLD_AVAILABLE_PLACES = 'availablePlaces';
    const FLD_DESCRIPTION = 'description';
    const FLD_LEVEL = 'level';
    const FLD_SORTING = 'sorting';
    const FLD_URL = 'url';
    const FLD_TYPE = 'type';

    const MODEL_NAME_PART = 'Option';
    const TABLE_NAME = 'eventmanager_option';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Option',
        self::RECORDS_NAME              => 'Options', // ngettext('Option', 'Options', n)
        self::TITLE_PROPERTY            => 'name',
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
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [

            ],
        ],

        self::LANGUAGES_AVAILABLE => [
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => EventManager_Config::LANGUAGES_AVAILABLE,
            self::CONFIG => [
                self::APP_NAME => EventManager_Config::APP_NAME,
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
            ],
            self::FLD_NAME => [
                self::TYPE => self::TYPE_LOCALIZED_STRING,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG => [
                    self::TYPE => self::TYPE_STRING,
                    self::LENGTH => 255,
                ],
                self::LABEL => 'Name', // _('Name')
            ],
            self::FLD_PRICE=> [
                self::TYPE => self::TYPE_MONEY,
                self::LABEL => 'Price', // _('Price')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_BOOKED_PLACES => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Booked places', // _('Booked places')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_AVAILABLE_PLACES => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Available places', // _('Available places')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_DESCRIPTION        => [
                self::LABEL             => 'Description', //_('Description')
                self::TYPE          => self::TYPE_LOCALIZED_STRING,
                self::CONFIG => [
                    self::TYPE => self::TYPE_FULLTEXT,
                ],
                self::NULLABLE      => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS     => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_SORTING => [
                self::TYPE => self::TYPE_FLOAT,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_TYPE => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Type', // _('Type')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::OPTION_TYPE,
                self::NULLABLE => true,
            ],
            self::FLD_LEVEL => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Level', // _('Level')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::OPTION_LEVEL,
                self::NULLABLE => true,
            ],
            self::FLD_URL => [
                self::TYPE => self::TYPE_LOCALIZED_STRING,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::CONFIG => [
                    self::TYPE => self::TYPE_STRING,
                    self::LENGTH => 255,
                ],
                self::LABEL => 'URL', // _('URL')
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
