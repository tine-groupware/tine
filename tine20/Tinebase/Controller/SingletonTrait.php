<?php declare(strict_types=1);
/**
 * Singleton Trait for Controllers
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

trait Tinebase_Controller_SingletonTrait
{
    private static ?self $_instance = null;

    public static function getInstance(): self
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function destroyInstance(): void
    {
        self::$_instance = null;
    }

    /**
     * don't use the constructor. use the singleton
     */
    protected function __construct() {}

    /**
     * don't clone. Use the singleton.
     */
    protected function __clone(): void {}
}
