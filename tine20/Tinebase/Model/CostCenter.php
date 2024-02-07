<?php
/**
 * class to hold CostCenter data
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold CostCenter data
 *
 * @package     Tinebase
 */
class Tinebase_Model_CostCenter extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'CostCenter';

    public const TABLE_NAME = 'cost_centers';

    public const FLD_NAME = 'name';
    public const FLD_NUMBER = 'number';
    public const FLD_DESCRIPTION = 'description';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Cost Center', // gettext('GENDER_Cost Center')
        self::RECORDS_NAME              => 'Cost Centers', // ngettext('Cost Center', 'Cost Centers', n)
        self::HAS_RELATIONS             => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::HAS_ATTACHMENTS           => false,
        self::CREATE_MODULE             => false,
        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_NUMBER],
        self::TITLE_PROPERTY            => '{{ ' . self::FLD_NUMBER . ' }} - {{ ' . self::FLD_NAME . ' }}',

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_NUMBER                => [
                    self::COLUMNS                   => [self::FLD_NUMBER],
                ],
            ],
        ],
        
        self::FIELDS                    => [
            self::FLD_NUMBER                => [
                self::LABEL                     => 'Number', //_('Number')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 64,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_NAME                => [
                self::LABEL                     => 'Name', // _('Name')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::QUERY_FILTER              => true,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_DESCRIPTION           => [
                self::LABEL                     => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * returns the title of the record
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->number . ' - ' . $this->name;
    }
}
