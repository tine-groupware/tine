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
 * Rate Limit Configuration Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_RateLimit extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'Rate_Limit';

    const FLD_METHOD = 'method';
    const FLD_MAX_REQUESTS = 'maxrequests';
    const FLD_PERIOD = 'period';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_METHOD                      => [
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
            ],
            self::FLD_MAX_REQUESTS             => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::LABEL                         => 'Max Requests', //_('Max Requests')
            ],
            self::FLD_PERIOD            => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::LABEL                         => 'Period', // _('Period')
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