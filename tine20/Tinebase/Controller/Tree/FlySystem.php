<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * @method static Tinebase_Controller_Tree_FlySystem getInstance()
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
            static::$flySystems[$id] = new \League\Flysystem\Filesystem(
                $flyConf->{Tinebase_Model_Tree_FlySystem::FLD_ADAPTER_CONFIG}->getFlySystemAdapter()
            );
            static::$flyConfs[$id] = $flyConf;
        }
        static::$currentFlyConf = static::$flyConfs[$id];
        return static::$flySystems[$id];
    }

    public static function getCurrentFlyConfiguration(): ?Tinebase_Model_Tree_FlySystem
    {
        return static::$currentFlyConf;
    }

    public static function getHashForPath(string $path, \League\Flysystem\Filesystem $flySystem): string
    {
        return sha1($flySystem->fileSize($path) . $flySystem->lastModified($path));
    }

    public function get($_id, $_containerId = null, $_getRelatedData = true, $_getDeleted = false, bool $_aclProtect = true)
    {
        $flySystem = parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted, $_aclProtect);
        $flySystem->{Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT} = $this->getFlySystemMountPoint($flySystem);
        return $flySystem;
    }

    public function getFlySystemMountPoint(Tinebase_Model_Tree_FlySystem $flySystem): ?Tinebase_Model_Tree_Node
    {
        $fileObject = Tinebase_FileSystem::getInstance()->getFileObjectBackend()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_Tree_FileObject::class, [
                [TMFA::FIELD => 'flysystem', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $flySystem->getId()],
                [TMFA::FIELD => 'flypath', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => '/'],
            ]))->getFirstRecord();

        if ($fileObject) {
            /** @var ?Tinebase_Model_Tree_Node $treeNode */
            $treeNode = Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_Tree_Node::class, [
                    [TMFA::FIELD => 'object_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $fileObject->getId()],
                ]))->getFirstRecord();
            return $treeNode;
        }
        return null;
    }

    /**
     * @param Tinebase_Model_Tree_FlySystem $_createdRecord
     * @param Tinebase_Record_Interface $_record
     * @return void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);
        $_createdRecord->{Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT} =
            $_record->{Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT};
        $this->inspectMountPoint($_createdRecord);
    }

    /**
     * @param Tinebase_Model_Tree_FlySystem $updatedRecord
     * @param $record
     * @param $currentRecord
     * @return void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);
        $updatedRecord->{Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT} =
            $record->{Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT};
        $this->inspectMountPoint($updatedRecord);
    }

    protected function inspectMountPoint(Tinebase_Model_Tree_FlySystem $flySystem): void
    {
        if (empty($nodeId = $flySystem->getIdFromProperty(Tinebase_Model_Tree_FlySystem::FLD_MOUNT_POINT)) ||
                /*$nodeId ===*/ $this->getFlySystemMountPoint($flySystem)/*?->getId()*/) {
            return;
        }
        $node = Tinebase_FileSystem::getInstance()->get($nodeId);
        $object = Tinebase_FileSystem::getInstance()->getFileObjectBackend()->get($node->object_id);
        $object->flysystem = $flySystem->getId();
        $object->flypath = '/';
        Tinebase_FileSystem::getInstance()->getFileObjectBackend()->update($object);
    }

    protected static $flySystems = [];
    protected static $flyConfs = [];
    protected static ?Tinebase_Model_Tree_FlySystem $currentFlyConf = null;
}