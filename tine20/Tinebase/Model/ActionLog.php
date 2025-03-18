<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold ActionLog data
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_ActionLog extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'ActionLog';

    public const TABLE_NAME = 'actionlog';

    public const FLD_ACTION_TYPE = 'action_type';
    public const FLD_USER = 'user';
    public const FLD_DATETIME = 'datetime';
    public const FLD_DATA = 'data';

    public const TYPE_ADD_USER_CONFIRMATION = 'add_user_confirmation';
    public const TYPE_DELETION = 'deletion';
    public const TYPE_EMAIL_NOTIFICATION = 'emailNotification';
    public const TYPE_SUPPORT_REQUEST = 'supportRequest';
    public const TYPE_DATEV_EMAIL = 'datevEmail';

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
        self::VERSION           => 1,
        self::APP_NAME          => Tinebase_Config::APP_NAME,
        self::MODEL_NAME        => self::MODEL_NAME_PART,

        self::IS_DEPENDENT              => true,
        self::MODLOG_ACTIVE             => true,
        self::RECORD_NAME               => 'ActionLog',
        self::RECORDS_NAME              => 'ActionLogs', // ngettext('ActionLog', 'ActionLogs', n)

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
        ],

        self::FIELDS            => [
            self::FLD_ACTION_TYPE       => [
                self::LABEL                 => 'Action type', // _('Action type')
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NAME                      => Tinebase_Config::ACTION_LOG_TYPES,
            ],
            self::FLD_USER       => [
                self::LABEL                 => 'User', // _('User')
                self::TYPE                  => self::TYPE_USER,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::LENGTH => 40,
            ],
            self::FLD_DATETIME       => [
                self::LABEL                 => 'Datetime', // _('Datetime')
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],                
                self::NULLABLE              => true,
                self::QUERY_FILTER          => true,
            ],
            self::FLD_DATA            => [
                self::LABEL                 => 'Data', // _('Data')
                self::TYPE                  => self::TYPE_TEXT,
                self::LENGTH                => 16000,
                self::NULLABLE              => true,
            ]
        ],
    ];
}
