<?php declare(strict_types=1);

/**
 * class to save expected answer time
 *
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to save expected answer time
 *
 * @package     Felamimail
 * @subpackage  Model
 */
class Felamimail_Model_MessageExpectedAnswer extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'MessageExpectedAnswer';
    const TABLE_NAME = 'felamimail_messageexpectedanswer';

    const FLD_ACCOUNT_ID = 'account_id';
    const FLD_MESSAGE_ID = 'message_id';
    const FLD_USER_ID = 'user_id';
    const FLD_SUBJECT = 'subject';
    const FLD_EXPECTED_ANSWER = 'expected_answer'; // time limit for an answer

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::HAS_ATTACHMENTS               => true,
        self::HAS_PERSONAL_CONTAINER        => false,

        self::APP_NAME                      => Felamimail_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::TABLE                         => [
            self::NAME                          => self::TABLE_NAME,
        ],

        self::FIELDS                        => [
            self::FLD_ACCOUNT_ID                    => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_MESSAGE_ID                    => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_USER_ID                    => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_SUBJECT                   => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_EXPECTED_ANSWER               => [
                self::TYPE                          => self::TYPE_DATETIME,
                self::NULLABLE                      => true,
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

