<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Controller_Tree_FlySystem extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_Tree_FlySystem::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_Tree_FlySystem::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_Tree_FlySystem::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
        $this->_doContainerACLChecks = false;
    }

    public static function getFlySystem(string $id): \League\Flysystem\Filesystem
    {
        if (!isset(static::$flySystems[$id])) {
            $flyConf = static::getInstance()->get($id);
            $adapter = $flyConf->{Tinebase_Model_Tree_FlySystem::FLD_ADAPTER};

            static::$flySystems[$id] = new \League\Flysystem\Filesystem(
                new $adapter(... $flyConf->{Tinebase_Model_Tree_FlySystem::FLD_ADAPTER_CONFIG})
            );
        }
        return static::$flySystems[$id];
    }

    public static function getHashForPath(string $path, \League\Flysystem\Filesystem $flySystem): string
    {
        return sha1($flySystem->fileSize($path) . $flySystem->lastModified($path));
    }

    protected static $flySystems = [];
}