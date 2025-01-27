<?php declare(strict_types=1);

/**
 * Abstract controller for localized properties
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Abstract controller for localized properties
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
abstract class Tinebase_Controller_Record_PropertyLocalization extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instances of the singleton
     *
     * @var array
     */
    private static array $_instances = [];

    /**
     * the singleton pattern
     *
     * @return self
     */
    public static function getInstance()
    {
        if (! isset(self::$_instances[static::class])) {
            /* @phpstan-ignore-next-line */
            self::$_instances[static::class] = new static();
        }

        return self::$_instances[static::class];
    }

    public static function destroyInstance()
    {
        unset(self::$_instances[static::class]);
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    protected function __clone() {}

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        if (!preg_match('/^(.*)_Controller_(.*Localization)$/', static::class, $m)) {
            throw new Tinebase_Exception_Record_DefinitionFailure('class name ' . static::class
                . ' doesn\'t fit convention');
        }
        $this->_applicationName = $m[1];
        $this->_modelName = $m[1] . '_Model_' . $m[2];
        /** @var Tinebase_Record_Interface $model */
        $model = $this->_modelName;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => $model,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => $model::getConfiguration()->getTableName(),
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
