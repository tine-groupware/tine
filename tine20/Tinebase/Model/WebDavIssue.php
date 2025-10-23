<?php declare(strict_types=1);
/**
 * class to hold web dav issues
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Model_WebDavIssue extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'WebDavIssue';
    public const TABLE_NAME = 'webdav_issue';

    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_EXCEPTION = 'exception';
    public const FLD_URI = 'uri';
    public const FLD_REQUEST_BODY = 'request_body';
    public const FLD_REQUEST_HEADERS = 'request_headers';
    public const FLD_REQUEST_METHOD = 'request_method';
    public const FLD_CREATION_TIME = 'creation_time';
    public const FLD_REPORTED = 'reported';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::APP_NAME => Tinebase_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_REPORTED => [
                    self::COLUMNS => [self::FLD_REPORTED]
                ]
            ],
        ],

        self::FIELDS => [
            self::FLD_REPORTED          => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
            ],
            self::FLD_ACCOUNT_ID        => [
                self::TYPE                  => self::TYPE_USER,
                self::NULLABLE              => true,
                self::LENGTH                => 40,
            ],
            self::FLD_URI               => [
                self::TYPE                  => self::TYPE_TEXT,
            ],
            self::FLD_REQUEST_METHOD    => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 40,
            ],
            self::FLD_REQUEST_HEADERS   => [
                self::TYPE                  => self::TYPE_TEXT,
                self::NULLABLE              => true,
            ],
            self::FLD_REQUEST_BODY      => [
                self::TYPE                  => self::TYPE_TEXT,
                self::NULLABLE              => true,
            ],
            self::FLD_CREATION_TIME     => [
                self::TYPE                  => self::TYPE_DATETIME,
            ],
            self::FLD_EXCEPTION          => [
                self::TYPE                  => self::TYPE_TEXT,
            ]
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
