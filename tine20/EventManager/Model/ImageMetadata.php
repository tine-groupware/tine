<?php

declare(strict_types=1);

/**
 * tine Groupware
 *
 * @package     EventManager
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Tonia Wulff <t.wulff@metaways.de>
 */

/**
 * Image Model
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_ImageMetadata extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'ImageMetadata';
    public const TABLE_NAME = 'eventmanager_imagemetadata';
    public const FLD_NODE_ID = 'node_id';
    public const FLD_CONSENT = 'consent';
    public const FLD_SOURCE = 'source';
    public const FLD_SORT = 'sort';
    public const FLD_IMAGE_VFS = 'image_vfs';
    public const FLD_EVENT = 'event';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,

        self::APP_NAME                      => EventManager_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Image Metadata', // gettext('Image Metadata')
        self::RECORDS_NAME => 'Images Metadata', // ngettext('Image Metadata', 'Images Metadata', n)

        self::TITLE_PROPERTY                => self::FLD_SOURCE,

        self::EXPOSE_JSON_API               => true,

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES       => [
                self::FLD_EVENT             => [
                    self::COLUMNS               => [self::FLD_EVENT],
                ],
            ],
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_EVENT => [
                    self::TARGET_ENTITY         => EventManager_Model_Event::class,
                    self::FIELD_NAME            => self::FLD_EVENT,
                    self::JOIN_COLUMNS          => [[
                        self::NAME                  => self::FLD_EVENT,
                        self::REFERENCED_COLUMN_NAME => 'id',
                        self::ON_DELETE             => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_NODE_ID                   => [
                self::LENGTH                        => 40,
                self::TYPE                          => self::TYPE_STRING,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_CONSENT                   => [
                self::LABEL                         => 'I have checked the rights to this image and hereby confirm that the image may be published on the Internet', // _('I have checked the rights to this image and hereby confirm that the image may be published on the Internet')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::DESCRIPTION                   => 'If you do not confirm that you have the right to publish the image, it will not be published on the Website', // _('If you do not confirm that you have the right to publish the image, it will not be published on the Website')
            ],
            self::FLD_SOURCE                    => [
                self::LABEL                         => 'Source', // _('Source')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 60,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::DESCRIPTION                   => 'This is the source of the image, it will be the text of the watermark for the image', //_('This is the source of the image, it will be the text of the watermark for the image')
            ],
            self::FLD_SORT                      => [
                self::LABEL                         => 'Sort', // _('Sort')
                self::TYPE                          => self::TYPE_INTEGER,
                self::NULLABLE                      => true,
            ],
            self::FLD_EVENT => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 255,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::ALLOW_EMPTY,
                ],
                self::LABEL             => 'Task', // _('Task')
                self::QUERY_FILTER      => true,
                self::CONFIG            => [
                    self::APP_NAME              => EventManager_Config::APP_NAME,
                    self::MODEL_NAME            => EventManager_Model_Event::MODEL_NAME_PART,
                    self::FOREIGN_FIELD         => EventManager_Model_Event::FLD_IMAGES,
                ],
                self::NULLABLE                      => true,
                self::DISABLED                      => true,
            ],
            self::FLD_IMAGE_VFS                 => [
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS                 => [Zend_Filter_Empty::class => null],
                // is saved in vfs, only image files allowed
                self::TYPE                          => 'image', //self::TYPE_STRING,
                self::DISABLED                      => true,
                self::NULLABLE                      => true,
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
