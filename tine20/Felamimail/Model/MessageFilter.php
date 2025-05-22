<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        replace 'custom' filters with normal filter classes
 * @todo        should implement acl filter
 */

/**
 * cache entry filter Class
 * 
 * @package     Felamimail
 */
class Felamimail_Model_MessageFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Felamimail_Model_Message::class;
    
    /**
     * path for all inboxes filter
     */
    const PATH_ALLINBOXES = '/*/INBOX';

    /**
     * path for all folders
     */
    const PATH_ALLFOLDERS = '/**';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'            => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Felamimail_Model_Message')), 
        'query'         => array(
            'filter'        => Tinebase_Model_Filter_Query::class,
            'options'       => array(
                'fields' => array('aggregated_data'),
                'modelName' => Felamimail_Model_Message::class,
            )
        ),
        'folder_id'     => array('filter' => 'Tinebase_Model_Filter_Id'),
        'aggregated_data'=> array('filter' => Tinebase_Model_Filter_Text::class),
        'subject'       => array('filter' => Tinebase_Model_Filter_Text::class),
        'from_email'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'from_name'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'received'      => array('filter' => Tinebase_Model_Filter_DateTime::class),
        'messageuid'    => array('filter' => 'Tinebase_Model_Filter_Int'),
        'message_id'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'size'          => array('filter' => 'Tinebase_Model_Filter_Int'),
        'has_attachment'=> ['filter' => Tinebase_Model_Filter_Bool::class],
    // custom filters
        'path'          => array('custom' => true),
        'to'            => array('filter' => Felamimail_Model_RecipientFilter::class),
        'to_list'       => array('filter' => Tinebase_Model_Filter_Text::class),
        'cc'            => array('filter' => Felamimail_Model_RecipientFilter::class),
        'cc_list'       => array('filter' => Tinebase_Model_Filter_Text::class),
        'bcc'           => array('filter' => Felamimail_Model_RecipientFilter::class),
        'bcc_list'      => array('filter' => Tinebase_Model_Filter_Text::class),
        'flags'         => array('custom' => true, 'requiredCols' => array('flags' => 'felamimail_cache_msg_flag.flag')),
        'account_id'    => array('custom' => true),
        'tag'           => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'felamimail_cache_message.id',
            'applicationName' => 'Felamimail',
        )),
    );

    /**
     * only fetch user account ids once
     * 
     * @var array
     */
    protected $_userAccountIds = array();
    
    /**
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                       $_select
     * @param  Felamimail_Backend_Cache_Sql_Message $_backend
     * @return void
     */
    public function appendFilterSql($_select, $_backend)
    {
        foreach ($this->_customData as $customData) {
            if ($customData['field'] == 'account_id') {
                $this->_addAccountFilter($_select, $_backend, (array) $customData['value']);
            } else if ($customData['field'] == 'path') {
                $this->_addPathSql($_select, $_backend, $customData);
            } else {
                $this->_addFlagsSql($_select, $_backend, $customData);
            }
        }
        foreach ($this->_filterObjects as $filterObject) {
            if ($filterObject instanceof Felamimail_Model_RecipientFilter) {
                $filterObject->appendFilterSql($_select, $_backend);
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $_select->__toString());
    }
    
    /**
     * add account filter
     * 
     * @param Zend_Db_Select $_select
     * @param  Felamimail_Backend_Cache_Sql_Message $_backend
     * @param array $_accountIds
     */
    protected function _addAccountFilter($_select, $_backend, array $_accountIds = array())
    {
        $accountIds = (empty($_accountIds)) ? $this->_getUserAccountIds() : $_accountIds;
        
        $db = $_backend->getAdapter();
        
        if (empty($accountIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' No email accounts found');
            $_select->where('1=0');
        } else {
            $_select->where($db->quoteInto($db->quoteIdentifier("felamimail_cache_message.account_id") . ' IN (?)', $accountIds));
        }
    }
    
    /**
     * get user account ids
     * 
     * @return array
     */
    protected function _getUserAccountIds()
    {
        if (empty($this->_userAccountIds)) {
            $this->_userAccountIds = Felamimail_Controller_Account::getInstance()->search(
                Felamimail_Controller_Account::getVisibleAccountsFilterForUser(), NULL, FALSE, TRUE);
        }
        
        return $this->_userAccountIds;
    }

    /**
     * add path custom filter
     * 
     * @param  Zend_Db_Select                       $_select
     * @param  Felamimail_Backend_Cache_Sql_Message $_backend
     * @param  array                                $_filterData
     * @return void
     */
    protected function _addPathSql($_select, $_backend, $_filterData)
    {
        $db = $_backend->getAdapter();
        $AllFiltersAccountIds = [];
        $folderIds = array();
        $folderBackend = new Felamimail_Backend_Folder();
        $folderFilterData = [];
        
        foreach ((array)$_filterData['value'] as $filterValue) {
            if (is_array($filterValue) && isset($filterValue['path'])) {
                $filterValue = $filterValue['path'];
            }
            if (empty($filterValue)) {
                $_select->where('1 = 0');
            } else if (strpos($filterValue, '/') !== FALSE) {
                $pathParts = array_values(array_filter(explode('/', $filterValue)));
                $parents = [''];
                
                foreach ($pathParts as $idx => $path) {
                    $isLastIndex = $idx === sizeof($pathParts) - 1;
                    if ($idx === 0) {
                        //index 0 is account
                        if (Tinebase_Helper::isHashId($path)) {
                            $AllFiltersAccountIds = array_merge($AllFiltersAccountIds, [$path]);
                            continue;
                        }
                        if ($path === '**' || $path === '*') {
                            $accountIds = array_unique($this->_getUserAccountIds());
                        } else {
                            $result = Felamimail_Controller_Account::getInstance()->search(
                                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class,
                                    [['field' => 'name', 'operator' => 'contains', 'value' => $path]]
                                ));
                            $accountIds = array_unique($result->getArrayOfIds());
                        }
                        $AllFiltersAccountIds = array_merge($AllFiltersAccountIds, $accountIds);
                    }
                    // index above 0 should be folders
                    if ($idx >= 1) {
                        if (Tinebase_Helper::isHashId($path) && $isLastIndex) {
                            $folderIds = array_merge($folderIds, [$path]);
                            continue;
                        }
                        if ($path === '*') {
                            // filter search parent folder none recursively
                            if ($isLastIndex) {
                                $folderFilterData[] = ['field' => 'parent', 'operator' => 'in', 'value' => $parents];
                                continue;
                            }
                            $folders = $folderBackend->search(new Felamimail_Model_FolderFilter($folderFilterData));
                            $parents = $folders->globalname;
                            continue;
                        }
                        foreach ($parents as &$parent) {
                            // filter search parent folders recursively
                            if ($path === '**') {
                                if ($idx === 1) {
                                    $folderFilterData[] = ['field' => 'account_id', 'operator' => 'in', 'value' => $AllFiltersAccountIds];
                                    $folders = $folderBackend->search(new Felamimail_Model_FolderFilter($folderFilterData));
                                    $folderIds = array_merge($folderIds, $folders->getArrayOfIds());
                                    continue;
                                }
                                $folderFilterData[] = ['field' => 'globalname', 'operator' => 'startswith', 'value' => $parent];
                                $folderFilterData[] = ['field' => 'parent', 'operator' => 'startswith', 'value' => $parent];
                                continue;
                            }
                            $parent = empty($parent) ? $path : "$parent.$path";
                            
                            if ($isLastIndex) {
                                $folderFilterData[] = ['field' => 'globalname', 'operator' => 'equals', 'value' => $parent];
                            }
                        }
                    }
                }
            }
         }
        
        if (sizeof($folderFilterData) > 0) {
            $folderFilterData[] = ['field' => 'account_id',  'operator' => 'in',  'value' => $AllFiltersAccountIds];
            $folders = $folderBackend->search(new Felamimail_Model_FolderFilter($folderFilterData));
            $folderIds = array_merge($folderIds, $folders->getArrayOfIds());
        }
        
        $this->_addAccountFilter($_select, $_backend, array_unique($AllFiltersAccountIds));

        if (count($folderIds) > 0) {
             $folderFilter = new Tinebase_Model_Filter_Id('folder_id', $_filterData['operator'], array_unique($folderIds));
             $folderFilter->appendFilterSql($_select, $_backend);
         }
     }
     
    /**
     * add flags custom filters
     * 
     * @param  Zend_Db_Select                       $_select
     * @param  Felamimail_Backend_Cache_Sql_Message $_backend
     * @param  array                                $_filterData
     * @return void
     */
    protected function _addFlagsSql($_select, $_backend, $_filterData)
    {
        $db = $_backend->getAdapter();
        $foreignTables = $_backend->getForeignTables();
        
        // add conditions
        $tablename  = $foreignTables[$_filterData['field']]['table'];

        // add filter value
        if (! isset($_filterData['value'])) {
            $_filterData['value'] = '';
        }

        $value = array();
        foreach ((array)$_filterData['value'] as $customValue) {
            $value[]      = '%' . $customValue . '%';
        }

        if ($_filterData['field'] == 'flags') {
            if (!is_array($_filterData['value'])) {
                $_filterData['value'] = [$_filterData['value']];
            }
            $flagCount = 0;
            $orConditions = '';
            foreach ($_filterData['value'] as $value) {
                $correl = $tablename . '_' . ++$flagCount;
                $_select->joinLeft([$correl => SQL_TABLE_PREFIX . $tablename], $_backend->getTableName() . '.id = ' . $correl . '.message_id AND ' . $correl . $db->quoteInto('.flag = ?', $value) , []);
                if ($_filterData['operator'] == 'equals' || $_filterData['operator'] == 'contains') {
                    $_select->where($correl . '.message_id IS NOT NULL');
                } elseif ($_filterData['operator'] == 'in') {
                    $orConditions .= ($orConditions ? ' OR ' : '') . $correl . '.message_id IS NOT NULL';
                } else {
                    $_select->where($correl . '.message_id IS NULL');
                }
            }
            if ($orConditions) {
                $_select->where('(' . $orConditions . ')');
            }
        }
    }
    
}
