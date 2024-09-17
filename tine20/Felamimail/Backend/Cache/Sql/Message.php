<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql cache backend class for Felamimail messages
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Cache_Sql_Message extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'felamimail_cache_message';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Felamimail_Model_Message';
    
    /**
    * default column(s) for count
    *
    * @var string
    */
    protected $_defaultCountCol = 'id';
    
    /**
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'to'    => array(
            'table'     => 'felamimail_cache_message_to',
            'joinOn'    => 'message_id',
            'field'     => 'email',
        ),
        'cc'    => array(
            'table'  => 'felamimail_cache_message_cc',
            'joinOn' => 'message_id',
            'field'  => 'email',
        ),
        'bcc'    => array(
            'table'  => 'felamimail_cache_message_bcc',
            'joinOn' => 'message_id',
            'field'  => 'email',
        ),
        'flags'    => array(
            'table'         => 'felamimail_cache_msg_flag',
            'joinOn'        => 'message_id',
            'field'         => 'flag',
        ),
    );

    /**
     * converts record into raw data for adapter
     *
     * @param  Felamimail_Model_Message $_record
     * @return array
     */
    protected function _recordToRawData(Tinebase_Record_Interface $_record)
    {
        Felamimail_Backend_Folder::lockFolderInTransaction($_record->folder_id);

        return parent::_recordToRawData($_record);
    }

    /**
     * Search for records matching given filter
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @return array
     */
    public function searchMessageUids(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL)    
    {
        return $this->search($_filter, $_pagination, array(self::IDCOL, 'messageuid'));
    }
    
    /**
     * get all flags for a given folder id
     *
     * @param string|Felamimail_Model_Folder $_folderId
     * @param integer $_start
     * @param integer $_limit
     * @return Tinebase_Record_RecordSet
     */
    public function getFlagsForFolder($_folderId, $_start = NULL, $_limit = NULL)
    {
        $filter = $this->_getMessageFilterWithFolderId($_folderId);
        $pagination = ($_start !== NULL || $_limit !== NULL) ? new Tinebase_Model_Pagination(array(
            'start' => $_start,
            'limit' => $_limit,
        ), TRUE) : NULL;
        
        return $this->search($filter, $pagination, array('messageuid' => 'messageuid', 'id' => self::IDCOL, 'flags' => 'felamimail_cache_msg_flag.flag'));
    }
        
    /**
     * update foreign key values
     * 
     * @param string $_mode create|update
     * @param Tinebase_Record_Interface $_record
     * 
     * @todo support update mode
     */
    protected function _updateForeignKeys($_mode, Tinebase_Record_Interface $_record)
    {
        if ($_mode == 'create') {
            foreach ($this->_foreignTables as $key => $foreign) {
                if (!isset($_record->{$key}) || empty($_record->{$key})) {
                    continue;
                }

                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__
                    . '::' . __LINE__ . ' ' . $key . ': ' . print_r($_record->{$key}, TRUE));

                foreach ($_record->{$key} as $data) {
                    if ($key == 'flags') {
                        $data = array(
                            'flag'      => $data,
                        );
                        if ($_record->has('folder_id')) {
                            $data['folder_id'] = $_record->folder_id;
                        }
                    } else {
                        // need to filter input as 'name' could contain invalid chars (emojis, ...) here
                        if (! is_array($data)) {
                            $data = array($foreign['field'] => $data);
                        }
                        foreach ($data as $field => $value) {
                            $data[$field] = Tinebase_Core::filterInputForDatabase($data[$field]);
                        }
                    }

                    $data['message_id'] = $_record->getId();
                    $this->_db->insert($this->_tablePrefix . $foreign['table'], $data);
                }
            }
        }
    }
    
    /**
     * add flag to message
     *
     * @param Felamimail_Model_Message $_message
     * @param string $_flag
     */
    public function addFlag($_message, $_flag)
    {
        if (empty($_flag)) {
            // nothing todo
            return;
        }
        
        $data = array(
            'flag'          => $_flag,
            'message_id'    => $_message->getId(),
            'folder_id'     => $_message->folder_id
        );
        $this->_db->insert($this->_tablePrefix . $this->_foreignTables['flags']['table'], $data);
    }
    
    /**
     * set flags of message
     *
     * @param  mixed         $_messages array of ids, recordset, single message record
     * @param  string|array  $_flags
     */
    public function setFlags($_messages, $_flags, $_folderId = NULL)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            if ($_messages instanceof Tinebase_Record_RecordSet) {
                $messages = $_messages;
            } elseif ($_messages instanceof Felamimail_Model_Message) {
                $messages = new Tinebase_Record_RecordSet('Felamimail_Model_Message', array($_messages));
            } elseif (is_array($_messages) && $_folderId !== null) {
                // array of ids
                $messages = $_messages;
            } else {
                throw new Tinebase_Exception_UnexpectedValue('$_messages must be instance of Felamimail_Model_Message');
            }
            $messageIds = ($messages instanceof Tinebase_Record_RecordSet) ? $messages->getArrayOfIds() : $messages;
            $where = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('message_id') . ' IN (?)', $messageIds)
            );
            $this->_db->delete($this->_tablePrefix . $this->_foreignTables['flags']['table'], $where);

            $this->setMessageTags( new Felamimail_Model_MessageFilter([
                array('field' => 'id', 'operator' => 'in', 'value' => $messageIds)
            ]), [], 'clear');

            $flags = (array)$_flags;
            $touchedMessages = array();

            foreach ($flags as $flag) {
                foreach ($messages as $message) {
                    $id = $touchedMessages[] = ($message instanceof Felamimail_Model_Message) ? $message->getId() : $message;
                    $folderId = ($message instanceof Felamimail_Model_Message) ? $message->folder_id : $_folderId;

                    $data = array(
                        'flag' => $flag,
                        'message_id' => $id,
                        'folder_id' => $folderId,
                    );
                    $this->_db->insert($this->_tablePrefix . $this->_foreignTables['flags']['table'], $data);
                }
            }
            
            $this->setMessageTags( new Felamimail_Model_MessageFilter([
                array('field' => 'id', 'operator' => 'in', 'value' => $messageIds)
            ]), $flags, 'add');
            
            // touch messages so sync can find the updates
            $this->updateMultiple($touchedMessages, array('timestamp' => Tinebase_DateTime::now()));

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
    
    /**
     * remove flag from messages
     *
     * @param  mixed  $_messages
     * @param  mixed  $_flag
     */
    public function clearFlag($_messages, $_flag)
    {
        if ($_messages instanceof Tinebase_Record_RecordSet) {
            $messageIds = $_messages->getArrayOfIds();
        } elseif ($_messages instanceof Felamimail_Model_Message) {
            $messageIds = $_messages->getId();
        } else {
            // single id or array of ids
            $messageIds = $_messages;
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('message_id') . ' IN (?)', $messageIds),
            $this->_db->quoteInto($this->_db->quoteIdentifier('flag') . ' IN (?)', $_flag)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_foreignTables['flags']['table'], $where);
    }

    /**
     * Deletes entries
     *
     * @param string|integer|Tinebase_Record_Interface|array $_id
     * @return int The number of affected rows.
     * @throws Felamimail_Exception
     */
    public function delete($_id)
    {
        if ((is_array($_id) && sizeof($_id) > 0) || is_string($_id)) {
            $this->setMessageTags( new Felamimail_Model_MessageFilter([
                array('field' => 'id', 'operator' => 'in', 'value' => $_id)
            ]), [], 'clear');
        }
        
        return parent::delete($_id);
    }
    
    /**
     * @param Felamimail_Model_Folder|string $_folderId
     * @return void
     * @throws Felamimail_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function deleteByFolderId($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;

        $filter = $this->_getMessageFilterWithFolderId($_folderId);
        try {
            $this->setMessageTags($filter, [], 'clear');
        } catch (Felamimail_Exception_IMAPFolderNotFound $feifnf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__
                . '::' . __LINE__ . ' Folder has already been removed - nothing more to do here');
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * get count of cached messages by folder (id) 
     *
     * @param  mixed  $_folderId
     * @return integer
     */
    public function searchCountByFolderId($_folderId)
    {
        $filter = $this->_getMessageFilterWithFolderId($_folderId);
        $count = $this->searchCount($filter);
        
        return $count;
    }
    
    /**
     * get folder id message filter
     * 
     * @param mixed $_folderId
     * @return Felamimail_Model_MessageFilter
     */
    protected function _getMessageFilterWithFolderId($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $folderId)
        ));
        
        return $filter;
    }
    
    /**
     * get count of seen cached messages by folder (id) 
     *
     * @param  mixed  $_folderId
     * @return integer
     * 
     */
    public function seenCountByFolderId($_folderId)
    {
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $select = $this->_db->select();
        $select->from(
            array('flags' => $this->_tablePrefix . $this->_foreignTables['flags']['table']), 
            array('count' => 'COUNT(DISTINCT message_id)')
        )->where(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        )->where(
            $this->_db->quoteInto($this->_db->quoteIdentifier('flag') . ' = ?', '\Seen')
        );

        $seenCount = $this->_db->fetchOne($select);
                
        return $seenCount;
    }
    
    /**
     * delete messages with given messageuids by folder (id)
     *
     * @param  array  $_msguids
     * @param  mixed  $_folderId
     * @return integer number of deleted rows
     */
    public function deleteMessageuidsByFolderId($_msguids, $_folderId)
    {
        if (empty($_msguids) || !is_array($_msguids)) {
            return FALSE;
        }
        
        $folderId = ($_folderId instanceof Felamimail_Model_Folder) ? $_folderId->getId() : $_folderId;
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('messageuid') . ' IN (?)', $_msguids),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $folderId)
        );
        
        return $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Felamimail_Exception
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function setMessageTags(Felamimail_Model_MessageFilter $filter, $_flags, $_mode)
    {
        $iterator = new Tinebase_Record_Iterator([
            'iteratable' => $this,
            'controller' => $this,
            'filter' => $filter,
            'options'    => [
                'limit' => 1000
            ],
            'function' => 'setMessageTagsIteration',
        ]);
        $result = $iterator->iterate($_flags, $_mode);
    }

    public function setMessageTagsIteration(Tinebase_Record_RecordSet $messages, $_flags, $_mode)
    {
        $tagIds = array_filter($_flags, function ($flag) { return strlen((string)$flag)=== 40;});
        $tags = sizeof($tagIds) > 0 ? Tinebase_Tags::getInstance()->getTagsById($tagIds) : [];

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' About to ' . $_mode . ' tags for ' . count($messages) . ' messages.');

        $filter =  new Felamimail_Model_MessageFilter([
            ['field' => 'id', 'operator' => 'in', 'value' => $messages->getId()]
        ]);

        if ($_mode === 'clear') {
            try {
                if (sizeof($tags) === 0) {
                    $tags = Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($messages);
                }
                if ($tags) {
                    Tinebase_Tags::getInstance()->detachTagsFromMultipleRecords($filter, $tags->getId());
                }
            } catch (Zend_Db_Statement_Exception $zdse) {
                throw new Felamimail_Exception('failed to detach tags: ' . print_r($tags, TRUE));
            }
        }

        if ($_mode === 'add') {
            foreach ($tags as $tag) {
                try {
                    Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $tag);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    throw new Felamimail_Exception('failed to attach tags: ' . print_r($tag->getId(), TRUE));
                }
            }
        }
    }
}
