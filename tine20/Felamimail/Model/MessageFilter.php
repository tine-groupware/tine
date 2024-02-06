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
    const PATH_ALLINBOXES = '/allinboxes';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'            => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Felamimail_Model_Message')), 
        'query'         => array(
            'filter'        => Tinebase_Model_Filter_Query::class,
            'options'       => array(
                'fields' => array('subject', 'from_email_ft', 'from_name_ft', 'to_list', 'cc_list', 'bcc_list'),
                'modelName' => Felamimail_Model_Message::class,
                'ignoreFullTextConfig' => true,
            )
        ),
        'folder_id'     => array('filter' => 'Tinebase_Model_Filter_Id'),
        'subject'       => array('filter' => Tinebase_Model_Filter_FullText::class),
        'from_email'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'from_email_ft' => array('filter' => Tinebase_Model_Filter_FullText::class, 'options' => [
            'field'         => 'from_email',
        ]),
        'from_name'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'from_name_ft'  => array('filter' => Tinebase_Model_Filter_FullText::class, 'options' => [
            'field'         => 'from_name',
        ]),
        'received'      => array('filter' => Tinebase_Model_Filter_DateTime::class),
        'messageuid'    => array('filter' => 'Tinebase_Model_Filter_Int'),
        'message_id'    => array('filter' => 'Tinebase_Model_Filter_Text'),
        'size'          => array('filter' => 'Tinebase_Model_Filter_Int'),
        'has_attachment'=> ['filter' => Tinebase_Model_Filter_Bool::class],
    // custom filters
        'path'          => array('custom' => true),
        'to'            => array('filter' => Felamimail_Model_RecipientFilter::class),
        'to_list'       => array('filter' => Tinebase_Model_Filter_FullText::class),
        'cc'            => array('filter' => Felamimail_Model_RecipientFilter::class),
        'cc_list'       => array('filter' => Tinebase_Model_Filter_FullText::class),
        'bcc'           => array('filter' => Felamimail_Model_RecipientFilter::class),
        'bcc_list'      => array('filter' => Tinebase_Model_Filter_FullText::class),
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
        
        $folderIds = array();
        foreach ((array)$_filterData['value'] as $filterValue) {
            if (is_array($filterValue) && isset($filterValue['path'])) {
                $filterValue = $filterValue['path'];
            }
            if ($filterValue === null || empty($filterValue)) {
                $_select->where('1 = 0');
            } else if ($filterValue === self::PATH_ALLINBOXES) {
                $folderIds = array_merge($folderIds, $this->_getFolderIdsOfAllInboxes());
            } else if (strpos($filterValue, '/') !== FALSE) {
                $pathParts = explode('/', $filterValue);
                if (! $pathParts) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                                __METHOD__ . '::' . __LINE__ . ' Could not explode filter:' . var_export($filterValue, true));
                    continue;
                }
                array_shift($pathParts);
                if (count($pathParts) == 1) {
                    // we only have an account id
                    $this->_addAccountFilter($_select, $_backend, (array) $pathParts[0]);
                } else if (count($pathParts) > 1) {
                    $folderIds[] = array_pop($pathParts);
                }
            }
        }
        
        if (count($folderIds) > 0) {
            $folderFilter = new Tinebase_Model_Filter_Id('folder_id', $_filterData['operator'], array_unique($folderIds));
            $folderFilter->appendFilterSql($_select, $_backend);
        }
    }
    
    /**
     * get folder ids of all inboxes for accounts of current user
     * 
     * @return array
     */
    protected function _getFolderIdsOfAllInboxes()
    {
        $folderFilter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'account_id',  'operator' => 'in',     'value' => $this->_getUserAccountIds()),
            array('field' => 'localname',   'operator' => 'equals', 'value' => 'INBOX')
        ));
        $folderBackend = new Felamimail_Backend_Folder();
        $folderIds = $folderBackend->search($folderFilter, NULL, TRUE);
        
        return $folderIds;
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
