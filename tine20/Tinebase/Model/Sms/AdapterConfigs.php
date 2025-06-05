<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SMS Adapter Configurations Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_Sms_AdapterConfigs extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Sms_AdapterConfigs';

    public const FLD_ADAPTER_CONFIGS = 'adapter_configs';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_ADAPTER_CONFIGS           => [
                self::TYPE                          => self::TYPE_RECORDS,
                self::LABEL                         => 'SMS Adapter Configs', // _('SMS Adapter Configs')
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_Sms_AdapterConfig::MODEL_NAME_PART,
                    self::STORAGE                       => self::TYPE_JSON,
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