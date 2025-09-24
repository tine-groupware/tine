<?php

/**
 * Abstract record controller for Tine 2.0 applications
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        this should be splitted into smaller parts!
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * abstract record controller class for Tine 2.0 applications
 *
 * @package     Tinebase
 * @subpackage  Controller
 *
 * @template T of Tinebase_Record_Interface
 */
abstract class Tinebase_Controller_Record_Abstract
    extends Tinebase_Controller_Event
    implements Tinebase_Controller_Record_Interface, Tinebase_Controller_SearchInterface
{
    use Tinebase_Controller_Record_ModlogTrait;

    /**
     * Model name
     *
     * @var class-string<T>
     */
    protected $_modelName;

    /**
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
     */
    protected $_doContainerACLChecks = true;

    /**
     * do right checks - can be enabled/disabled by doRightChecks
     *
     * @var boolean
     */
    protected $_doRightChecks = true;

    /**
     * only do area lock validation once
     *
     * @var array
     */
    protected $_areaLockValidated = [];

    /**
     * do area lock check
     *
     * @var boolean
     */
    protected $_doAreaLockCheck = true;

    /**
     * use notes - can be enabled/disabled by useNotes
     *
     * @var boolean
     */
    protected $_setNotes = true;

    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = true;

    /**
     * resolve customfields in search()
     *
     * @var boolean
     */
    protected $_resolveCustomFields = false;

    /**
     * clear customfields cache on create / update
     * 
     * @var boolean
     */
    protected $_clearCustomFieldsCache = false;

    /**
     * Do we update relation to this record
     * 
     * @var boolean
     */
    protected $_doRelationUpdate = true;

    /**
     * Do we force sent modlog for this record
     * 
     * @var boolean
     */
    protected $_doForceModlogInfo = false;

    /**
     * send notifications?
     *
     * @var boolean
     */
    protected $_sendNotifications = false;

    /**
     * if some of the relations should be deleted
     *
     * @var array
     */
    protected $_relatedObjectsToDelete = array();

    /**
     * set this to true to create/update related records
     * 
     * @var boolean
     */
    protected $_inspectRelatedRecords  = false;

    /**
     * set this to true to check (duplicate/freebusy/...) in create/update of related records
     *
     * @var boolean
     */
    protected $_doRelatedCreateUpdateCheck  = false;

    /**
     * set this to true to create / update / delete(?) dependent records
     *
     * @var boolean
     */
    protected $_handleDependentRecords = true;

    /**
     * record alarm field
     *
     * @var string
     */
    protected $_recordAlarmField = 'dtstart';

    /**
     * duplicate check fields / if this is null -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = null;

    protected $_duplicateCheckConfig = array();

    protected $_duplicateCheck = true;

    protected $_duplicateCheckOnUpdate = false;

    /**
     * holds new relation on update multiple
     * @var array
     */
    protected $_newRelations = null;

    /**
     * holds relations to remove on update multiple
     * @var array
     */
    protected $_removeRelations = null;

    /**
     * result of updateMultiple function
     * 
     * @var array
     */
    protected $_updateMultipleResult = array();

    /**
     * should each record be validated in updateMultiple
     * - false: only the first record is validated with the incoming data
     *
     * @var boolean
     */
    protected $_updateMultipleValidateEachRecord = false;

    /**
     * don't get in an endless recursion in get related data
     *
     * @var array
     */
    protected $_getRelatedDataRecursion = [];

    /**
     * cache if path feature is enabled or not
     *
     * @var bool
     */
    protected $_recordPathFeatureEnabled = null;

    /**
     * add / remove relations during _setReleatedData for virtual relation proproperties
     *
     * @var bool
     */
    protected $_handleVirtualRelationProperties = false;

    protected bool $_delayDependentRecords = false;
    protected array $_delayedDepRecFuncs = [];
    protected array $_delayedDepRecRaiis = [];
    protected array $_delyedDepRecCtrls = [];

    /**
     * constants for actions
     *
     * @var string
     */
    public const ACTION_ALL = 'all';
    public const ACTION_GET = 'get';
    public const ACTION_SYNC = 'sync';
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_UNDELETE = 'undelete';

    protected $_getMultipleGrant = Tinebase_Model_Grants::GRANT_READ;
    protected $_requiredFilterACLget = [Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_ADMIN];
    protected $_requiredFilterACLupdate  = [Tinebase_Model_Grants::GRANT_EDIT, Tinebase_Model_Grants::GRANT_ADMIN];
    protected $_requiredFilterACLsync  = [Tinebase_Model_Grants::GRANT_SYNC, Tinebase_Model_Grants::GRANT_ADMIN];
    protected $_requiredFilterACLexport  = [Tinebase_Model_Grants::GRANT_EXPORT, Tinebase_Model_Grants::GRANT_ADMIN];

    protected $_transitionConfig = null;
    protected $_transitionStatusField = null;

    /**
     * returns controller for records of given model
     *
     * @param string $_model
     *
     * @deprecated use Tinebase_Core::getApplicationInstance!
     *
     * @return Tinebase_Controller|Tinebase_Controller_Abstract|Tinebase_Controller_Record_Abstract
     */
    public static function getController($_model)
    {
        [$appName, , $modelName] = explode('_', $_model, 3);
        return Tinebase_Core::getApplicationInstance($appName, $modelName);
    }
    
    /**
     * returns backend for this controller
     * @return Tinebase_Backend_Sql_Interface
     */
    public function getBackend()
    {
        return $this->_backend;
    }

    protected function _setAnonymousUser()
    {
        $user = Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS);
        Tinebase_Core::setUser($user);
    }

    public function assertPublicUsage()
    {
        $currentUser = Tinebase_Core::getUser();
        if (! $currentUser) {
            $this->_setAnonymousUser();
        }

        $oldvalues = [
            'containerACLChecks' => $this->doContainerACLChecks(false),
            'rightChecks' => $this->doRightChecks(false),
            'currentUser' => $currentUser,
        ];

        if (method_exists($this, 'doGrantChecks')) {
            $oldvalues['doGrantChecks'] = $this->doGrantChecks(false);
        }

        return function () use ($oldvalues) {
            $this->doContainerACLChecks($oldvalues['containerACLChecks']);
            $this->doRightChecks($oldvalues['rightChecks']);
            if (!$oldvalues['currentUser']) {
                Tinebase_Core::unsetUser();
            }
            if (isset($oldvalues['doGrantChecks'])) {
                /** @phpstan-ignore method.notFound */
                $this->doGrantChecks($oldvalues['doGrantChecks']);
            }
        };
    }

    /*********** get / search / count **************/

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean|array|Tinebase_Record_Expander $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet<T>|array
     */
    public function search(
        ?\Tinebase_Model_Filter_FilterGroup $_filter = null,
        ?\Tinebase_Model_Pagination $_pagination = null,
        $_getRelations = false,
        $_onlyIds = false,
        $_action = self::ACTION_GET
    ) {
        if (! $_filter) {
            $_filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName);
        }
        $this->_checkRight($_action);
        $this->checkFilterACL($_filter, $_action);
        $this->_addDefaultFilter($_filter);
        
        $result = $this->_backend->search($_filter, $_pagination, $_onlyIds);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Got ' . count($result) . ' search results of ' . $this->_modelName);
        }

        if (! $_onlyIds) {
            if ($_getRelations instanceof Tinebase_Record_Expander) {
                $_getRelations->expand($result);
            } else {
                if ($_getRelations && count($result) > 0 && $result->getFirstRecord()->has('relations')) {
                    // if getRelations is true, all relations should be fetched
                    if ($_getRelations === true) {
                        $_getRelations = null;
                    }
                    $result->setByIndices('relations',
                        Tinebase_Relations::getInstance()->getMultipleRelations($this->_modelName,
                            $this->_getBackendType(), $result->getArrayOfIds(), null, array(), false, $_getRelations));
                }
            }
            // TODO eventually put this into the expander!
            if ($this->resolveCustomfields()) {
                Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($result);
            }

            $result->applyFieldGrants($_action);
        }
        
        return $result;
    }
    
    /**
     * you can define default filters here
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     */
    protected function _addDefaultFilter(?\Tinebase_Model_Filter_FilterGroup $_filter = null)
    {
        
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int|array
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        $this->_checkRight($_action);
        $this->checkFilterACL($_filter, $_action);
        $this->_addDefaultFilter($_filter);
        
        $count = $this->_backend->searchCount($_filter);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Got ' . (is_array($count) ? print_r($count, 1) : $count) . ' search count');
        
        return $count;
    }

    /**
     * set/get the sendNotifications state
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function sendNotifications($setTo = null)
    {
        return $this->_setBooleanMemberVar('_sendNotifications', $setTo);
    }

    public function delayDependentRecords(?bool $setTo = null): bool
    {
        $result = $this->_delayDependentRecords;
        if (null !== $setTo) {
            $this->_delayDependentRecords = $setTo;
        }
        return $result;
    }

    /**
     * set/get a boolean member var
     * 
     * @param string $name
     * @param boolean $value
     * @return boolean
     */
    protected function _setBooleanMemberVar($name, $value = null)
    {
        $currValue = $this->{$name};
        if ($value !== null) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Resetting ' . $name . ' to ' . (int) $value);
            }
            $this->{$name} = (bool)$value;
        }
        
        return $currValue;
    }

    /**
     * setter for $relatedObjectsToDelete
     *
     * @param array $relatedObjectNames
     */
    public function setRelatedObjectsToDelete(array $relatedObjectNames)
    {
        $this->_relatedObjectsToDelete = $relatedObjectNames;
    }

    /**
     * set/get purging of record when deleting
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function purgeRecords($setTo = null)
    {
        return $this->_setBooleanMemberVar('_purgeRecords', $setTo);
    }

    /**
     * set/get checking ACL rights
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doContainerACLChecks($setTo = null)
    {
        return $this->_setBooleanMemberVar('_doContainerACLChecks', $setTo);
    }

    /**
     * set/get checking area lock
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doAreaLockCheck($setTo = null)
    {
        return $this->_setBooleanMemberVar('_doAreaLockCheck', $setTo);
    }

    /**
     * set/get resolving of customfields
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function resolveCustomfields($setTo = null)
    {
        $currentValue = ($this->_setBooleanMemberVar('_resolveCustomFields', $setTo)
            && Tinebase_CustomField::getInstance()->appHasCustomFields($this->_applicationName, $this->_modelName));
        return $currentValue;
    }

    /**
     * set/get relation update
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doRelationUpdate($setTo = null)
    {
        return $this->_setBooleanMemberVar('_doRelationUpdate', $setTo);
    }
    
    /**
     * set/get force modlog info
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doForceModlogInfo($setTo = null)
    {
        return $this->_setBooleanMemberVar('_doForceModlogInfo', $setTo);
    }
    
    /**
     * set/get _inspectRelatedRecords
     *
     * @param boolean $setTo
     * @return boolean
     */
    public function doInspectRelatedRecords($setTo = null)
    {
        return $this->_setBooleanMemberVar('_inspectRelatedRecords', $setTo);
    }
    
    /**
     * set/get duplicateCheckFields
     * 
     * @param array $setTo
     * @return array
     */
    public function duplicateCheckFields($setTo = null)
    {
        if (null !== $setTo) {
            $this->_duplicateCheckFields = $setTo;
        }
        
        return $this->_duplicateCheckFields;
    }
    
    /**
     * disable this to do not check any rights
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doRightChecks($setTo = null)
    {
        return $this->_setBooleanMemberVar('_doRightChecks', $setTo);
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @param bool $_aclProtect
     * @return T
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function get(
        $_id,
        $_containerId = null,
        $_getRelatedData = true,
        $_getDeleted = false,
        bool $_aclProtect = true
    ) {
        $this->_checkRight(self::ACTION_GET);

        if (! is_scalar($_id)) {
            if (! $_id instanceof Tinebase_Record_Interface) {
                if (is_array($_id) && isset($_id['id']) && ! is_scalar($_id['id'])) {
                    throw new Tinebase_Exception_InvalidArgument('ID should be scalar');
                }
            }
        }
        
        if (! $_id) { // yes, we mean 0, null, false, ''
            $record = new $this->_modelName(array(), true);
            
            if ($this->_doContainerACLChecks && $record->has('container_id')) {
                if ($_containerId === null) {
                    $containers = Tinebase_Container::getInstance()->getPersonalContainer(
                        Tinebase_Core::getUser(),
                        $this->_modelName,
                        Tinebase_Core::getUser(),
                        Tinebase_Model_Grants::GRANT_ADD
                    );
                    $record->container_id = $containers[0]->getId();
                } else {
                    $record->container_id = $_containerId;
                }
            }
            
        } else {
            $record = $this->_backend->get($_id, $_getDeleted);
            $this->_checkGrant($record, self::ACTION_GET);

            // get related data only on request (defaults to true)
            if ($_getRelatedData) {
                $this->_getRelatedData($record);
            }

            if ($_aclProtect) {
                $record->applyFieldGrants(self::ACTION_GET);
            }
        }
        
        return $record;
    }
    
    /**
     * check if record with given $id exists
     * 
     * @param string $id
     * @return boolean
     */
    public function exists($id)
    {
        if (!$id) {
            return false;
        }

        $this->_checkRight(self::ACTION_GET);
        
        try {
            $record = $this->_backend->get($id);
            $result = $this->_checkGrant($record, self::ACTION_GET, false);
        } catch (Tinebase_Exception_NotFound) {
            $result = false;
        }
        
        return $result;
    }
    
    /**
     * add related data to record
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _getRelatedData($record)
    {
        if (isset($this->_getRelatedDataRecursion[$record->getId()])) {
            return;
        }
        try {
            // prevent endless recursion loop
            $this->_getRelatedDataRecursion[$record->getId()] = true;

            if ($record->has('tags')) {
                Tinebase_Tags::getInstance()->getTagsOfRecord($record);
            }
            if ($record->has('relations')) {
                $record->relations = Tinebase_Relations::getInstance()->getRelations(
                    $this->_modelName,
                    $this->_getBackendType(),
                    $record->getId());
            }
            if ($record->has('alarms')) {
                $this->getAlarms($record);
            }
            if ($this->resolveCustomfields()) {
                $cfConfigs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication(
                    Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName));
                Tinebase_CustomField::getInstance()->resolveRecordCustomFields($record, null, $cfConfigs);
            }
            if ($record->has('attachments') && Tinebase_Core::isFilesystemAvailable()) {
                Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($record);
            }
            if ($record->has('notes') && $this->useNotes()) {
                $record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord($this->_modelName, $record->getId());
            }
            if (!empty($record::getConfiguration()->jsonExpander)) {
                Tinebase_Record_Expander::expandRecord($record);
            }
        } finally {
            unset($this->_getRelatedDataRecursion[$record->getId()]);
        }
    }

    /**
     * Returns a set of records identified by their id's
     *
     * @param   array $_ids array of record identifiers
     * @param   bool $_ignoreACL don't check acl grants
     * @param Tinebase_Record_Expander $_expander
     * @param   bool $_getDeleted
     * @return Tinebase_Record_RecordSet<T>
     */
    public function getMultiple($_ids, $_ignoreACL = false, ?\Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        $this->_checkRight(self::ACTION_GET);

        // get all allowed containers and add them to getMultiple query
        $containerIds = ($this->_doContainerACLChecks && $_ignoreACL !== true)
           ? Tinebase_Container::getInstance()->getContainerByACL(
               Tinebase_Core::getUser(),
               $this->_modelName,
               $this->_getMultipleGrant,
               true
           )
           : null;
        if ($_getDeleted && $this->_backend->getModlogActive()) {
            $this->_backend->setModlogActive(false);
            try {
                $records = $this->_backend->getMultiple($_ids, $containerIds);
            } finally {
                $this->_backend->setModlogActive(true);
            }
        } else {
            $records = $this->_backend->getMultiple($_ids, $containerIds);
        }

        if ($_expander !== null) {
            $_expander->expand($records);
        } elseif ($this->resolveCustomfields()) {
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($records);
        }

        $records->applyFieldGrants(self::ACTION_GET);

        return $records;
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet<T>
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC')
    {
        $this->_checkRight(self::ACTION_GET);

        $records = $this->_backend->getAll($_orderBy, $_orderDirection);

        if ($this->resolveCustomfields()) {
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($records);
        }

        $records->applyFieldGrants(self::ACTION_GET);

        return $records;
    }

    /*************** add / update / delete / move *****************/

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  T
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $this->_checkRight(self::ACTION_CREATE);

        $this->_duplicateCheck = $_duplicateCheck;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
            . print_r($_record->toArray(),true));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Create new ' . $this->_modelName);

        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();

        if ($_record->has('attachments') && isset($_record->attachments) && Tinebase_Core::isFilesystemAvailable()) {
            // fill stat cache to avoid deadlocks. Needs to happen outside a transaction
            $path = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentBasePath($_record);
            Tinebase_FileSystem::getInstance()->fileExists($path);
        }
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $this->_setContainer($_record);

            $_record->applyFieldGrants(self::ACTION_CREATE);
            $_record->isValid(true);

            $this->_checkGrant($_record, self::ACTION_CREATE);

            // added _doForceModlogInfo behavior
            if ($_record->has('created_by')) {
                $origRecord = clone ($_record);
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, self::ACTION_CREATE);
                $this->_forceModlogInfo($_record, $origRecord, self::ACTION_CREATE);
            }

            $this->_inspectBeforeCreate($_record);
            if ($_duplicateCheck) {
                $this->_duplicateCheck($_record);
            }

            $this->_setAutoincrementValues($_record);

            $createdRecord = $this->_backend->create($_record);
            $this->_inspectAfterCreate($createdRecord, $_record);
            $createdRecordWithRelated = $this->_setRelatedData($createdRecord, $_record, null, true, true);
            $this->_inspectAfterSetRelatedDataCreate($createdRecordWithRelated, $_record);
            $mods = $this->_writeModLog($createdRecordWithRelated, null);
            $this->_setSystemNotes($createdRecordWithRelated, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $mods);

            if ($this->sendNotifications() && !$_record->mute) {
                $this->doSendNotifications($createdRecord, Tinebase_Core::getUser(), 'created');
            }
            
            $this->_increaseContainerContentSequence($createdRecord, Tinebase_Model_ContainerContent::ACTION_CREATE);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }
        
        if ($this->_clearCustomFieldsCache) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $this->get($createdRecord);
    }

    /**
     * sets personal container id if container id is missing in record - can be overwritten to set a different container
     *
     * @param $_record
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _setContainer(Tinebase_Record_Interface $_record)
    {
        if ($_record->has('container_id') && empty($_record->container_id)) {
            $configuration = $_record->getConfiguration();
            if ($configuration && ! $configuration->hasPersonalContainer) {
                // as model has no personal containers, we can't use that as default container
                throw new Tinebase_Exception_SystemGeneric('Container must be given');
            }

            $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_modelName, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_ADD);
            $_record->container_id = $containers[0]->getId();
        }
    }

    protected function _getRecordAutoincrementFields(Tinebase_Record_Interface $_record): ?array
    {
        $configuration = $_record->getConfiguration();
        if (null === $configuration) {
            return null;
        }

        if (! method_exists($configuration, 'getAutoincrementFields')) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Class has no getAutoincrementFields(): ' . $configuration::class);
            }
            return null;
        }

        return $configuration->getAutoincrementFields();
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface|null $_oldRecord
     */
    protected function _setAutoincrementValues(Tinebase_Record_Interface $_record, ?\Tinebase_Record_Interface $_oldRecord = null)
    {
        $autoincrementFields = $this->_getRecordAutoincrementFields($_record);
        if (empty($autoincrementFields)) {
            return;
        }
        $className = $_record::class;
        foreach ($autoincrementFields as $fieldDef) {
            $createNewValue = false;
            $checkValue = false;
            $freeOldValue = false;
            $numberable = null;

            // if new record field is not set and if there is no old record, we assign a new value
            if (!isset($_record->{$fieldDef['fieldName']}) &&
                null === $_oldRecord) {
                    $createNewValue = true;
            } else {

                // if new record field is set to empty string, we assign a new value
                if (empty($_record->{$fieldDef['fieldName']})) {
                    $createNewValue = true;

                // if new record field is populated and it differs from the old record value, we need to check the value
                } elseif (null !== $_oldRecord) {
                    if ($_record->{$fieldDef['fieldName']} != $_oldRecord->{$fieldDef['fieldName']}) {
                        $checkValue = true;
                    }

                // if new record field is populated and there is no old record, we need to check the value
                } else {
                    $checkValue = true;
                }
            }

            if (true !== $checkValue && true !== $createNewValue) {
                continue;
            }

            if (!($numberable = Tinebase_Numberable::getNumberable($_record, $className, $fieldDef['fieldName'], $fieldDef))) {
                if (null !== $_oldRecord) {
                    $_record->{$fieldDef['fieldName']} = $_oldRecord->{$fieldDef['fieldName']};
                }
                continue;
            }

            if (true === $checkValue) {
                if (false === $numberable->insert($_record->{$fieldDef['fieldName']})) {
                    throw new Tinebase_Exception_UnexpectedValue($fieldDef['fieldName'] . ' can\'t save value: "' . $_record->{$fieldDef['fieldName']} . '"');
                } elseif ($_oldRecord && !empty($_oldRecord->{$fieldDef['fieldName']})) {
                    $freeOldValue = true;
                }
            }

            if (true === $createNewValue) {
                $_record->{$fieldDef['fieldName']} = $numberable->getNext();
                if (null !== $_oldRecord && !empty($_oldRecord->{$fieldDef['fieldName']}) && $_oldRecord->{$fieldDef['fieldName']} != $_record->{$fieldDef['fieldName']}) {
                    $freeOldValue = true;
                }
            }

            if (true === $freeOldValue) {
                try {
                    $numberable->free($_oldRecord->{$fieldDef['fieldName']});
                } catch (Tinebase_Exception_UnexpectedValue) {} // we ignore this free
            }
        }
    }

    protected function _freeAutoincrements(Tinebase_Record_Interface $record): void
    {
        $autoincrementFields = $this->_getRecordAutoincrementFields($record);
        if (empty($autoincrementFields)) {
            return;
        }
        $className = $record::class;
        foreach ($autoincrementFields as $fieldDef) {
            $numberable = Tinebase_Numberable::getNumberable($record, $className, $fieldDef['fieldName'], $fieldDef);
            if ($numberable && !empty($record->{$fieldDef['fieldName']})) {
                $numberable->free($record->{$fieldDef['fieldName']});
            }
        }
    }

    /**
     * handle record exception
     * 
     * @param Throwable $e
     * @throws Throwable
     * 
     * @todo invent hooking mechanism for database/backend independent exception handling (like lock timeouts)
     */
    protected function _handleRecordCreateOrUpdateException(\Throwable $e)
    {
        if ($e instanceof Tinebase_Exception_ProgramFlow || Tinebase_Exception::isDbDuplicate($e)) {
            // log as ERROR? or better INFO? NOTICE?
            Tinebase_Exception::logExceptionToLogger($e);
        } else {
            Tinebase_Exception::log($e);
        }

        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($e instanceof Zend_Db_Statement_Exception && preg_match('/Lock wait timeout exceeded/', $e->getMessage())) {
            throw new Tinebase_Exception_Backend_Database_LockTimeout($e->getMessage());
        }
        
        throw $e;
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_inspectDenormalization($_record);
        $this->_handleCreateRecords($_record);
    }

    protected function _denormalizedDiff(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_otherRecord)
    {
        return $_record->diff($_otherRecord,
            ['id', 'seq', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'deleted_by',
                'deleted_time', 'is_deleted', TMCC::FLD_LOCALLY_CHANGED, TMCC::FLD_ORIGINAL_ID]);
    }

    /**
     * @param Tinebase_Record_Interface $newRecord
     * @param Tinebase_Record_Interface|null $currentRecord
     * @return void
     */
    protected function _inspectDenormalization(Tinebase_Record_Interface $newRecord, ?\Tinebase_Record_Interface $currentRecord = null)
    {
        if (null === ($mc = $newRecord::getConfiguration()) || !$mc->denormalizedFields) {
            return;
        }

        if (null !== $currentRecord) {
            $jsonExpander = $mc->jsonExpander;
            $expander = [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => array_fill_keys(array_keys($mc->denormalizedFields), [])
            ];
            foreach ($jsonExpander[Tinebase_Record_Expander::EXPANDER_PROPERTIES] ?? [] as $key => $arr) {
                unset($expander[Tinebase_Record_Expander::EXPANDER_PROPERTIES][$key]);
            }
            if (!empty($expander[Tinebase_Record_Expander::EXPANDER_PROPERTIES])) {
                $expander = new Tinebase_Record_Expander($currentRecord::class, $expander);
                $expander->expand(new Tinebase_Record_RecordSet($currentRecord::class, [$currentRecord]));
            }
        }

        foreach ($mc->denormalizedFields as $property => $definition) {
            if (TMCC::TYPE_RECORD === $definition[TMCC::TYPE]) {
                if ($newRecord->{$property} instanceof $definition[TMCC::CONFIG][TMCC::DENORMALIZATION_OF] &&
                        !$newRecord->{$property} instanceof $definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME]) {
                    $model = $definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME];
                    $newRecord->{$property} = new $model($newRecord->{$property}->toArray());
                }
                if (null === $currentRecord) {
                    if (!empty($newRecord->{$property})) {
                        $this->_newDenormalizedRecord($newRecord->{$property}, $definition);
                    }
                } else {
                    if ($newRecord->{$property} instanceof $definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME]) {
                        if (null === $currentRecord->{$property} || $currentRecord->{$property}->getId() !== $newRecord->{$property}->getId()) {
                            $this->_newDenormalizedRecord($newRecord->{$property}, $definition, $currentRecord->{$property});
                        } else {
                            $newRecord->{$property}->{TMCC::FLD_ORIGINAL_ID} = $currentRecord->{$property}->{TMCC::FLD_ORIGINAL_ID};
                            if ($newRecord->{$property}->has(TMCC::FLD_LOCALLY_CHANGED)) {
                                if (!$currentRecord->{$property}->{TMCC::FLD_LOCALLY_CHANGED} &&
                                    !$this->_denormalizedDiff($newRecord->{$property}, $currentRecord->{$property})->isEmpty()) {
                                    $newRecord->{$property}->{TMCC::FLD_LOCALLY_CHANGED} = 1;
                                } else {
                                    $newRecord->{$property}->{TMCC::FLD_LOCALLY_CHANGED} = $currentRecord->{$property}->{TMCC::FLD_LOCALLY_CHANGED};
                                }
                            }
                        }
                    }
                }
            } elseif (TMCC::TYPE_RECORDS === $definition[TMCC::TYPE]) {
                if ($newRecord->{$property} instanceof Tinebase_Record_RecordSet && $newRecord->{$property}
                        ->getRecordClassName() === $definition[TMCC::CONFIG][TMCC::DENORMALIZATION_OF]) {
                    $rs = $newRecord->{$property};
                    $model = $definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME];
                    $newRecord->{$property} = new Tinebase_Record_RecordSet($model);
                    foreach ($rs as $rec) {
                        $newRecord->{$property}->addRecord(new $model($rec->toArray()));
                    }
                }
                if (null === $currentRecord) {
                    if (!empty($newRecord->{$property})) {
                        foreach($newRecord->{$property} as $rec) {
                            $this->_newDenormalizedRecord($rec, $definition);
                        }
                    }
                } else {
                    if ($newRecord->{$property} instanceof Tinebase_Record_RecordSet) {
                        /** @var Tinebase_Record_RecordSetDiff $diff */
                        $diff = $currentRecord->{$property}->diff($newRecord->{$property});
                        foreach ($diff->added as $addedRecord) {
                            $this->_newDenormalizedRecord($addedRecord, $definition);
                        }
                        foreach ($diff->modified as $diff) {
                            $nr = $newRecord->{$property}->getById($diff->getId());
                            $cr = $currentRecord->{$property}->getById($diff->getId());
                            $nr->{TMCC::FLD_ORIGINAL_ID} = $cr->{TMCC::FLD_ORIGINAL_ID};
                            if ($nr->has(TMCC::FLD_LOCALLY_CHANGED)) {
                                if (!$cr->{TMCC::FLD_LOCALLY_CHANGED} && !$this->_denormalizedDiff($nr, $cr)->isEmpty()) {
                                    $nr->{TMCC::FLD_LOCALLY_CHANGED} = 1;
                                } else {
                                    $nr->{TMCC::FLD_LOCALLY_CHANGED} = $cr->{TMCC::FLD_LOCALLY_CHANGED};
                                }
                            }
                        }
                    }
                }
            } else {
                throw new Tinebase_Exception_Record_DefinitionFailure('property ' . $property . ' needs to be of type record[s]');
            }
        }
    }

    protected function _newDenormalizedRecord(Tinebase_Record_Interface $record, array $definition, ?Tinebase_Record_Interface $oldRecord = null)
    {
        if (!$record instanceof $definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME]) {
            throw new Tinebase_Exception_UnexpectedValue('is not instance of ' .
                $definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME]);
        }
        $originalRecord = null;

        if ($oldRecord) {
            /** @var Tinebase_Controller_Record_Abstract $ctrl */
            $ctrl = Tinebase_Core::getApplicationInstance($definition[TMCC::CONFIG][TMCC::RECORD_CLASS_NAME]);
            $ctrl->delete([$oldRecord->getId()]);
        }
        if ($record->getId() || $record->{TMCC::FLD_ORIGINAL_ID}) {
            try {
                /** @var Tinebase_Controller_Record_Abstract $ctrl */
                $ctrl = Tinebase_Core::getApplicationInstance($definition[TMCC::CONFIG][TMCC::DENORMALIZATION_OF]);
                $originalRecord = $ctrl->get(_id: $record->{TMCC::FLD_ORIGINAL_ID} ?: $record->getId(), _getRelatedData:  false, _getDeleted: true);
            } catch (Tinebase_Exception_NotFound) {
                $record->setId(null);
            }
        }
        if (!$record->{TMCC::FLD_ORIGINAL_ID}) {
            // this may be null, if the denormalized record in fact is not denormalized! it may also be just a local instance
            $record->{TMCC::FLD_ORIGINAL_ID} = $record->getId();
        }

        if ((isset($record::getConfiguration()->denormalizationConfig[TMCC::TRACK_CHANGES]) &&
                $record::getConfiguration()->denormalizationConfig[TMCC::TRACK_CHANGES])) {
            if (null === $originalRecord || !$this->_denormalizedDiff($record, $originalRecord)->isEmpty()) {
                $record->{TMCC::FLD_LOCALLY_CHANGED} = 1;
            } else {
                $record->{TMCC::FLD_LOCALLY_CHANGED} = 0;
            }
        }

        $record->setId(Tinebase_Record_Abstract::generateUID());
    }

    /**
     * do duplicate check (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     * @throws Tinebase_Exception_Duplicate
     */
    protected function _duplicateCheck(Tinebase_Record_Interface $_record)
    {
        $duplicateFilter = $this->_getDuplicateFilter($_record);

        if ($duplicateFilter === null) {
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Doing duplicate check.');

        $duplicates = $this->search($duplicateFilter, new Tinebase_Model_Pagination(array('limit' => 5)), /* $_getRelations = */ true);

        if (count($duplicates) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Found ' . count($duplicates) . ' duplicate(s). Checked fields: '
                    . print_r($this->_duplicateCheckFields, true));
            }

            // fetch tags here as they are not included yet - this is important when importing records with merge strategy
            if ($_record->has('tags')) {
                Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($duplicates);
            }

            $ted = new Tinebase_Exception_Duplicate('Duplicate record(s) found');
            $ted->setModelName($this->_modelName);
            $ted->setData($duplicates);
            $ted->setClientRecord($_record);
            throw $ted;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' No duplicates found.');
        }
    }

    /**
     * get duplicate filter
     *
     * @param Tinebase_Record_Interface $_record
     * @return Tinebase_Model_Filter_FilterGroup|null
     */
    protected function _getDuplicateFilter(Tinebase_Record_Interface $_record)
    {
        if (!is_array($this->_duplicateCheckFields) || count($this->_duplicateCheckFields) === 0) {
            return null;
        }
        
        $filters = array();
        foreach ($this->_duplicateCheckFields as $group) {
            $addFilter = array();
            if (! is_array($group)) {
                $group = array($group);
            }
            foreach ($group as $field) {
                $customFieldConfig = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
                    $this->_applicationName,
                    $field,
                    $this->_modelName
                );

                if ($customFieldConfig && isset($_record->customfields[$field])) {
                    $value = $_record->customfields[$field];
                    if (! empty($value)) {
                        $addFilter[] = array(
                            'field' => 'customfield',
                            'operator' => 'equals',
                            'value' => array(
                                'value' => $value,
                                'cfId' => $customFieldConfig->getId()
                            )
                        );
                    } else {
                        // empty: go to next group
                        continue 2;
                    }

                } else {
                    if (! empty($_record->{$field})) {
                        if ($field === 'relations') {
                            $relationFilter = $this->_getRelationDuplicateFilter($_record);
                            if ($relationFilter) {
                                $addFilter[] = $relationFilter;
                            }
                        } else {
                            $addFilter[] = array(
                                'field' => $field,
                                'operator' => 'equals',
                                'value' => $_record->{$field}
                            );
                        }
                    } else {
                        // empty: go to next group
                        continue 2;
                    }
                }
            }
            if (! empty($addFilter)) {
                $filters[] = array('condition' => 'AND', 'filters' => $addFilter);
            }
        }

        if (empty($filters)) {
            return null;
        }

        $filterData = (count($filters) > 1) ? array(array('condition' => 'OR', 'filters' => $filters)) : $filters;

        // exclude own record if it has an id
        $recordId = $_record->getId();
        if (! empty($recordId)) {
            $filterData[] = array('field' => 'id', 'operator' => 'notin', 'value' => array($recordId));
        }

        /** @var Tinebase_Model_Filter_FilterGroup $filter */
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, $filterData);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . print_r($filter->toArray(), true));
        }

        return $filter;
    }
    
    protected function _getRelationDuplicateFilter($record)
    {
        $filter = null;
        /** @var Tinebase_Record_RecordSet $relations */
        $relations = $record->relations;
        
        if (count($relations) === 0 || ! isset($this->_duplicateCheckConfig['relations']['filterField'])) {
            return $filter;
        }
        
        if (! $relations instanceof Tinebase_Record_RecordSet) {
            $relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation', $relations, /* $_bypassFilters = */ true);
        }
        
        // check for relation and add relation filter
        $type = $this->_duplicateCheckConfig['relations']['type'] ?? '';
        $relations = $relations->filter('type', $type);
        if (count($relations) > 0) {
            /** @var Tinebase_Model_Relation $duplicateRelation */
            $duplicateRelation = $relations->getFirstRecord();
            if ($duplicateRelation->related_id) {
                $filter = array(
                    'field' => $this->_duplicateCheckConfig['relations']['filterField'],
                    'operator' => 'AND',
                    'value' => array(array('field' => ':id', 'operator' => 'equals', 'value' => $duplicateRelation->related_id))
                );
            }
        }
        
        return $filter;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
    }

    /**
     * inspect creation of one record (after setReleatedData)
     *
     * @param   Tinebase_Record_Interface $createdRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataCreate($createdRecord, $record)
    {
    }

    /**
     * increase container content sequence
     * 
     * @param Tinebase_Record_Interface $record
     * @param string $action
     */
    protected function _increaseContainerContentSequence(Tinebase_Record_Interface $record, $action = null)
    {
        if ($record->has('container_id')) {
            Tinebase_Container::getInstance()->increaseContentSequence($record->container_id, $action, $record->getId());
        }
    }
    
    /**
     * Force modlog info if set
     *  
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_origRecord
     * @param string $_action
     * @return  void
     */
    protected function _forceModlogInfo(
        Tinebase_Record_Interface $_record,
        Tinebase_Record_Interface $_origRecord,
        $_action = null
    ) {
        if ($this->_doForceModlogInfo && ! empty($_origRecord)) {
            // on create
            if ($_action == self::ACTION_CREATE) {
                if (! empty($_origRecord->created_by)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Force modlog - created_by: ' . $_origRecord->created_by
                        );
                    }
                    $_record->created_by = $_origRecord->created_by;
                }
                if (! empty($_origRecord->creation_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Force modlog - creation_time: '
                            . $_origRecord->creation_time
                        );
                    }
                    $_record->creation_time = $_origRecord->creation_time;
                }
                if (! empty($_origRecord->last_modified_by)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_by: '
                            . $_origRecord->last_modified_by
                        );
                    }
                    $_record->last_modified_by = $_origRecord->last_modified_by;
                }
                if (! empty($_origRecord->last_modified_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_time: '
                            . $_origRecord->last_modified_time
                        );
                    }
                    $_record->last_modified_time = $_origRecord->last_modified_time;
                }
            }
            
            // on update
            if ($_action == self::ACTION_UPDATE) {
                if (! empty($_origRecord->last_modified_by)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_by: '
                            . $_origRecord->last_modified_by
                        );
                    }
                    $_record->last_modified_by = $_origRecord->last_modified_by;
                }
                if (! empty($_origRecord->last_modified_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Force modlog - last_modified_time: '
                            . $_origRecord->last_modified_time
                        );
                    }
                    $_record->last_modified_time = $_origRecord->last_modified_time;
                }
            }
        }   
    }

    /**
     * @param string $id
     * @param bool $persist
     * @return T
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function copy(string $id, bool $persist): Tinebase_Record_Interface
    {
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $record = $this->get($id);

        Tinebase_Record_Expander::expandRecord($record, true);
        $record->prepareForCopy();

        if ($persist) {
            $record = $this->create($record);
        }
        $transaction->release();

        return $record;
    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @param   boolean $_updateDeleted
     * @return  T
     * @throws  Tinebase_Exception_AccessDenied
     * 
     * @todo    fix duplicate check on update / merge needs to remove the changed record / ux discussion
     *          (duplicate check is currently only enabled in Sales_Controller_PurchaseInvoice)
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = true, $_updateDeleted = false)
    {
        $this->_duplicateCheck = $_duplicateCheck;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' Record to update: ' . print_r($_record->toArray(), true));
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Update ' . $this->_modelName . ' (duplicate check: ' . (int)$_duplicateCheck . ')');
        }

        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();
        if ($_record->has('attachments') && isset($_record->attachments) && Tinebase_Core::isFilesystemAvailable()) {
            // fill stat cache to avoid deadlocks. Needs to happen outside a transaction
            $path = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($_record);
            Tinebase_FileSystem::getInstance()->fileExists($path);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $_record->isValid(true);

            if ($this->_backend instanceof Tinebase_Backend_Sql_Abstract) {
                $raii = Tinebase_Backend_Sql_SelectForUpdateHook::getRAII($this->_backend);
            }
            $currentRecord = $this->get($_record->getId(), null, true, $_updateDeleted, false);
            unset($raii);

            // add _doForceModlogInfo behavior
            $origRecord = clone ($_record);
            $this->_updateACLCheck($_record, $currentRecord);

            $_record->applyFieldGrants(self::ACTION_UPDATE, $currentRecord);

            Tinebase_Record_Expander::expandRecord($currentRecord);

            $this->_concurrencyManagement($_record, $currentRecord);
            $this->_forceModlogInfo($_record, $origRecord, self::ACTION_UPDATE);
            $this->_inspectBeforeUpdate($_record, $currentRecord);
            
            if ($this->_duplicateCheckOnUpdate && $_duplicateCheck) {
                 $this->_duplicateCheck($_record);
            }

            $this->_setAutoincrementValues($_record, $currentRecord);
            
            $updatedRecord = $this->_backend->update($_record);

            $this->_inspectAfterUpdate($updatedRecord, $_record, $currentRecord);
            $updatedRecordWithRelatedData = $this->_setRelatedData($updatedRecord, $_record, $currentRecord, true);

            $this->_inspectAfterSetRelatedDataUpdate($updatedRecordWithRelatedData, $_record, $currentRecord);

            $currentMods = $this->_writeModLog($updatedRecordWithRelatedData, $currentRecord);
            $this->_setSystemNotes($updatedRecordWithRelatedData, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);
            
            if ($this->_sendNotifications && count($currentMods) > 0) {
                $this->doSendNotifications($updatedRecordWithRelatedData, Tinebase_Core::getUser(), 'changed', $currentRecord);
            }
            
            if ($_record->has('container_id') && $currentRecord->container_id !== $updatedRecord->container_id) {
                $this->_increaseContainerContentSequence($currentRecord, Tinebase_Model_ContainerContent::ACTION_DELETE);
                $this->_increaseContainerContentSequence($updatedRecord, Tinebase_Model_ContainerContent::ACTION_CREATE);
            } else {
                $this->_increaseContainerContentSequence($updatedRecord, Tinebase_Model_ContainerContent::ACTION_UPDATE);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }

        if ($this->_clearCustomFieldsCache) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        }

        /** @noinspection PhpUndefinedVariableInspection */
        return $this->get($updatedRecord->getId(), null, true, true);
    }
    
    /**
     * do ACL check for update record
     * 
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_currentRecord
     */
    protected function _updateACLCheck($_record, $_currentRecord)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Doing ACL check ...');
        
        if (($_currentRecord->has('container_id') && Tinebase_Record_Abstract::convertId($_currentRecord->container_id) != Tinebase_Record_Abstract::convertId($_record->container_id)) ||
            (($mc = $_record::getConfiguration()) && ($daf = $mc->delegateAclField) && (
                $mc->getFields()[$daf][TMCC::TYPE] === TMCC::TYPE_RECORDS ?
                    (($migration = $_currentRecord->{$daf}->getMigration($_record->{$daf}->getArrayOfIds())) && (count($migration['toDeleteIds']) > 0 || count($migration['toCreateIds']) > 0))
                    : ($_currentRecord->getIdFromProperty($daf) !== $_record->getIdFromProperty($daf))))) {
            $this->_checkGrant($_record, self::ACTION_CREATE);
            $this->_checkRight(self::ACTION_CREATE);
            // NOTE: It's not yet clear if we have to demand delete grants here or also edit grants would be fine
            $this->_checkGrant($_currentRecord, self::ACTION_DELETE);
            $this->_checkRight(self::ACTION_DELETE);
        } else {
            $this->_checkGrant($_record, self::ACTION_UPDATE, true, 'No permission to update record.', $_currentRecord);
            $this->_checkRight(self::ACTION_UPDATE);
        }
    }

    /**
     * concurrency management & history log
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_currentRecord
     */
    protected function _concurrencyManagement($_record, $_currentRecord)
    {
        if (! $_record->has('created_by')) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Doing concurrency check ...');

        $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
        $modLog->manageConcurrentUpdates(
            Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId(),
            $_record, $_currentRecord);
        $modLog->setRecordMetaData($_record, self::ACTION_UPDATE, $_currentRecord);
    }

    /**
     * set relations / tags / alarms
     *
     * @param   Tinebase_Record_Interface $updatedRecord the just updated record
     * @param   Tinebase_Record_Interface $record the update record
     * @param   Tinebase_Record_Interface $currentRecord the original record if one exists
     * @param   boolean $returnUpdatedRelatedData
     * @param   boolean $isCreate
     * @return  Tinebase_Record_Interface
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _setRelatedData(Tinebase_Record_Interface $updatedRecord, Tinebase_Record_Interface $record, ?\Tinebase_Record_Interface $currentRecord = null, $returnUpdatedRelatedData = false, $isCreate = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Update record: ' . print_r($record->toArray(), true));

        // needs to be done before relation updates happen!
        if ($this->_handleVirtualRelationProperties) {
            $this->_setRelationsByVirtualProps($record, $currentRecord);
        }

        // relations won't be touched if the property is set to null
        // an empty array on the relations property will remove all relations
        if ($record->has('relations') && isset($record->relations)
            && (is_array($record->relations) || $record->relations instanceof Tinebase_Record_RecordSet))
        {
            $type = $this->_getBackendType();
            Tinebase_Relations::getInstance()->setRelations(
                $this->_modelName,
                $type,
                $updatedRecord->getId(),
                $record->relations,
                false,
                $this->_inspectRelatedRecords,
                $this->_doRelatedCreateUpdateCheck);
        }

        if ($record->has('tags') && isset($record->tags) && (is_array($record->tags) || $record->tags instanceof Tinebase_Record_RecordSet)) {
            $updatedRecord->tags = $record->tags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($updatedRecord);
        }
        if ($record->has('alarms') && isset($record->alarms)) {
            $this->_saveAlarms($record);
        }
        if ($record->has('attachments') && isset($record->attachments) && Tinebase_Core::isFilesystemAvailable()) {
            $updatedRecord->attachments = $record->attachments;
            Tinebase_FileSystem_RecordAttachments::getInstance()->setRecordAttachments($updatedRecord);
            $this->_inspectAfterUpdateRecordAttachments($updatedRecord);
        }
        if ($record->has('notes') && $this->_setNotes !== false) {
            if (isset($record->notes) && (is_array($record->notes) || $record->notes instanceof Tinebase_Record_RecordSet)) {
                $updatedRecord->notes = $record->notes;
                Tinebase_Notes::getInstance()->setNotesOfRecord($updatedRecord);
            }
        }

        $this->handleSetDependentRecords($updatedRecord, $record, $currentRecord, $isCreate);

        if ($returnUpdatedRelatedData) {
            $this->_getRelatedData($updatedRecord);
        }

        // rebuild paths
        if ($this->_isRecordPathFeatureEnabled() && $updatedRecord::generatesPaths()) {
            Tinebase_Record_Path::getInstance()->rebuildPaths($updatedRecord, $currentRecord);
        }

        if (null !== ($mc = $updatedRecord::getConfiguration())) {
            foreach (array_keys($mc->getVirtualFields()) as $virtualField) {
                if (!isset($updatedRecord[$virtualField])) {
                    $updatedRecord->{$virtualField} = $record->{$virtualField};
                }
            }
        }

        return $updatedRecord;
    }

    public function handleSetDependentRecords($updatedRecord, $record, $currentRecord, $isCreate): void
    {
        if ($this->_handleDependentRecords && ($config = $updatedRecord::getConfiguration())) {
            if (is_array($config->recordsFields)) {
                foreach ($config->recordsFields as $property => $fieldDef) {
                    if ($fieldDef[TMCC::CREATE] ?? false) {
                        continue;
                    } elseif ($isCreate) {
                        $this->_createDependentRecords($updatedRecord, $record, $property, $fieldDef['config']);
                    } else {
                        $this->_updateDependentRecords($record, $currentRecord, $property, $fieldDef['config']);
                        $updatedRecord->{$property} = $record->{$property};
                    }
                }
            }
            if (is_array($config->recordFields)) {
                foreach ($config->recordFields as $property => $fieldDef) {
                    if ($fieldDef[TMCC::CREATE] ?? false) {
                        continue;
                    } elseif ($isCreate) {
                        $this->_createDependentRecord($updatedRecord, $record, $property, $fieldDef['config']);
                    } else {
                        $this->_updateDependentRecord($record, $currentRecord, $property, $fieldDef['config']);
                        $updatedRecord->{$property} = $record->{$property};
                    }
                }
            }
            // unset them all
            $this->_delayedDepRecRaiis = [];
            $ctrls = $this->_delyedDepRecCtrls;
            $this->_delyedDepRecCtrls = [];
            /** @var Tinebase_Controller_Record_Abstract $ctrl */
            foreach ($ctrls as $ctrl) {
                $ctrl->executeDelayedDependentRecords();
            }
        }
    }

    /**
     * set relations from virtual relation properties
     *
     * @param   Tinebase_Record_Interface $record the update record
     * @param   Tinebase_Record_Interface $currentRecord the original record if one exists
     */
    protected function _setRelationsByVirtualProps(Tinebase_Record_Interface $record, ?\Tinebase_Record_Interface $currentRecord = null)
    {
        $mc = $record::getConfiguration();
        $properties = $mc->getFields();
        $relationsModified = false;
        $addRelations = [];
        $removeRelations = [];

        foreach (array_keys($mc->getVirtualFields()) as $virtualField) {
            if (!isset($properties[$virtualField][TMCC::CONFIG][TMCC::TYPE]) || (TMCC::TYPE_RELATION !==
                    $properties[$virtualField][TMCC::CONFIG][TMCC::TYPE] && TMCC::TYPE_RELATIONS !==
                    $properties[$virtualField][TMCC::CONFIG][TMCC::TYPE])) {
                continue;
            }
            $model = $properties[$virtualField][TMCC::CONFIG][TMCC::CONFIG][TMCC::RECORD_CLASS_NAME];
            $type = $properties[$virtualField][TMCC::CONFIG][TMCC::CONFIG][TMCC::TYPE];
            $degree = $properties[$virtualField][TMCC::CONFIG][TMCC::CONFIG][TMCC::DEGREE];
            if (null !== $currentRecord && $currentRecord->relations->count() > 0) {
                $existingRelations = $currentRecord->relations->filter('related_model', $model)->filter('type', $type)
                    ->filter('degree', $degree);
            } else {
                $existingRelations = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class);
            }

            if (null === $record->{$virtualField}) {
                if ($existingRelations->count() > 0) {
                    $addRelations[] = $existingRelations;
                }
                continue;
            }

            $toAdd = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class);
            foreach ($record->{$virtualField} as $item) {
                if (is_array($item)) {
                    $item = $item['id'] ?? null;
                }
                if (null === ($existingRel = $existingRelations->find('related_id', $item))) {
                    $relationsModified = true;
                    $toAdd->addRecord(new Tinebase_Model_Relation([
                            'related_model'     => $model,
                            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                            'related_id'        => $item,
                            'related_degree'    => $degree,
                            'type'              => $type,
                        ], true));
                } else {
                    $existingRelations->removeById($existingRel->getId());
                }
            }

            if ($toAdd->count() > 0) {
                $addRelations[] = $toAdd;
            }
            if ($existingRelations->count() > 0) {
                $relationsModified = true;
                $removeRelations[] = $existingRelations;
            }
        }

        if ($relationsModified) {
            if (null !== $record->relations) {
                if (is_array($record->relations)) {
                    $record->relations = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class, $record->relations);
                }
            } else {
                if (null !== $currentRecord) {
                    $record->relations = clone $currentRecord->relations;
                } else {
                    $record->relations = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class);
                }
            }

            /** @var Tinebase_Record_RecordSet $add */
            foreach ($addRelations as $add) {
                $firstRecord = $add->getFirstRecord();
                $existingRelations = $record->relations->filter('related_model', $firstRecord->related_model)
                    ->filter('type', $firstRecord->type)->filter('degree', $firstRecord->degree);
                foreach ($add as $toAdd) {
                    if ($existingRelations->find('related_id', $toAdd->related_id) === null) {
                        $record->relations->addRecord($toAdd);
                    }
                }
            }

            foreach ($removeRelations as $remove) {
                $record->relations->removeRecordsById($remove);
            }
        }
    }

    /**
     * @return bool
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _isRecordPathFeatureEnabled()
    {
        if (null === $this->_recordPathFeatureEnabled) {
            $this->_recordPathFeatureEnabled = Tinebase_Config::getInstance()
                ->featureEnabled(Tinebase_Config::FEATURE_SEARCH_PATH);
        }
        return $this->_recordPathFeatureEnabled;
    }

    /**
     * set system notes
     *
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   string $_systemNoteType
     * @param   Tinebase_Record_RecordSet $_currentMods
     */
    protected function _setSystemNotes(
        $_updatedRecord,
        $_systemNoteType = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED,
        $_currentMods = null
    ) {
        if (! $_updatedRecord->has('notes') || $this->_setNotes === false) {
            return;
        }

        Tinebase_Notes::getInstance()->addSystemNote(
            $_updatedRecord,
            Tinebase_Core::getUser(),
            $_systemNoteType,
            $_currentMods
        );
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (null !== ($mc = $_record::getConfiguration())) {
            foreach ($mc->getControllerHooksBeforeUpdate() as $hook) {
                if (count($hook) > 2) {
                    $params = array_slice($hook, 2);
                    $hook = array_slice($hook, 0, 2);
                } else {
                    $params = [];
                }
                if (is_callable($hook)) {
                    call_user_func_array($hook, array_merge($params, [$_record, $_oldRecord]));
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' Hook is not callable: ' . print_r($hook, true));
                }
            }
        }

        $this->_inspectDenormalization($_record, $_oldRecord);
        $this->_handleCreateRecords($_record);
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
    }

    protected function _inspectAfterUpdateRecordAttachments($updatedRecord)
    {
    }

    /**
     * inspect delete of one record (after delete)
     *
     * @param   Tinebase_Record_Interface $record          the just deleted record
     * @return  void
     */
    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        $bchub = Tinebase_BroadcastHub::getInstance();
        if ($bchub->isActive() && $record->notifyBroadcastHub()) {
            $bchub->pushAfterCommit('delete', $record::class, $record->getId(), $record->has('container_id') ? $record->container_id : null);
        }
    }

    /**
     * inspect update of one record (after setReleatedData)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
    }

    /**
     * update modlog / metadata / add systemnote for multiple records defined by filter
     * 
     * NOTE: this should be done in a transaction because of the concurrency handling as
     *  we want the same seq in the record and in the modlog
     * 
     * @param Tinebase_Model_Filter_FilterGroup|array $_filterOrIds
     * @param array $_oldData
     * @param array $_newData
     */
    public function concurrencyManagementAndModlogMultiple($_filterOrIds, $_oldData, $_newData)
    {
        $ids = ($_filterOrIds instanceof Tinebase_Model_Filter_FilterGroup)
            ? $this->search($_filterOrIds, null, false, true, self::ACTION_UPDATE)
            : $_filterOrIds;
        if (! is_array($ids) || count($ids) === 0) {
            return;
        }

        if ($this->_omitModLog !== true) {
            $recordSeqs = $this->_backend->getPropertyByIds($ids, 'seq');
            
            [$currentAccountId, $currentTime] = Tinebase_Timemachine_ModificationLog::getCurrentAccountIdAndTime();
            $updateMetaData = array(
                'last_modified_by'   => $currentAccountId,
                'last_modified_time' => $currentTime,
                'seq'                => new Zend_Db_Expr('seq + 1'),
                'recordSeqs'         => $recordSeqs, // is not written to DB yet
            );
        } else {
            $updateMetaData = array();
        }
        
        $this->_backend->updateMultiple($ids, $updateMetaData);

        if ($this->_omitModLog !== true && is_object(Tinebase_Core::getUser())) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Writing modlog for ' . count($ids) . ' records ...');
            }

            $currentMods = Tinebase_Timemachine_ModificationLog::getInstance()->writeModLogMultiple(
                $ids,
                $_oldData,
                $_newData,
                $this->_modelName,
                $this->_getBackendType(),
                $updateMetaData
            );
            /** @noinspection PhpUndefinedVariableInspection */
            Tinebase_Notes::getInstance()->addMultipleModificationSystemNotes($currentMods, $currentAccountId, $this->_modelName);
        }
    }
    
    /**
     * handles relations on update multiple
     *
     * Syntax 1 (old): key: '%<type>-<related_model>', value: <related_id>
     * Syntax 2      : key: '%<add|remove|replace>', value: <relation json>
     * 
     * @param string $key
     * @param string $value
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _handleRelationsOnUpdateMultiple($key, $value)
    {
        if (preg_match('/%(add|remove|replace)/', $key, $matches)) {
            $action = $matches[1];
            $rel = json_decode($value, true);
        } else if (preg_match('/%(.+)-((.+)_Model_(.+))/', $key, $a)) {
            $action = $value ? 'replace' : 'remove';
            $rel = array(
                'related_model' => $a[2],
                'type' => $a[1],
                'related_id' => $value,
            );
        } else {
            throw new Tinebase_Exception_Record_DefinitionFailure('The relation to delete/set is not configured properly!');
        }

        // find constraint config
        $constraintsConfig = array();
        $relConfig = Tinebase_Relations::getConstraintsConfigs(array($this->_modelName, $rel['related_model']));
        if ($relConfig) {
            foreach ($relConfig as $config) {
                if ($rel['related_model'] == "{$config['relatedApp']}_Model_{$config['relatedModel']}" && isset($config['config']) && is_array($config['config'])) {
                    foreach ($config['config'] as $constraint) {
                        if (isset($rel['type']) && isset($constraint['type']) && $constraint['type'] == $rel['type']) {
                            $constraintsConfig = $constraint;
                            break 2;
                        }
                    }
                }
            }
        }

        // apply defaults
        $rel = array_merge($rel, array(
            'own_model'         => $this->_modelName,
            'own_backend'       => 'Sql',
            'related_backend'   => 'Sql',
            'related_degree'    => $rel['related_degree'] ?? (isset($constraintsConfig['sibling']) ?: 'sibling'),
            'type'              => $rel['type'] ?? (isset($constraintsConfig['type']) ?: ' '),
            'remark'            => $rel['remark'] ?? (isset($constraintsConfig['defaultRemark']) ?: ' '),
        ));

        if (in_array($action, array('remove', 'replace'))) {
            $this->_removeRelations ?: array();
            $this->_removeRelations[] = $rel;
        }

        if (in_array($action, array('add', 'replace'))) {
            $this->_newRelations ?: array();
            $this->_newRelations[] = $rel;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' New relations: ' . print_r($this->_newRelations, true)
               . ' Remove relations: ' . print_r($this->_removeRelations, true));
        }
    }

    /**
     * update multiple records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_data
     * @param Tinebase_Model_Pagination $_pagination
     * @return array $this->_updateMultipleResult
     * 
     * @todo add param $_returnFullResults (if false, do not return updated records in 'results')
     */
    public function updateMultiple($_filter, $_data, $_pagination = null)
    {
        $this->_checkRight(self::ACTION_UPDATE);
        $this->checkFilterACL($_filter, self::ACTION_UPDATE);
        $getRelations = false;

        $this->_newRelations = null;
        $this->_removeRelations = null;

        foreach ($_data as $key => $value) {
            if (stristr($key,'#')) {
                $_data['customfields'][substr($key,1)] = $value;
                unset($_data[$key]);
            }
            if (stristr($key, '%')) {
                $getRelations = true;
                $this->_handleRelationsOnUpdateMultiple($key, $value);
                unset($_data[$key]);
            }
        }

        $this->_updateMultipleResult = array(
            'results'           => new Tinebase_Record_RecordSet($this->_modelName),
            'exceptions'        => new Tinebase_Record_RecordSet(Tinebase_Model_UpdateMultipleException::class),
            'totalcount'        => 0,
            'failcount'         => 0,
        );

        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => $this,
            'filter'     => $_filter,
            'options'    => [
                'getRelations' => $getRelations,
                'sortInfo' => $_pagination,
            ],
            'function'   => 'processUpdateMultipleIteration',
        ));
        /*$result = */$iterator->iterate($_data);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updated ' . $this->_updateMultipleResult['totalcount'] . ' records.');
        }
        
        if ($this->_clearCustomFieldsCache) {
            Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        }
        
        return $this->_updateMultipleResult;
    }
    
    /**
     * enable / disable notes
     *
     * @param boolean $setTo
     * @return boolean
     */
    public function useNotes($setTo = null)
    {
        return $this->_setBooleanMemberVar('_setNotes', $setTo);
    }
    
    /**
     * iterate relations
     * 
     * @param Tinebase_Record_Interface $currentRecord
     * @return array
     */
    protected function _iterateRelations($currentRecord)
    {
        if(! $currentRecord->relations || ! $currentRecord->relations instanceof Tinebase_Record_RecordSet) {
            $currentRecord->relations = new Tinebase_Record_RecordSet('Tinebase_Model_Relation');
        }

        // handle relations to remove
        if($this->_removeRelations) {
            if($currentRecord->relations->count()) {
                foreach($this->_removeRelations as $remRelation) {
                    $removeRelations = $currentRecord->relations
                        ->filter('type', $remRelation['type'])
                        ->filter('related_model', $remRelation['related_model']);
                    
                    $currentRecord->relations->removeRecords($removeRelations);
                }
            }
        }

        // handle new relations
        if($this->_newRelations) {
            foreach($this->_newRelations as $newRelation) {
                // convert duplicate to update (remark / degree)
                $duplicate = $currentRecord->relations
                    ->filter('related_model', $newRelation['related_model'])
                    ->filter('related_id',    $newRelation['related_id'])
                    ->filter('type',          $newRelation['type'])
                    ->getFirstRecord();

                if ($duplicate) {
                    $currentRecord->relations->removeRecord($duplicate);
                }

                $newRelation['own_id'] = $currentRecord->getId();
                $rel = new Tinebase_Model_Relation();
                $rel->setFromArray($newRelation);
                $currentRecord->relations->addRecord($rel);
            }
        }
        
        return $currentRecord->relations->toArray();
    }

    /**
     * update multiple records in an iteration
     * @see Tinebase_Record_Iterator / self::updateMultiple()
     *
     * @param Tinebase_Record_RecordSet $_records
     * @param array $_data
     * @throws Exception
     * @throws Tinebase_Exception_Record_Validation
     */
    public function processUpdateMultipleIteration($_records, $_data)
    {
        if (count($_records) === 0) {
            return;
        }
        $bypassFilters = false;
        /** @var Tinebase_Record_Interface $currentRecord */
        foreach ($_records as $currentRecord) {
            $oldRecordArray = $currentRecord->toArray();
            unset($oldRecordArray['relations']);
            
            $data = array_merge($oldRecordArray, $_data);
            
            if ($this->_newRelations || $this->_removeRelations) {
                $data['relations'] = $this->_iterateRelations($currentRecord);
            }
            try {
                $record = new $this->_modelName($data, $bypassFilters);
                $updatedRecord = $this->update($record, false);

                /** @noinspection PhpUndefinedMethodInspection */
                $this->_updateMultipleResult['results']->addRecord($updatedRecord);
                $this->_updateMultipleResult['totalcount'] ++;
                
            } catch (Tinebase_Exception_Record_Validation $e) {
                if ($this->_updateMultipleValidateEachRecord === false) {
                    throw $e;
                }
                /** @noinspection PhpUndefinedMethodInspection */
                $this->_updateMultipleResult['exceptions']->addRecord(new Tinebase_Model_UpdateMultipleException(array(
                    'id'         => $currentRecord->getId(),
                    'exception'  => $e,
                    'record'     => $currentRecord,
                    'code'       => $e->getCode(),
                    'message'    => $e->getMessage()
                )));
                $this->_updateMultipleResult['failcount'] ++;
            }
            if ($this->_updateMultipleValidateEachRecord === false) {
                // only validate the first record
                $bypassFilters = true;
            }
        }
    }

    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param  array|T|Tinebase_Record_RecordSet<T> $_ids array of record identifiers
     * @return Tinebase_Record_RecordSet<T>
     * @throws Exception
     */
    public function delete($_ids)
    {
        if ($_ids instanceof $this->_modelName || $_ids instanceof Tinebase_Record_RecordSet) {
            /** @var Tinebase_Record_Interface $_ids */
            $_ids = (array)$_ids->getId();
        }

        try {
            $raii = Tinebase_RAII::getTransactionManagerRAII();

            /** @var string[] $_ids */
            $ids = $this->_inspectDelete((array) $_ids);
            if ($ids instanceof Tinebase_Record_RecordSet) {
                /** @var Tinebase_Record_RecordSet $records */
                $records = $ids;
                $ids = array_unique($records->getArrayOfIds());
            } else {
                /** @var Tinebase_Record_RecordSet $records */
                $records = $this->_backend->getMultiple((array)$ids);
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && count((array)$ids) != count($records)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Only ' . count($records)
                    . ' of ' . count((array)$ids) . ' records exist.');
            }

            $this->_checkRight(self::ACTION_DELETE);

            if ($records->count() === 0) {
                $raii->release();
                return $records;
            }
        
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Deleting ' . count($records) . ' of ' . $this->_modelName . ' records ...');
            
            foreach ($records as $record) {
                $this->_getRelatedData($record);
                $this->_deleteRecord($record);
                $this->_inspectAfterDelete($record);
            }

            if (true === $this->_isRecordPathFeatureEnabled()) {
                $pathController = Tinebase_Record_Path::getInstance();
                $shadowPathParts = array();
                /** @var Tinebase_Record_Interface $record */
                foreach ($records as $record) {
                    $shadowPathParts[] = $record->getShadowPathPart();
                }
                $pathController->deleteShadowPathParts($shadowPathParts);
            }

            $raii->release();

            // send notifications
            if ($this->sendNotifications()) {
                foreach ($records as $record) {
                    $this->doSendNotifications($record, Tinebase_Core::getUser(), 'deleted');
                }
            }

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            throw $e;
        }
        
        if ($this->_clearCustomFieldsCache) {
             Tinebase_Core::getCache()->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('customfields'));
        }

        // returns deleted records
        return $records;
    }

    /**
     * delete by given filter (+ optional pagination)
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination|null $_pagination
     * @return null|Tinebase_Record_RecordSet<T>
     * @throws Exception
     */
    public function deleteByFilter(Tinebase_Model_Filter_FilterGroup $_filter,
                                   ?\Tinebase_Model_Pagination $_pagination = null): ?Tinebase_Record_RecordSet
    {
        $oldMaxExcecutionTime = ini_get('max_execution_time');

        Tinebase_Core::setExecutionLifeTime(300); // 5 minutes

        $ids = $this->search($_filter, $_pagination, false, true);
        $deletedRecords = $this->delete($ids);
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        return $deletedRecords;
    }

    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return Tinebase_Record_RecordSet|array<string> records to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        return $_ids;
    }

    /**
     * move records to new container / folder / whatever
     *
     * @param mixed $_records (can be record set, filter, array, string)
     * @param mixed $_target (string, container record, ...)
     * @param string $_containerProperty
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function move(mixed $_records, mixed $_target, $_containerProperty = 'container_id')
    {
        $records = $this->_convertToRecordSet($_records);
        $targetContainerId = ($_target instanceof Tinebase_Model_Container) ? $_target->getId() : $_target;

        if ($this->_doContainerACLChecks) {
            // check add grant in target container
            if (! Tinebase_Core::getUser()->hasGrant($targetContainerId, Tinebase_Model_Grants::GRANT_ADD)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Permission denied to add records to container.');
                throw new Tinebase_Exception_AccessDenied('You are not allowed to move records to this container');
            }
            
            // check delete grant in source container
            $containerIdsWithDeleteGrant = Tinebase_Container::getInstance()->getContainerByACL(
                Tinebase_Core::getUser(),
                $this->_modelName,
                Tinebase_Model_Grants::GRANT_DELETE,
                true
            );
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Containers with delete grant: ' . print_r($containerIdsWithDeleteGrant, true));
            }
            foreach ($records as $index => $record) {
                if (! in_array($record->{$_containerProperty}, $containerIdsWithDeleteGrant)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Permission denied to remove record ' . $record->getId() . ' from container '
                        . $record->{$_containerProperty});
                    unset($records[$index]);
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Moving ' . count($records) . ' ' . $this->_modelName . '(s) to container ' . $targetContainerId);
        
        // move (update container id)
        $idsToMove = $records->getArrayOfIds();
        $filterClass = $this->_modelName . 'Filter';

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterClass, [
            ['field' => 'id', 'operator' => 'in', 'value' => $idsToMove]
        ]);

        if (!$filter) {
            throw new Tinebase_Exception_NotFound('Filter ' . $filterClass . ' not found!');
        }

        /*$updateResult = */$this->updateMultiple($filter, array(
            $_containerProperty => $targetContainerId
        ));
        
        return $idsToMove;
    }

    /**
     * undelete one record
     *
     * TODO finish implementaion
     *
     * @param T $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    public function unDelete(Tinebase_Record_Interface $_record)
    {
        if ($this->_purgeRecords && !$_record->has('created_by')) {
            throw new Tinebase_Exception_InvalidArgument('record of type ' . $_record::class . ' can\'t be undeleted');
        }
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $this->_checkGrant($_record, self::ACTION_DELETE);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Undeleting record ' . $_record->getId() . ' of type ' . $this->_modelName);
            }

            $originalRecord = $this->get($_record->getId(), null, false, true);
            $updateRecord = clone $originalRecord;

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($updateRecord, 'undelete', $updateRecord);
            $this->_backend->update($updateRecord);

            $this->_unDeleteLinkedObjects($_record);

            $this->_writeModLog($updateRecord, $originalRecord);

            $this->_increaseContainerContentSequence($_record, Tinebase_Model_ContainerContent::ACTION_UNDELETE);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }

    /*********** helper funcs **************/

    /**
     * delete one record
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        $this->_checkGrant($_record, self::ACTION_DELETE);

        if (! $this->_purgeRecords && $_record->has('created_by')) {
            $currentRecord = clone $_record;
            $this->_deleteLinkedObjects($_record);
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_record, self::ACTION_DELETE, $_record);
            $this->_backend->update($_record);
            $this->_writeModLog($_record, $currentRecord);
        } else {
            $this->_deleteLinkedObjects($_record);
            $this->_backend->delete($_record);
            $this->_writeModLog(null, $_record);
        }
        $this->_freeAutoincrements($_record);
        $this->_increaseContainerContentSequence($_record, Tinebase_Model_ContainerContent::ACTION_DELETE);
    }

    /**
     * delete linked objects (notes, relations, attachments, alarms) of record
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        if ($_record->has('notes') && $this->useNotes()) {
            Tinebase_Notes::getInstance()->deleteNotesOfRecord($this->_modelName, $this->_getBackendType(), $_record->getId());
        }
        
        if ($_record->has('relations')) {
            $this->deleteLinkedRelations($_record);
        }

        if ($_record->has('attachments') && Tinebase_Core::isFilesystemAvailable()) {
            Tinebase_FileSystem_RecordAttachments::getInstance()->deleteRecordAttachments($_record);
        }

        if ($_record->has('alarms')) {
            $this->_deleteAlarmsForIds(array($_record->getId()));
        }

        $this->handleDeleteDependentRecords($_record);
    }

    public function handleDeleteDependentRecords(Tinebase_Record_Interface $_record): void
    {
        if ($this->_handleDependentRecords && ($config = $_record::getConfiguration())) {
            if (is_array($config->recordsFields)) {
                foreach ($config->recordsFields as $property => $fieldDef) {
                    $this->_deleteDependentRecords($_record, $property, $fieldDef['config']);
                }
            }
            if (is_array($config->recordFields)) {
                foreach ($config->recordFields as $property => $fieldDef) {
                    $this->_deleteDependentRecords($_record, $property, $fieldDef['config']);
                }
            }
        }
    }

    /**
     * unDelete linked objects (notes, relations, attachments) of record
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _unDeleteLinkedObjects(Tinebase_Record_Interface $_record): void
    {
        if ($_record->has('notes') && count($_record->notes ?: []) > 0) {
            $ids = array();
            foreach($_record['notes'] as $val) {
                $ids[] = $val['id'];
            }
            Tinebase_Notes::getInstance()->unDeleteNotes($ids);
        }

        if ($_record->has('relations') && $_record->relations && count($_record->relations) > 0) {
            Tinebase_Relations::getInstance()->undeleteRelations($_record->relations);
        }

        if ($_record->has('attachments')
            && count($_record->attachments) > 0
            && Tinebase_Core::isFilesystemAvailable()
        ) {
            foreach ($_record->attachments as $attachment) {
                Tinebase_FileSystem::getInstance()->unDeleteFileNode($attachment['id']);
            }
        }

        if ($_record->has('alarms') && count($_record->alarms) > 0) {
            $_record->alarms->setId(null);
            $this->_saveAlarms($_record);
        }

        if ($this->_handleDependentRecords && ($config = $_record::getConfiguration())) {
            if (is_array($config->recordsFields)) {
                foreach ($config->recordsFields as $property => $fieldDef) {
                    $this->_undeleteDependentRecords($_record, $property, $fieldDef['config']);
                }
            }
            if (is_array($config->recordFields)) {
                foreach ($config->recordFields as $property => $fieldDef) {
                    $this->_undeleteDependentRecords($_record, $property, $fieldDef['config']);
                }
            }
        }
    }
    
    /**
     * delete linked relations
     * 
     * @param T $record
     * @param array $modelsToDelete
     * @param array $typesToDelete
     */
    public function deleteLinkedRelations(Tinebase_Record_Interface $record, $modelsToDelete = array(), $typesToDelete = array())
    {
        $relations = isset($record->relations) && $record->relations instanceof Tinebase_Record_RecordSet
            ? $record->relations
            : Tinebase_Relations::getInstance()->getRelations($this->_modelName, $this->_getBackendType(), $record->getId());

        if (count($relations) === 0) {
            return;
        }

        // unset record relations
        Tinebase_Relations::getInstance()->setRelations($this->_modelName, $this->_getBackendType(), $record->getId(), array());

        if (empty($modelsToDelete)) {
            $modelsToDelete = $this->_relatedObjectsToDelete;
        }
        if (empty($modelsToDelete) && empty($typesToDelete)) {
            return;
        }
        
        // remove related objects
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleting all '
            . implode(',', $modelsToDelete) . ' relations.');

        foreach ($relations as $relation) {
            if (in_array($relation->related_model, $modelsToDelete) || in_array($relation->type, $typesToDelete)) {
                [$appName, , $itemName] = explode('_', $relation->related_model);
                $appController = Tinebase_Core::getApplicationInstance($appName, $itemName);

                try {
                    $appController->delete($relation->related_id);
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                        . ' Error deleting: ' . $e->getMessage());
                }
            }
        }
    }

    public function public_checkRight($_action)
    {
        $this->_checkRight($_action);
    }

    public function checkGrant(
        $_record,
        $_action,
        $_throw = true,
        $_errorMessage = 'No Permission.',
        $_oldRecord = null
    ) {
        return $this->_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    /**
     * check grant for action (CRUD)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Tinebase_Record_Interface $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     *
     * @todo use this function in other create + update functions
     * @todo invent concept for simple adding of grants (plugins?)
     *
     */
    protected function _checkGrant(
        $_record,
        $_action,
        $_throw = true,
        $_errorMessage = 'No Permission.',
        /** @noinspection PhpUnusedParameterInspection */ $_oldRecord = null
    ) {
        if (
            ! $this->_doContainerACLChecks
            || (! $_record->has('container_id') && (!($mc = $_record->getConfiguration()) || !$mc->delegateAclField))
        ) {
            return true;
        }

        if (($mc = $_record->getConfiguration()) && $mc->delegateAclField) {
            return $this->_checkDelegatedGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
        }
        
        if (! is_object(Tinebase_Core::getUser())) {
            throw new Tinebase_Exception_AccessDenied('User object required to check grants');
        }
        
        // admin grant includes all others
        if (Tinebase_Core::getUser()->hasGrant($_record->container_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
            return true;
        }
        
        $hasGrant = match ($_action) {
            self::ACTION_GET => Tinebase_Core::getUser()->hasGrant($_record->container_id,
                Tinebase_Model_Grants::GRANT_READ),
            self::ACTION_CREATE => Tinebase_Core::getUser()->hasGrant($_record->container_id,
                Tinebase_Model_Grants::GRANT_ADD),
            self::ACTION_UPDATE => Tinebase_Core::getUser()->hasGrant($_record->container_id,
                Tinebase_Model_Grants::GRANT_EDIT),
            self::ACTION_DELETE => Tinebase_Core::getUser()->hasGrant($_record->container_id,
                Tinebase_Model_Grants::GRANT_DELETE),
            default => Tinebase_Core::getUser()->hasGrant($_record->container_id, $_action),
        };

        if (! $hasGrant) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                $containerId = $_record->container_id instanceof Tinebase_Model_Container
                    ? $_record->container_id->getId()
                    : $_record->container_id;
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' No permissions to ' . $_action . ' in container ' . print_r($containerId, true));
            }
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
        }
        
        return $hasGrant;
    }

    protected function _checkDelegatedGrant(Tinebase_Record_Interface $_record,
                                            string $_action,
                                            bool $_throw,
                                            string $_errorMessage,
                                            ?Tinebase_Record_Interface $_oldRecord): bool
    {
        $mc = $_record->getConfiguration();
        if (empty($_record->{$mc->delegateAclField}) && isset($mc->recordsFields[$mc->delegateAclField])) {
            (new Tinebase_Record_Expander($_record::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [$mc->delegateAclField => []]
            ]))->expand(new Tinebase_Record_RecordSet($_record::class, [$_record]));
        }
        if (empty($_record->{$mc->delegateAclField})) {
            throw new Tinebase_Exception_AccessDenied('acl delegation field ' . $mc->delegateAclField .
                ' must not be empty');
        }
        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = $mc->fields[$mc->delegateAclField][Tinebase_ModelConfiguration::CONFIG]
            [Tinebase_ModelConfiguration::CONTROLLER_CLASS_NAME];
        $ctrl = $ctrl::getInstance();
        if ($_record->{$mc->delegateAclField} instanceof Tinebase_Record_RecordSet) {
            if (count($_record->{$mc->delegateAclField}) === 0) {
                return true;
            }
            foreach ($_record->{$mc->delegateAclField} as $delegateRec) {
                if ($ctrl->checkGrant($delegateRec, $_action, false, $_errorMessage, $_oldRecord?->
                {$mc->delegateAclField}->getById($delegateRec->getId()) ?: null)) {
                    return true;
                }
            }
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }

        return $ctrl->checkGrant(
            $_record->{$mc->delegateAclField} instanceof Tinebase_Record_Interface ?
                $_record->{$mc->delegateAclField} :
                $ctrl->get($_record->{$mc->delegateAclField}),
            $_action, $_throw, $_errorMessage, $_oldRecord?->{$mc->delegateAclField}
        );
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_AreaLocked
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        $this->_checkAreaLock($_action);
    }

    /**
     * check area lock
     *
     * @param string $_action {get|create|update|delete}
     * @throws Tinebase_Exception_AreaLocked
     */
    protected function _checkAreaLock($_action)
    {
        if (!Tinebase_AreaLock::getInstance()->isActivatedByFE()) {
            return;
        }

        $check = $this->_applicationName . '.' . $this->getModel() . '.' . $_action;

        if (isset($this->_areaLockValidated[$check])) {
            return;
        }

        if (Tinebase_AreaLock::getInstance()->hasLock($check)) {
            if (Tinebase_AreaLock::getInstance()->isLocked($check)) {
                $teal = new Tinebase_Exception_AreaLocked('Controller action is locked: '
                    . $check);
                $cfg = Tinebase_AreaLock::getInstance()->getLastAuthFailedAreaConfig();
                $teal->setArea($cfg->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME});
                $teal->setMFAUserConfigs($cfg->getUserMFAIntersection(Tinebase_Core::getUser()));
                throw $teal;
            } else {
                $this->_areaLockValidated[$check] = true;
            }
        } else {
            $this->_areaLockValidated[$check] = true;
        }
    }

    /**
     * reset area lock validation
     */
    public function resetValidatedAreaLock()
    {
        $this->_areaLockValidated = [];
    }

    /**
     * Removes records where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     * @return void
     * @throws Tinebase_Exception_Backend
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (! $this->_doContainerACLChecks) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return;
        }

        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            $_filter->andWrapItself();
            $_filter->isImplicit(true);
        }

        $aclFilters = $_filter->getAclFilters();

        if (! $aclFilters) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Force a standard containerFilter (specialNode = all) as ACL filter.');

            $containerFilter = null;
            if (($mc = ($this->_modelName)::getConfiguration())) { /** @var Tinebase_ModelConfiguration $mc */
                if ($containerProp = $mc->{Tinebase_ModelConfiguration::DELEGATED_ACL_FIELD}) {
                    $containerFilter = new Tinebase_Model_Filter_DelegatedAcl($containerProp, null, null,
                        array_merge($_filter->getOptions(),[
                            'modelName' => $this->_modelName
                        ]));
                } elseif ($containerProp = $mc->getContainerProperty()) {
                    $containerFilter = $_filter->createFilter($containerProp, 'specialNode', 'all');
                }
            }
            if (null === $containerFilter) {
                $containerFilter = $_filter->createFilter('container_id', 'specialNode', 'all');
            }
            $containerFilter->isImplicit(true);
            $_filter->addFilter($containerFilter);
        } else {
            /** @var Tinebase_Model_Filter_Abstract $filter */
            foreach ($aclFilters as $filter) {
                if ($filter instanceof Tinebase_Model_Filter_Abstract && str_starts_with($filter->getOperator(), 'not')) {
                    throw new Tinebase_Exception_Backend('acl filters musn\'t be "not[...]"');
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Setting filter grants for action ' . $_action);
        match ($_action) {
            self::ACTION_GET => $_filter->setRequiredGrants($this->_requiredFilterACLget),
            self::ACTION_UPDATE => $_filter->setRequiredGrants($this->_requiredFilterACLupdate),
            'export' => $_filter->setRequiredGrants($this->_requiredFilterACLexport),
            'sync' => $_filter->setRequiredGrants($this->_requiredFilterACLsync),
            default => throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action),
        };
    }

    /**
     * saves alarms of given record
     *
     * @param Tinebase_Record_Interface $_record
     * @return void
     *
     * TODO refactor -> make this public / add acl check if required
     */
    protected function _saveAlarms(Tinebase_Record_Interface $_record)
    {
        if (! $_record->alarms instanceof Tinebase_Record_RecordSet) {
            $_record->alarms = new Tinebase_Record_RecordSet(Tinebase_Model_Alarm::class,
                is_array($_record->alarms) ? $_record->alarms : [], true);
        }
        $alarms = new Tinebase_Record_RecordSet(Tinebase_Model_Alarm::class);

        // create / update alarms
        foreach ($_record->alarms as $alarm) {
            try {
                $this->_inspectAlarmSet($_record, $alarm);
                $alarms->addRecord($alarm);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $teia->getMessage());
            }
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . " About to save " . count($alarms) . " alarms for {$_record->getId()} ");
        $_record->alarms = $alarms;

        Tinebase_Alarm::getInstance()->setAlarmsOfRecord($_record);
    }

    /**
     * inspect alarm and set time
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Model_Alarm $_alarm
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _inspectAlarmSet(Tinebase_Record_Interface $_record, Tinebase_Model_Alarm $_alarm)
    {
        if (! $_record->{$this->_recordAlarmField} instanceof DateTime) {
            throw new Tinebase_Exception_InvalidArgument('alarm reference time is not set');
        }

        $_alarm->setTime($_record->{$this->_recordAlarmField});
    }

    /**
     * get and resolve all alarms of given record(s)
     *
     * @param  T|Tinebase_Record_RecordSet<T> $_record
     */
    public function getAlarms($_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Resolving alarms and add them to record set.");
        
        $records = $_record instanceof Tinebase_Record_RecordSet ? $_record : new Tinebase_Record_RecordSet($this->_modelName, array($_record));

        $alarms = Tinebase_Alarm::getInstance()->getAlarmsOfRecord($this->_modelName, $records);
        
        foreach ($alarms as $alarm) {
            $record = $records->getById($alarm->record_id);
            
            if (!isset($record->alarms)) {
                $record->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
            }
            
            if (!$record->alarms->getById($alarm->getId())) {
                $record->alarms->addRecord($alarm);
            }
        }
        
        foreach ($records as $record) {
            if (!isset($record->alarms)) {
                $record->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
            } else {
                // calc minutes_before
                if ($record->has($this->_recordAlarmField) && $record->{$this->_recordAlarmField} instanceof DateTime) {
                    $this->_inspectAlarmGet($record);
                }
            }
        }
    }

    /**
     * inspect alarms of record (all alarms minutes_before fields are set here by default)
     *
     * @param Tinebase_Record_Interface $_record
     * @return void
     */
    protected function _inspectAlarmGet(Tinebase_Record_Interface $_record)
    {
        $_record->alarms->setMinutesBefore($_record->{$this->_recordAlarmField});
    }

    /**
     * delete alarms for records
     *
     * @param array $_recordIds
     */
    protected function _deleteAlarmsForIds($_recordIds)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Deleting alarms for records " . print_r($_recordIds, true));
        }

        Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord($this->_modelName, $_recordIds);
    }

    /**
     * convert input to recordset
     *
     * input can have the following datatypes:
     * - Tinebase_Model_Filter_FilterGroup
     * - Tinebase_Record_RecordSet
     * - Tinebase_Record_Interface
     * - string (single id)
     * - array (multiple ids)
     *
     * @param boolean $_refresh if this is true, refresh the recordset by calling getMultiple
     * @param Tinebase_Model_Pagination $_pagination
     *          (only valid if $_mixed instanceof Tinebase_Model_Filter_FilterGroup)
     * @return Tinebase_Record_RecordSet
     */
    protected function _convertToRecordSet(mixed $_mixed, $_refresh = false, ?\Tinebase_Model_Pagination $_pagination = null)
    {
        if ($_mixed instanceof Tinebase_Model_Filter_FilterGroup) {
            // FILTER (Tinebase_Model_Filter_FilterGroup)
            $result = $this->search($_mixed, $_pagination);
        } elseif ($_mixed instanceof Tinebase_Record_RecordSet) {
            // RECORDSET (Tinebase_Record_RecordSet)
            $result = ($_refresh) ? $this->_backend->getMultiple($_mixed->getArrayOfIds()) : $_mixed;
        } elseif ($_mixed instanceof Tinebase_Record_Interface) {
            // RECORD (Tinebase_Record_Interface)
            if ($_refresh) {
                $result = $this->_backend->getMultiple($_mixed->getId());
            } else {
                $result = new Tinebase_Record_RecordSet($_mixed::class, array($_mixed));
            }
        } elseif (is_string($_mixed) || is_array($_mixed)) {
            // SINGLE ID or ARRAY OF IDS
            $result = $this->_backend->getMultiple($_mixed);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Could not convert input param to RecordSet: Unsupported type: ' . gettype($_mixed));
            $result = new Tinebase_Record_RecordSet($this->_modelName);
        }

        return $result;
    }

    public function executeDelayedDependentRecords(): void
    {
        $funcs = $this->_delayedDepRecFuncs;
        $this->_delayedDepRecFuncs = [];
        foreach ($funcs as $fun) {
            $fun();
        }
    }

    protected function _getDelayedCrUpFunc(Tinebase_Record_Interface $record, Tinebase_Controller_Record_Abstract $controller, bool $ignoreAcl, bool $create): Closure
    {
        $tmpRecord = clone $record;
        return function() use($tmpRecord, $controller, $ignoreAcl, $create) {
            $ctrlAclRaiii = null;
            if ($ignoreAcl) {
                if (true === $controller->doContainerACLChecks(false)) {
                    $ctrlAclRaiii = new Tinebase_RAII(function () use ($controller) {
                        $controller->doContainerACLChecks(true);
                    });
                }
            }
            if ($create) {
                $controller->create($tmpRecord);
            } else {
                $controller->update($tmpRecord);
            }
            unset($ctrlAclRaiii);
        };
    }

    /**
     * creates dependent record after creating the parent record
     *
     * @param Tinebase_Record_Interface $_createdRecord
     * @param Tinebase_Record_Interface $_record
     * @param string $_property
     * @param array $_fieldConfig
     */
    protected function _createDependentRecord(Tinebase_Record_Interface $_createdRecord, Tinebase_Record_Interface $_record, $_property, $_fieldConfig)
    {
        // records stored e.g. in JSON are also 'dependend' / 'owned'
        if (!($_fieldConfig[TMCC::DEPENDENT_RECORDS] ?? false) || isset($_fieldConfig[TMCC::STORAGE])) {
            return;
        }

        if (! isset($_fieldConfig[TMCC::REF_ID_FIELD])) {
            throw new Tinebase_Exception_Record_DefinitionFailure('If a record is dependent, a refIdField has to be defined!');
        }

        if (!$_record->has($_property) || !$_record->{$_property}) {
            return;
        }

        $recordClassName = $_fieldConfig[TMCC::RECORD_CLASS_NAME];
        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldConfig[TMCC::CONTROLLER_CLASS_NAME];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();
        $ctrlAclRaii = null;
        if ($_fieldConfig[TMCC::IGNORE_ACL] ?? false) {
            if (true === $controller->doContainerACLChecks(false)) {
                $ctrlAclRaii = new Tinebase_RAII(function () use ($controller) {
                    $controller->doContainerACLChecks(true);
                });
            }
        }
        if (($_fieldConfig[TMCC::DELAY_DEPENDENT_RECORDS] ?? false) && !$this->_delayDependentRecords) {
            if (!in_array($controller, $this->_delyedDepRecCtrls)) {
                $this->_delyedDepRecCtrls[] = $controller;
            }
            if (false === $controller->delayDependentRecords(true)) {
                $this->_delayedDepRecRaiis[] = new Tinebase_RAII(function () use ($controller) {
                    $controller->delayDependentRecords(false);
                });
            }
        }

        /** @var Tinebase_Record_Interface $rec */
        // legacy - should be already done in frontend json - remove if all record properties are record sets before getting to controller
        if (is_array($_record->{$_property})) {
            /** @var Tinebase_Record_Interface $rec */
            $rec = new $recordClassName(array(),true);
            $tmp = $_record->{$_property};
            $rec->setFromJsonInUsersTimezone($tmp);

            $_record->{$_property} = $rec;
        }
        // legacy end

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Creating a dependent record on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
        }

        if (strlen((string)$_record->{$_property}->getId()) < 40) {
            $_record->{$_property}->setId(Tinebase_Record_Abstract::generateUID());
        }
        $_record->{$_property}->{$_fieldConfig[TMCC::REF_ID_FIELD]} = $_createdRecord->getId();
        if (isset($_fieldConfig[TMCC::FORCE_VALUES])) {
            foreach ($_fieldConfig[TMCC::FORCE_VALUES] as $prop => $val) {
                $_record->{$_property}->{$prop} = $val;
            }
        }

        if ($this->_delayDependentRecords) {
            $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc($_record->{$_property}, $controller, $_fieldConfig[TMCC::IGNORE_ACL] ?? false, true);
        } else {
            $_createdRecord->{$_property} = $controller->create($_record->{$_property});
        }

        unset($ctrlAclRaii);

    }

    /**
     * updates dependent record on update the parent record
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_oldRecord
     * @param string $_property
     * @param array $_fieldConfig
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _updateDependentRecord(Tinebase_Record_Interface $_record, /** @noinspection PhpUnusedParameterInspection */
                                               Tinebase_Record_Interface $_oldRecord, $_property, $_fieldConfig)
    {
        // records stored e.g. in JSON are also 'dependend' / 'owned'
        if (! isset($_fieldConfig[TMCC::DEPENDENT_RECORDS]) || ! $_fieldConfig[TMCC::DEPENDENT_RECORDS] || isset($_fieldConfig[TMCC::STORAGE])) {
            return;
        }

        if (! isset ($_fieldConfig[TMCC::REF_ID_FIELD])) {
            throw new Tinebase_Exception_Record_DefinitionFailure('If a record is dependent, a refIdField has to be defined!');
        }

        // don't handle dependent records on property if it is set to null or doesn't exist.
        if ($_record->{$_property} === null || ! $_record->has($_property)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Skip updating dependent record (got null) on property ' . $_property . ' for '
                    . $this->_applicationName . ' ' . $this->_modelName . ' with id = "' . $_record->getId() . '"');
            }
            return;
        }

        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldConfig[TMCC::CONTROLLER_CLASS_NAME];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();
        $ctrlAclRaii = null;
        if ($_fieldConfig[TMCC::IGNORE_ACL] ?? false) {
            if (true === $controller->doContainerACLChecks(false)) {
                $ctrlAclRaii = new Tinebase_RAII(function () use ($controller) {
                    $controller->doContainerACLChecks(true);
                });
            }
        }
        if (($_fieldConfig[TMCC::DELAY_DEPENDENT_RECORDS] ?? false) && !$this->_delayDependentRecords) {
            if (!in_array($controller, $this->_delyedDepRecCtrls)) {
                $this->_delyedDepRecCtrls[] = $controller;
            }
            if (false === $controller->delayDependentRecords(true)) {
                $this->_delayedDepRecRaiis[] = new Tinebase_RAII(function () use ($controller) {
                    $controller->delayDependentRecords(false);
                });
            }
        }

        $recordClassName = $_fieldConfig[TMCC::RECORD_CLASS_NAME];
        $filter = [['field' => $_fieldConfig[TMCC::REF_ID_FIELD], 'operator' => 'equals', 'value' => $_record->getId()]];
        if (isset($_fieldConfig[TMCC::ADD_FILTERS])) {
            $filter = array_merge($filter, $_fieldConfig[TMCC::ADD_FILTERS]);
        }
        $existingDepRec = ($exRecs = $controller->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            $recordClassName,$filter)))->getFirstRecord();
        if ($exRecs->count() > 1) {
            $exRecs->removeRecord($existingDepRec);
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                ' found more than one dependent record, deleting: ' . print_r($exRecs->getArrayOfIds(), true));
            $controller->delete($exRecs->getArrayOfIds());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($_record->{$_property}, true));
        }

        // legacy - should be already done in frontend json - remove if all record properties are record instances before getting to controller
        if (is_array($_record->{$_property}) && ! empty($_record->{$_property})) {
            $rec = new $recordClassName(array(),true);
            $tmp = $_record->{$_property};
            $rec->setFromJsonInUsersTimezone($tmp);
            $_record->{$_property} = $rec;
        }
        //legacy end

        if ($_record->{$_property} instanceof $recordClassName) {

            $_record->{$_property}->{$_fieldConfig[TMCC::REF_ID_FIELD]} = $_record->getId();
            if (isset($_fieldConfig[TMCC::FORCE_VALUES])) {
                foreach ($_fieldConfig[TMCC::FORCE_VALUES] as $prop => $val) {
                    $_record->{$_property}->{$prop} = $val;
                }
            }

            if ($this->_delayDependentRecords) {
                $create = true;
                if ($existingDepRec) {
                    $_record->{$_property}->setId($existingDepRec->getId());
                    $create = false;
                }
                $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc($_record->{$_property}, $controller, $_fieldConfig[TMCC::IGNORE_ACL] ?? false, $create);
            } else {
                if ($existingDepRec) {
                    $_record->{$_property}->setId($existingDepRec->getId());
                    $_record->{$_property} = $controller->update($_record->{$_property});
                } else {
                    $_record->{$_property} = $controller->create($_record->{$_property});
                }
            }

        } elseif ($existingDepRec) {
            $controller->delete($existingDepRec);
        }
        unset($ctrlAclRaii);
    }

    protected function _handleCreateRecords(Tinebase_Record_Interface $_record): void
    {
        if ($config = $_record::getConfiguration()) {
            if (is_array($config->recordsFields)) {
                foreach ($config->recordsFields as $property => $fieldDef) {
                    if ($fieldDef[TMCC::CONFIG][TMCC::CREATE] ?? false) {
                        if ($_record->{$property} instanceof Tinebase_Record_RecordSet) {
                            $this->_createCreateRecords($_record->{$property}, $_record, $property, $fieldDef);
                        }
                    }
                }
            }
            if (is_array($config->recordFields)) {
                foreach ($config->recordFields as $property => $fieldDef) {
                    if ($fieldDef[TMCC::CONFIG][TMCC::CREATE] ?? false) {
                        if ($_record->{$property} instanceof Tinebase_Record_Interface) {
                            $this->_createCreateRecords(new Tinebase_Record_RecordSet($_record->{$property}::class, [$_record->{$property}]), $_record, $property, $fieldDef);
                        }
                    }
                }
            }
        }
    }

    protected function _createCreateRecords(Tinebase_Record_RecordSet $_records, Tinebase_Record_Interface $_record, string $property, $_fieldDef): void
    {
        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldDef[TMCC::CONFIG][TMCC::CONTROLLER_CLASS_NAME];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();

        $ctrlAclRaii = null;
        if ($_fieldDef[TMCC::CONFIG][TMCC::IGNORE_ACL] ?? false) {
            if (true === $controller->doContainerACLChecks(false)) {
                $ctrlAclRaii = new Tinebase_RAII(function () use ($controller) {
                    $controller->doContainerACLChecks(true);
                });
            }
        }

        $records = new Tinebase_Record_RecordSet($_records->getRecordClassName());

        if (!empty($ids = $_records->getArrayOfIds())) {
            foreach ($controller->has($ids, true) as $id) {
                $records->addRecord($_records->getById($id));
                $_records->removeById($id);
            }
        }


        foreach ($_records as $record) {
            $records->addRecord($controller->create($record));
        }

        $_record->{$property} = $_fieldDef[TMCC::TYPE] === TMCC::TYPE_RECORDS ? $records : $records->getFirstRecord();

        unset($ctrlAclRaii);
    }

    /**
     * creates dependent records after creating the parent record
     *
     * @param Tinebase_Record_Interface $_createdRecord
     * @param Tinebase_Record_Interface $_record
     * @param string $_property
     * @param array $_fieldConfig
     */
    protected function _createDependentRecords(Tinebase_Record_Interface $_createdRecord, Tinebase_Record_Interface $_record, $_property, $_fieldConfig)
    {
        if (! (isset($_fieldConfig['dependentRecords']) || array_key_exists('dependentRecords', $_fieldConfig)) || ! $_fieldConfig['dependentRecords'] ||
                (isset($_fieldConfig[TMCC::STORAGE]) && $_fieldConfig[TMCC::STORAGE] === TMCC::TYPE_JSON)) {
            return;
        }
        
        if (!$_record->has($_property) || !$_record->{$_property}) {
            return;
        }

        $recordClassName = $_fieldConfig['recordClassName'];
        $new = new Tinebase_Record_RecordSet($recordClassName);
        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldConfig['controllerClassName'];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();

        $ctrlAclRaii = null;
        if ($_fieldConfig[TMCC::IGNORE_ACL] ?? false) {
            if (true === $controller->doContainerACLChecks(false)) {
                $ctrlAclRaii = new Tinebase_RAII(function () use ($controller) {
                    $controller->doContainerACLChecks(true);
                });
            }
        }
        if (($_fieldConfig[TMCC::DELAY_DEPENDENT_RECORDS] ?? false) && !$this->_delayDependentRecords) {
            if (!in_array($controller, $this->_delyedDepRecCtrls)) {
                $this->_delyedDepRecCtrls[] = $controller;
            }
            if (false === $controller->delayDependentRecords(true)) {
                $this->_delayedDepRecRaiis[] = new Tinebase_RAII(function () use ($controller) {
                    $controller->delayDependentRecords(false);
                });
            }
        }

        /** @var Tinebase_Record_Interface $rec */
        // legacy - should be already done in frontend json - remove if all record properties are record sets before getting to controller
        if (is_array($_record->{$_property})) {
            $rs = new Tinebase_Record_RecordSet($recordClassName);
            foreach ($_record->{$_property} as $recordArray) {
                /** @var Tinebase_Record_Interface $rec */
                $rec = new $recordClassName(array(),true);
                $rec->setFromJsonInUsersTimezone($recordArray);

                if (strlen((string)$rec->getId()) < 40) {
                    $rec->setId(Tinebase_Record_Abstract::generateUID());
                    $rs->addRecord($rec);
                } elseif ([$rec->getId()] === $controller->has([$rec->getId()])) {
                    $rec->{$_fieldConfig['refIdField']} = $_createdRecord->getId();
                    if (isset($_fieldConfig[TMCC::FORCE_VALUES])) {
                        foreach ($_fieldConfig[TMCC::FORCE_VALUES] as $prop => $val) {
                            $rec->{$prop} = $val;
                        }
                    }
                    if ($this->_delayDependentRecords) {
                        $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc($rec, $controller, $_fieldConfig[TMCC::IGNORE_ACL] ?? false, false);
                    } else {
                        $new->addRecord($controller->update($rec));
                    }
                }
            }
            $_record->{$_property} = $rs;
        } else {
            foreach ($_record->{$_property} as $rec) {
                if (strlen((string)$rec->getId()) < 40) {
                    $rec->setId(Tinebase_Record_Abstract::generateUID());
                } elseif ([$rec->getId()] === $controller->has([$rec->getId()])) {
                    $_record->{$_property}->removeRecord($rec);
                    $rec->{$_fieldConfig['refIdField']} = $_createdRecord->getId();
                    if (isset($_fieldConfig[TMCC::FORCE_VALUES])) {
                        foreach ($_fieldConfig[TMCC::FORCE_VALUES] as $prop => $val) {
                            $rec->{$prop} = $val;
                        }
                    }
                    if ($this->_delayDependentRecords) {
                        $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc($rec, $controller, $_fieldConfig[TMCC::IGNORE_ACL] ?? false, false);
                    } else {
                        $new->addRecord($controller->update($rec));
                    }
                }
            }
        }
        // legacy end

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Creating ' . $_record->{$_property}->count() . ' dependent records on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
        }

        if (isset($_fieldConfig[TMCC::FORCE_VALUES])) {
            foreach ($_fieldConfig[TMCC::FORCE_VALUES] as $prop => $val) {
                $_record->{$_property}->{$prop} = $val;
            }
        }
        foreach ($_record->{$_property} as $record) {
            $record->{$_fieldConfig['refIdField']} = $_createdRecord->getId();
            if (! $record->getId() || strlen((string)$record->getId()) != 40) {
                $record->{$record->getIdProperty()} = null;
            }
            if ($this->_delayDependentRecords) {
                $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc($record, $controller, $_fieldConfig[TMCC::IGNORE_ACL] ?? false, true);
            } else {
                $new->addRecord($controller->create($record));
            }
        }

        $_createdRecord->{$_property} = $new;

        unset($ctrlAclRaii);
    }

    /**
     * updates dependent records on update the parent record
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface $_oldRecord
     * @param string $_property
     * @param array $_fieldConfig
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _updateDependentRecords(Tinebase_Record_Interface $_record, /** @noinspection PhpUnusedParameterInspection */
                                               Tinebase_Record_Interface $_oldRecord, $_property, $_fieldConfig)
    {
        // records stored e.g. in JSON are also 'dependend' / 'owned'
        if (! (isset($_fieldConfig['dependentRecords']) || array_key_exists('dependentRecords', $_fieldConfig)) || ! $_fieldConfig['dependentRecords'] ||
                (isset($_fieldConfig[TMCC::STORAGE]) && $_fieldConfig[TMCC::STORAGE] === TMCC::TYPE_JSON)) {
            return;
        }
        
        if (! isset ($_fieldConfig['refIdField'])) {
            throw new Tinebase_Exception_Record_DefinitionFailure('If a record is dependent, a refIdField has to be defined!');
        }
        
        // don't handle dependent records on property if it is set to null or doesn't exist.
        if (($_record->{$_property} === null) || (! $_record->has($_property))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Skip updating dependent records (got null) on property ' . $_property . ' for '
                    . $this->_applicationName . ' ' . $this->_modelName . ' with id = "' . $_record->getId() . '"');
            }
            return;
        }

        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldConfig['controllerClassName'];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();
        $recordClassName = $_fieldConfig['recordClassName'];
        $filterClassName = $_fieldConfig['filterClassName'];
        /** @var Tinebase_Record_RecordSet|Tinebase_Record_Interface $existing */
        $existing = new Tinebase_Record_RecordSet($recordClassName);

        $ctrlAclRaii = null;
        if ($_fieldConfig[TMCC::IGNORE_ACL] ?? false) {
            if (true === $controller->doContainerACLChecks(false)) {
                $ctrlAclRaii = new Tinebase_RAII(function () use ($controller) {
                    $controller->doContainerACLChecks(true);
                });
            }
        }
        if (($_fieldConfig[TMCC::DELAY_DEPENDENT_RECORDS] ?? false) && !$this->_delayDependentRecords) {
            if (!in_array($controller, $this->_delyedDepRecCtrls)) {
                $this->_delyedDepRecCtrls[] = $controller;
            }
            if (false === $controller->delayDependentRecords(true)) {
                $this->_delayedDepRecRaiis[] = new Tinebase_RAII(function () use ($controller) {
                    $controller->delayDependentRecords(false);
                });
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($_record->{$_property}, true));
        }

        // legacy - should be already done in frontend json - remove if all record properties are record sets before getting to controller
        if (is_array($_record->{$_property})) {
            $rs = new Tinebase_Record_RecordSet($recordClassName);
            foreach ($_record->{$_property} as $recordArray) {
                /** @var Tinebase_Record_Interface $rec */
                $rec = new $recordClassName(array(),true);
                $rec->setFromJsonInUsersTimezone($recordArray);
                $rs->addRecord($rec);
            }
            $_record->{$_property} = $rs;
        }
        
        if (! empty($_record->{$_property}) && $_record->{$_property} && ! is_scalar($_record->{$_property})
            && $_record->{$_property}->count() > 0) {
            if (isset($_fieldConfig[TMCC::FORCE_VALUES])) {
                foreach ($_fieldConfig[TMCC::FORCE_VALUES] as $prop => $val) {
                    $_record->{$_property}->{$prop} = $val;
                }
            }

            /** @var Tinebase_Record_Interface $record */
            foreach ($_record->{$_property} as $record) {
                $record->{$_fieldConfig['refIdField']} = $_record->getId();

                $create = false;
                if (!empty($record->getId())) {
                    try {

                        $prevRecord = $controller->get($record->getId());
                        if ($prevRecord->{$_fieldConfig['refIdField']} !== $prevRecord->{$_fieldConfig['refIdField']}) {
                            throw new Tinebase_Exception_UnexpectedValue('refId mismatch');
                        }

                        if (!empty($prevRecord->diff($record)->diff)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                    . ' Updating dependent record with id = "' . $record->getId()
                                    . '" on property ' . $_property . ' for ' . $this->_applicationName
                                    . ' ' . $this->_modelName);
                            }
                            if ($this->_delayDependentRecords) {
                                $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc(
                                    $record,
                                    $controller,
                                    $_fieldConfig[TMCC::IGNORE_ACL] ?? false,
                                    false);
                                $existing->addRecord($record);
                            } else {
                                $existing->addRecord($controller->update($record));
                            }
                        } else {
                            $existing->addRecord($record);
                        }

                    } catch (Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                . ' ' . $tenf->getMessage());
                        }
                        $create = true;
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Record has no ID -> create');
                    }
                    $create = true;
                    $record->setId(Tinebase_Record_Abstract::generateUID());
                }

                if (true === $create) {
                    if ($this->_delayDependentRecords) {
                        $this->_delayedDepRecFuncs[] = $this->_getDelayedCrUpFunc($record, $controller, $_fieldConfig[TMCC::IGNORE_ACL] ?? false, true);
                        $crc = $record;
                    } else {
                        $crc = $controller->create($record);
                        $existing->addRecord($crc);
                    }

                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating dependent record with id = "' . $crc->getId() . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
                    }
                }
            }
        }

        $filterArray = $_fieldConfig['addFilters'] ?? [];
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterClassName, $filterArray, 'AND',
            $_fieldConfig[TMCC::FILTER_OPTIONS] ?? []);

        $filter->addFilter($filter->createFilter($_fieldConfig['refIdField'], 'equals', $_record->getId()));

        // an empty array will remove all records on this property
        if (! empty($_record->{$_property})) {
            $filter->addFilter($filter->createFilter('id', 'notin', $existing->getId()));
        }

        $deleteIds = $controller->search($filter, null, false, true);

        if (! empty($deleteIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Deleting dependent records with id = "' . print_r($deleteIds, 1) . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
            }
            $controller->delete($deleteIds);
        }
        $_record->{$_property} = $existing;

        unset($ctrlAclRaii);
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param string $_property
     * @param array $_fieldConfig
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _deleteDependentRecords($_record, $_property, $_fieldConfig)
    {
        if (! isset($_fieldConfig['dependentRecords']) || ! $_fieldConfig['dependentRecords'] ||
                (isset($_fieldConfig[TMCC::STORAGE]) && $_fieldConfig[TMCC::STORAGE] === TMCC::TYPE_JSON)) {
            return;
        }

        if (! isset ($_fieldConfig['refIdField'])) {
            throw new Tinebase_Exception_Record_DefinitionFailure('If a record is dependent, a refIdField has to be defined!');
        }

        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldConfig['controllerClassName'];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();
        $filterClassName = $_fieldConfig['filterClassName'];

        $ctrlAclRaii = null;
        if (isset($_fieldConfig[TMCC::IGNORE_ACL]) && $_fieldConfig[TMCC::IGNORE_ACL]) {
            $oldCtrlAclVal = $controller->doContainerACLChecks(false);
            $ctrlAclRaii = new Tinebase_RAII(function() use($oldCtrlAclVal, $controller) {
                $controller->doContainerACLChecks($oldCtrlAclVal);
            });
        }

        $filterArray = $_fieldConfig['addFilters'] ?? [];
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filterClassName, $filterArray, 'AND');

        //try {
          //  $filter->addFilter($filter->createFilter($_fieldConfig['refIdField'], 'equals', $_record->getId()));

            // TODO fix this:
            // bad work around. Fields of type record return ForeignId Filter, but that filter can not do equals.
            // remove try  catch and look for
            /*     Sales_ControllerTest.testAddDeleteProducts
    Sales_JsonTest.testSearchContracts
    Sales_JsonTest.testSearchContractsByProduct
    Sales_JsonTest.testSearchEmptyDateTimeFilter
    Sales_JsonTest.testAdvancedContractsSearch
    Sales_InvoiceJsonTests.testCRUD
    Sales_InvoiceJsonTests.testSanitizingProductId
    HumanResources_JsonTests.testEmployee
    HumanResources_JsonTests.testDateTimeConversion
    HumanResources_JsonTests.testContractDates
    HumanResources_JsonTests.testAddContract
    HumanResources_JsonTests.testSavingRelatedRecord
    HumanResources_JsonTests.testSavingRelatedRecordWithCorruptId
    HumanResources_CliTests.testSetContractsEndDate */

        //} catch (Tinebase_Exception_UnexpectedValue $teuv) {
            $filter->addFilter(new Tinebase_Model_Filter_Id($_fieldConfig['refIdField'], 'equals', $_record->getId()));
        //}
        $deleteIds = $controller->search($filter, null, false, true);

        if (! empty($deleteIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' Deleting dependent records with id = "' . print_r($deleteIds, 1) . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
            }
            $controller->delete($deleteIds);
        }

        unset($ctrlAclRaii);
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @param string $_property
     * @param array $_fieldConfig
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _undeleteDependentRecords($_record, $_property, $_fieldConfig)
    {
        if (! isset($_fieldConfig['dependentRecords']) || ! $_fieldConfig['dependentRecords']) {
            return;
        }

        if (! isset ($_fieldConfig['refIdField'])) {
            throw new Tinebase_Exception_Record_DefinitionFailure('If a record is dependent, a refIdField has to be defined!');
        }


        /** @var Tinebase_Controller_Interface $ccn */
        $ccn = $_fieldConfig['controllerClassName'];
        /** @var Tinebase_Controller_Record_Abstract $controller */
        $controller = $ccn::getInstance();
        if (!$controller->modlogActive()) {
            return;
        }

        $ctrlAclRaii = null;
        if (isset($_fieldConfig[TMCC::IGNORE_ACL]) && $_fieldConfig[TMCC::IGNORE_ACL]) {
            $oldCtrlAclVal = $controller->doContainerACLChecks(false);
            $ctrlAclRaii = new Tinebase_RAII(function() use($oldCtrlAclVal, $controller) {
                $controller->doContainerACLChecks($oldCtrlAclVal);
            });
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . $_property);

        /** @var Tinebase_Record_Interface $subModel */
        $subModel = $_fieldConfig['recordClassName'];
        $uniques = [];
        $uniqueData = [];
        if (($subMC = $subModel::getConfiguration()) && $subMC->hasDeletedTimeUnique) {
            foreach ($subMC->table[Tinebase_ModelConfiguration_Const::UNIQUE_CONSTRAINTS] ?? [] as $constraint) {
                if (false !== ($pos = array_search(Tinebase_ModelConfiguration_Const::FLD_DELETED_TIME, $constraint[Tinebase_ModelConfiguration_Const::COLUMNS]))) {
                    $tmp = $constraint[Tinebase_ModelConfiguration_Const::COLUMNS];
                    unset($tmp[$pos]);
                    $uniques[] = $tmp;
                }
            }
        }
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($subModel, $_fieldConfig['addFilters'] ?? [], 'AND');
        //try {
        //  $filter->addFilter($filter->createFilter($_fieldConfig['refIdField'], 'equals', $_record->getId()));

        // TODO fix this:
        // bad work around. Fields of type record return ForeignId Filter, but that filter can not do equals.
        // remove try  catch and look for
        /*     Sales_ControllerTest.testAddDeleteProducts
Sales_JsonTest.testSearchContracts
Sales_JsonTest.testSearchContractsByProduct
Sales_JsonTest.testSearchEmptyDateTimeFilter
Sales_JsonTest.testAdvancedContractsSearch
Sales_InvoiceJsonTests.testCRUD
Sales_InvoiceJsonTests.testSanitizingProductId
HumanResources_JsonTests.testEmployee
HumanResources_JsonTests.testDateTimeConversion
HumanResources_JsonTests.testContractDates
HumanResources_JsonTests.testAddContract
HumanResources_JsonTests.testSavingRelatedRecord
HumanResources_JsonTests.testSavingRelatedRecordWithCorruptId
HumanResources_CliTests.testSetContractsEndDate */

        //} catch (Tinebase_Exception_UnexpectedValue $teuv) {
        $filter->addFilter(new Tinebase_Model_Filter_Id($_fieldConfig['refIdField'], 'equals', $_record->getId()));
        $filter->addFilter(new Tinebase_Model_Filter_Bool('is_deleted', 'equals', 1));
        //}
        $unDeleteRecords = $controller->search($filter, new Tinebase_Model_Pagination([
            Tinebase_Model_Pagination::FLD_SORT => Tinebase_ModelConfiguration_Const::FLD_DELETED_TIME,
            Tinebase_Model_Pagination::FLD_DIR => 'DESC',
        ]));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO) && $unDeleteRecords->count() > 0) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__. ' undeleting dependent records with id = "' . print_r($unDeleteRecords->getArrayOfIds(), 1) . '" on property ' . $_property . ' for ' . $this->_applicationName . ' ' . $this->_modelName);
        }
        /** @var Tinebase_Record_Interface $record */
        foreach ($unDeleteRecords as $record) {
            foreach ($uniques as $offset => $props) {
                $key = '';
                $first = true;
                foreach ($props as $property) {
                    try {
                        $key .= ($first ? '' : '-') . strtolower($record->getIdFromProperty($property));
                    } catch (Tinebase_Exception_UnexpectedValue) {}
                    $first = false;
                }
                if ($uniqueData[$offset][$key] ?? false) {
                    continue 2;
                }
                $uniqueData[$offset][$key] = true;
            }
            $controller->unDelete($record);
        }

        unset($ctrlAclRaii);
    }

    // we dont want to mention the throw there, or it would be reflected everywhere
    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * send notifications
     *
     * @param T  $_record
     * @param Tinebase_Model_FullUser    $_updater
     * @param String                     $_action
     * @param ?T  $_oldRecord
     * @param Array                      $_additionalData
     */
    public function doSendNotifications(/** @noinspection PhpUnusedParameterInspection */
        Tinebase_Record_Interface $_record,
        Tinebase_Model_FullUser $_updater,
        $_action,
        ?\Tinebase_Record_Interface $_oldRecord = null,
        array $_additionalData = array()
    ) {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * @param Tinebase_Model_Container $_container
     * @param bool $_ignoreAcl
     * @param null $_filter
     */
    public function deleteContainerContents(Tinebase_Model_Container $_container, $_ignoreAcl = false, $_filter = null)
    {
        $model = $_container->model;
        $filterName = $model . 'Filter';

        // workaround to fix Filemanager as we don't want to delete container contents when moving folders
        // TODO find a better solution here - needs Filemanager refactoring
        if (! in_array($model, array('Filemanager_Model_Node')) &&
            method_exists($this->_backend, 'search') && ($_filter !== null || class_exists($filterName))) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Delete ' . $model . ' records in container ' . $_container->getId());

            if (null === $_filter) {
                /** @var Tinebase_Model_Filter_FilterGroup $_filter */
                $_filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    $model,
                    [],
                    Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                    ['ignoreAcl' => $_ignoreAcl]
                );

                // we add the container_id filter like this because Calendar Filters have special behaviour that we want to avoid
                // alternatively the calender event controller would have to overwrite this method and deal with this application
                // specifics itself. But for the time being, this seems like a good generic solution
                $_filter->addFilter(new Tinebase_Model_Filter_Id('container_id', 'equals', $_container->id));
            }

            if ($_filter->getFilter('container_id', false, true) === null) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' no container filter in model -> skip');
                return;
            }

            if ($_ignoreAcl) {
                $idsToDelete = $this->_backend->search($_filter, null, /* $_onlyIds */true);
                $this->delete($idsToDelete);
            } else {
                $this->deleteByFilter($_filter);
            }
        }
    }

    /**
     * if dependent records should not be handled, set this to false
     *
     * @param bool $toggle
     * @return bool
     */
    public function setHandleDependentRecords($bool = null)
    {
        $oldValue = $this->_handleDependentRecords;
        if (null !== $bool) {
            $this->_handleDependentRecords = (bool)$bool;
        }
        return $oldValue;
    }

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false)
    {
        return $this->_backend->has($_ids, $_getDeleted);
    }

    /**
     * get resolved group records
     * NOTE: modelconfig only!
     *
     * TODO replace converter usage when we have refactored the record resolving
     *
     * @template T1 of Tinebase_Record_Interface
     * @param T $record
     * @param class-string<T1> $foreignModel
     * @param $groupField
     * @param $idProp
     * @return Tinebase_Record_RecordSet<T1>
     */
    public function getResolvedGroupRecords(Tinebase_Record_Interface $record, $foreignModel, $groupField, $idProp)
    {
        $record = $this->get($record->getId());

        // use converter to resolve the foreign records recursivly
        // NOTE: you have to activate 'recursiveResolving' for the 'records' field
        // TODO: replace this when we have better resolving in the controllers
        $converter = Tinebase_Convert_Factory::factory($this->_modelName);
        $converter->setRecursiveResolve(true);
        $recordArray = $converter->fromTine20Model($record);

        $result = new Tinebase_Record_RecordSet($foreignModel);
        $foreignData = isset($recordArray[$groupField]) && is_array($recordArray[$groupField]) ? $recordArray[$groupField] : array();

        foreach ($foreignData as $groupArray) {
            $record = new $foreignModel(array(), true);
            $record->setFromJsonInUsersTimezone($groupArray[$idProp]);
            $result->addRecord($record);
        }
        return $result;
    }

    /**
     * file message as record attachment
     *
     * @param Felamimail_Model_MessageFileLocation $location
     * @param Felamimail_Model_Message $message
     * @return T|null
     * @throws Zend_Db_Statement_Exception
     */
    public function fileMessage(Felamimail_Model_MessageFileLocation $location, Felamimail_Model_Message $message)
    {
        $recordId = is_string($location['record_id'])
            ? $location['record_id']
            : (!empty($location['record_id']['id']) ? $location['record_id']['id'] : null);
        try {
            if (is_array($location['record_id']) && !$recordId) {
                /** @var Tinebase_Record_Interface $recordData */
                $recordData = new $location['model']([], true);
                $tmp = $location['record_id'];
                $recordData->setFromJsonInUsersTimezone($tmp);
                $record = $this->create($recordData);
            } else {
                $record = $this->get($recordId);
            }

            if (! $record->getId()) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Record has no ID: ' . print_r($record->toArray(), true));
                return null;
            }
        } catch (Tinebase_Exception_NotFound) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Record not found');
            return null;
        }

        $this->checkGrant($record, self::ACTION_UPDATE, true, 'No permission to update record.');

        $tempFile = Felamimail_Controller_Message::getInstance()->putRawMessageIntoTempfile($message);
        $filename = Felamimail_Controller_Message::getInstance()->getMessageNodeFilename($message);

        $node = $this->_addTempfileAttachment($record, $filename, $tempFile);
        if (! $node) {
            return null;
        }
        Felamimail_Controller_MessageFileLocation::getInstance()->createMessageLocationForRecord($message, $location, $record, $node);
        $this->_setFileMessageNote($record, $node);

        return $record;
    }

    protected function _addTempfileAttachment($record, $filename, $tempFile): ?Tinebase_Model_Tree_Node
    {
        try {
            $node = Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment($record, $filename, $tempFile);

        } catch (Tinebase_Exception_Duplicate) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' ' . $filename . ' already exists');
            return null;
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Exception::isDbDuplicate($zdse)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Location already exists');
            } else {
                throw $zdse;
            }
            return null;
        }
        return $node;
    }

    protected function _setFileMessageNote($record, $node)
    {
        $translation = Tinebase_Translation::getTranslation();
        $noteText = str_replace(
            ['{0}'],
            [$node->name],
            $translation->_("A Message has been filed to this record. Subject: {0}")
        );

        // TODO add link to node attachment (like attachment icon in grid)
        
        $note = new Tinebase_Model_Note([
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_EMAIL,
            'restricted_to'     => null,
            'note'              => mb_substr($noteText, 0, Tinebase_Notes::MAX_NOTE_LENGTH),
            'record_model'      => $this->_modelName,
            'record_backend'    => ucfirst(strtolower('sql')),
            'record_id'         => $record->getId(),
        ]);
        if (!$record->notes instanceof Tinebase_Record_RecordSet) {
            $record->notes = new Tinebase_Record_RecordSet(Tinebase_Model_Note::class);
        }
        $record->notes->addRecord($note);
        Tinebase_Notes::getInstance()->setNotesOfRecord($record);
    }

    public static function cascadeDenormalization(string $targetModel, Tinebase_Record_Interface $_record)
    {
        /** @var Tinebase_Record_Interface $targetModel */
        $mc = $targetModel::getConfiguration();
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($targetModel, [
            ['field' => TMCC::FLD_ORIGINAL_ID, 'operator' => 'equals', 'value' => $_record->getId()],
        ]);
        if (isset($mc->{TMCC::DENORMALIZATION_CONFIG}[TMCC::TRACK_CHANGES]) &&
                $mc->{TMCC::DENORMALIZATION_CONFIG}[TMCC::TRACK_CHANGES]) {
            $filter->addFilter(new Tinebase_Model_Filter_Bool(TMCC::FLD_LOCALLY_CHANGED, 'equals', false));
        }

        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = Tinebase_Core::getApplicationInstance($targetModel);
        /** @var Tinebase_Record_Interface $toUpdate */
        foreach ($ctrl->search($filter) as $toUpdate) {
            $changed = false;
            foreach ($_record::getConfiguration()->fields as $key => $cfg) {
                if (!$toUpdate->has($key) || in_array($key, ['id', 'seq', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time', 'deleted_by', 'deleted_time', 'is_deleted'])) continue;
                if ($toUpdate->{$key} !== $_record->{$key}) {
                    $changed = true;
                    $toUpdate->{$key} = $_record->{$key};
                }
            }
            if ($changed) {
                $ctrl->update($toUpdate);
            }
        }
    }

    public function fileMessageAttachment($location, $message, $attachment, $forceOverwrite = false): ?Tinebase_Model_Tree_Node
    {
        $recordId = is_array($location['record_id']) && isset($location['record_id']['id'])
            ? $location['record_id']['id']
            : $location['record_id'];
        $record = $this->get($recordId);

        $tempFile = Felamimail_Controller_Message::getInstance()->putRawMessageIntoTempfile(
            $message,
            $attachment['partId']);
        $filename = $this->_getfiledAttachmentFilename($attachment, $message);

        return $this->_addTempfileAttachment($record, $filename, $tempFile);
    }

    protected function _getfiledAttachmentFilename($attachment, $message)
    {
        return ! empty($attachment['filename'])
            ? $attachment['filename']
            : Felamimail_Controller_Message::getInstance()->getMessageNodeFilename($message)
            . 'part_' . $attachment['partId'];
    }

    /**
     * @return class-string<T>
     */
    public function getModel()
    {
        return $this->_modelName;
    }

    /**
     * @param $property
     * @param bool $_getRelatedData
     * @return null|T
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_AreaLocked
     */
    public function getRecordByTitleProperty($property, $_getRelatedData = true)
    {
        $modelName = $this->_modelName;

        try {
            $modelConfig = $modelName::getConfiguration();
        } catch (Exception) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . $modelName . 'don´t have modelConfig');
            return null;
        }

        $titleProperty = $modelConfig->titleProperty;


        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($modelName, [
            ['field' => $titleProperty, 'operator' => 'equals', 'value' => $property]
        ]);
        $record = $this->search($filter)->getFirstRecord();

        if ($_getRelatedData && $record != null) {
            $this->_getRelatedData($record);
        }

        return $record;
    }

    /**
     * validate state
     *
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface|null $_oldRecord
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _validateTransitionState(string $_field, array $_config, Tinebase_Record_Interface $_record,
                                      ?\Tinebase_Record_Interface $_oldRecord = null)
    {
        $currentStatus = $_record->{$_field};
        $oldStatus = $_oldRecord ? $_oldRecord->{$_field} : '';
        $targetStatus = $_config[$oldStatus][Tinebase_Config_Abstract::TRANSITION_TARGET_STATUS] ?? null;

        if (empty($currentStatus)) {
            throw new Tinebase_Exception_UnexpectedValue('status is not set');
        }

        if ($oldStatus !== $currentStatus) {
            if (!$targetStatus) {
                throw new Tinebase_Exception_UnexpectedValue('targetStatus in transitions : ' . $oldStatus
                    . ' is not set');
            }
            if (!in_array($currentStatus, $targetStatus)) {
                $translation = Tinebase_Translation::getTranslation();
                throw new Tinebase_Exception_SystemGeneric(sprintf(
                    $translation->_('Status %s is not valid.'),
                    $currentStatus
                ));
            }
        }
    }

    /**
     * renames records with duplicate (string) property
     *
     * @param string $duplicateProperty
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function renameDuplicateRecords(string $duplicateProperty): int
    {
        // TODO we should introduce pagination to make sure we don't run into memory limit
        // TODO allow to ignore acl
        $records = $this->getAll();

        $changedCount = 0;
        while (null !== ($record = $records->getFirstRecord())) {
            // first find all containers sorted by creation_time
            $duplicate = $records->filter($duplicateProperty, $record->{$duplicateProperty});
            $duplicate->sort('creation_time', 'ASC');

            $records->removeRecord($duplicate->getFirstRecord());
            $duplicate->removeFirst();
            if ($duplicate->count() > 0) {

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Duplicates found: ' . print_r($duplicate->getArrayOfIds(), true));

                $renameCounter = 1;
                foreach ($duplicate as $dup) {
                    $dup->{$duplicateProperty} .= '(' . ($renameCounter++) . ')';
                    // skip validation/acl/inspection stuff here
                    $this->getBackend()->update($dup);
                }
                $changedCount += $duplicate->count();
                $records->removeRecords($duplicate);
            }
        }

        return $changedCount;
    }
}
