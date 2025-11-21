<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Calendar_Model_SyncContainerConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'SyncContainerConfig';

    public const FLD_CLOUD_ACCOUNT_ID = 'cloud_account_id';
    public const FLD_CALENDAR_PATH = 'calendar_path';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::JSON_EXPANDER                 => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_CLOUD_ACCOUNT_ID          => [],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_CLOUD_ACCOUNT_ID          => [
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_CloudAccount::MODEL_NAME_PART,
                ],
            ],
            self::FLD_CALENDAR_PATH              => [
                self::TYPE                          => self::TYPE_STRING,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}