<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Calendar_Model_Resource extends Tinebase_Record_NewAbstract
{
    public const TABLE_NAME = 'cal_resources';
    public const MODEL_NAME_PART = 'Resource';

    public const FLD_NAME = 'name';
    public const FLD_HIERACHY = 'hierarchy';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_MAX_NUMBER_OF_PEOPLE = 'max_number_of_people';
    public const FLD_EMAIL = 'email';
    public const FLD_TYPE = 'type';
    public const FLD_STATUS = 'status';
    public const FLD_STATUS_WITH_GRANT = 'status_with_grant';
    public const FLD_BUSY_TYPE = 'busy_type';
    public const FLD_SUPRESS_NOTIFICATION = 'suppress_notification';
    public const FLD_COLOR = 'color';
    public const FLD_SITE = 'site';
    public const FLD_LOCATION = 'location';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 10,
        self::MODLOG_ACTIVE             => true,
        self::EXTENDS_CONTAINER         => self::FLD_CONTAINER_ID,
        self::HAS_RELATIONS             => true,
        self::HAS_ATTACHMENTS           => true,
        self::HAS_TAGS                  => true,
        self::HAS_NOTES                 => true,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,

        self::APP_NAME                  => Calendar_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'Calendar User', // gettext('GENDER_Calendar User')
        self::RECORDS_NAME              => 'Calendar Users', // ngettext('Calendar User', 'Calendar Users', n)

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_NAME                  => [
                    self::COLUMNS                   => [self::FLD_NAME],
                ],
                self::FLD_EMAIL                 => [
                    self::COLUMNS                   => [self::FLD_EMAIL],
                ],
                self::FLD_STATUS                => [
                    self::COLUMNS                   => [self::FLD_STATUS],
                ],
                self::FLD_SUPRESS_NOTIFICATION  => [
                    self::COLUMNS                   => [self::FLD_SUPRESS_NOTIFICATION],
                ],
            ],
        ],
        self::ASSOCIATIONS                  => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_CONTAINER_ID       => [
                    self::TARGET_ENTITY             => Tinebase_Model_Container::class,
                    self::FIELD_NAME                => self::FLD_CONTAINER_ID,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_CONTAINER_ID,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                    ]],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
            ],
            self::FLD_HIERACHY              => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 65535,
                self::NULLABLE                  => true,
            ],
            self::FLD_DESCRIPTION           => [
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
            ],
            self::FLD_MAX_NUMBER_OF_PEOPLE  => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::UNSIGNED                  => true,
                self::NULLABLE                  => true,
            ],
            self::FLD_EMAIL                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,

            ],
            self::FLD_TYPE                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 32,
                self::DEFAULT_VAL               => 'RESOURCE',
            ],
            self::FLD_STATUS                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 32,
                self::DEFAULT_VAL               => 'NEEDS-ACTION',
            ],
            self::FLD_STATUS_WITH_GRANT     => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 32,
                self::DEFAULT_VAL               => 'NEEDS-ACTION',
            ],
            self::FLD_BUSY_TYPE             => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 32,
                self::DEFAULT_VAL               => 'BUSY',
            ],
            self::FLD_SUPRESS_NOTIFICATION  => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::NULLABLE                  => true,
                self::DEFAULT_VAL               => 0,
            ],
            self::FLD_COLOR                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 7,
                self::NULLABLE                  => true,
            ],
            self::FLD_LOCATION              => [
                self::TYPE                      => self::TYPE_VIRTUAL,
            ],
            self::FLD_SITE                  => [
                self::TYPE                      => self::TYPE_VIRTUAL,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'SITE', 'degree' => 'child', 'text' => 'Site', 'max' => '0:0'), // _('Site'),
            array('type' => 'LOCATION', 'degree' => 'child', 'text' => 'Location', 'max' => '0:0'), // _('Location')
        )),
    );
}
