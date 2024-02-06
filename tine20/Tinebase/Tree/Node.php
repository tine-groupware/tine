<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql backend class for tree nodes
 *
 * @package     Tinebase
 *             //string|Tinebase_Record_Interface
 * @method get(mixed $_id, boolean $_getDeleted = false): Tinebase_Model_Tree_Node
 *
 * TODO refactor to Tinebase_Tree_Backend_Node
 */
class Tinebase_Tree_Node extends Tinebase_Backend_Sql_Abstract
{
    use Tinebase_Controller_Record_ModlogTrait;

    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'tree_nodes';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_Tree_Node';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     * we don't use modlog here because the name is unique. If the only do a soft delete, it is not possible to create
     * the same node again!
     *
     * @var boolean
     */
    protected $_modlogActive = false;

    protected $_notificationActive = false;

    protected $_revision = null;

    protected $_beforeCreateHook = [];
    protected $_afterCreateHook = [];
    protected $_beforeUpdateHook = [];
    protected $_afterUpdateHook = [];

    protected $_doSynchronousPreviewCreation = false;

    /**
     * NOTE: returns fake tree controller
     *       needed by Tinebase_Core::getApplicationInstance('Tinebase_Model_Tree_Node')
     *
     * @return Tinebase_Tree
     */
    public static function getInstance()
    {
        return Tinebase_Tree::getInstance();
    }

    /**
     * the constructor
     *
     * allowed options:
     *  - modelName
     *  - tableName
     *  - tablePrefix
     *  - modlogActive
     *
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        if (isset($_options[Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS]) && true === $_options[Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS]) {
            $this->_notificationActive = $_options[Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS];
        }

        if (isset($_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE]) && true === $_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE]) {
            $this->_modlogActive = $_options[Tinebase_Config::FILESYSTEM_MODLOGACTIVE];
        } else {
            $this->_omitModLog = true;
        }


        parent::__construct($_dbAdapter, $_options);
    }

    public function registerBeforeCreateHook($key, $hook)
    {
        $this->_beforeCreateHook[$key] = $hook;
    }

    public function registerAfterCreateHook($key, $hook)
    {
        $this->_afterCreateHook[$key] = $hook;
    }

    public function registerBeforeUpdateHook($key, $hook)
    {
        $this->_beforeUpdateHook[$key] = $hook;
    }

    public function registerAfterUpdateHook($key, $hook)
    {
        $this->_afterUpdateHook[$key] = $hook;
    }

    public function doSynchronousPreviewCreation(?bool $synchronously = null): bool
    {
        $result = $this->_doSynchronousPreviewCreation;
        if (null !== $synchronously) {
            $this->_doSynchronousPreviewCreation = $synchronously;
        }
        return $result;
    }

    /**
     * if set to an integer value, only revisions of that number will be selected
     * if set to null value, regular revision will be selected
     *
     * @param int|null $_revision
     */
    public function setRevision($_revision)
    {
        $this->_revision = null !== $_revision ? (int)$_revision : null;
    }

    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        $select
            ->joinLeft(
                /* table  */ array('tree_fileobjects' => $this->_tablePrefix . 'tree_fileobjects'), 
                /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.object_id') . ' = ' . $this->_db->quoteIdentifier('tree_fileobjects.id'),
                /* select */ array('type', 'flysystem', 'flypath', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'seq', 'contenttype', 'revision_size', 'indexed_hash', 'description')
            )
            ->joinLeft(
                /* table  */ array('tree_filerevisions' => $this->_tablePrefix . 'tree_filerevisions'), 
                /* on     */ $this->_db->quoteIdentifier('tree_fileobjects.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions.id') . ' AND ' .
                $this->_db->quoteIdentifier('tree_filerevisions.revision') . ' = ' . (null !== $this->_revision ? (int)$this->_revision : $this->_db->quoteIdentifier('tree_fileobjects.revision')),
                /* select */ array('hash', 'size', 'preview_count', 'preview_status', 'preview_error_count', 'lastavscan_time', 'is_quarantined', 'revision')
            )->joinLeft(
            /* table  */ array('tree_filerevisions2' => $this->_tablePrefix . 'tree_filerevisions'),
                /* on     */ $this->_db->quoteIdentifier('tree_fileobjects.id') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions2.id'),
                /* select */ array('available_revisions' => Tinebase_Backend_Sql_Command::factory($select->getAdapter())->getAggregate('tree_filerevisions2.revision'))
            )->group($this->_tableName . '.object_id'
        );

