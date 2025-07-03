<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract class to import data from egw14
 * 
 * @TODO extend Tinebase_Import_Abstract
 * 
 * @package     Tinebase
 * @subpackage  Setup
 */
abstract class Tinebase_Setup_Import_Egw14_Abstract
{
    protected $_appname;
    protected $_egwTableName;
    protected $_egwOwnerColumn;
    protected $_defaultContainerConfigProperty;
    protected $_tineRecordModel;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_egwDb = null;
    
    protected $_failures = array();

    /**
     * @var Tinebase_DateTime
     */
    protected ?Tinebase_DateTime $_migrationStartTime = null;
    
    /**
     * import result array
     * 
     * @var array
     */
    protected $_importResult = array(
        'totalcount'        => 0,
        'failcount'         => 0,
        'duplicatecount'    => 0,
    );
    
    /**
     * 
     * @var array
     */
    protected $_accountIdMapCache = array();
    
    /**
     * in class container cache
     * 
     * @var array
     */
    protected $_personalContainerCache = array();
    
    /**
     * in class container cache
     * 
     * @var array
     */
    protected $_privateContainerCache = array();
    
    /**
     * in class tag cache
     * 
     * @var array
     */
    protected $_tagMapCache = array();
    
    /**
     * map egwPriority => tine prioroty
     * 
     * @see etemplate/inc/class.select_widget.inc.php
     * @var array
     */
    protected $_priorityMap = array(
        0 => null,  // not set
        1 => 0,     // low
        2 => 1,     // normaml
        3 => 2,     // high
    );
    
    /**
     * map egwGrant => tine grant
     * 
     * @todo   move to a generic egw import helper
     * @see phpgwapi/inc/class.egw.inc.php
     * @var array
     */
    protected $_grantMap = array(
         1 => Tinebase_Model_Grants::GRANT_READ,
         2 => Tinebase_Model_Grants::GRANT_ADD,
         4 => Tinebase_Model_Grants::GRANT_EDIT,
         8 => Tinebase_Model_Grants::GRANT_DELETE,
        16 => Calendar_Model_EventPersonalGrants::GRANT_PRIVATE,
    );
    
    /**
     * constructs this importer
     * 
     * @param Zend_Config               $_config
     * @param Zend_Log                  $_log
     */
    public function __construct(protected $_config, protected $_log)
    {
        $dbConfig = $this->_config->all ? $this->_config->all->egwDb : $this->_config->egwDb;
        $this->_egwDb = Zend_Db::factory('PDO_MYSQL', $dbConfig);
        
        /* egw config is utf-8 but db needs utf8 as string -> leave as config atm.
        $select = $this->_egwDb->select()
            ->from(array('grants' => 'egw_config'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('config_name') . ' = (?)', 'system_charset'));
            
        $egwConfig = $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
        $charset = $egwConfig[0]['config_value'];
        */
        
        $this->_log->INFO(__METHOD__ . '::' . __LINE__ . " setting egw charset to {$this->_config->all->egwDb->charset}");
        $this->_egwDb->query("SET NAMES {$dbConfig->charset}");

        if ($this->_config->all->dryRun) {
            Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        }
        
        if ($this->_config->all->accountIdMap) {
            $this->_accountIdMapCache = include($this->_config->all->accountIdMap);
        }
    }
    
    /**
     * do the import
     */
    abstract public function import();
    
