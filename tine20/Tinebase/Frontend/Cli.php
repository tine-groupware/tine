<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 * @subpackage  Frontend
 */
class Tinebase_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';

    /**
     * needed by demo data fns
     *
     * @var array
     */
    protected $_applicationsToWorkOn = array();

    /**
     * @param Zend_Console_Getopt $opts
     * @return boolean success
     */
    public function increaseReplicationMasterId($opts)
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($opts, array());
        $count = $args['count'] ?? 1;

        Tinebase_Timemachine_ModificationLog::getInstance()->increaseReplicationMasterId($count);

        return true;
    }

    public function sanitizeFSMimeTypes()
    {
        $this->_checkAdminRight();
        Tinebase_FileSystem::getInstance()->sanitizeMimeTypes();
        return true;
    }

    /**
     * @param Zend_Console_Getopt $opts
     * @return boolean success
     */
    public function readModifictionLogFromMaster($opts)
    {
        $this->_checkAdminRight();

        Tinebase_Timemachine_ModificationLog::getInstance()->readModificationLogFromMaster();

        return true;
    }

    /**
     * rebuildPaths
     *
    * @param Zend_Console_Getopt $opts
    * @return integer success
    */
    public function rebuildPaths($opts)
    {
        $this->_checkAdminRight();

        $result = Tinebase_Controller::getInstance()->rebuildPaths();

        return $result ? true : 1;
    }

    public function forceResync($_opts)
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($_opts, array());
        $userIds = isset($args['userIds']) ? (is_array($args['userIds']) ? $args['userIds'] : [$args['userIds']])
            : [];
        $contentClasses = isset($args['contentClasses']) ? (is_array($args['contentClasses'])
            ? $args['contentClasses'] : [$args['contentClasses']]) : [];
        $apis = isset($args['apis']) ? (is_array($args['apis']) ? $args['apis'] : [$args['apis']]) : [];

        // NOTE: this needs to be adjusted in Tinebase_Controller::forceResync() too
        $allowedContentClasses = [
            Tinebase_Controller::SYNC_CLASS_CONTACTS,
            Tinebase_Controller::SYNC_CLASS_EMAIL,
            Tinebase_Controller::SYNC_CLASS_EVENTS,
            Tinebase_Controller::SYNC_CLASS_TASKS,
        ];
        $allowedApis = [
            Tinebase_Controller::SYNC_API_ACTIVESYNC,
            Tinebase_Controller::SYNC_API_DAV,
        ];

        if (empty($apis)) {
            $apis = $allowedApis;
        } else {
            $apis = array_intersect($allowedApis, $apis);
        }

        if (empty($contentClasses)) {
            $contentClasses = $allowedContentClasses;
        } else {
            $contentClasses = array_intersect($allowedContentClasses, $contentClasses);
        }

        $msg = 'forcing resync for APIs: ' . join(', ', $apis) . ' with content classes: ' .
            join(', ', $contentClasses) . (empty($userIds) ? ' for all users' : ' for users: ' . join(', ', $userIds));
        echo $msg . PHP_EOL;

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $msg);

        Tinebase_Controller::getInstance()->forceResync($contentClasses, $userIds, $apis);
    }

    /**
     * forces containers that support sync token to resync via WebDAV sync tokens
     *
     * this will DELETE the complete content history for the affected containers
     * this will increate the sequence for all records in all affected containers
     * this will increate the sequence of all affected containers
     *
     * this will cause 2 BadRequest responses to sync token requests
     * the first one as soon as the client notices that something changed and sends a sync token request
     * eventually the client receives a false sync token (as we increased content sequence, but we dont have a content history entry)
     * eventually not (if something really changed in the calendar in the meantime)
     *
     * in case the client got a fake sync token, the clients next sync token request (once something really changed) will fail again
     * after something really changed valid sync tokens will be handed out again
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function forceSyncTokenResync($_opts)
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($_opts, array());

        if (isset($args['userIds'])) {
            $args['userIds'] = !is_array($args['userIds']) ? array($args['userIds']) : $args['userIds'];
            $filter = new Tinebase_Model_ContainerFilter(array(
                array('field' => 'owner_id', 'operator' => 'in', 'value' => $args['userIds'])
            ));
        } elseif (isset($args['containerIds'])) {
            if (!is_array($args['containerIds'])) {
                $args['containerIds'] = array($args['containerIds']);
            }
            $filter = new Tinebase_Model_ContainerFilter(array(
                array('field' => 'id', 'operator' => 'in', 'value' => $args['containerIds'])
            ));
        } else {
            echo 'userIds or containerIds need to be provided';
            return;
        }

        Tinebase_Container::getInstance()->forceSyncTokenResync($filter);
    }

    /**
     * clean timemachine_modlog for records that have been pruned (not deleted!)
     *  - accepts optional param date=YYYY-MM-DD to delete all modlogs before this date
     *  - accepts optional param instanceseq=NUMBER to delete all modlogs before this instance_seq
     *
     * @param Zend_Console_Getopt|null $_opts
     * @return int
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function cleanModlog(?Zend_Console_Getopt $_opts = null): int
    {
        $this->_checkAdminRight();

        $args = $_opts ? $this->_parseArgs($_opts, array()) : [];

        $before = isset($args['date']) ? new Tinebase_DateTime($args['date']) : null;
        $beforeSeq = $args['instanceseq'] ?? null;

        $additionalFilter = [];
        if (isset($args['app_id'])) {
            $additionalFilter['application_id'] = $args['app_id'];
        }
        if (isset($args['model'])) {
            $additionalFilter['record_type'] = $args['model'];
        }
        if (isset($args['change_type'])) {
            $additionalFilter['change_type'] = $args['change_type'];
        }

        if ($beforeSeq || $before) {
            $deleted = Tinebase_Timemachine_ModificationLog::getInstance()->clearTable($before, $beforeSeq, $additionalFilter);
        } else {
            $deleted = Tinebase_Timemachine_ModificationLog::getInstance()->clean($additionalFilter);
        }

        echo "\nDeleted $deleted modlogs records\n";

        return 0;
    }

    /**
     * clean relations, set relation to deleted if at least one of the ends has been set to deleted or pruned
     */
    public function cleanRelations(): int
    {
        $this->_checkAdminRight();

        $result = Tinebase_Relations::getInstance()->cleanRelations();
        return $result ? 0 : 1;
    }

    /**
     * authentication
     *
     * @param string $_username
     * @param string $_password
     */
    public function authenticate($_username, $_password)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Tinebase_Exception_NotFound) {
                echo 'account ' . $authResult->getIdentity() . ' not found in account storage'."\n";
                exit();
            }
            
            Tinebase_Core::set('currentAccount', $account);

            $ipAddress = '127.0.0.1';
            $account->setLoginTime($ipAddress);

            Tinebase_AccessLog::getInstance()->create(new Tinebase_Model_AccessLog(array(
                'sessionid'     => 'cli call',
                'login_name'    => $authResult->getIdentity(),
                'ip'            => $ipAddress,
                'li'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'lo'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'result'        => $authResult->getCode(),
                'account_id'    => Tinebase_Core::getUser()->getId(),
                'clienttype'    => 'TineCli',
            )));

            $credentialCache = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials($_username, $_password);
            Tinebase_Core::set(Tinebase_Core::USERCREDENTIALCACHE, $credentialCache);

        } else {
            echo "Wrong username and/or password.\n";
            exit();
        }
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean|integer success
     */
    public function handle($_opts)
    {
        [$application, $method] = explode('.', $_opts->method);
        $class = $application . '_Frontend_Cli';
        
        if (@class_exists($class)) {
            $object = new $class;
            if ($_opts->info) {
                $result = $object->getHelp();
            } else if (method_exists($object, $method)) {
                $result = call_user_func(array($object, $method), $_opts);
            } else {
                $result = 1;
                echo "Method $method not found.\n";
            }
        } else {
            echo "Class $class does not exist.\n";
            $result = 2;
        }
        
        return $result;
    }

    /**
     * trigger async events (for example via cronjob)
     *
     * - respects maintenance mode
     * - can be deactivated via Tinebase_Config::CRON_DISABLED
     *
     * @param Zend_Console_Getopt $_opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Statement_Exception
     */
    public function triggerAsyncEvents(Zend_Console_Getopt $_opts): int
    {
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::CRON_DISABLED)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                    __LINE__ . ' Cronjob is disabled.');
            }
            return 1;
        }

        if (Tinebase_Core::inMaintenanceModeAll()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                    __LINE__ . ' Maintenance mode prevents trigger async events.');
            }
            return 1;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Triggering async events from CLI.');
        }

        $cronuser = null;
        try {
            $userController = Tinebase_User::getInstance();
            $cronuser = $userController->getFullUserByLoginName($_opts->username);
        } catch (Tinebase_Exception_NotFound) {
            $cronuser = $this->_getCronuserFromConfigOrCreateOnTheFly();
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' .
                    __LINE__ . ' Maybe Addressbook is not ready or tine not installed yet: ' . $zdse->getMessage());
            }
        }
        if (! $cronuser) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                    __LINE__ . ' No valid cronuser found.');
            }
            return 1;
        }
        Tinebase_Core::set(Tinebase_Core::USER, $cronuser);
        
        $scheduler = Tinebase_Core::getScheduler();
        $result = $scheduler->run();
        
        return $result ? 0 : 1;
    }

    /**
     * process given queue job
     *   --jobId the queue job id to execute
     *
     * - respects maintenance mode
     *
     * @param Zend_Console_Getopt $_opts
     * @return bool success
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Db_Statement_Exception
     */
    public function executeQueueJob(Zend_Console_Getopt $_opts): bool
    {
        if (Tinebase_Core::inMaintenanceModeAll()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                    __LINE__ . ' Maintenance mode prevents execution of queue jobs');
            }
            return false;
        }

        try {
            $cronuser = Tinebase_User::getInstance()->getFullUserByLoginName($_opts->username);
        } catch (Tinebase_Exception_NotFound) {
            $cronuser = $this->_getCronuserFromConfigOrCreateOnTheFly();
        }

        if (! $cronuser) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                __LINE__ . ' No valid cronuser found.');
            return false;
        }

        Tinebase_Core::set(Tinebase_Core::USER, $cronuser);
        
        $args = $_opts->getRemainingArgs();
        $jobId = preg_replace('/^jobId=/', '', $args[0]);
        
        if (! $jobId) {
            throw new Tinebase_Exception_InvalidArgument('mandatory parameter "jobId" is missing');
        }

        if (isset($args[1]) ) {
            $actionQueue = Tinebase_ActionQueue::getInstance(preg_replace('/^queueName=/', '', $args[1]));
        } else {
            $actionQueue = Tinebase_ActionQueue::getInstance();
        }
        $job = $actionQueue->receive($jobId);

        if (isset($job['account_id'])) {
            Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()->getFullUserById($job['account_id']));
        }

        $result = $actionQueue->executeAction($job);

        // NOTE: queue job execution expects boolean result - don't change to integer (0,1,2...) here
        return false !== $result;
    }
    
    /**
     * clear table as defined in arguments
     * can clear the following tables:
     * - credential_cache
     * - access_log
     * - async_job
     * - temp_files
     * - timemachine_modlog
     *
     * if param date is given (date=2010-09-17), all records before this date are deleted (if the table has a date field)
     * 
     * @param $_opts
     * @return boolean success
     */
    public function clearTable(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();
        
        $args = $this->_parseArgs($_opts, array('tables'), 'tables');
        $dateString = (isset($args['date']) || array_key_exists('date', $args)) ? $args['date'] : NULL;

        $date = ($dateString) ? new Tinebase_DateTime($dateString) : NULL;

        foreach ((array)$args['tables'] as $table) {
            switch ($table) {
                case 'access_log':
                    Tinebase_AccessLog::getInstance()->clearTable($date);
                    break;
                case 'async_job':
                    echo 'async_job has been dropped, no need to clear it anymore' . PHP_EOL;
                    break;
                case 'credential_cache':
                    Tinebase_Auth_CredentialCache::getInstance()->clearCacheTable();
                    break;
                case 'temp_files':
                    Tinebase_TempFile::getInstance()->clearTableAndTempdir($dateString);
                    break;
                case 'timemachine_modlog':
                    Tinebase_Timemachine_ModificationLog::getInstance()->clearTable($date);
                    break;
                default:
                    echo 'Table ' . $table . " not supported or argument missing.\n";
            }
            echo "\nCleared table $table.";
        }
        echo "\n\n";
        
        return TRUE;
    }
    
    /**
     * purge deleted records
     *
     * If param date is given (for example: date=2010-09-17), all records before this date are deleted
     * (if the table has a date field). If table names are given, purge only records from these tables.
     * We also remove all modification log records before the given date (with param modlog=purge)!
     *      - modlog can be skipped with skip=modlog
     *
     * @param Zend_Console_Getopt $_opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function purgeDeletedRecords(Zend_Console_Getopt $_opts): int
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($_opts, array(), 'tables');
        $date = isset($args['date']) ? new Tinebase_DateTime($args['date']) : null;
        $tables = isset($args['tables']) ? (array) $args['tables'] : [];
        $skip = $args['skip'] ?? null;

        echo "\nPurging obsolete data from tables...";
        $result = Tinebase_Controller::getInstance()->removeObsoleteData($date, $tables, false);

        if (empty($tables)) {
            // TODO move to \Tinebase_Controller::removeObsoleteData
            echo "\nCleaning relations...";
            $this->cleanRelations();

            if ('modlog' !== $skip) {
                echo "\nCleaning modlog...";
                $this->cleanModlog(isset($args['modlog']) && $args['modlog'] === 'purge' ? $_opts : null);
            }

            echo "\nCleaning customfields...";
            $this->cleanCustomfields();

            echo "\nCleaning notes...";
            $this->cleanNotes($_opts);

            echo "\nCleaning files...";
            $this->clearDeletedFiles();

            echo "\nOptimizing path table...";
            Tinebase_Path_Backend_Sql::optimizePathsTable();
        }

        return $result ? 0 : 1;
    }

    /**
     * cleanNotes: removes notes of records that have been deleted and old avscans
     *
     * -- purge=1 param also removes redundant notes (empty updates + create notes)
     * supports dry run (-d)
     */
    public function cleanNotes(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($_opts, array(), 'cleanNotesOffset');

        $offset = ($args['cleanNotesOffset'] ?? 0);
        $purge = $args['purge'] ?? false;

        $deletedCount = Tinebase_Notes::getInstance()->removeObsoleteData($purge, $offset, $_opts->d);

        if ($_opts->d) {
            echo "\nDRY RUN!";
        }

        echo "\nDeleted " . $deletedCount . " notes\n";
    }

    /**
     * cleanCustomfields
     */
    public function cleanCustomfields()
    {
        $this->_checkAdminRight();

        $customFieldController = Tinebase_CustomField::getInstance();
        $customFieldConfigs = $customFieldController->searchConfig();
        $deleteCount = 0;

        /** @var Tinebase_Model_CustomField_Config $customFieldConfig */
        foreach($customFieldConfigs as $customFieldConfig) {
            $deleteAll = false;
            try {
                $controller = Tinebase_Core::getApplicationInstance($customFieldConfig->model);

                $oldACLCheckValue = $controller->doContainerACLChecks(false);
                if ($customFieldConfig->model !== 'Filemanager_Model_Node') {
                    $filterClass = $customFieldConfig->model . 'Filter';
                } else {
                    $filterClass = 'ClassThatDoesNotExist';
                }
            } catch(Tinebase_Exception_AccessDenied) {
                // TODO log
                continue;
            } catch(Tinebase_Exception_NotFound $tenf) {
                $deleteAll = true;
            }



            $filter = new Tinebase_Model_CustomField_ValueFilter(array(
                array('field' => 'customfield_id', 'operator' => 'equals', 'value' => $customFieldConfig->id)
            ));
            $customFieldValues = $customFieldController->search($filter);
            $deleteIds = array();

            if (true === $deleteAll) {
                $deleteIds = $customFieldValues->getArrayOfIds();
            } elseif (class_exists($filterClass)) {
                $model = new $customFieldConfig->model();
                /** @var Tinebase_Model_CustomField_Value $customFieldValue */
                foreach ($customFieldValues as $customFieldValue) {
                    $filter = new $filterClass(array(
                        array('field' => $model->getIdProperty(), 'operator' => 'equals', 'value' => $customFieldValue->record_id)
                    ));
                    if ($model->has('is_deleted')) {
                        $filter->addFilter(new Tinebase_Model_Filter_Int(array('field' => 'is_deleted', 'operator' => 'notnull', 'value' => NULL)));
                    }

                    $result = $controller->searchCount($filter);

                    if (is_bool($result) || (is_string($result) && $result === ((string)intval($result)))) {
                        $result = (int)$result;
                    }

                    if (!is_int($result)) {
                        if (is_array($result) && isset($result['totalcount'])) {
                            $result = (int)$result['totalcount'];
                        } elseif(is_array($result) && isset($result['count'])) {
                            $result = (int)$result['count'];
                        } else {
                            // todo log
                            // dummy line, remove!
                            $result = 1;
                        }
                    }

                    if ($result === 0) {
                        $deleteIds[] = $customFieldValue->getId();
                    }
                }
            } else {
                /** @var Tinebase_Model_CustomField_Value $customFieldValue */
                foreach ($customFieldValues as $customFieldValue) {
                    try {
                        $controller->get($customFieldValue->record_id, null, false, true);
                    } catch(Tinebase_Exception_NotFound) {
                        $deleteIds[] = $customFieldValue->getId();
                    }
                }
            }

            if (count($deleteIds) > 0) {
                $customFieldController->deleteCustomFieldValue($deleteIds);
                $deleteCount += count($deleteIds);
            }

            if (true !== $deleteAll) {
                $controller->doContainerACLChecks($oldACLCheckValue);
            }
        }

        echo "\ndeleted " . $deleteCount . " customfield values\n";
    }

    /**
     * add new customfield config
     *
     * example:
     * $ php tine20.php --method=Tinebase.addCustomfield -- \
         application="Addressbook" model="Addressbook_Model_Contact" name="datefield" \
         definition='{"label":"Date","type":"datetime", "uiconfig": {"group":"Dates", "order": 30}}'
     * @see Tinebase_Model_CustomField_Config for full list
     *
     * @param $_opts
     * @return boolean success
     */
    public function addCustomfield(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();
        
        // parse args
        $args = $_opts->getRemainingArgs();
        $data = array();
        foreach ($args as $idx => $arg) {
            [$key, $value] = explode('=', (string) $arg);
            if ($key == 'application') {
                $key = 'application_id';
                $value = Tinebase_Application::getInstance()->getApplicationByName($value)->getId();
            }
            $data[$key] = $value;
        }
        
        $customfieldConfig = new Tinebase_Model_CustomField_Config($data);
        $cf = Tinebase_CustomField::getInstance()->addCustomField($customfieldConfig);

        echo "\nCreated customfield: ";
        print_r($cf->toArray());
        echo "\n";
        
        return 0;
    }

    /**
     * set customfield acl
     *
     * example:
     * $ php tine20.php --method Tinebase.setCustomfieldAcl -- application=Addressbook \
     *   model=Addressbook_Model_Contact name=$CFNAME \
     *   grants='[{"account":"$USERNAME","account_type":"user","readGrant":1,"writeGrant":1},{"account_type":"anyone","readGrant":1}]'
     *
     * @param $_opts
     * @return integer
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function setCustomfieldAcl(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();

        // parse args
        $args = $_opts->getRemainingArgs();
        $data = array();
        foreach ($args as $idx => $arg) {
            [$key, $value] = explode('=', (string) $arg);
            if ($key == 'application') {
                $key = 'application_id';
                $value = Tinebase_Application::getInstance()->getApplicationByName($value)->getId();
            }
            $data[$key] = $value;
        }

        if (! isset($data['grants']) || ! isset($data['name']) || ! isset($data['model'])) {
            throw new Tinebase_Exception_InvalidArgument('grants, name, model params are required');
        }

        $cf = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            $data['application_id'],
            $data['name'],
            $data['model'],
            false,
            true
        );

        if (! $cf) {
            throw new Tinebase_Exception_InvalidArgument('customfield not found');
        }

        $grantsArray = Tinebase_Helper::jsonDecode($data['grants']);
        $removeOldGrants = true;
        foreach ($grantsArray as $grant) {
            $accountType = $grant['account_type'] ?? null;
            if (isset($grant['account'])) {
                if ($accountType === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
                    $group = Tinebase_Group::getInstance()->getGroupByName($grant['account']);
                    $accountId = $group->getId();
                } else {
                    $user = Tinebase_User::getInstance()->getFullUserByLoginName($grant['account']);
                    $accountId = $user->getId();
                    $accountType = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
                }
            } else {
                $accountId = $grant['account_id'] ?? null;
            }
            $grants = [];
            $allGrants = Tinebase_Model_CustomField_Grant::getAllGrants();
            foreach ($grant as $key => $value) {
                if (in_array($key, $allGrants) && $value) {
                    $grants[] = $key;
                }
            }
            Tinebase_CustomField::getInstance()->setGrants($cf->getId(), $grants, $accountType, $accountId, $removeOldGrants);
            // prevent overwrite
            $removeOldGrants = false;
        }

        return 0;
    }

    /**
     * set node acl
     *
     * example:
     * $ php tine20.php --method Tinebase.setNodeAcl [-d] -- id=NODEID \
     *   grants='[{"account":"$USERNAME","account_type":"user","readGrant":1,"writeGrant":1},{"account":"$GROUPNAME","account_type":"group","readGrant":1}]'
     *
     * @param $_opts
     * @return integer
     *
     * @todo generalize this - see \Tinebase_Frontend_Cli::setCustomfieldAcl
     * @todo add a test
     */
    public function setNodeAcl(Zend_Console_Getopt $_opts)
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($_opts, ['id', 'grants'], 'other', false);
        $node = Tinebase_FileSystem::getInstance()->get($args['id']);

        $grantsArray = Tinebase_Helper::jsonDecode($args['grants']);
        #print_r($grantsArray);
        // @todo generalize this - see \Tinebase_Frontend_Cli::setCustomfieldAcl
        $grantsToSet = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class);
        foreach ($grantsArray as $grant) {
            $accountType = $grant['account_type'] ?? Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
            if (isset($grant['account'])) {
                if ($accountType === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
                    $group = Tinebase_Group::getInstance()->getGroupByName($grant['account']);
                    $accountId = $group->getId();
                } else {
                    $user = Tinebase_User::getInstance()->getFullUserByLoginName($grant['account']);
                    $accountId = $user->getId();
                }
            } else {
                $accountId = $grant['account_id'] ?? null;
            }
            $grantRecord = new Tinebase_Model_Grants([
                'account_id' => $accountId,
                'account_type' => $accountType,
            ]);
            foreach (Tinebase_Model_Grants::getAllGrants() as $possibleGrant) {
                if (isset($grant[$possibleGrant])) {
                    $grantRecord->{$possibleGrant} = (boolean) $grant[$possibleGrant];
                }
            }
            $grantsToSet->addRecord($grantRecord);
        }
        if ($_opts->d) {
            echo "DRYRUN! grants to be set:\n";
            print_r($grantsToSet->toArray());
        } else {
            Tinebase_FileSystem::getInstance()->setGrantsForNode($node, $grantsToSet);
        }

        return 0;
    }

    /**
     * nagios monitoring for tine 2.0 database connection
     * 
     * @return integer
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckDB()
    {
        $result = 0;
        $message = 'DB CONNECTION FAIL';
        try {
            if (! Setup_Core::isRegistered(Setup_Core::CONFIG)) {
                Setup_Core::setupConfig();
            }
            if (! Setup_Core::isRegistered(Setup_Core::LOGGER)) {
                Setup_Core::setupLogger();
            }
            $time_start = microtime(true);
            $dbcheck = Setup_Core::setupDatabaseConnection();
            $time = (microtime(true) - $time_start) * 1000;
        } catch (Exception $e) {
            $message .= ': ' . $e->getMessage();
            $dbcheck = FALSE;
        }
        
        if ($dbcheck) {
            $message = "DB CONNECTION OK | connecttime={$time}ms;;;;";
        } else {
            $result = 2;
        }
        
        echo $message . "\n";
        $this->_logMonitoringResult($result, $message);

        return $result;
    }
    
    /**
     * nagios monitoring for tine 2.0 config file
     * 
     * @return integer
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckConfig()
    {
        $message = 'CONFIG FAIL';
        $configcheck = Tinebase_Controller::getInstance()->checkConfig();
        $result = 0;

        if ($configcheck) {
            $message = "CONFIG FILE OK";
        } else {
            $result = 2;
        }

        echo $message . "\n";
        $this->_logMonitoringResult($result, $message);

        return $result;
    }
    
    /**
    * nagios monitoring for tine 2.0 async cronjob run
    *
    * @return integer
    * 
    * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
    * @see 0008038: monitoringCheckCron -> check if cron did run in the last hour
    */
    public function monitoringCheckCron()
    {
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::CRON_DISABLED)) {
            $message = 'CRON INACTIVE';
            $result = 0;
        } else {
            $message = 'CRON FAIL';
            try {
                $lastJob = Tinebase_Scheduler::getInstance()->getLastRun();

                if ($lastJob === NULL || !$lastJob->last_run instanceof Tinebase_DateTime) {
                    $message .= ': NO LAST JOB FOUND';
                    $result = 1;
                } else {
                    $valueString = ' | duration=' . $lastJob->last_duration . 's;;;;';
                    $valueString .= ' end=' . $lastJob->last_run->getClone()->addSecond($lastJob->last_duration)->getIso() . ';;;;';

                    if ($lastJob->server_time->isLater($lastJob->last_run->getClone()->addHour(1))) {
                        $message .= ': NO JOB IN THE LAST HOUR';
                        $result = 1;
                    } else {
                        $message = 'CRON OK';
                        $result = 0;
                    }
                    $message .= $valueString;
                }
            } catch (Exception $e) {
                $message .= ': ' . $e->getMessage();
                $result = 2;
            }
        }

        $this->_logMonitoringResult($result, $message);
        echo $message . "\n";
        return $result;
    }

    protected function _logMonitoringResult($result, $message)
    {
        if ($result > 0) {
            try {
                Tinebase_Exception::log(new Tinebase_Exception($message));
            } catch (Throwable) {
                // just logging
            }
        }
    }
    
    /**
     * nagios monitoring for successful tine 2.0 logins during the last 5 mins
     * 
     * @return number
     * 
     * @todo allow to configure timeslot
     */
    public function monitoringLoginNumber()
    {
        $message = 'LOGINS';
        $result  = 0;
        
        try {
            $filter = new Tinebase_Model_AccessLogFilter(array(
                array('field' => 'li', 'operator' => 'after', 'value' => Tinebase_DateTime::now()->subMinute(5)),
                array('field' => 'result', 'operator' => 'equals', 'value' => 1),
            ));
            $accesslogs = Tinebase_AccessLog::getInstance()->search($filter, NULL, FALSE, TRUE);
            $valueString = ' | count=' . count($accesslogs) . ';;;;';
            $message .= ' OK' . $valueString;
        } catch (Exception $e) {
            $message .= ' FAIL: ' . $e->getMessage();
            $result = 2;
        }

        $this->_logMonitoringResult($result, $message);
        
        echo $message . "\n";
        return $result;
    }

    /**
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function monitoringCheckQuota(): int
    {
        $result = 0;
        $quotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};
        $monitoringQuota = $quotaConfig->{Tinebase_Config::QUOTA_MONITORING};
        $totalQuota = $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB}*1024*1024;

        if ($monitoringQuota && Tinebase_FileSystem_Quota::getTotalQuotaBytes() > 0) {
            $totalUsage = Tinebase_FileSystem_Quota::getRootUsedBytes();
            $usagePercentage = ($totalUsage / $totalQuota) * 100;
            $quotaValueString = 'usage=' . Tinebase_Helper::formatBytes($totalUsage) . ';totalQuota=' . Tinebase_Helper::formatBytes($totalQuota) . ';;;';

            if ($usagePercentage >= 99) {
                $message = 'QUOTA LIMIT REACHED | ' . $quotaValueString;
                $result = 2;
            } elseif ($usagePercentage >= $quotaConfig->{Tinebase_Config::QUOTA_SOFT_QUOTA}) {
                $message = 'QUOTA LIMIT WARN | ' . $quotaValueString;
                $result = 1;
            } else {
                $message = 'QUOTA LIMIT OK | ' . $quotaValueString;
            }

            $this->_logMonitoringResult($result, $message);
        } else {
            $message = 'QUOTA MONITORING INACTIVE';
        }

        echo $message . "\n";
        return $result;
    }


    /**
     * nagios monitoring for tine 2.0 active users
     *
     * @return number
     *
     * @todo allow to configure timeslot / currently the active users of the last month are returned
     */
    public function monitoringActiveUsers()
    {
        $message = 'ACTIVE USERS';
        $result  = 0;

        try {
            $userCount = Tinebase_User::getInstance()->getActiveUserCount();
            $valueString = ' | count=' . $userCount . ';;;;';
            $message .= ' OK' . $valueString;
        } catch (Exception $e) {
            $message .= ' FAIL: ' . $e->getMessage();
            $result = 2;
        }

        $this->_logMonitoringResult($result, $message);

        echo $message . "\n";
        return $result;
    }

    /**
     * nagios monitoring for tine 2.0 action queue
     *
     * @return integer
     *
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckQueue()
    {
        $result = 0;
        $queueConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::ACTIONQUEUE);
        if (! $queueConfig->{Tinebase_Config::ACTIONQUEUE_ACTIVE}) {
            $message = 'QUEUE INACTIVE';
        } else {
            $actionQueue = Tinebase_ActionQueue::getInstance();
            if (! $actionQueue->hasAsyncBackend()) {
                $message = 'QUEUE INACTIVE';
            } else {
                if ($queueConfig->{Tinebase_Config::ACTIONQUEUE_LONG_RUNNING}) {
                    $actionLRQueue = Tinebase_ActionQueue::getInstance(Tinebase_ActionQueue::QUEUE_LONG_RUN);
                } else {
                    $actionLRQueue = null;
                }
                try {
                    if (null === ($lastDuration = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION))) {
                        throw new Tinebase_Exception('state ' . Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION .
                            ' not set');
                    }
                    if (null === ($lastDurationUpdate = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE))) {
                        throw new Tinebase_Exception('state ' .
                            Tinebase_Application::STATE_ACTION_QUEUE_LAST_DURATION_UPDATE . ' not set');
                    }
                    $lastDuration = floatval($lastDuration);
                    $lastDurationUpdate = intval($lastDurationUpdate);

                    $now = time();
                    $diff = 0;
                    $warn = null;
                    if (false !== ($currentJobId = $actionQueue->peekJobId())) {
                        if ($currentJobId === ($lastJobId = Tinebase_Application::getInstance()->getApplicationState(
                                'Tinebase', Tinebase_Application::STATE_ACTION_QUEUE_LAST_JOB_ID))) {
                            if (null === ($lastChange = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                                    Tinebase_Application::STATE_ACTION_QUEUE_LAST_JOB_CHANGE))) {
                                throw new Tinebase_Exception('state ' .
                                    Tinebase_Application::STATE_ACTION_QUEUE_LAST_JOB_CHANGE . ' not set');
                            }
                            if (($diff = $now - intval($lastChange)) > (15 * 60)) {
                                throw new Tinebase_Exception('last job id change > ' . (15 * 60) . ' sec - ' . $diff);
                            }

                        } else {
                            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                                Tinebase_Application::STATE_ACTION_QUEUE_LAST_JOB_CHANGE, (string)$now);
                            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                                Tinebase_Application::STATE_ACTION_QUEUE_LAST_JOB_ID, $currentJobId);
                        }
                    } else {
                        Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                            Tinebase_Application::STATE_ACTION_QUEUE_LAST_JOB_ID, '');
                    }

                    if ($lastDuration > $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_DURATION_CRIT}) {
                        throw new Tinebase_Exception('last duration > '
                            . $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_DURATION_CRIT} . ' sec - ' . $lastDuration);
                    }
                    if ($now - $lastDurationUpdate > $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_LASTUPDATE_CRIT}) {
                        throw new Tinebase_Exception('last duration update > '
                            . $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_LASTUPDATE_CRIT} . ' sec - ' . ($now - $lastDurationUpdate));
                    }

                    if ($diff > $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_DURATION_WARN} && null === $warn) {
                        $warn = 'last job id change > '
                            . $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_DURATION_WARN} . ' sec - ' . $diff;
                    }

                    if ($lastDuration > $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_DURATION_WARN} && null === $warn) {
                        $warn = 'last duration > '
                            . $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_DURATION_WARN} . ' sec - ' . $lastDuration;
                    }

                    if ($now - $lastDurationUpdate > $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_LASTUPDATE_WARN}
                        && null === $warn
                    ) {
                        $warn = 'last duration update > '
                            . $queueConfig->{Tinebase_Config::ACTIONQUEUE_MONITORING_LASTUPDATE_WARN} . ' sec - '
                            . ($now - $lastDurationUpdate);
                    }


                    if ($actionLRQueue) {
                        if (null === ($lastLRDuration = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                                Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION))) {
                            throw new Tinebase_Exception('state ' . Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION .
                                ' not set');
                        }
                        if (null === ($lastLRDurationUpdate = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                                Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION_UPDATE))) {
                            throw new Tinebase_Exception('state ' .
                                Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_DURATION_UPDATE . ' not set');
                        }
                        $lastLRDuration = floatval($lastLRDuration);
                        $lastLRDurationUpdate = intval($lastLRDurationUpdate);

                        $now = time();
                        $diff = 0;
                        if (false !== ($currentJobId = $actionLRQueue->peekJobId())) {
                            if ($currentJobId === ($lastJobId = Tinebase_Application::getInstance()->getApplicationState(
                                    'Tinebase', Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_JOB_ID))) {
                                if (null === ($lastChange = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                                        Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_JOB_CHANGE))) {
                                    throw new Tinebase_Exception('state ' .
                                        Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_JOB_CHANGE . ' not set');
                                }
                                if (($diff = $now - intval($lastChange)) > (15 * 60)) {
                                    throw new Tinebase_Exception('last job id change > ' . (15 * 60) . ' sec - ' . $diff);
                                }

                            } else {
                                Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                                    Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_JOB_CHANGE, (string)$now);
                                Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                                    Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_JOB_ID, $currentJobId);
                            }
                        } else {
                            Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                                Tinebase_Application::STATE_ACTION_QUEUE_LR_LAST_JOB_ID, '');
                        }

                        if ($lastLRDuration > $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DURATION_CRIT}) {
                            throw new Tinebase_Exception('last duration > '
                                . $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DURATION_CRIT} . ' sec - ' . $lastLRDuration);
                        }
                        if ($now - $lastLRDurationUpdate > $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_LASTUPDATE_CRIT}) {
                            throw new Tinebase_Exception('last duration update > '
                                . $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_LASTUPDATE_CRIT} . ' sec - ' . ($now - $lastLRDurationUpdate));
                        }

                        if ($diff > $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DURATION_WARN} && null === $warn) {
                            $warn = 'last job id change > '
                                . $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DURATION_WARN} . ' sec - ' . $diff;
                        }

                        if ($lastLRDuration > $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DURATION_WARN} && null === $warn) {
                            $warn = 'last duration > '
                                . $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DURATION_WARN} . ' sec - ' . $lastLRDuration;
                        }

                        if ($now - $lastLRDurationUpdate > $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_LASTUPDATE_WARN}
                            && null === $warn
                        ) {
                            $warn = 'last duration update > '
                                . $queueConfig->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_LASTUPDATE_WARN} . ' sec - '
                                . ($now - $lastLRDurationUpdate);
                        }
                    }

                    $queueState = Tinebase_Application::getInstance()->getApplicationState('Tinebase',
                        Tinebase_Application::STATE_ACTION_QUEUE_STATE);
                    if (null === $queueState) {
                        $queueState = [
                            'lastFullCheck' => 0,
                            'lastSizeOver10k' => false,
                            'actionQueueMissingQueueKeys' => [],
                            'actionQueueMissingDaemonKeys' => [],
                            'lastLRSizeOver10k' => false,
                            'actionQueueLRMissingQueueKeys' => [],
                            'actionQueueLRMissingDaemonKeys' => [],
                        ];
                    } else {
                        $queueState = json_decode($queueState, true);
                    }

                    $queueSize = $actionQueue->getQueueSize();
                    if (null === $warn && $actionQueue->getDaemonStructSize() > $queueConfig
                            ->{Tinebase_Config::ACTIONQUEUE_MONITORING_DAEMONSTRCTSIZE_CRIT}) {
                        $warn = 'daemon struct size > ' . $queueConfig
                                ->{Tinebase_Config::ACTIONQUEUE_MONITORING_DAEMONSTRCTSIZE_CRIT};
                    }

                    $queueSizeLR = $actionLRQueue?->getQueueSize() ?: 0;
                    if (null === $warn && $actionLRQueue && $actionLRQueue->getDaemonStructSize() > $queueConfig
                            ->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DAEMONSTRCTSIZE_CRIT}) {
                        $warn = 'LR daemon struct size > ' . $queueConfig
                                ->{Tinebase_Config::ACTIONQUEUE_LR_MONITORING_DAEMONSTRCTSIZE_CRIT};
                    }

                    // last full check older than one hour
                    if (null === $warn && time() - $queueState['lastFullCheck'] > 3600) {
                        if ($queueSize > 10000) {
                            if (null === $warn && $queueState['lastSizeOver10k']) {
                                $warn = 'at least two consecutive full checks with queue size > 10k';
                            }
                            $queueState['lastSizeOver10k'] = true;
                        } else {
                            $queueState['lastSizeOver10k'] = false;
                        }

                        if ($queueSizeLR > 10000) {
                            if (null === $warn && $queueState['lastLRSizeOver10k']) {
                                $warn = 'LR at least two consecutive full checks with queue size > 10k';
                            }
                            $queueState['lastLRSizeOver10k'] = true;
                        } else {
                            $queueState['lastLRSizeOver10k'] = false;
                        }

                        $queueState['lastFullCheck'] = time();
                        Tinebase_Application::getInstance()->setApplicationState('Tinebase',
                            Tinebase_Application::STATE_ACTION_QUEUE_STATE, json_encode($queueState));
                    }

                    if (null !== $warn) {
                        $message = 'QUEUE WARN: ' . $warn;
                        $result = 1;
                    } else {
                        $message = 'QUEUE OK';
                    }

                    $message .= ' | size=' . $queueSize . ';lrsize=' . $queueSizeLR
                        . ';lastJobId=' . $diff . ';lastDuration=' . $lastDuration
                        . ';lastDurationUpdate=' . ($now - $lastDurationUpdate) . ';';
                } catch (Exception $e) {
                    $message = 'QUEUE FAIL: ' . $e::class . ' - ' . $e->getMessage();
                    $message .= ' - https://tine-docu.s3web.rz1.metaways.net/operators/howto/tine20AdminQueue/';
                    $result = 2;
                }

                $this->_logMonitoringResult($result, $message);
            }
        }

        echo $message . "\n";
        return $result;
    }

    /**
     * nagios monitoring for tine 2.0 maintenance mode
     *
     * @return integer
     *
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringMaintenanceMode()
    {
        $result = 0;

        if (Tinebase_Core::inMaintenanceMode()) {
            $message = 'MAINTENANCEMODE FAIL: it is on!';
            $result = 2;
        } else {
            $message = 'MAINTENANCEMODE OK';
        }

        echo $message . "\n";
        return $result;
    }

    /**
     * nagios monitoring for tine 2.0 cache
     *
     * @return integer
     *
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckCache()
    {
        $result = 0;
        $cacheConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::CACHE);
        $active = ($cacheConfig && $cacheConfig->active);
        if (! $active) {
            $message = 'CACHE INACTIVE';
        } else {
            try {
                $cache = Tinebase_Core::getCache();

                // TODO support size / see https://redis.io/commands/dbsize
                //$cacheSize = $cache->getSize();
                $cacheSize = 'unknown';
                // TODO add cache access time?

                // write, read and delete to test cache
                $cacheId = Tinebase_Helper::convertCacheId(uniqid(__METHOD__, true));
                if (false !== $cache->save(true, $cacheId)) {
                    $value = $cache->load($cacheId);
                    $cache->remove($cacheId);

                    if ($value) {
                        $message = 'CACHE OK | size=' . $cacheSize . ';;;;';
                    } else {
                        $message = 'CACHE FAIL: loading value failed';
                        $result = 1;
                    }
                } else {
                    $message = 'CACHE FAIL: saving value failed';
                    $result = 1;
                }
            } catch (Exception $e) {
                $message = 'CACHE FAIL: ' . $e->getMessage();
                $result = 2;
            }

            $this->_logMonitoringResult($result, $message);
        }
        echo $message . "\n";
        return $result;
    }

    /**
     * nagios monitoring for tine 2.0 license
     *
     * @return integer
     *
     * @see http://nagiosplug.sourceforge.net/developer-guidelines.html#PLUGOUTPUT
     */
    public function monitoringCheckLicense()
    {
        $result = 0;
        $licenseStatus = Tinebase_License::getInstance()->getStatus();
        if (in_array($licenseStatus, [Tinebase_License::STATUS_LICENSE_INVALID, Tinebase_License::STATUS_NO_LICENSE_AVAILABLE])) {
            $result = 2;
            $message = 'LICENSE FAIL | status=' . $licenseStatus . ';;;;';
        } else {
            $maxUsersMessage = 'maxusers=' . Tinebase_License::getInstance()->getMaxUsers();
            $remainingDays = Tinebase_License::getInstance()->getLicenseExpireEstimate();
            $remainingDaysMessage = 'remainingDays=' . $remainingDays;
            $features = Tinebase_License::getInstance()->getFeatures();
            $featuresMessage = $features ? 'features=' . implode(',', $features) : '';
            $infos = $maxUsersMessage . ';' . $remainingDaysMessage . ';' . $featuresMessage . ';;';
            if ($remainingDays < 7) {
                $result = 1;
                $message = 'LICENSE WARN: only a few days remaining | ' . $infos;
            } else {
                $message = 'LICENSE OK | ' . $infos;
            }
        }
        $this->_logMonitoringResult($result, $message);
        echo $message . "\n";
        return $result;
    }

    /**
     * monitoring for mail servers
     * imap/smtp/sieve
     *
     * @return integer
     */
    public function monitoringMailServers()
    {
        $result = 0;
        $skipcount = 0;
        $error = '';

        foreach ([
                     Tinebase_Config::SMTP,
                     Tinebase_Config::IMAP,
                     Tinebase_Config::SIEVE
                 ] as $server) {
            $serverConfig = Tinebase_Config::getInstance()->{$server};
            
            if (empty($serverConfig) || ! $serverConfig->active) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' CONFIG : ' . $server . ' IS NOT SET/ACTIVE, SKIP');
                $skipcount++;
                continue;
            }
            
            $host = $serverConfig->{'hostname'} ?? $serverConfig->{'host'};
            $port = $serverConfig->{'port'};

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' ' .$server . ' | host: '. $host . ' | port: ' . $port);

            if (empty($host) || empty($port)) {
                $skipcount++;
                continue;
            }
            
            $command = 'nc -v -d -N -w3 ' . $host . ' ' . $port . ' 2>&1';
            exec($command, $output, $result_code);
            $output = print_r($output, true);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . PHP_EOL . 'netcat command: ' . $command
                    . PHP_EOL . 'result code : ' . $result_code
                    . PHP_EOL . 'output : ' . $output
                );
            };

            $result = $result_code;
            if ($result > 0) {
                $error .= '| ' . $server . ' on ' . $host . ' failed';
                break;
            } else {
                // check SSL (certificate expired...)
                if ($port === 993 || $port === 587) {
                    $command = 'echo -n Q | openssl s_client -connect ' . $host . ':993 2>&1';
                    exec($command, $output, $result_code);
                    $output = print_r($output, true);

                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . PHP_EOL . 'openssl command: ' . $command
                            . PHP_EOL . 'result code : ' . $result_code
                            . PHP_EOL . 'output : ' . $output
                        );
                    };

                    $result = $result_code;
                    if ($result > 0) {
                        $error .= '| ' . $server . ' on ' . $host . ' failed';
                        break;
                    }
                }
            }
        }

        // also check mail db connectivity
        if ($result === 0 && Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            try {
                $plugin = Tinebase_EmailUser::getInstance();
                if ($plugin instanceof Tinebase_EmailUser_Imap_Dovecot) {
                    $db = $plugin->getDb();
                    $table = $db->describeTable('dovecot_users');
                    if (empty($table)) {
                        $result = 1;
                        // TODO add more schema checks here?
                        $error .= '| dovecot_users table not found';
                    }
                }
            } catch (Exception $e) {
                $result = 1;
                $error .= '| ' . $e->getMessage();
            }
        }

        if ($result === 0) {
            if ($skipcount > 2) {
                $message = 'MAIL INACTIVE';
            } else {
                $message = 'MAIL OK';
            }
        } else {
            $message = 'MAIL FAIL: ' . $error;
        }

        $this->_logMonitoringResult($result, $message);

        echo $message . PHP_EOL;
        return $result;
    }

    /**
     * nagios monitoring for tine 2.0 sentry integration
     *
     * @return integer
     */
    public function monitoringCheckSentry()
    {
        $result = 0;
        if (empty(Tinebase_Config::getInstance()->get(Tinebase_Config::SENTRY_URI))) {
            $message = 'SENTRY INACTIVE';
        } else {
            $exception = new Exception('sentry test');

            try {
                $boolResult = Tinebase_Exception::sendExceptionToSentry($exception);
                $message = $boolResult ? 'SENTRY OK' : 'SENTRY WARN';
                $result = $boolResult ? 0 : 1;
            } catch (Exception $e) {
                $message = 'SENTRY FAIL: ' . $e->getMessage();
                $result = 2;
            }

            $this->_logMonitoringResult($result, $message);
        }
        echo $message . "\n";
        return $result;
    }

    /**
     * nagios monitoring for tine preview service integration
     *
     * @return integer
     *
     * TODO also display on status page
     * TODO use tine logic to test docservice?
     * TODO catch output
     */
    public function monitoringCheckPreviewService()
    {
        $result = 0;
        if (! Tinebase_FileSystem::getInstance()->isPreviewActive()) {
            $message = 'PREVIEWSERVICE INACTIVE';
        } else {
            $script = 'PREV_URL=' .  Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} .'
echo "This is a ASCII text, used to test the document-preview-service." > /tmp/test.txt
res=$(curl -F config="{\"test\": {\"firstPage\":true,\"filetype\":\"jpg\",\"x\":100,\"y\":100,\"color\":false}}" -F "file=@/tmp/test.txt" $PREV_URL)
sha=$(echo $res | sha256sum)
if [ "$sha" != "0458d8bc6966fd1894986545478c69d0295eefdbc3115cc56ffb7fcc5667e778  -" ]; then
  echo "FAILED"
  exit 1
fi';
            ob_start();
            $result = system($script);
            $output = ob_get_flush();
            $message = 'PREVIEWSERVICE OK';
            if ($result === 'FAILED') {
                $message = 'PREVIEWSERVICE FAIL: ' . $output;
                $result = 2;
            } else {
                $result = 0;
            }

            $this->_logMonitoringResult($result, $message);
        }
        echo $message . "\n";
        return $result;
    }

    /**
     * undo modlog operations (like file delete, ...)
     *
     * usage:
     *
     * --method=Tinebase.undo [-d] -- iseqfrom=INSTANCESEQ1 iseqto=INSTANCESEQ2 accountid=IDFROMUSERTABLE models=MODELS
     *
     * params:
     *
     * - iseqfrom: starting instance_seq
     * - iseqto: ending instance_seq
     * - accountid: user account whose actions should be undeleted (field in modlog: modification_account)
     * - models: the models for undelete, "fs" means Filesystem (undo all filesystem operations)
     *           comma separated string, example: Addressbook_Model_Contact,Addressbook_Model_List
     *           (field in modlog: record_type)
     *
     * => all param information can be fetched from timemachine_modlog table
     *
     * example:
     *
     * --method Tinebase.undo -- iseqfrom=1932 iseqto=1934 accountid=81db314e75f5c5e8eab8d2b3a7a5a566b0b98d5d models=fs
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function undo(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($opts, array('iseqfrom', 'iseqto', 'accountid', 'models'));
        $dryrun = (bool)$opts->d;
        $overwrite = (bool)($data['overwrite'] ?? false);

        $data['iseqfrom'] = intval($data['iseqfrom']);
        $data['iseqto'] = intval($data['iseqto']);
        if ($data['iseqto'] < $data['iseqfrom']) {
            throw new Tinebase_Exception_UnexpectedValue('iseqto needs to be equal or greater than iseqfrom');
        }
        $data['accountid'] = explode(',', (string) $data['accountid']);
        if (!is_array($data['models'])) {
            $data['models'] = explode(',', (string) $data['models']);
        }
        if (false !== ($key = array_search('fs', $data['models']))) {
            unset($data['models'][$key]);
            $data['models'][] = Tinebase_Model_Tree_Node::class;
            $data['models'][] = Tinebase_Model_Tree_FileObject::class;
        }

        $filterData = [
            ['field' => 'instance_seq', 'operator' => 'greater',    'value' => ($data['iseqfrom'] - 1)],
            ['field' => 'instance_seq', 'operator' => 'less',       'value' => ($data['iseqto'] + 1)],
        ];

        if (['all'] !== $data['accountid']) {
            $filterData[] = ['field' => 'modification_account', 'operator' => 'in', 'value' => $data['accountid']];
        }
        if (['all'] !== $data['models']) {
            $filterData[] = ['field' => 'record_type', 'operator' => 'in', 'value' => $data['models']];
        }

        $filter = new Tinebase_Model_ModificationLogFilter($filterData);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->undo($filter, $overwrite, $dryrun);

        if (! $dryrun) {
            Setup_Controller::getInstance()->clearCache(false);
            echo 'Reverted ' . $result['totalcount'] . " change(s)\n";
        } else {
            echo "Dry run\n";
            echo 'Would revert ' . $result['totalcount'] . " change(s):\n";
            foreach ($result['undoneModlogs'] as $modlog) {
                if ($modlog->change_type === Tinebase_Timemachine_ModificationLog::CREATED) {
                    echo 'id ' . $modlog->record_id . ' DELETE' . PHP_EOL;
                } elseif ($modlog->change_type === Tinebase_Timemachine_ModificationLog::DELETED) {
                    echo 'id ' . $modlog->record_id . ' UNDELETE' . PHP_EOL;
                } else {
                    $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value));
                    if (is_array($diff->diff)) {
                        foreach ($diff->diff as $key => $val) {
                            echo 'id ' . $modlog->record_id . ' [' . $key . ']: ' . $val . ' -> ' . $diff->oldData[$key] . PHP_EOL;
                        }
                    }
                }
            }
        }
        echo 'Failcount: ' . $result['failcount'] . "\n";
        return 0;
    }

    public function actionQueueRestoreDeadLetter(Zend_Console_Getopt $opts): int
    {
        $this->_checkAdminRight();

        if (Tinebase_ActionQueue_Backend_Redis::class !== Tinebase_ActionQueue::getInstance()->getBackendType()) {
            echo 'only works with redis backend' . PHP_EOL;
            return 1;
        }

        $data = $this->_parseArgs($opts, array('jobId'));

        if (Tinebase_ActionQueue::getInstance()->restoreDeadletter($data['jobId'])) {
            echo 'restore succeeded' . PHP_EOL;
        } else {
            echo 'restore failed' . PHP_EOL;
        }

        return 0;
    }

    /**
     * undo changes to records defined by certain criteria (user, date, fields, ...)
     * 
     * example: $ php tine20.php --username pschuele --method Tinebase.undoDeprecated -d
     *   -- record_type=Addressbook_Model_Contact modification_time=2013-05-08 modification_account=3263
     * 
     * @param Zend_Console_Getopt $opts
     * @return integer
     */
    public function undoDeprecated(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();
        
        $data = $this->_parseArgs($opts, array('modification_time'));
        
        // build filter from params
        $filterData = array();
        $allowedFilters = array(
            'record_type',
            'modification_time',
            'modification_account',
            'record_id',
            'client',
        );
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFilters)) {
                $operator = ($key === 'modification_time') ? 'within' : 'equals';
                $filterData[] = array('field' => $key, 'operator' => $operator, 'value' => $value);
            }
        }
        $filter = new Tinebase_Model_ModificationLogFilter($filterData);
        
        $dryrun = $opts->d;
        $overwrite = (isset($data['overwrite']) && $data['overwrite']) ? TRUE : FALSE;
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->undo($filter, $overwrite, $dryrun, ($data['modified_attribute'] ?? null));
        
        if (! $dryrun) {
            Setup_Controller::getInstance()->clearCache(false);
            echo 'Reverted ' . $result['totalcount'] . " change(s)\n";
        } else {
            echo "Dry run\n";
            echo 'Would revert ' . $result['totalcount'] . " change(s):\n";
            foreach ($result['undoneModlogs'] as $modlog) {
                $modifiedAttribute = $modlog->modified_attribute;
                if (!empty($modifiedAttribute)) {
                    echo 'id ' . $modlog->record_id . ' [' . $modifiedAttribute . ']: ' . $modlog->new_value . ' -> ' . $modlog->old_value . PHP_EOL;
                } else {
                    if ($modlog->change_type === Tinebase_Timemachine_ModificationLog::CREATED) {
                        echo 'id ' . $modlog->record_id . ' DELETE' . PHP_EOL;
                    } elseif ($modlog->change_type === Tinebase_Timemachine_ModificationLog::DELETED) {
                        echo 'id ' . $modlog->record_id . ' UNDELETE' . PHP_EOL;
                    } else {
                        $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value));
                        if (is_array($diff->diff)) {
                            foreach ($diff->diff as $key => $val) {
                                echo 'id ' . $modlog->record_id . ' [' . $key . ']: ' . $val . ' -> ' . $diff->oldData[$key] . PHP_EOL;
                            }
                        }
                    }
                }
            }
        }
        echo 'Failcount: ' . $result['failcount'] . "\n";
        return 0;
    }

    /**
     * recursive undelete of file nodes - needs parent id param (only works if file objects still exist)
     *
     * @param Zend_Console_Getopt $_opts
     * @return int
     */
    public function undeleteFileNodes(Zend_Console_Getopt $_opts): int
    {
        $parentIds = $_opts->getRemainingArgs();
        $treeNodeBackend = new Tinebase_Tree_Node();

        foreach ($parentIds as $parentId) {
            $treeNodeBackend->recursiveUndelete($parentId);
        }

        return 0;
    }

    /**
     * creates demo data for all applications
     * accepts same arguments as Tinebase_Frontend_Cli_Abstract::createDemoData
     * and the additional argument "skipAdmin" to force no user/group/role creation
     * 
     * @param Zend_Console_Getopt $_opts
     */
    public function createAllDemoData($_opts)
    {
        $this->_checkAdminRight();
        
        // fetch all applications and check if required are installed, otherwise remove app from array
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->name;
        foreach ($applications as $appName) {
            echo 'Searching for DemoData in application "' . $appName . '"...' . PHP_EOL;
            $className = $appName.'_Setup_DemoData';
            if (class_exists($className)) {
                echo 'DemoData in application "' . $appName . '" found!' . PHP_EOL;
                $required = $className::getRequiredApplications();
                foreach ($required as $requiredApplication) {
                    if (! Tinebase_Helper::in_array_case($applications, $requiredApplication)) {
                        echo 'Creating DemoData for Application ' . $appName . ' is impossible, because application "' . $requiredApplication . '" is not installed.' . PHP_EOL;
                        continue 2;
                    }
                }
                $this->_applicationsToWorkOn[$appName] = array('appName' => $appName, 'required' => $required);
            } else {
                echo 'DemoData in application "' . $appName . '" not found.' . PHP_EOL . PHP_EOL;
            }
        }
        unset($applications);

        $this->_createDemoDataRecursive(null, null, null);
        foreach ($this->_applicationsToWorkOn as $app => $cfg) {
            $this->_createDemoDataRecursive($app, $cfg, $_opts);
        }

        return 0;
    }
    
    /**
     * creates demo data and calls itself if there are required apps
     * 
     * @param string $app
     * @param array $cfg
     * @param Zend_Console_Getopt $opts
     */
    protected function _createDemoDataRecursive($app, $cfg, $opts)
    {
        static $recursiveApps = [];
        if (null === $app) {
            $recursiveApps = [];
            return;
        }

        if (isset($recursiveApps[$app])) {
            return;
        }
        $recursiveApps[$app] = true;

        if (isset($cfg['required']) && is_array($cfg['required'])) {
            foreach($cfg['required'] as $requiredApp) {
                $this->_createDemoDataRecursive($requiredApp, $this->_applicationsToWorkOn[$requiredApp], $opts);
            }
        }
        
        $className = $app . '_Frontend_Cli';
        $classNameDD = $app . '_Setup_DemoData';
        
        if (class_exists($className)) {
            if (! class_exists($classNameDD) || ! $classNameDD::hasBeenRun()) {
                $class = new $className();
                if (method_exists($class, 'createDemoData')) {
                    echo 'Creating DemoData in application "' . $app . '"...' . PHP_EOL;
                    $class->createDemoData($opts, false);
                } else {
                    echo $className . ' has no method createDemoData() ...' . PHP_EOL;
                }
            } else {
                echo 'DemoData for ' . $app . ' has been run already, skipping...' . PHP_EOL;
            }
        } else {
            echo 'Could not found ' . $className . ', so DemoData for application "' . $app . '" could not be created!';
        }
    }
    
    /**
     * clears deleted files from filesystem
     *
     * @return int
     */
    public function clearDeletedFiles()
    {
        $this->_checkAdminRight();
        
        $this->_addOutputLogWriter();
        
        Tinebase_FileSystem::getInstance()->clearDeletedFiles();

        return 0;
    }

    /**
     * clears deleted files from the database, use -- d=false or -- d=0 to turn off dryRun. Default is -- d=true
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function clearDeletedFilesFromDatabase(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $this->_addOutputLogWriter();

        $data = $this->_parseArgs($opts);
        if (isset($data['d']) && ($data['d'] === 'false' || $data['d'] === '0')) {
            $dryrun = false;
        } else {
            $dryrun = true;
        }

        echo PHP_EOL . ($dryrun ? 'would delete ' : 'deleted ') . Tinebase_FileSystem::getInstance()
                ->clearDeletedFilesFromDatabase((bool)$dryrun) . ' hashes from the database' . PHP_EOL;

        return 0;
    }

    /**
     * repair acl of nodes (supports -d for dry run)
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Db_Statement_Exception
     */
    public function repairFileSystemAclNodes(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $fs = Tinebase_FileSystem::getInstance();
        $counter = 0;
        foreach (Tinebase_Core::getDb()->query('SELECT tnchild.id, tnparent.acl_node FROM ' .
                SQL_TABLE_PREFIX . 'tree_nodes as tnchild JOIN ' . SQL_TABLE_PREFIX .
                'tree_nodes as tnparent ON tnchild.parent_id = tnparent.id WHERE tnparent.acl_node IS NOT NULL '
                . 'AND tnchild.acl_node IS NULL')->fetchAll() as $row) {

            if ($opts->d) {
                echo "repairing acl of node id " . $row['id'] . PHP_EOL;
            } else {
                $fs->repairAclOfNode($row['id'], $row['acl_node']);
            }
            $counter++;
        }
        echo "repaired $counter nodes" . PHP_EOL;

        $sharedCounter = $fs->repairSharedAclOfNode($opts->d);

        echo "repaired $sharedCounter shared nodes" . PHP_EOL;

        return 0;
    }

    /**
     * recalculates the revision sizes and then the folder sizes
     *
     * @return int
     */
    public function fileSystemSizeRecalculation()
    {
        $this->_checkAdminRight();

        Tinebase_FileSystem::getInstance()->recalculateRevisionSize();

        Tinebase_FileSystem::getInstance()->recalculateFolderSize();

        return 0;
    }

    /**
     * checks if there are not yet indexed file objects and adds them to the index synchronously
     * that means this can be very time consuming
     *
     * @return int
     */
    public function fileSystemCheckIndexing()
    {
        $this->_checkAdminRight();

        Tinebase_FileSystem::getInstance()->checkIndexing();

        return 0;
    }

    /**
     * checks if there are files missing previews and creates them synchronously
     * that means this can be very time-consuming
     * also deletes previews of files that no longer exist
     *
     * accepts (node) ids=XXXX,YYYY as param
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Zend_Db_Statement_Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function fileSystemCheckPreviews(Zend_Console_Getopt $opts): int
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($opts);
        $ids = [];
        if (isset($data['ids'])) {
            $ids = explode(',', (string) $data['ids']);
        }

        Tinebase_FileSystem_Previews::getInstance()->resetErrorCount();
        Tinebase_FileSystem::getInstance()->sanitizePreviews($ids);

        return 0;
    }

    /**
     * recreates all previews
     *
     * @return int
     */
    public function fileSystemRecreateAllPreviews()
    {
        $this->_checkAdminRight();

        Tinebase_FileSystem_Previews::getInstance()->deleteAllPreviews();
        Tinebase_FileSystem::getInstance()->sanitizePreviews();

        return 0;
    }

    /**
     * repair a table
     * 
     * @param Zend_Console_Getopt $opts
     * 
     * @todo add more tables
     */
    public function repairTable($opts)
    {
        $this->_checkAdminRight();
        
        $this->_addOutputLogWriter();
        
        $data = $this->_parseArgs($opts, array('table'));
        
        switch ($data['table']) {
            case 'importexport_definition':
                Tinebase_ImportExportDefinition::getInstance()->repairTable();
                $result = 0;
                break;
            default:
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' No repair script found for ' . $data['table']);
                $result = 1;
        }
        
        exit($result);
    }

    /**
     * import records
     *
     * @param Zend_Console_Getopt $_opts
     * @return integer
     */
    public function import(Zend_Console_Getopt $_opts)
    {
       $result = parent::_import($_opts);
       return empty($result) ? 1 : 0;
    }

    /**
     * export records
     *
     * usage: method=Tinebase.export -- definition=DEFINITION_NAME
     *
     * @param Zend_Console_Getopt $_opts
     * @return int
     */
    public function export(Zend_Console_Getopt $_opts): int
    {
        $args = $this->_parseArgs($_opts, array('definition'));

        if (preg_match("/\.xml/", (string) $args['definition'])) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                $args['definition'],
                Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
            );
        } else {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($args['definition']);
        }

        /** @var Tinebase_Export_Abstract $export */
        $export = new $definition->plugin(null, null, [
            'definitionId' => $definition->getId()
        ]);
        if (method_exists($export, 'write')) {
            $export->generate();
            $fh = fopen('php://stdout', 'r+');
            $export->write($fh);
            return 0;
        } else {
            echo "Export write() not implemented yet";
            return 1;
        }
    }

    /**
     * transfer relations
     * 
     * @param Zend_Console_Getopt $opts
     */
    public function transferRelations($opts)
    {
        $this->_checkAdminRight();
        
        $this->_addOutputLogWriter();
        
        try {
            $args = $this->_parseArgs($opts, array('oldId', 'newId', 'model'));
        } catch (Tinebase_Exception_InvalidArgument) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Parameters "oldId", "newId" and "model" are required!');
            }
            exit(1);
        }
        
        $skippedEntries = Tinebase_Relations::getInstance()->transferRelations($args['oldId'], $args['newId'], $args['model']);

        if (! empty($skippedEntries) && Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . count($skippedEntries) . ' entries has been skipped:');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' The operation has been terminated successfully.');
        }

        return 0;
    }

    /**
     * repair function for persistent filters (favorites) without grants: this adds default grants for those filters.
     *
     * @return int
     */
    public function setDefaultGrantsOfPersistentFilters()
    {
        $this->_checkAdminRight();

        $this->_addOutputLogWriter(6);

        // get all persistent filters without grants
        // TODO this could be enhanced by allowing to set default grants for other filters, too
        Tinebase_PersistentFilter::getInstance()->doContainerACLChecks(false);
        $filters = Tinebase_PersistentFilter::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(array(),'', array('ignoreAcl' => true)));
        $filtersWithoutGrants = 0;

        foreach ($filters as $filter) {
            if (count($filter->grants) == 0) {
                // update to set default grants
                $filter = Tinebase_PersistentFilter::getInstance()->update($filter);
                $filtersWithoutGrants++;

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Updated filter: ' . print_r($filter->toArray(), true));
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' Set default grants for ' . $filtersWithoutGrants . ' filters'
                . ' (checked ' . count($filters) . ' in total).');
        }

        return 0;
    }

    /**
     *
     *
     * @return int
     */
    public function repairContainerOwner()
    {
        $this->_checkAdminRight();

        $this->_addOutputLogWriter(6);
        Tinebase_Container::getInstance()->setContainerOwners();

        return 0;
    }

    /**
     * show user report (number of enabled, disabled, ... users)
     *
     * TODO add system user count
     * TODO use twig?
     */
    public function userReport()
    {
        $this->_checkAdminRight();

        $translation = Tinebase_Translation::getTranslation('Tinebase');

        $userStatus = array(
            'total' => array(),
            Tinebase_Model_User::ACCOUNT_STATUS_ENABLED => array(/* 'showUserNames' => true, 'showClients' => true */),
            Tinebase_Model_User::ACCOUNT_STATUS_DISABLED => array(),
            Tinebase_Model_User::ACCOUNT_STATUS_BLOCKED => array(),
            Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED => array(),
            //'system' => array(),
            'lastmonth' => array('lastMonths' => 1, 'showUserNames' => true, 'showClients' => true),
            'last 3 months' => array('lastMonths' => 3),
        );

        foreach ($userStatus as $status => $options) {
            switch ($status) {
                case 'lastmonth':
                case 'last 3 months':
                    $userCount = Tinebase_User::getInstance()->getActiveUserCount($options['lastMonths']);
                    $text = $translation->_("Number of distinct users") . " (" . $status . "): " . $userCount . "\n";
                    break;
                case 'system':
                    $text = "TODO add me\n";
                    break;
                default:
                    $userCount = Tinebase_User::getInstance()->getUserCount($status);
                    $text = $translation->_("Number of users") . " (" . $status . "): " . $userCount . "\n";
            }
            echo $text;

            if (isset($options['showUserNames']) && $options['showUserNames']
                && in_array($status, array('lastmonth', 'last 3 months'))
                && isset($options['lastMonths'])
            ) {
                // TODO allow this for other status
                echo $translation->_("  User Accounts:\n");
                $userIds = Tinebase_User::getInstance()->getActiveUserIds($options['lastMonths']);
                foreach ($userIds as $userId) {
                    $user = Tinebase_User::getInstance()->getUserByProperty('accountId', $userId, 'Tinebase_Model_FullUser');
                    echo "  * " . $user->accountLoginName . ' / ' . $user->accountDisplayName . "\n";
                    if (isset($options['showClients']) && $options['showClients']) {
                        $userClients = Tinebase_AccessLog::getInstance()->getUserClients($user, $options['lastMonths']);
                        echo "    Clients: \n";
                        foreach ($userClients as $client) {
                            echo "     - $client\n";
                        }
                        echo "\n";
                    }
                }
            }
            echo "\n";
        }

        return 0;
    }

    public function cleanFileObjects()
    {
        $this->_checkAdminRight();

        Tinebase_FileSystem::getInstance()->clearFileObjects();
    }

    public function cleanAclTables()
    {
        $this->_checkAdminRight();

        Tinebase_Controller::getInstance()->cleanAclTables();
    }

    public function waitForActionQueueToEmpty()
    {
        $actionQueue = Tinebase_ActionQueue::getInstance();
        if (!$actionQueue->hasAsyncBackend()) {
            return 0;
        }

        $startTime = time();
        while ($actionQueue->getQueueSize() > 0 && time() - $startTime < 300) {
            usleep(1000);
        }

        return $actionQueue->getQueueSize();
    }

    /**
     * default is dryRun, to make changes use "-- dryRun=[0|false]
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function sanitizeGroupListSync(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($opts);
        if (isset($data['dryRun']) && ($data['dryRun'] === '0' || $data['dryRun'] === 'false')) {
            $dryRun = false;
        } else {
            $dryRun = true;
        }

        Tinebase_Group::getInstance()->sanitizeGroupListSync();

        return 0;
    }

    public function importGroupFromSyncBackend(Zend_Console_Getopt $opts): int
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($opts);
        if (!isset($data['group']) || empty($data['group'])) {
            echo 'mandatory argument "group" missing' . PHP_EOL;
            return 1;
        }

        $groupCtrl = Tinebase_Group::getInstance();
        if (!$groupCtrl instanceof Tinebase_Group_Interface_SyncAble) {
            echo 'no group syncable backend configured' . PHP_EOL;
            return 1;
        }
        $group = $groupCtrl->getGroupsFromSyncBackend(
            Zend_Ldap_Filter::equals('cn', $data['group'])
        )->getFirstRecord();

        if ($group) {
            $groupCtrl->addGroup($group);
            echo 'created group: ' . $group->name . PHP_EOL;
        } else {
            echo 'group ' . $data['name'] . ' not found in sync backend' . PHP_EOL;
        }

        return 0;
    }

    public function syncFileTreeFromBackupDB(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        exit('this is a dangerous operation');

        $data = $this->_parseArgs($opts);
        if (!isset($data['dbname']) || empty($data['dbname'])) {
            echo 'mandatory argument "dbname" missing' . PHP_EOL;
            return 1;
        }
        if (!isset($data['rootNodeId']) || empty($data['rootNodeId'])) {
            echo 'mandatory argument "rootNodeId" missing' . PHP_EOL;
            return 1;
        }

        $config = Tinebase_Core::getConfig();
        $oldDbName = $config->database->dbname;
        $oldUsername = $config->database->username;
        $oldPassword = $config->database->password;
        $config->database->dbname = $data['dbname'];
        if (isset($data['rootNodeId']))
            $config->database->username = $data['username'];
        if (isset($data['rootNodeId']))
            $config->database->password = $data['password'];
        Tinebase_Core::set(Tinebase_Core::DB, null);
        Tinebase_Core::getDb();
        Tinebase_FileSystem::getInstance()->resetBackends();

        $node = Tinebase_FileSystem::getInstance()->get($data['rootNodeId']);
        if ($node->acl_node === $node->getId()) {
            Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($node);
        }
        $records = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, [$node]);
        $func = function ($id, $func) use ($records) {
            foreach (Tinebase_FileSystem::getInstance()->getTreeNodeChildren($id) as $node) {
                if ($node->type !== \Tinebase_Model_Tree_FileObject::TYPE_FOLDER) continue;
                if ($node->acl_node === $node->getId()) {
                    Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($node);
                }
                $records->addRecord($node);
                $func($node->getId(), $func);
            }
        };
        $func($data['rootNodeId'], $func);

        $config->database->dbname = $oldDbName;
        $config->database->username = $oldUsername;
        $config->database->password = $oldPassword;
        Tinebase_Core::set(Tinebase_Core::DB, null);
        Tinebase_Core::getDb();
        Tinebase_FileSystem::getInstance()->resetBackends();
        Tinebase_Tree_NodeGrants::destroyInstance();

        Tinebase_FileSystem::getInstance()->rmdir(Tinebase_FileSystem::getInstance()->getPathOfNode($records->getFirstRecord(), true), true);

        foreach ($records as $record) {
            try {
                Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->create(clone $record);
            } catch (Exception) {
                Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->update(clone $record);
            }
            if ($record->acl_node === $record->getId()) {
                Tinebase_Tree_NodeGrants::getInstance()->setGrants($record);
            }
        }
    }

    /**
     * utility function to be adjusted for the needs at hand at the time of usage
     */
    public function restoreOrDiffEtcFileTreeFromBackupDB(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($opts);
        if (!isset($data['dbname']) || empty($data['dbname'])) {
            echo 'mandatory argument "dbname" missing' . PHP_EOL;
            return 1;
        }
        if (!isset($data['rootNodeId']) || empty($data['rootNodeId'])) {
            echo 'mandatory argument "rootNodeId" missing' . PHP_EOL;
            return 1;
        }

        $config = Tinebase_Core::getConfig();
        $oldDbName = $config->database->dbname;
        $config->database->dbname = $data['dbname'];
        Tinebase_Core::set(Tinebase_Core::DB, null);
        Tinebase_Core::getDb();
        Tinebase_FileSystem::getInstance()->resetBackends();

        $records = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
        $func = function($id, $func) use($records) {
            foreach (Tinebase_FileSystem::getInstance()->getTreeNodeChildren($id) as $node) {
                $records->addRecord($node);
                $func($node->getId(), $func);
            }
        };
        $func($data['rootNodeId'], $func);

        $config->database->dbname = $oldDbName;
        Tinebase_Core::set(Tinebase_Core::DB, null);
        Tinebase_Core::getDb();
        Tinebase_FileSystem::getInstance()->resetBackends();

        $newRecords = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
        $func = function($id, $func) use($newRecords) {
            foreach (Tinebase_FileSystem::getInstance()->getTreeNodeChildren($id) as $node) {
                $newRecords->addRecord($node);
                $func($node->getId(), $func);
            }
        };
        $func($data['rootNodeId'], $func);

        $func = function($record, $msg) {
            echo $record->getId() . ' ' . $record->name . ' ' . $msg . PHP_EOL;
        };

        /** @var Tinebase_Model_Tree_Node $record */
        foreach ($records as $record) {

            $records->removeRecord($record);
            if (false === ($newRecord = $newRecords->getById($record->getId()))) {
                $func($record, 'missing in current db');
                continue;
            }
            $newRecords->removeRecord($newRecord);
            if ($record->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER && $newRecord->hash !== $record->hash) {
                $func($record, 'hash changed to ' . $newRecord->hash);
            }
            if ($newRecord->name !== $record->name) {
                $func($record, 'changed to ' . $newRecord->name);
            }
        }

        foreach ($newRecords as $newRecord) {
            $func($newRecord, 'is new in current db');
        }
        return 0;
    }

    /**
     * re-adds all scheduler tasks (if they are missing)
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function resetSchedulerTasks(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        Tinebase_Setup_Initialize::addSchedulerTasks();

        return 0;
    }

    /**
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function reportPreviewStatus(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        print_r(Tinebase_FileSystem::getInstance()->reportPreviewStatus());

        return 0;
    }

    /**
     * @param Zend_Console_Getopt $opts
     * @return int
     */
    public function reReplicateContainer(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();

        $data = $this->_parseArgs($opts);
        if (!isset($data['container'])) {
            echo 'usage: --reReplicateContainer -- container={containerId}' . PHP_EOL;
            return 1;
        }

        $db = Tinebase_Core::getDb();
        $transId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

        /** @var Tinebase_Model_Container $container */
        $container = Tinebase_Container::getInstance()->get($data['container']);
        $container->application_id;
        $container->model;

        $filter = new Tinebase_Model_ContainerContentFilter([
            ['field' => 'container_id', 'operator' => 'equals',  'value' => $container->getId()],
        ]);
        $result = array_keys(Tinebase_Container::getInstance()->getContentBackend()
            ->search($filter, null, ['record_id']));

        if (count($result) > 0) {
            $db->query('SELECT @i := (SELECT MAX(instance_seq) FROM ' . SQL_TABLE_PREFIX . 'timemachine_modlog)');

            $db->query('UPDATE ' . SQL_TABLE_PREFIX . 'timemachine_modlog SET instance_seq = @i:=@i+1, instance_id = "'
                . Tinebase_Core::getTinebaseId() . '" WHERE record_type = "' . $container->model .
                '" AND application_id = "' . $container->application_id . '" AND record_id IN ("' .
                join('","', $result) . '") ORDER BY instance_seq ASC');

            $autoInc = $db->query('SELECT @i:=@i+1')->fetchColumn();

            $db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . 'timemachine_modlog AUTO_INCREMENT ' . $autoInc);
        }

        Tinebase_TransactionManager::getInstance()->commitTransaction($transId);

        return 0;
    }

    public function testNotification()
    {
        $this->_checkAdminRight();

        $recipient = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        $messageSubject = 'Tine 2.0 test notification';
        $messageBody = 'Tine 2.0 test notification has been sent successfully';
        Tinebase_Notification::getInstance()->send(null, array($recipient), $messageSubject, $messageBody);
        return 0;
    }

    /**
     * Delete duplicate personal container without content.
     *
     * e.g. php tine20.php --method=Tinebase.duplicatePersonalContainerCheck app=Addressbook [-d]
     *
     * @param Zend_Console_Getopt $opts
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_SystemContainer
     */
    public function duplicatePersonalContainerCheck(Zend_Console_Getopt $opts)
    {
        $this->_checkAdminRight();
        $args = $this->_parseArgs($opts, array('app'));

        $removeCount = Tinebase_Container::getInstance()->deleteDuplicateContainer($args['app'], $opts->d);
        if ($opts->d) {
            echo "Would remove " . $removeCount . " duplicates\n";
        } else {
            echo $removeCount . " duplicates removed\n";
        }
    }

    public function repairTreeIsDeletedState($opts)
    {
        $this->_checkAdminRight();
        Tinebase_FileSystem::getInstance()->repairTreeIsDeletedState();
    }

    /**
     * Generates new js Translation List files for given locale and path
     * If no locales is given all available locales are generated
     *
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function generateTranslationLists($opts): int
    {
        $this->_checkAdminRight();
        $args = $this->_parseArgs($opts);
        $locale = $args['locale'] ?? null;
        $path = $args['path'] ?? null;

        $translations = new Tinebase_Translation();
        $translations->generateTranslationLists($locale, $path);

        return 0;
    }

    /**
     * @deprecated no longer needed in 2025.11+ (we no longer create avscan notes)
     * @param Zend_Console_Getopt $opts
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Statement_Exception
     */
    public function removeAllAvScanNotes(Zend_Console_Getopt $opts): int
    {
        $this->_checkAdminRight();
        $db = Tinebase_Core::getDb();
        $args = $this->_parseArgs($opts);
        $start = isset($args['start']) ? (int)$args['start'] : 0;
        $limit = 10000;

        echo 'starting at ' . $start . PHP_EOL;

        do {
            $run = false;
            foreach ($db->query('select id from ' . SQL_TABLE_PREFIX . 'tree_nodes ORDER BY id ASC LIMIT ' . $start . ', ' . $limit)->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $run = true;
                $db->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'notes where record_id = ' . $db->quote($id) . ' AND note_type_id = "avscan"');
            }
            $start += $limit;
            echo 'finished ' . $start . PHP_EOL;
        } while ($run);

        return 0;
    }

    public function exportGroupListIds(): int
    {
        $this->_checkAdminRight();

        $backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME => 'groups',
            Tinebase_Backend_Sql::MODEL_NAME => Tinebase_Model_Group::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => false,
        ]);

        $groups = $backend->getAll()->filter(fn($group) => $group->list_id);

        foreach ($groups as $group) {
            echo $group->getId() . ';' . $group->list_id . PHP_EOL;
        }

        return 0;
    }

    public function setGroupsListIds(Zend_Console_Getopt $opts): int
    {
        $this->_checkAdminRight();

        $args = $this->_parseArgs($opts, ['file']);
        if (!($fh = fopen($args['file'], 'r'))) {
            echo 'can\'t open file ' . $args['file'] . PHP_EOL;
            return 1;
        }
        
        $db = Tinebase_Core::getDb();

        while ($row = fgetcsv($fh, separator: ';')) {
            $transaction = Tinebase_RAII::getTransactionManagerRAII();
            $groupId = $row[0];
            $listId = $row[1];

            try {
                if (false === ($oldListId = $db->query('SELECT list_id FROM ' . SQL_TABLE_PREFIX . 'groups WHERE id = ' . $db->quoteInto('?', $groupId))->fetchColumn())) {
                    throw new Tinebase_Exception_NotFound('group not found');
                }
                if (null === $oldListId) {
                    throw new Tinebase_Exception_UnexpectedValue('old list id is null');
                }

                $db->update(SQL_TABLE_PREFIX . 'groups', ['list_id' => $listId], 'id = ' . $db->quoteInto('?', $groupId));
                // addressbook_lists has foreign key constraints that will update adb_list_m_role / addressbook_list_members
                $db->update(SQL_TABLE_PREFIX . 'addressbook_lists', ['id' => $listId], 'id = ' . $db->quoteInto('?', $oldListId));

                $db->update(SQL_TABLE_PREFIX . 'notes', ['record_id' => $listId], 'record_id = ' . $db->quoteInto('?', $oldListId) . ' AND record_model = "' . Addressbook_Model_List::class . '"');

                $db->update(SQL_TABLE_PREFIX . 'cal_attendee', ['user_id' => $listId], 'user_id = ' . $db->quoteInto('?', $oldListId));

                $db->update(SQL_TABLE_PREFIX . 'relations', ['own_id' => $listId], 'own_id = ' . $db->quoteInto('?', $oldListId) . ' AND own_model = "' . Addressbook_Model_List::class . '" AND own_backend = "Sql"');
                $db->update(SQL_TABLE_PREFIX . 'relations', ['related_id' => $listId], 'related_id = ' . $db->quoteInto('?', $oldListId) . ' AND related_model = "' . Addressbook_Model_List::class . '"');


                $transaction->release();
            } catch (Exception $e) {
                echo 'failed to process row: "' . $row[0] . ';' . $row[1] . '"' . PHP_EOL;
                echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL . PHP_EOL;
            }

            unset($transaction);
        }

        return 0;
    }
}