        // NOTE: we need to do it here if $this->_modlogActive is false
        if (false === $this->_modlogActive && !$_getDeleted) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');
        }

        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $select->columns(new Zend_Db_Expr('CAST(MIN(' . $this->_db->quoteIdentifier('tree_fileobjects.indexed_hash') . ') = MIN(' . $this->_db->quoteIdentifier('tree_filerevisions.hash') . ') AS int) AS ' . $this->_db->quoteIdentifier('isIndexed')));
        } else {
            $select->columns(new Zend_Db_Expr('IF (' . $this->_db->quoteIdentifier('tree_fileobjects.indexed_hash') . ' = ' . $this->_db->quoteIdentifier('tree_filerevisions.hash') . ', TRUE, FALSE) AS ' . $this->_db->quoteIdentifier('isIndexed')));
        }
            
        return $select;
    }

    /**
     * do something after creation of record
     *
     * @param Tinebase_Record_Interface $_newRecord
     * @param Tinebase_Record_Interface $_recordToCreate
     * @return void
     */
    protected function _inspectAfterCreate(Tinebase_Record_Interface $_newRecord, Tinebase_Record_Interface $_recordToCreate)
    {
        Tinebase_Timemachine_ModificationLog::getInstance()
            ->setRecordMetaData($_newRecord, Tinebase_Controller_Record_Abstract::ACTION_CREATE);
        $this->_writeModLog($_newRecord, null);
        Tinebase_Notes::getInstance()->addSystemNote($_newRecord, Tinebase_Core::getUser(), Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED);

        /** @var Tinebase_Model_Tree_Node $_newRecord */
        $this->_inspectForPreviewCreation($_newRecord);

        if (true === $this->_notificationActive && Tinebase_Model_Tree_FileObject::TYPE_FILE === $_newRecord->type) {
            Tinebase_ActionQueue::getInstance()->queueAction('Tinebase_FOO_FileSystem.checkForCRUDNotifications', $_newRecord->getId(), 'created');
        }
    }

    protected function _checkName($name)
    {
        if (strpos($name, '/') !== false) {
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            throw new Tinebase_Exception_SystemGeneric($translate->_('node name must not contain the character /'));
        }
    }

    protected function _checkRecordName(Tinebase_Model_Tree_Node $node)
    {
        $this->_checkName($node->name);
    }

    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface
     * @throws Exception
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $this->_checkRecordName($_record);
        foreach ($this->_beforeCreateHook as $hook) {
            call_user_func($hook, $_record);
        }

        $createdRecord = parent::create($_record);

        foreach ($this->_afterCreateHook as $hook) {
            call_user_func($hook, $createdRecord, $_record);
        }

        return $createdRecord;
    }

    public function writeModLog(?Tinebase_Model_Tree_Node $_newRecord, ?Tinebase_Model_Tree_Node $_oldRecord): ?Tinebase_Record_RecordSet
    {
        return $this->_writeModLog($_newRecord, $_oldRecord);
    }

    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean                   $_doModLog
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    public function update(Tinebase_Record_Interface $_record, $_doModLog = true)
    {
        $this->_checkRecordName($_record);

        $oldRecord = $this->get($_record->getId(), true);
        foreach ($this->_beforeUpdateHook as $hook) {
            call_user_func($hook, $_record, $oldRecord);
        }

        $newRecord = parent::update($_record);

        if (true === $_doModLog) {
            if (isset($_record->grants)) {
                $newRecord->grants = $_record->grants;
                Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($oldRecord);
            }
            Tinebase_Timemachine_ModificationLog::getInstance()
                ->setRecordMetaData($newRecord, (bool)$newRecord->is_deleted === (bool)$oldRecord->is_deleted ?
                    Tinebase_Controller_Record_Abstract::ACTION_UPDATE : ($newRecord->is_deleted ?
                        Tinebase_Controller_Record_Abstract::ACTION_DELETE :
                        Tinebase_Controller_Record_Abstract::ACTION_UNDELETE), $oldRecord);
            $currentMods = $this->_writeModLog($newRecord, $oldRecord);
            if (null !== $currentMods && $currentMods->count() > 0) {
                Tinebase_Notes::getInstance()->addSystemNote($newRecord, Tinebase_Core::getUser(), Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);
            }
        }

        foreach ($this->_afterUpdateHook as $hook) {
            call_user_func($hook, $newRecord, $oldRecord);
        }

        /** @var Tinebase_Model_Tree_Node $newRecord */
        /** @var Tinebase_Model_Tree_Node $oldRecord */
        $this->_inspectForPreviewCreation($newRecord, $newRecord);

        return $newRecord;
    }

    /**
     * writes mod log and system notes
     *
     * @param Tinebase_Record_Interface $_newRecord
     * @param Tinebase_Record_Interface $_oldRecord
     */
    public function updated(Tinebase_Record_Interface $_newRecord, Tinebase_Record_Interface $_oldRecord)
    {
        /** @var Tinebase_Model_Tree_Node $_newRecord */
        /** @var Tinebase_Model_Tree_Node $_oldRecord */
        Tinebase_Timemachine_ModificationLog::getInstance()
            ->setRecordMetaData($_newRecord, (bool)$_newRecord->is_deleted === (bool)$_oldRecord->is_deleted ?
                Tinebase_Controller_Record_Abstract::ACTION_UPDATE : ($_newRecord->is_deleted ?
                    Tinebase_Controller_Record_Abstract::ACTION_DELETE :
                    Tinebase_Controller_Record_Abstract::ACTION_UNDELETE), $_oldRecord);
        $currentMods = $this->_writeModLog($_newRecord, $_oldRecord);
        if (null !== $currentMods && $currentMods->count() > 0 && (!$_newRecord->is_deleted || !$_newRecord->flysystem)) {
            Tinebase_Notes::getInstance()->addSystemNote($_newRecord, Tinebase_Core::getUser(), Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);

            if (true === $this->_notificationActive && Tinebase_Model_Tree_FileObject::TYPE_FILE === $_newRecord->type) {
                Tinebase_ActionQueue::getInstance()->queueAction('Tinebase_FOO_FileSystem.checkForCRUDNotifications', $_newRecord->getId(), 'updated');
            }
        }

        $this->_inspectForPreviewCreation($_newRecord, $_oldRecord);
    }

    /**
     * Updates multiple entries
     *
     * @param array $_ids to update
     * @param array $_data
     * @return integer number of affected rows
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     */
    public function updateMultiple($_ids, $_data)
    {
        $oldRecords = null;
        if ($this->_omitModLog !== true) {
            $oldRecords = $this->getMultiple($_ids);
        }

        if (isset($_data['name'])) {
            $this->_checkName($_data['name']);
        }

        $result = parent::updateMultiple($_ids, $_data);

        if (null !== $oldRecords) {
            foreach ($oldRecords as $oldRecord) {
                $newRecord = $this->get($oldRecord->getId());
                Tinebase_Timemachine_ModificationLog::getInstance()
                    ->setRecordMetaData($newRecord, (bool)$newRecord->is_deleted === (bool)$oldRecord->is_deleted ?
                        Tinebase_Controller_Record_Abstract::ACTION_UPDATE : ($newRecord->is_deleted ?
                            Tinebase_Controller_Record_Abstract::ACTION_DELETE :
                            Tinebase_Controller_Record_Abstract::ACTION_UNDELETE), $oldRecord);
                $currentMods = $this->_writeModLog($newRecord, $oldRecord);
                if (null !== $currentMods && $currentMods->count() > 0) {
                    Tinebase_Notes::getInstance()->addSystemNote($newRecord, Tinebase_Core::getUser(),
                        Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);

                }
            }
        }

        return $result;
    }

    /**
     * @param array $_ids
     */
    protected function _inspectBeforeSoftDelete(array $_ids)
    {
        if (!empty($_ids)) {
            list($accountId, $now) = Tinebase_Timemachine_ModificationLog::getCurrentAccountIdAndTime();
            /** @var Tinebase_Model_Tree_Node $node */
            foreach($this->getMultiple($_ids) as $node) {
                $node->deleted_by = $accountId;
                $node->deleted_time = $now;
                $node->is_deleted = 1;
                $this->_writeModLog(null, $node);
            }
        }
    }

    /**
     * @param Tinebase_Model_Tree_Node $_newRecord
     * @param Tinebase_Model_Tree_Node|null $_oldRecord
     */
    protected function _inspectForPreviewCreation(Tinebase_Model_Tree_Node $_newRecord, Tinebase_Model_Tree_Node $_oldRecord = null)
    {
        if (! Tinebase_FileSystem::getInstance()->isPreviewActive()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Skipping preview creation (preview not active)');
            return;
        }

        if (($_oldRecord !== null && $_oldRecord->hash === $_newRecord->hash) || (int)$_newRecord->revision < 1 ||
                empty($_newRecord->hash)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Skipping preview creation (no new hash or revision empty)');
            return;
        }

        if (Tinebase_Model_Tree_FileObject::TYPE_FILE !== $_newRecord->type) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Skipping preview creation (node is no TYPE_FILE)');
            return;
        }

        if (false === Tinebase_FileSystem_Previews::getInstance()->canNodeHavePreviews($_newRecord)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Skipping preview creation (canNodeHavePreviews === false)');
            return;
        }

        if ($this->_doSynchronousPreviewCreation) {
            if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback([
                    Tinebase_FileSystem_Previews::getInstance(), 'createPreviewsFromNode'
                ], [$_newRecord]);
            } else {
                Tinebase_FileSystem_Previews::getInstance()->createPreviewsFromNode($_newRecord);
            }
        } else {
            Tinebase_ActionQueue::getInstance(Tinebase_ActionQueue::QUEUE_LONG_RUN)->queueAction(
                'Tinebase_FOO_FileSystem_Previews.createPreviews', $_newRecord->getId(), $_newRecord->revision);
        }
    }

    /**
     * returns columns to fetch in first query and if an id/value pair is requested 
     * 
     * @param array|string $_cols
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array
     */
    protected function _getColumnsToFetch($_cols, Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)
    {
        $result = parent::_getColumnsToFetch($_cols, $_filter, $_pagination);
        
        // sanitize sorting fields
        $foreignTableSortFields = array(
            'size'               =>  'tree_filerevisions',
            'creation_time'      =>  'tree_fileobjects',
            'created_by'         =>  'tree_fileobjects',
            'last_modified_time' =>  'tree_fileobjects',
            'last_modified_by'   =>  'tree_fileobjects',
            'type'               =>  'tree_fileobjects',
            'contenttype'        =>  'tree_fileobjects',
            'revision'           =>  'tree_fileobjects',
        );
        
        foreach ($foreignTableSortFields as $field => $table) {
            if (isset($result[0][$field])) {
                $result[0][$field] = $table . '.' . $field;
            }
        }
        
        return $result;
    }
    
    /**
     * return child identified by name
     * 
     * @param  string|Tinebase_Model_Tree_Node  $parentId   the id of the parent node
     * @param  string|Tinebase_Model_Tree_Node  $childName  the name of the child node
     * @param  boolean                          $getDeleted = false
     * @param  boolean                          $throw = true
     * @throws Tinebase_Exception_NotFound
     * @return Tinebase_Model_Tree_Node|null
     */
    public function getChild($parentId, $childName, $getDeleted = false, $throw = true)
    {
        $flySystem = null;
        $flyNode = null;
        if ($parentId instanceof Tinebase_Model_Tree_Node) {
            if ($parentId->flysystem) {
                $flySystem = Tinebase_Controller_Tree_FlySystem::getFlySystem($parentId->flysystem);
                $flyNode = $parentId;
            }
            $parentId  = $parentId->getId();
        }
        $childName = $childName instanceof Tinebase_Model_Tree_Node ? $childName->name   : $childName;
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => $parentId ? 'equals' : 'isnull',
                'value'     => $parentId
            ),
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $childName
            )
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
        $searchFilter->ignorePinProtection();
        if (true === $getDeleted) {
            $searchFilter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', 'equals',
                Tinebase_Model_Filter_Bool::VALUE_NOTSET));
        }
        /** @var Tinebase_Model_Tree_Node $child */
        $child = $this->search($searchFilter)->getFirstRecord();
        
        if (!$child) {
            if ($flySystem && ($flySystem->fileExists($flyNode->flypath . '/' . $childName) ||
                    $flySystem->directoryExists($flyNode->flypath . '/' . $childName))) {
                Tinebase_FileSystem::getInstance()->syncFlySystem($flyNode, 0);
                return $this->getChild($parentId, $childName, $getDeleted, $throw);
            }
            if (true === $throw) {
                throw new Tinebase_Exception_NotFound('child: ' . $childName . ' not found!');
            }
            return null;
        } elseif ($flySystem && is_string($child->flypath)) {
            if ($child->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER ? !$flySystem->directoryExists($child->flypath) :  !$flySystem->fileExists($child->flypath)) {
                Tinebase_FileSystem::getInstance()->syncFlySystem($child, 0);
                return $this->getChild($parentId, $childName, $getDeleted, $throw);
            } elseif ($child->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                Tinebase_FileSystem::getInstance()->syncFlySystem($child, 0);
            }
        }
        
        return $child;
    }
    
    /**
     * return direct children of tree node
     * 
     * @param  string|Tinebase_Model_Tree_Node  $nodeId  the id of the node
     * @param  bool $ignoreAcl default is true
     * @param  bool $getDeleted
     * @return Tinebase_Record_RecordSet
     */
    public function getChildren($nodeId, $ignoreAcl = true, $getDeleted = false)
    {
        $nodeId = $nodeId instanceof Tinebase_Model_Tree_Node ? $nodeId->getId() : $nodeId;

        $options = [];
        if ($ignoreAcl) {
            $options['ignoreAcl'] = true;
        }
        $filterArr = [[
            'field' => 'parent_id', 'operator' => 'equals', 'value' => $nodeId
        ]];
        if (true === $getDeleted) {
            $filterArr[] = [
                'field' => 'is_deleted', 'operator'  => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET
            ];
        }
        return $this->search(new Tinebase_Model_Tree_Node_Filter($filterArr,
            Tinebase_Model_Filter_FilterGroup::CONDITION_AND, $options));
    }

    /**
     * returns all directory nodes up to the root(s), ignores ACL!
     *
     * @param Tinebase_Record_RecordSet $_nodes
     * @param Tinebase_Record_RecordSet $_result
     * @return Tinebase_Record_RecordSet
     */
    public function getAllFolderNodes(Tinebase_Record_RecordSet $_nodes, Tinebase_Record_RecordSet $_result = null)
    {
        if (null === $_result) {
            $_result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        $ids = array();
        /** @var Tinebase_Model_Tree_Node $node */
        foreach($_nodes as $node) {
            if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                $_result->addRecord($node);
            }
            if (!empty($node->parent_id)) {
                $ids[] = $node->parent_id;
            }
        }

        if (!empty($ids)) {
            $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
                array(
                    'field'     => 'id',
                    'operator'  => 'in',
                    'value'     => $ids
                )
            ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
            $parents = $this->search($searchFilter);
            $this->getAllFolderNodes($parents, $_result);
        }

        return $_result;
    }

    /**
     * @param  string  $path
     * @return Tinebase_Model_Tree_Node
     */
    public function getLastPathNode($path)
    {
        $fullPath = $this->getPathNodes($path);
        
        return $fullPath[$fullPath->count()-1];
    }
    
    /**
     * get object count
     * 
     * @param string $_objectId
     * @return integer
     */
    public function getObjectCount($_objectId)
    {
        return $this->getObjectUsage($_objectId)->count();
    }

    /**
     * get object usage
     *
     * @param string $_objectId
     * @return Tinebase_Record_RecordSet
     */
    public function getObjectUsage($_objectId)
    {
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'object_id',
                'operator'  => 'equals',
                'value'     => $_objectId
            )
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
        return $this->search($searchFilter);
    }
    
    /**
     * getPathNodes
     * 
     * @param string $_path
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getPathNodes($_path)
    {
        $pathParts = $this->splitPath($_path);
        
        if (empty($pathParts)) {
            throw new Tinebase_Exception_InvalidArgument('empty path provided');
        }
        
        $parentId  = null;
        $pathNodes = new Tinebase_Record_RecordSet($this->_modelName);
        
        foreach ($pathParts as $pathPart) {
            $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
                array(
                    'field'     => 'parent_id',
                    'operator'  => $parentId ? 'equals' : 'isnull',
                    'value'     => $parentId
                ),
                array(
                    'field'     => 'name',
                    'operator'  => 'equals',
                    'value'     => $pathPart
                )
            ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
            $node = $this->search($searchFilter)->getFirstRecord();
            
            if (!$node) {
                throw new Tinebase_Exception_NotFound('path: ' . $_path . ' not found!');
            }
            
            $pathNodes->addRecord($node);
            
            $parentId = $node->getId();
        }
        
        return $pathNodes;
    }
    
    /**
     * pathExists
     * 
     * @param  string  $_path
     * @return bool
     */
    public function pathExists($_path)
    {
        try {
            $this->getLastPathNode($_path);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Found path: ' . $_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . $teia);
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' ' . $tenf);
            return false;
        }
        
        return true;
    }
    
    public function sanitizePath($path)
    {
        return trim($path, '/');
    }
    
    /**
     * @param  string  $_path
     * @return array
     */
    public function splitPath($_path)
    {
        return explode('/', $this->sanitizePath($_path));
    }

    /**
     * recalculates all folder sizes
     *
     * on error it still continues and tries to calculate as many folder sizes as possible, but returns false
     *
     * @param Tinebase_Tree_FileObject $_fileObjectBackend
     * @return bool
     */
    public function recalculateFolderSize(Tinebase_Tree_FileObject $_fileObjectBackend)
    {
        // no transactions yet
        // get root node ids
        $searchFilter = Tinebase_Model_Tree_Node_Filter::getFolderParentIdFilterIgnoringAcl(null);
        $result = $this->_recalculateFolderSize($_fileObjectBackend, $this->_getIdsOfDeepestFolders($this->search($searchFilter, null, true), true));

        $size = 0;
        $revisionSize = 0;
        /** @var Tinebase_Model_Tree_Node $rootNode */
        foreach ($this->search($searchFilter) as $rootNode) {
            $size += $rootNode->size;
            $revisionSize += $rootNode->revision_size;
        }

        Tinebase_Application::getInstance()->setApplicationState(Tinebase_Core::getTinebaseId(),
            Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE, $size);
        Tinebase_Application::getInstance()->setApplicationState(Tinebase_Core::getTinebaseId(),
            Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE, $revisionSize);

        return $result;
    }

    /**
     * @param Tinebase_Tree_FileObject $_fileObjectBackend
     * @param array $_folderIds
     * @return bool
     */
    protected function _recalculateFolderSize(Tinebase_Tree_FileObject $_fileObjectBackend, array $_folderIds)
    {
        $success = true;
        $parentIds = array();
        $transactionManager = Tinebase_TransactionManager::getInstance();

        foreach($_folderIds as $id) {
            $transactionId = $transactionManager->startTransaction($this->_db);

            try {
                try {
                    /** @var Tinebase_Model_Tree_Node $record */
                    $record = $this->get($id, true);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $transactionManager->commitTransaction($transactionId);
                    continue;
                }

                if (!empty($record->parent_id) && !isset($parentIds[$record->parent_id])) {
                    $parentIds[$record->parent_id] = $record->parent_id;
                }

                $childrenNodes = $this->getChildren($id, true, true);
                $size = 0;
                $revision_size = 0;

                /** @var Tinebase_Model_Tree_Node $child */
                foreach($childrenNodes as $child) {
                    if (!$child->is_deleted) {
                        $size += ((int)$child->size);
                    }
                    $revision_size += ((int)$child->revision_size);
                }

                if ($size !== ((int)$record->size) || $revision_size !== ((int)$record->revision_size)) {
                    /** @var Tinebase_Model_Tree_FileObject $fileObject */
                    try {
                        $fileObject = $_fileObjectBackend->get($record->object_id, true);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $transactionManager->commitTransaction($transactionId);
                        continue;
                    }
                    $fileObject->size = $size;
                    $fileObject->revision_size = $revision_size;
                    $_fileObjectBackend->update($fileObject);
                }

                $transactionManager->commitTransaction($transactionId);

            // this shouldn't happen
            } catch (Exception $e) {
                $transactionManager->rollBack();
                Tinebase_Exception::log($e);
                $success = false;
            }

            Tinebase_Lock::keepLocksAlive();
        }

        if (!empty($parentIds)) {
            $success = $this->_recalculateFolderSize($_fileObjectBackend, $parentIds) && $success;
        }

        return $success;
    }

    /**
     * returns ids of folders that do not have any sub folders
     *
     * @param array $_folderIds
     * @param boolean $_getDeleted
     * @return array
     */
    protected function _getIdsOfDeepestFolders(array $_folderIds, $_getDeleted = false)
    {
        $result = array();
        $subFolderIds = array();
        foreach($_folderIds as $folderId) {
            // children folders
            $searchFilter = Tinebase_Model_Tree_Node_Filter::getFolderParentIdFilterIgnoringAcl($folderId, $_getDeleted);
            $nodeIds = $this->search($searchFilter, null, true);
            if (empty($nodeIds)) {
                // no children, this is a result
                $result[] = $folderId;
            } else {
                $subFolderIds = array_merge($subFolderIds, $nodeIds);
            }
        }

        if (!empty($subFolderIds)) {
            $result = array_merge($result, $this->_getIdsOfDeepestFolders($subFolderIds, $_getDeleted));
        }

        return $result;
    }

    /**
     * switches all sub-nodes of given parentId to is_deleted=0
     *
     * @param string $parentId
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    public function recursiveUndelete(string $parentId)
    {
        $select = parent::_getSelect('id', true);
        $select->where('parent_id = ?', $parentId);
        $ids = $this->_db->fetchAll($select);
        if (! empty($ids)) {
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $ids),
            );
            $this->_db->update($this->_tablePrefix . $this->_tableName, [
                'is_deleted' => 0
            ], $where);
            foreach ($ids as $id) {
                $this->recursiveUndelete($id);
            }
        }
    }
}