    /**
     * appends filter to egw14 select obj. for raw record retirval
     * 
     * @param Zend_Db_Select $_select
     * @return void
     */
    protected function _appendRecordFilter($_select)
    {
        if ($this->_config->all->egwOwnerFilter) {
            $ownerIds = explode(',', preg_replace('/\s/', '', $this->_config->all->egwOwnerFilter));
            
            $_select->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier($this->_egwOwnerColumn) . ' IN (?)', $ownerIds));
        }
    }
    
    /**
     * get estimate count of records to import
     */
    protected function _getEgwRecordEstimate()
    {
        $select = $this->_egwDb->select()
            ->from(array('records' => $this->_egwTableName), 'COUNT(*) AS count');
            
        $this->_appendRecordFilter($select);
        
        $recordCount = Tinebase_Helper::array_value(0, $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC));
        return $recordCount['count'];
    }
    
    /**
     * gets a page of raw egw record data
     * 
     * @param  int $pageNumber
     * @param  int $pageSize
     * @return array
     */
    protected function _getRawEgwRecordPage($pageNumber, $pageSize)
    {
        $select = $this->_egwDb->select()
            ->from(array('records' => $this->_egwTableName))
            ->limitPage($pageNumber, $pageSize);
            
        $this->_appendRecordFilter($select);
        
        $recordPage = $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
        
        return $recordPage;
    }
    
    /**
     * returns appname of the app this import is for
     * 
     * @return Tinebase_Model_Application
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function getApplication()
    {
        if (! $this->_appname) {
            throw new Tinebase_Exception_UnexpectedValue('app name is not defined');
        }
        
        return Tinebase_Application::getInstance()->getApplicationByName($this->_appname);
    }
    /**
     * converts egw date to Tinebase_DateTime
     * 
     * @param  int $_egwTS
     * @param  string $_tz timezone
     * @return Tinebase_DateTime
     */
    public function convertDate($_egwTS, $_tz=null)
    {
        if (! $_egwTS) {
            return null;
        }
        
        $_tz = $_tz ?: $this->_config->all->egwServerTimezone;
        
        $date = new Tinebase_DateTime($_egwTS, $_tz);
        
        return $date;
    }
    
    /**
     * converts egw -> tine priority
     * 
     * @param  int $_egwPrio
     * @return mixed
     */
    public function getPriority($_egwPrio)
    {
        return $this->_priorityMap[(int) $_egwPrio];
    }
    
    /**
     * maps egw account to tine 2.0 user/group Id
     * 
     * @param  int $_egwAccountId
     * @return string 
     */
    public function mapAccountIdEgw2Tine($_egwAccountId, $_throwException = TRUE)
    {
        if (! isset ($this->_accountIdMapCache[$_egwAccountId])) {
            
            try {
                $tineUserId = $_egwAccountId;
                if ($this->_config->all->accountIdMap) {
                    throw new Exception("account with id {$_egwAccountId} not found in static map");
                } else if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
                    if ((int) $_egwAccountId < 0) {
                        /** @var Tinebase_Group_Ldap $groupBackend */
                        $groupBackend = Tinebase_Group::getInstance();
                        $this->_accountIdMapCache[$_egwAccountId] = $groupBackend->resolveGIdNumberToUUId($_egwAccountId);
                    } else {
                        /** @var Tinebase_User_Ldap $userBackend */
                        $userBackend = Tinebase_User::getInstance();
                        $this->_accountIdMapCache[$_egwAccountId] = $userBackend->resolveUIdNumberToUUId($_egwAccountId);
                    }
                } else {
                    $this->_accountIdMapCache[$_egwAccountId] = abs($_egwAccountId);
                }
            } catch(Exception $e) {
                if ($_throwException !== FALSE) {
                    throw $e;
                }
                
                $this->_accountIdMapCache[$_egwAccountId] = null;
            }
        }
        
        return $this->_accountIdMapCache[$_egwAccountId];
    }
    
    /**
     * maps tine 2.0 user/group Id to egw account Id
     * 
     * @param  int      $_tineAccountId
     * @param  string   $_accountType
     * @return string 
     */
    public function mapAccountIdTine2Egw($_tineAccountId, $_accountType = 'User')
    {
        $egwAccountId = null;
        
        $keys = array_keys($this->_accountIdMapCache, $_tineAccountId);
        foreach ((array) $keys as $candidate) {
            if ($_accountType == 'User' && (int) $candidate > 0) {
                $egwAccountId = $candidate;
            } else if ($_accountType == 'Group' && (int) $candidate < 0) {
                $egwAccountId = $candidate;
            }
        }
        
        // not in cache
        if (! $egwAccountId) {
            if ($this->_config->all->accountIdMap) {
                throw new Exception("account with id {$_tineAccountId} not found in static map");
            } else if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
                if ($_accountType == 'User') {
                    /** @var Tinebase_User_Ldap $userBackend */
                    $userBackend = Tinebase_User::getInstance();
                    $_tineAccountId = $userBackend->resolveUUIdToUIdNumber($_tineAccountId);
                } else {
                    /** @var Tinebase_Group_Ldap $groupBackend */
                    $groupBackend = Tinebase_Group::getInstance();
                    $_tineAccountId = $groupBackend->resolveUUIdToGIdNumber($_tineAccountId);
                }
            } else {
                $egwAccountId = ($_accountType == 'User' ? '' : '-') . $_tineAccountId;
            }
            
            $this->_accountIdMapCache[$egwAccountId] = $_tineAccountId;
        }
        
        return $egwAccountId;
    }
    
    /**
     * gets the personal container of given user
     * 
     * @param  string $userId
     * @return Tinebase_Model_Container
     */
    public function getPersonalContainer($userId)
    {
        if (! (isset($this->_personalContainerCache[$userId]) || array_key_exists($userId, $this->_personalContainerCache))) {
            // get container by preference to ensure its the default personal
            $defaultContainerId = Tinebase_Core::getPreference($this->getApplication()->name)->getValueForUser($this->_defaultContainerConfigProperty, $userId, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER);
            $container = Tinebase_Container::getInstance()->getContainerById($defaultContainerId);
            
            // detect if container just got created
            $isNewContainer = false;
            if ($container->creation_time instanceof DateTime) {
                $isNewContainer = $this->_migrationStartTime->isEarlier($container->creation_time);
            }
            
            if (($isNewContainer && $this->_config->setPersonalContainerGrants) || $this->_config->forcePersonalContainerGrants) {
                // resolve grants based on user/groupmemberships
                $grants = $this->getGrantsByOwner($this->getApplication()->name, $userId);
                Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, TRUE);
            }
            
            $this->_personalContainerCache[$userId] = $container;
        }
        
        return $this->_personalContainerCache[$userId];
    }
    
    /**
     * get a private container
     * 
     * @param  string $userId
     * @return Tinebase_Model_Container
     */
    public function getPrivateContainer($userId)
    {
        $privateString = 'Private';
        
        if (! (isset($this->_privateContainerCache[$userId]) || array_key_exists($userId, $this->_privateContainerCache))) {
            $personalContainers = Tinebase_Container::getInstance()->getPersonalContainer($userId,
                $this->getApplication()->name, $userId, Tinebase_Model_Grants::GRANT_ADMIN, TRUE);
            $privateContainer = $personalContainers->filter('name', $privateString);
            
            if (count($privateContainer) < 1) {
                $container = new Tinebase_Model_Container(array(
                    'name'           => $privateString,
                    'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
                    'owner_id'       => $userId,
                    'application_id' => $this->getApplication()->getId(),
                    'model'          => $this->_tineRecordModel,
                    'backend'        => 'sql',
                ));
                
                // NOTE: if no grants are given, container class gives all grants to accountId
                $privateContainer = Tinebase_Container::getInstance()->addContainer($container, null, TRUE);
            } else {
                $privateContainer = $privateContainer->getFirstRecord();
            }
            
            $this->_privateContainerCache[$userId] = $privateContainer;
        }
        
        return $this->_privateContainerCache[$userId];
    }
    
    /**
     * returns grants by owner
     * 
     * eGW has owner based grants whereas Tine 2.0 has container based grants.
     * this class reads the egw owner grants and converts them into Tine 2.0 grants
     * attacheable to a tine 2.0 container
     * 
     * @param  string $_application
     * @param  string $_accountId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Grant
     * @throws Tinebase_Exception_NotFound
     */
    public function getGrantsByOwner($_application, $_accountId)
    {
        $egwAccountId = $this->mapAccountIdTine2Egw($_accountId);
        $acl_account = array($egwAccountId);
        
        if ($egwAccountId > 0) {
            $user     = Tinebase_User::getInstance()->getUserById($_accountId);
            $groupIds = $user->getGroupMemberships();
            
            
            foreach($groupIds as $groupId) {
                try {
                    $acl_account[] = $this->mapAccountIdTine2Egw($groupId, 'Group');
                } catch (Exception $e) {
                    $this->_log->INFO(__METHOD__ . '::' . __LINE__ . " skipping group {$groupId} in grants migration cause: " . $e);
                }
            }
        }
        
        $select = $this->_egwDb->select()
            ->from(array('grants' => 'egw_acl'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('acl_appname') . ' = ?', $_application))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('acl_account') . ' IN (?)', $acl_account));
            
        $egwGrantDatas = $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);

        // in a first run we merge grants from different sources
        $effectiveGrants = array();
        if ($egwAccountId > 0) {
            // owner has implicitly all grants in egw
            $effectiveGrants[$egwAccountId] = 31;
        }
        foreach ($egwGrantDatas as $egwGrantData) {
            // grants are int != 0
            if ( (int) $egwGrantData['acl_location'] == 0) {
                continue;
            }
            
            // NOTE: The grant source is not resolveable in Tine 2.0!
            //       In Tine 2.0 grants are directly given to a container
            $grantsSource      = $egwGrantData['acl_account'];
            $grantsDestination = $egwGrantData['acl_location'];
            $grantsGiven       = $egwGrantData['acl_rights'];
            
            if (! (isset($effectiveGrants[$grantsDestination]) || array_key_exists($grantsDestination, $effectiveGrants))) {
                $effectiveGrants[$grantsDestination] = 0;
            }
            $effectiveGrants[$grantsDestination] |= $grantsGiven;
        }
        //print_r($effectiveGrants);
        
        // convert to tine grants
        $tineGrants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants');
        foreach ($effectiveGrants as $grantAccount => $egwGrants) {
            $tineGrant = new Tinebase_Model_Grants(array(
                'account_id' => $this->mapAccountIdEgw2Tine($grantAccount),
                'account_type' => (int) $grantAccount > 0 ? 
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_USER : 
                    Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
            ));
            
            foreach ($this->_grantMap as $egwGrant => $tineGrantString) {
                $tineGrant->{$tineGrantString} = (bool) ($egwGrants & $egwGrant);
            }
            
            // the owner also gets admin grants
            if ($egwAccountId > 0 && $grantAccount == $egwAccountId) {
                $tineGrant->{Tinebase_Model_Grants::GRANT_ADMIN} = TRUE;
            }
            
            $tineGrant->{Tinebase_Model_Grants::GRANT_EXPORT} = $tineGrant->{Tinebase_Model_Grants::GRANT_READ};
            $tineGrant->{Tinebase_Model_Grants::GRANT_SYNC} = $tineGrant->{Tinebase_Model_Grants::GRANT_READ};
            $tineGrant->{Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY} = $this->getApplication()->name == 'Calendar';
            
            $tineGrants->addRecord($tineGrant);
        }
        // for group owners (e.g. group addressbooks) we need an container admin
        if ($egwAccountId < 0) {
            $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
            $tineGrant = new Tinebase_Model_Grants(array(
                'account_id' => $this->mapAccountIdEgw2Tine($_accountId),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP
            ));
            $tineGrant->{Tinebase_Model_Grants::GRANT_ADMIN} = TRUE;
            
            $tineGrants->addRecord($tineGrant);
        }
        
        return $tineGrants;
    }
    
    /**
     * converts egw categories into tine20 tags
     * 
     * @param string $catIds (comma seperated list)
     * @return array of tag ids
     */
    public function convertCategories($catIds)
    {
        $tagIds = array();
        
        foreach((array) explode(',', $catIds) as $catId) {
            if ($catId) {
                $tagId = $this->getTag($catId);
                
                if ($tagId) {
                    $tagIds[] = $tagId;
                }
            }
        }
        
        return $tagIds;
    }
    
    /**
     * converts category to tag
     * 
     * @param int $catId
     * @return string tagid
     */
    public function getTag($catId)
    {
        if (! (isset($this->_tagMapCache[$catId]) || array_key_exists($catId, $this->_tagMapCache))) {
            $select = $this->_egwDb->select()
                ->from(array('cats' => 'egw_categories'))
                ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cat_id') . ' = ?', $catId));
            
            $cat = $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
            $cat = count($cat) === 1 ? $cat[0] : null;
            
            if (! $cat) {
                $this->_log->DEBUG(__METHOD__ . '::' . __LINE__ . " category {$catId} not found in egw, skipping tag");
                return $this->_tagMapCache[$catId] = null;
            }
            
            $tineDb = Tinebase_Core::getDb();
            $select = $tineDb->select()
                ->from(array('tags' => $tineDb->table_prefix . 'tags'))
                ->where($tineDb->quoteInto($tineDb->quoteIdentifier('name') . ' LIKE ?', $cat['cat_name']));
            
            $tag = $tineDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
            $tag = count($tag) > 0 ? $tag[0] : null;
            
            if ($tag) {
                return $this->_tagMapCache[$catId] = $tag['id'];
            }
            
            // create tag
            $catData = unserialize($cat['cat_data']);
            
            $tagId = Tinebase_Record_Abstract::generateUID();
            $tagType = $cat['cat_access'] == 'public' ? Tinebase_Model_Tag::TYPE_SHARED : Tinebase_Model_Tag::TYPE_PERSONAL;
            $tagOwner = $tagType == Tinebase_Model_Tag::TYPE_SHARED ? 0 : $this->mapAccountIdEgw2Tine($cat['cat_owner']);
            
            $this->_log->NOTICE(__METHOD__ . '::' . __LINE__ . " creating new {$tagType} tag '{$cat['cat_name']}'");
            
            $tineDb->insert($tineDb->table_prefix . 'tags', array(
                'id'                     => $tagId,
                'type'                   => $tagType,
                'owner'                  => $tagOwner,
                'name'                   => $cat['cat_name'],
                'description'            => $cat['cat_description'],
                'color'                  => $catData['color'],
                'created_by'             => $tagOwner ?: Tinebase_Core::getUser()->getId(),
                'creation_time'          => $cat['last_mod'] ? $this->convertDate($cat['last_mod']) : Tinebase_DateTime::now(),
            ));
            
            $right = new Tinebase_Model_TagRight(array(
                'tag_id'        => $tagId,
                'account_type'  => $tagType == Tinebase_Model_Tag::TYPE_SHARED ? Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE : Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                'account_id'    => $tagOwner,
                'view_right'    => true,
                'use_right'     => true,
            ));
    
            Tinebase_Tags::getInstance()->setRights($right);
            Tinebase_Tags::getInstance()->setContexts(array(0), $tagId);
            
            $this->_tagMapCache[$catId] = $tagId;
        }
        
        return $this->_tagMapCache[$catId];
    }
    
    /**
     * attach tags to record
     * 
     * NOTE occurence counting is done at the end of the imports
     * 
     * @param array $tagIds
     * @param string $recordId
     */
    public function attachTags($tagIds, $recordId)
    {
        $tineDb = Tinebase_Core::getDb();
        
        foreach ((array) $tagIds as $tagId) {
            $tineDb->insert($tineDb->table_prefix . 'tagging', array(
                'tag_id'         => $tagId,
                'application_id' => $this->getApplication()->getId(),
                'record_id'      => $recordId,
                'record_backend_id' => ' ',
            ));
        }
    }
    
    /**
     * save alarms
     * 
     * @param  Tinebase_Record_RecordSet $alarms
     * @param  string $recordId
     * @return Tinebase_Record_RecordSet
     */
    public function saveAlarms(Tinebase_Record_RecordSet $alarms, $recordId)
    {
        $createdAlarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $alarms->record_id = $recordId;
        
        $ac = Tinebase_Alarm::getInstance();
        foreach($alarms as $alarm) {
            $createdAlarms->addRecord($ac->create($alarm));
        }
        
        return $createdAlarms;
    }

    protected function _getInfoLogData(int $recordId, string $recordApp, $allButTheLastOne = false): string
    {
        $egwInfologs = $this->_fetchInfoLogs($recordId, $recordApp);

        echo "Found " . count($egwInfologs) . " infologs for " . $recordApp . ' / ' . $recordId . "\n";

        if (count($egwInfologs) > 1 && $allButTheLastOne) {
            array_pop($egwInfologs);
        }

        $result = '';
        foreach ($egwInfologs as $infolog) {
            $subject = !empty($infolog['info_subject']) && $infolog['info_subject'] !== '-' ? $infolog['info_subject'] . ": \n" : '';
            $from = !empty($infolog['info_from']) ? $infolog['info_from'] . "\n" : '';
            $result .= '[' . $infolog['info_type'] . '] ' .  "\n"  . $subject . $from
               . $infolog['info_des'] . "\n\n";
        }
        return $result;
    }

    protected function _fetchInfoLogs(int $recordId, string $recordApp): array
    {
        $select = $this->_egwDb->select()
            ->from(array('infolog' => 'egw_infolog'))
            ->join('egw_links', 'infolog.info_id = egw_links.link_id1')
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('egw_links.link_app2') . ' = ?', $recordApp))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('egw_links.link_app1') . ' = ?', 'infolog'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('egw_links.link_id2') . ' = ?', $recordId));

        return $this->_egwDb->fetchAll($select, null, Zend_Db::FETCH_ASSOC);
    }

    protected function _getInfoLogTags(int $recordId, string $recordApp): array
    {
        $egwInfologs = $this->_fetchInfoLogs($recordId, $recordApp);

        $result = [];
        foreach ($egwInfologs as $infolog) {
            $result = $this->convertCategories($infolog['info_cat']);
        }
        return $result;
    }

    protected function _getEgwCustomFieldConfig(string $recordApp): array
    {
        $select = $this->_egwDb->select()
            ->from('egw_config')
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('config_app') . ' = ?', $recordApp))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('config_name') . ' = ?', 'customfields'));
        $config = $this->_egwDb->fetchAssoc($select);
        if (isset($config['addressbook']['config_value'])) {
            $value = $config['addressbook']['config_value'];
            // somehow the serialized config data might be corrupted - fix from stackoverflow (THX!)
            // https://stackoverflow.com/questions/10152904/how-to-repair-a-serialized-string-which-has-been-corrupted-by-an-incorrect-byte/21389439#21389439
            $value = preg_replace_callback ( '!s:(\d+):"(.*?)";!', fn($match) => ($match[1] == strlen((string) $match[2])) ? $match[0] : 's:' . strlen((string) $match[2]) . ':"' . $match[2] . '";', $value);
            return unserialize($value);
        } else {
            return [];
        }
    }
}
