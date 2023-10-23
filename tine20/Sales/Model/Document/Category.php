<?php declare(strict_types=1);
/**
 * class to hold Document Category data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Document Category data
 *
 * @package     Sales
 */
class Sales_Model_Document_Category extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'Document_Category';
    public const TABLE_NAME = 'sales_document_category';

    public const FLD_NAME = 'name';
    public const FLD_DIVISION_ID     = 'division_id';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::APP_NAME => Sales_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::RECORD_NAME => 'Category', // gettext('GENDER_Category')
        self::RECORDS_NAME => 'Categories', // ngettext('Category', 'Categories', n)
        self::MODLOG_ACTIVE => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::EXPOSE_JSON_API => true,
        self::TITLE_PROPERTY => "{{ name }}{% if division_id.title %} ({{ division_id.title }}){% endif %}",
        self::CONTAINER_PROPERTY => null,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_DIVISION_ID => [
                    self::COLUMNS => [self::FLD_DIVISION_ID],
                ]
            ],
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_NAME => [
                    self::COLUMNS => [self::FLD_NAME, self::FLD_DIVISION_ID, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DIVISION_ID => [],
            ],
        ],

        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::QUERY_FILTER => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL => 'Name', // _('Name')
            ],
            self::FLD_DIVISION_ID => [
                self::TYPE => self::TYPE_RECORD,
                self::LABEL => 'Division', // _('Division')
                self::CONFIG => [
                    self::APP_NAME => Sales_Config::APP_NAME,
                    self::MODEL_NAME => Sales_Model_Division::MODEL_NAME_PART,
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],

        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
