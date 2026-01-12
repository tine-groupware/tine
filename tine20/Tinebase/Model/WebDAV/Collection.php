<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Tinebase_Model_WebDAV_Collection extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'WebDAV_Collection';


    public const FLD_URI = 'uri';
    public const FLD_NAME = 'name';
    public const FLD_COLOR = 'color';
    public const FLD_TYPE = 'type';
    public const FLD_ACL = 'acl';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => false,


        self::FIELDS                    => [
            self::FLD_URI                   => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_COLOR                 => [
                self::TYPE                      => self::TYPE_HEX_COLOR,
            ],
            self::FLD_TYPE                  => [
                self::TYPE                      => self::TYPE_STRING,
            ],
            self::FLD_ACL                   => [
                self::TYPE                      => self::TYPE_INTEGER,
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
