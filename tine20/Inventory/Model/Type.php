<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Type Record Class
 *
 * @package     Inventory
 * @subpackage  Model
 */
class Inventory_Model_Type extends Tinebase_Record_NewAbstract
{
    public const TABLE_NAME = 'inventory_type';
    public const MODEL_NAME_PART = 'Type';
    public const FLD_NAME = 'name';
    public const FLD_DESCRIPTION = 'description';

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
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,

        self::APP_NAME => Inventory_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Inventory type',
        self::RECORDS_NAME => 'Inventory types', // ngettext('Inventory type', 'Inventory types', n)
        self::TITLE_PROPERTY => self::FLD_NAME,

        self::HAS_DELETED_TIME_UNIQUE => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        
        self::HAS_RELATIONS => false,
        self::HAS_CUSTOM_FIELDS => false,
        self::HAS_NOTES => false,
        self::HAS_TAGS => false,
        self::HAS_ATTACHMENTS => false,

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CONTAINER_PROPERTY => null,

        self::CREATE_MODULE => false,

        self::SINGULAR_CONTAINER_MODE => false,
        self::HAS_PERSONAL_CONTAINER => false,

        'copyEditAction' => true,
        'multipleEdit' => false,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_DESCRIPTION => [
                    self::COLUMNS       => [self::FLD_DESCRIPTION],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL                     => 'Name', // _('Name')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_DESCRIPTION => [
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Description', // _('Description')
                self::QUERY_FILTER              => true,
            ],
        ]
    ];
}
