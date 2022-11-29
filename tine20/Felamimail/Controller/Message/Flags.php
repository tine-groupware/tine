<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * message flags controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Message_Flags extends Felamimail_Controller_Message
{
    /**
     * imap flags to constants translation
     * @var array
     */
    protected static $_allowedFlags = array(
        'Passed'   => Zend_Mail_Storage::FLAG_PASSED,      // _("Passed")
        '\Answered' => Zend_Mail_Storage::FLAG_ANSWERED,    // _("Answered")
        '\Seen'     => Zend_Mail_Storage::FLAG_SEEN,        // _("Seen")
        '\Deleted'  => Zend_Mail_Storage::FLAG_DELETED,     // _("Deleted")
        '\Draft'    => Zend_Mail_Storage::FLAG_DRAFT,       // _("Draft")
        '\Flagged'  => Zend_Mail_Storage::FLAG_FLAGGED,     // _("Flagged")
    );
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Message_Flags
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() 
    {
        $this->_modelName = 'Felamimail_Model_Message';
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();

        if (Felamimail_Config::getInstance()->featureEnabled(Felamimail_Config::FEATURE_TINE20_FLAG)) {
            self::$_allowedFlags['Tine20'] = 'Tine20';
        }
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Message_Flags
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Message_Flags();
        }
        
        return self::$_instance;
    }
    
    /**
     * add flags to messages
     *
     * @param mixed                     $_message
     * @param array                     $_flags
     * @return Tinebase_Record_RecordSet with affected folders
     */
    public function addFlags($_messages, $_flags)
    {
        return $this->_addOrClearFlags($_messages, $_flags, 'add');
    }
    
    /**
     * clear message flag(s)
     *
     * @param mixed                     $_messages
     * @param array                     $_flags
     * @return Tinebase_Record_RecordSet with affected folders
     */
    public function clearFlags($_messages, $_flags)
    {
        return $this->_addOrClearFlags($_messages, $_flags, 'clear');
    }
    
    /**
     * add or clear message flag(s)
     *
     * @param mixed                     $_messages
     * @param array                     $_flags
     * @param string                    $_mode add/clear
     * @return Tinebase_Record_RecordSet with affected folders
     * 
     * @todo use iterator here
     */
    protected function _addOrClearFlags($_messages, $_flags, $_mode = 'add')
    {
        $flags = (array) $_flags;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_mode. ' flags: ' . print_r($_flags, TRUE));

        $ids = null;
        if ($_messages instanceof Tinebase_Model_Filter_FilterGroup) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' searching for msgs');
            $ids = $this->search($_messages, null, false, true);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' sorting found msgs');
            $ids = $this->search(new Felamimail_Model_MessageFilter([
                ['field' => 'id', 'operator' => 'in', 'value' => $ids],
            ]), new Tinebase_Model_Pagination(['sort' => 'folder_id']), false, true);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' done');
            $_messages = new Felamimail_Model_MessageFilter([
                ['field' => 'id', 'operator' => 'in', 'value' => array_slice($ids, 0, 100)],
            ]);
            $ids = array_slice($ids, 100);
        }
        $messagesToUpdate = $this->_convertToRecordSet($_messages, true);
        
        $lastFolderId       = null;
        $imapBackend        = null;
        $folderCounterById  = [];
        $imapMessageUids    = [];
        
        while (count($messagesToUpdate) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Retrieved ' . count($messagesToUpdate) . ' messages from cache.');
            
            // update flags on imap server
            foreach ($messagesToUpdate as $message) {
                // write flags on imap (if folder changes)
                if ($imapBackend !== null && ($lastFolderId != $message->folder_id)) {
                    $this->_updateFlagsOnImap($imapMessageUids, $flags, $imapBackend, $_mode);
                    $imapMessageUids = array();
                }
                
                // init new folder
                if ($lastFolderId != $message->folder_id) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                        __METHOD__ . '::' . __LINE__ . ' Getting new IMAP backend for folder ' . $message->folder_id);
                    $imapBackend              = $this->_getBackendAndSelectFolder($message->folder_id);
                    $lastFolderId             = $message->folder_id;
                    
                    if ($_mode === 'add') {
                        $folderCounterById[$lastFolderId] = array(
                            'decrementMessagesCounter' => 0, 
                            'decrementUnreadCounter'   => 0
                        );
                    } elseif ($_mode === 'clear') {
                        $folderCounterById[$lastFolderId] = array(
                            'incrementUnreadCounter' => 0
                        );
                    }
                }
                
                $imapMessageUids[] = $message->messageuid;
            }
            
            // write remaining flags
            if ($imapBackend !== null && count($imapMessageUids) > 0) {
                $this->_updateFlagsOnImap($imapMessageUids, $flags, $imapBackend, $_mode);
            }
    
            if ($_mode === 'add') {
                $folderCounterById = $this->_addFlagsOnCache($messagesToUpdate, $flags, $folderCounterById);
            } else if ($_mode === 'clear') {
                $folderCounterById = $this->_clearFlagsOnCache($messagesToUpdate, $flags, $folderCounterById);
            }
            
            // get next 100 messages if we had a filter
            if ($_messages instanceof Tinebase_Model_Filter_FilterGroup) {
                $_messages->getFilter('id')->setValue(array_slice($ids, 0, 100));
                $ids = array_slice($ids, 100);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' fetching more msgs');
                $messagesToUpdate = $this->_convertToRecordSet($_messages, true);
            } else {
                $messagesToUpdate = array();
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . $_mode . 'ed flags');
        
        $affectedFolders = $this->_updateFolderCounts($folderCounterById);
        return $affectedFolders;
    }
    
    /**
     * add/clear flags on imap server
     * 
     * @param array $_imapMessageUids
     * @param array $_flags
     * @param Felamimail_Backend_ImapProxy $_imapBackend
     * @throws Felamimail_Exception_IMAP
     */
    protected function _updateFlagsOnImap($_imapMessageUids, $_flags, $_imapBackend, $_mode)
    {
        $flagsToChange = array_filter($_flags, function($val, $key) {
            return in_array($val, array_keys(self::$_allowedFlags)) || strlen((string)$val) === 40;
        }, ARRAY_FILTER_USE_BOTH); 
        
        if (empty($flagsToChange)) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . $_mode .'ing flags on IMAP server for ' . print_r($_imapMessageUids, TRUE) . ' messages:' . print_r($flagsToChange, TRUE));
        
        try {
            if ($_mode === 'add') {
                $_imapBackend->addFlags($_imapMessageUids, $flagsToChange);
            } else if ($_mode === 'clear') {
                $_imapBackend->clearFlags($_imapMessageUids, $flagsToChange);
            }
        } catch (Zend_Mail_Storage_Exception $zmse) {
            throw new Felamimail_Exception_IMAP($zmse->getMessage());
        }
    }
    
    /**
     * returns supported flags
     * 
     * @param boolean $_translated
     * @return array
     * 
     * @todo add gettext for flags
     */
    public function getSupportedFlags($_translated = TRUE)
    {
        if ($_translated) {
            $result = array();
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            
            foreach (self::$_allowedFlags as $flag) {
                $flagName = str_replace('\\', '', $flag);
                $result[] = array('id'        => $flag,      'name'      => $translate->_($flagName));
            }
            
            return $result;
        } else {
            return self::$_allowedFlags;
        }
    }
    
    /**
     * set flags in local database
     * 
     * @param Tinebase_Record_RecordSet $_messagesToFlag
     * @param array $_flags
     * @param array $_folderCounts
     * @return array folder counts
     */
    protected function _addFlagsOnCache(Tinebase_Record_RecordSet $_messagesToFlag, $_flags, $_folderCounts)
    {
        $folderCounts = $_folderCounts;
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            try {
                $idsToDelete = array();
                foreach ($_messagesToFlag as $message) {
                    foreach ($_flags as $flag) {
                        if ($flag == Zend_Mail_Storage::FLAG_DELETED) {
                            if (is_array($message->flags) && !in_array(Zend_Mail_Storage::FLAG_SEEN, $message->flags)) {
                                $folderCounts[$message->folder_id]['decrementUnreadCounter']++;
                            }
                            $folderCounts[$message->folder_id]['decrementMessagesCounter']++;
                            $idsToDelete[] = $message->getId();
                        } elseif (!is_array($message->flags) || !in_array($flag, $message->flags)) {
                            $this->_backend->addFlag($message, $flag);
                            if ($flag == Zend_Mail_Storage::FLAG_SEEN) {
                                // count messages with seen flag for the first time
                                $folderCounts[$message->folder_id]['decrementUnreadCounter']++;
                            }
                        }
                    }
                }

                $this->_backend->delete($idsToDelete);

                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
            } finally {
                if (null !== $transactionId) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                }
            }
            
            $idsToMarkAsChanged = array_diff($_messagesToFlag->getArrayOfIds(), $idsToDelete);
            $this->_backend->updateMultiple($idsToMarkAsChanged, array(
                'timestamp' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
            ));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Set flags on cache:'
                . ' Deleted records -> ' . count($idsToDelete)
                . ' Updated records -> ' . count($idsToMarkAsChanged)
            );
                
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            return $_folderCounts;
        }
        
        return $folderCounts;
    }
    
    /**
     * clears flags in local database
     * 
     * @param Tinebase_Record_RecordSet $_messagesToFlag
     * @param array $_flags
     * @param array $_folderCounts
     * @return array folder counts
     */
    protected function _clearFlagsOnCache(Tinebase_Record_RecordSet $_messagesToUnflag, $_flags, $_folderCounts)
    {
        $folderCounts = $_folderCounts;
        
        // set flags in local database
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {

            // store flags in local cache
            foreach ($_messagesToUnflag as $message) {
                if (in_array(Zend_Mail_Storage::FLAG_SEEN, $_flags) && in_array(Zend_Mail_Storage::FLAG_SEEN,
                        $message->flags)) {
                    // count messages with seen flag for the first time
                    $folderCounts[$message->folder_id]['incrementUnreadCounter']++;
                }

                $this->_backend->clearFlag($message, $_flags);
            }

            // mark message as changed in the cache backend
            $this->_backend->updateMultiple(
                $_messagesToUnflag->getArrayOfIds(),
                array(
                    'timestamp' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
                )
            );

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
        
        return $folderCounts;
    }
    
    /**
     * set seen flag of message
     * 
     * @param Felamimail_Model_Message $_message
     */
    public function setSeenFlag(Felamimail_Model_Message $_message)
    {
        if (! in_array(Zend_Mail_Storage::FLAG_SEEN, $_message->flags)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Add \Seen flag to msg uid ' . $_message->messageuid);
            
            $this->addFlags($_message, Zend_Mail_Storage::FLAG_SEEN);
            $_message->flags[] = Zend_Mail_Storage::FLAG_SEEN;
        }        
    }

    /**
     * tine20FlagEnabled
     *
     * @param array|Felamimail_Model_Message $message
     * @return bool
     */
    public function tine20FlagEnabled($message = null)
    {
        if (Felamimail_Config::getInstance()->featureEnabled(Felamimail_Config::FEATURE_TINE20_FLAG)) {
            if ($message && isset($message['header']['user-agent'])) {
                $userAgentHeader = $message['header']['user-agent'];
                foreach((array) $userAgentHeader as $userAgent) {
                    if (strpos($userAgent, "Tine 2.0") !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * set tine20 flag of message
     *
     * @param Felamimail_Model_Message $_message
     */
    public function setTine20Flag(Felamimail_Model_Message $_message)
    {
        $flags = isset($_message->flags) ? $_message->flags : array();

        if (is_array($flags) && ! in_array("Tine20", $flags)) {
            $this->addFlags($_message->id, "Tine20");
            array_push($flags, "Tine20");
            $_message->flags = $flags;
        }
    }
}
