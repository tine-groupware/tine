<?php
/**
 * class to hold Matrix Room data
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Room data
 * 
 * @package     MatrixSynapseIntegrator
 * @subpackage  Model
 */
class MatrixSynapseIntegrator_Model_Room extends Tinebase_Record_NewAbstract
{
    public const FLD_LIST_ID = 'list_id';

    public const FLD_NAME = 'name';
    public const FLD_ROOM_ID = 'room_id';
    public const FLD_SYSTEM_USER_ONLY = 'system_user_only';
    public const FLD_TOPIC = 'topic';

    public const MODEL_NAME_PART = 'Room';
    public const TABLE_NAME = 'matrix_room';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => MatrixSynapseIntegrator_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'Matrix Room', // _('Matrix Room') ngettext('Matrix Room', 'Matrix Rooms', n)
        self::RECORDS_NAME              => 'Matrix Rooms', // _('Matrix Rooms')
        self::TITLE_PROPERTY            => self::FLD_NAME,

        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,

        self::CREATE_MODULE             => false, // true? maybe in CoreData, Admin?
        self::EXPOSE_HTTP_API           => false,
        self::EXPOSE_JSON_API           => false, // atm it is only updated via adb saveList

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_NAME           => [
                    self::COLUMNS                   => [self::FLD_NAME],
                ],
                self::FLD_TOPIC           => [
                    self::COLUMNS                   => [self::FLD_TOPIC],
                    self::FLAGS                     => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::EXPORT                    => [
            self::SUPPORTED_FORMATS         => ['csv'],
        ],

        self::FIELDS                    => [
            self::FLD_TOPIC           => [
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Topic', // _('Topic')
                self::QUERY_FILTER              => true,
            ],
           self::FLD_NAME => [
               self::TYPE                      => self::TYPE_STRING,
               self::NULLABLE                  => false,
               self::LENGTH                    => 255,
               self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false],
               self::LABEL                     => 'Name', // _('Name')
            ],
            // we get the room id from synapse -> save it here
            self::FLD_ROOM_ID => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Room ID', // _('Room ID')
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SYSTEM_USER_ONLY => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::NULLABLE                  => false,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => false],
                self::LABEL                     => 'Only System Users', // _('Only System Users')
                self::DEFAULT_VAL               => true,
                // TODO make this changeable
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_LIST_ID => [
                self::TYPE                      => self::TYPE_RECORD,
                self::NULLABLE                  => false,
                self::CONFIG                        => [
                    self::APP_NAME                      => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME                    => Addressbook_Model_List::MODEL_NAME_PART,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                self::LABEL                     => 'Group', // _('Group')
            ],
        ]
    ];
}
