<?php

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model for Boilerplates
 *
 * @package     Sales
 * @subpackage  Model
 *
 * @property string $name
 * @property string $listId
 */
class Sales_Model_Boilerplate extends Tinebase_Record_NewAbstract
{
    public const FLD_BOILERPLATE = 'boilerplate';
    public const FLD_CUSTOMER = 'customer';
    public const FLD_DOCUMENT_CATEGORY = 'documentCategory';
    public const FLD_FROM = 'from';
    public const FLD_LANGUAGE = 'language';
    public const FLD_MODEL = 'model';
    public const FLD_NAME = 'name';
    public const FLD_UNTIL = 'until';

    public const MODEL_NAME_PART = 'Boilerplate';
    public const TABLE_NAME = 'sales_boilerplate';
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 2,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,

        self::APP_NAME => Sales_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => self::MODEL_NAME_PART,
        self::RECORDS_NAME => 'Boilerplates', // ngettext('Boilerplate', 'Boilerplates', n)
        self::TITLE_PROPERTY => "{{ name }}{% if locally_changed %} (individuell){% elseif customer or from or until %} ( {% if customer %}customerspecific {% endif %}{% if from %}from {{ from |date('Y/m/d') }} {% endif %}{% if until %}until {{ until |date('Y/m/d') }}{% endif %}){% endif %}", //self::FLD_NAME,

        self::HAS_RELATIONS => false,
        self::HAS_ATTACHMENTS => false,
        self::HAS_NOTES => false,
        self::HAS_TAGS => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_CUSTOMER => [
                    self::COLUMNS => [self::FLD_CUSTOMER],
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_MODEL => [
                self::TYPE => self::TYPE_MODEL,
                self::LENGTH => 255,
                self::QUERY_FILTER => true,
                self::LABEL => 'Model', // _('Model')
                self::CONFIG => [
                    'availableModelsRegExp' => '/Sales_Model_Document_(?!(Customer|Address|Boilerplate))/',
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING_AUTOCOMPLETE,
                self::LENGTH => 255,
                self::QUERY_FILTER => true,
                self::LABEL => 'Name', // _('Name')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_FROM => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'From', // _('From')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Date::class,
                    self::OPTIONS                   => [
                        Tinebase_Model_Filter_Date::BEFORE_OR_IS_NULL => true,
                    ]
                ],
            ],
            self::FLD_UNTIL => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'Until', // _('Until')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Date::class,
                    self::OPTIONS                   => [
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ],
            ],
            self::FLD_DOCUMENT_CATEGORY => [
                self::LABEL                 => 'Category', // _('Category')
                self::TYPE                  => self::TYPE_RECORD,
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Category::MODEL_NAME_PART,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::NULLABLE              => true,
            ],
            self::FLD_CUSTOMER => [
                self::TYPE => self::TYPE_RECORD,
                self::QUERY_FILTER => true,
                self::LABEL => 'Customer', // _('Customer')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Customer',
                    'idProperty'  => 'id'
                )
            ],
            self::FLD_BOILERPLATE        => [
                self::LABEL             => 'Boilerplate', //_('Boilerplate')
                self::TYPE          => self::TYPE_TEXT,
                self::LENGTH        => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_LANGUAGE          => [
                self::LABEL                 => 'Language', // _('Language')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => Sales_Config::LANGUAGES_AVAILABLE,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
