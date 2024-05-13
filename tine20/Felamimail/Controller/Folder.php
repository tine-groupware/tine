<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use other/shared namespaces
 * @todo        extend Tinebase_Controller_Record Abstract and add modlog fields
 */

/**
 * folder controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Folder extends Tinebase_Controller_Abstract implements Tinebase_Controller_SearchInterface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * last search count (2 dim array: userId => accountId => count)
     *
     * @var array
     */
    protected $_lastSearchCount = array();
    
    /**
     * default system folder names
     *
     * @var array
     */
    protected $_systemFolders = array('inbox', 'drafts', 'sent', 'templates', 'junk', 'trash');
    
    /**
     * folder delimiter/separator
     * 
     * @var string
     */
    protected $_delimiter = '/';
    
    /**
     * folder backend
     *
     * @var Felamimail_Backend_Folder
     */
    protected $_backend = NULL;
    
    /**
     * cache controller
     *
     * @var Felamimail_Controller_Cache_Folder
     */
    protected $_cacheController = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Folder
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_backend = new Felamimail_Backend_Folder();
        $this->_cacheController = Felamimail_Controller_Cache_Folder::getInstance();
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
     * @return Felamimail_Controller_Folder
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Folder();
        }
        
        return self::$_instance;
    }

    /************************************* public functions *************************************/
    
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string $_action
     * @return Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $filterValues = $this->_extractFilter($_filter);
        
        if (empty($filterValues['account_id'])) {
            throw new Felamimail_Exception('No account id set in search filter. Check default account preference!');
        }

        Felamimail_Controller_Account::getInstance()->checkAccountAcl($filterValues['account_id']);
        
        // get folders from db
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'parent',      'operator' => 'equals', 'value' => $filterValues['globalname']),
            array('field' => 'account_id',  'operator' => 'equals', 'value' => $filterValues['account_id'])
        ));
        $result = $this->_backend->search($filter);
        if (count($result) == 0) {
            // try to get folders from imap server
            $result = $this->_cacheController->update($filterValues['account_id'], $filterValues['globalname']);
        }
        
        $this->_lastSearchCount[Tinebase_Core::getUser()->getId()][$filterValues['account_id']] = count($result);
        
        // sort folders
        return $this->_sortFolders($result, $filterValues['globalname']);
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        $filterValues = $this->_extractFilter($_filter);
        
        return $this->_lastSearchCount[Tinebase_Core::getUser()->getId()][$filterValues['account_id']];
    }
    
    /**
     * get single folder
     * 
     * @param string $_id
     * @return Felamimail_Model_Folder
     */
    public function get($_id)
    {
        $folder = $this->_backend->get($_id);
        /** @var Felamimail_Model_Folder $folder */
        Felamimail_Controller_Account::getInstance()->checkAccess($folder);
        return $folder;
    }

    /**
     * get multiple folders
     * 
     * @param string|array $_ids
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids)
    {
        $result = $this->_backend->getMultiple($_ids);
        foreach ($result as $folder) {
            Felamimail_Controller_Account::getInstance()->checkAccess($folder);
        }
        return $result;
    }
    
    /**
     * get saved folder record by backend and globalname
     *
     * @param  mixed   $_accountId
     * @param  string  $_globalName
     * @return Felamimail_Model_Folder
     */
    public function getByBackendAndGlobalName($_accountId, $_globalName)
    {
        $accountId = ($_accountId instanceof Felamimail_Model_Account) ? $_accountId->getId() : $_accountId;

        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'account_id', 'operator' => 'equals', 'value' => $accountId),
            array('field' => 'globalname', 'operator' => 'equals', 'value' => $_globalName),
        ));
        
        $folders = $this->_backend->search($filter);
        
        if (count($folders) > 0) {
            $result = $folders->getFirstRecord();
            Felamimail_Controller_Account::getInstance()->checkAccess($result);
        } else {
            throw new Tinebase_Exception_NotFound("Folder $_globalName not found.");
        }
        
        return $result;
    }
        
    /**
     * create folder
     *
     * @param string|Felamimail_Model_Account $_accountId
     * @param string $_folderName
     * @param string $_parentFolder
     * @return Felamimail_Model_Folder
     * @throws Felamimail_Exception
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function create($_accountId, string $_folderName, string $_parentFolder = ''): Felamimail_Model_Folder
    {
        $account = ($_accountId instanceof Felamimail_Model_Account)
            ? $_accountId
            : Felamimail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;
        
        $foldername = $this->_prepareFolderName($_folderName);
        $globalname = (empty($_parentFolder)) ? $foldername : $_parentFolder . $this->_delimiter . $foldername;
            
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Trying to create new folder: ' . $globalname
            . ' (parent: ' . $_parentFolder . ')');
        
        $imap = Felamimail_Backend_ImapFactory::factory($account);

        // check if parent folder exists
        if (! empty($_parentFolder)) {
            try {
                $imap->examineFolder(Felamimail_Model_Folder::encodeFolderName($_parentFolder));
            } catch (Zend_Mail_Storage_Exception $zmse) {
                throw new Tinebase_Exception_NotFound('Could not create folder: parent folder '
                    . $_parentFolder . ' not found');
            }
        }

        try {
            $imap->createFolder(
                Felamimail_Model_Folder::encodeFolderName($foldername), 
                (empty($_parentFolder)) ? null : Felamimail_Model_Folder::encodeFolderName($_parentFolder),
                $this->_delimiter
            );

            // create new folder
            $folder = new Felamimail_Model_Folder(array(
                'localname' => $foldername,
                'globalname' => $globalname,
                'account_id' => $account->getId(),
                'parent' => $_parentFolder,
            ));
            $folder->supports_condstore = $this->supportsCondStore($folder, $account, $imap);

            /* @var Felamimail_Model_Folder $folder */
            $folder = $this->_backend->create($folder);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Create new folder: ' . $globalname);
            
        } catch (Zend_Mail_Storage_Exception $zmse) {
            // perhaps the folder already exists
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Could not create new folder: ' . $globalname
                . ' (' . $zmse->getMessage() . ')');
            
            // reload folder cache of parent
            $parentSubs = $this->_cacheController->update($account, $_parentFolder);
            $folder = $parentSubs->filter('globalname', $globalname)->getFirstRecord();
            if ($folder === null) {
                throw new Felamimail_Exception_IMAPServiceUnavailable($zmse->getMessage());
            }

            $translation = Tinebase_Translation::getTranslation('Felamimail');
            $nodeExistException = new Tinebase_Exception_SystemGeneric(str_replace(
                ['{0}'],
                [$globalname],
                $translation->translate('Folder with name {0} already exists!')
            ));
            $nodeExistException->setTitle($translation->translate('Failure on create folder'));
            throw $nodeExistException;
        }

        // update parent (has_children)
        $this->_updateHasChildren($_accountId, $_parentFolder, 1);
        
        return $folder;
    }

    /**
     * check if folder support condstore: try to exmime folder on imap server if supports_condstore is null
     *
     * @param string|Felamimail_Model_Folder $folder
     * @param Felamimail_Model_Account|null $account
     * @param Felamimail_Backend_ImapProxy|null $imap
     * @return boolean
     */
    public function supportsCondStore($folder, Felamimail_Model_Account $account = null, $imap = null)
    {
        if (is_string($folder) || $folder->supports_condstore === null) {
            $folderName = is_string($folder) ? $folder : $folder->globalname;

            if (! $imap) {
                if (!$account) {
                    if ($folder instanceof Felamimail_Model_Folder) {
                        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
                    } else {
                        throw new Felamimail_Exception('no valid account found for folder');
                    }
                }
                $imap = Felamimail_Backend_ImapFactory::factory($account);
            }

            try {
                $folderData = $imap->examineFolder(Felamimail_Model_Folder::encodeFolderName($folderName));

                $result = isset($folderData['highestmodseq']) || array_key_exists('highestmodseq', $folderData);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    ' Folder ' . $folderName . ' supports condstore: ' . intval($result));

                return $result;

            } catch (Zend_Mail_Storage_Exception $zmse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . " Could not examine folder $folderName. Skipping it.");
                return null;
            }
        }

        return $folder->supports_condstore;
    }

    /**
     * prepare foldername given by user (remove some bad chars)
     * 
     * @param string $_folderName
     * @return string
     */
    protected function _prepareFolderName($_folderName)
    {
        $result = stripslashes($_folderName);
        $result = str_replace(array('/', $this->_delimiter), '', $result);
        return $result;
    }
    
    /**
     * update single folder
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     */
    public function update(Felamimail_Model_Folder $_folder)
    {
        Felamimail_Controller_Account::getInstance()->checkAccess($_folder);
        $_folder->supports_condstore = $this->supportsCondStore($_folder);
        return $this->_backend->update($_folder);
    }
    
    /**
     * update single folders counter
     * 
     * @param  mixed  $_folderId
     * @param  array  $_counters
     * @return Felamimail_Model_Folder
     */
    public function updateFolderCounter($_folderId, array $_counters)
    {
        return $this->_backend->updateFolderCounter($_folderId, $_counters);
    }
    
    /**
     * remove folder
     *
     * @param string $_accountId
     * @param string $_folderName globalName (complete path) of folder to delete
     * @param boolean $_recursive
     * @return void
     */
    public function delete($_accountId, $_folderName, $_recursive = FALSE)
    {
        try {
            $folder = $this->getByBackendAndGlobalName($_accountId, $_folderName);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Trying to delete non-existant folder ' . $_folderName);
            $folder = NULL;
        }
        
        // check if folder has subfolders and throw exception if that is the case / OR: delete subfolders if recursive param is TRUE
        // @todo this should not be a Tinebase_Exception_AccessDenied -> we have to create a new exception and call the fmail exception handler when deleting/adding/renaming folders
        $subfolders = $this->getSubfolders($_accountId, $_folderName);
        if (count($subfolders) > 0 && $folder) {
            if ($_recursive) {
                $this->deleteSubfolders($folder, $subfolders);
            } else {
                throw new Tinebase_Exception_AccessDenied('Could not delete folder ' . $_folderName . ' because it has subfolders.');
            }
        }
        
        $this->_deleteFolderInCache($_accountId, $folder);
        $this->_deleteFolderOnIMAP($_accountId, $_folderName);
    }

    /**
     * rename folder on imap server
     * 
     * @param string|Felamimail_Model_Account $_account
     * @param string $_folderName
     * @throws Felamimail_Exception_IMAPFolderNotFound
     * @throws Felamimail_Exception_IMAP
     */
    protected function _deleteFolderOnIMAP($_accountId, $_folderName)
    {
        try {
            $imap = Felamimail_Backend_ImapFactory::factory($_accountId);
            $imap->removeFolder(Felamimail_Model_Folder::encodeFolderName($_folderName));
        } catch (Zend_Mail_Storage_Exception $zmse) {
            try {
                $imap->selectFolder(Felamimail_Model_Folder::encodeFolderName($_folderName));
            } catch (Zend_Mail_Storage_Exception $zmse2) {
                throw new Felamimail_Exception_IMAPFolderNotFound('Folder not found (error: ' . $zmse2->getMessage() . ').');
            }
            
            throw new Felamimail_Exception_IMAP('Could not delete folder ' . $_folderName . '. IMAP Error: ' . $zmse->getMessage());
        }
    }
    
    /**
     * delete folder in cache
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @param Felamimail_Model_Folder $_folder
     */
    protected function _deleteFolderInCache($_accountId, $_folder)
    {
        if ($_folder === NULL) {
            return;
        }

        Felamimail_Controller_Message::getInstance()->deleteByFolder($_folder);
        $this->_backend->delete($_folder->getId());
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deleted folder ' . $_folder->globalname);
        $this->_updateHasChildren($_accountId, $_folder->parent);
    }
    
    /**
     * rename/move folder
     *
     * @param string $_accountId
     * @param string $_newName
     * @param string $_oldGlobalName
     * @param bool $targetIsLocal
     * @return Felamimail_Model_Folder
     */
    public function rename($_accountId, string $_newName, string $_oldGlobalName, bool $targetIsLocal = true): Felamimail_Model_Folder
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_accountId);
        $this->_delimiter = $account->delimiter;

        if ($targetIsLocal) {
            $newLocalName = $this->_prepareFolderName($_newName);
            $newGlobalName = $this->_buildNewGlobalName($newLocalName, $_oldGlobalName);
        } else {
            $globalNameParts = explode($this->_delimiter, $_newName);
            $newLocalName = array_pop($globalNameParts);
            $newGlobalName = $_newName;
        }

        if ($_oldGlobalName === $newGlobalName) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No change required, new name = old name.');
            return $this->getByBackendAndGlobalName($account, $newGlobalName);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Renaming ... ' . $_oldGlobalName . ' -> ' . $newGlobalName);
        
        $this->_renameFolderOnIMAP($account, $newGlobalName, $_oldGlobalName);
        $folder = $this->_renameFolderInCache($account, $newGlobalName, $_oldGlobalName, $newLocalName);
        $this->_updateSubfoldersAfterRename($account, $newGlobalName, $_oldGlobalName);
        if (! $targetIsLocal) {
            $this->_updateParentsAfterRename($account, $newGlobalName, $_oldGlobalName);
        }
        
        return $folder;
    }
    
    /**
     * remove old localname and build new globalname
     * 
     * @param string $_newLocalName
     * @param string $_oldGlobalName
     * @return string
     */
    protected function _buildNewGlobalName($_newLocalName, $_oldGlobalName)
    {
        $newGlobalName = $this->_getParentGlobalname($_oldGlobalName);
        if (! empty($_newLocalName)) {
            if ($newGlobalName === '') {
                $newGlobalName = $_newLocalName;
            } else {
                $newGlobalName .= $this->_delimiter . $_newLocalName;
            }
        }

        return $newGlobalName;
    }
    
    /**
     * rename folder on imap server
     * 
     * @param Felamimail_Model_Account $_account
     * @param string $_newGlobalName
     * @param string $_oldGlobalName
     * @throws Felamimail_Exception_IMAPFolderNotFound
     */
    protected function _renameFolderOnIMAP(Felamimail_Model_Account $_account, $_newGlobalName, $_oldGlobalName)
    {
        $imap = Felamimail_Backend_ImapFactory::factory($_account);
        
        try {
            /* @var Felamimail_Backend_Imap $imap */
            $imap->renameFolder(Felamimail_Model_Folder::encodeFolderName($_oldGlobalName), Felamimail_Model_Folder::encodeFolderName($_newGlobalName));
        } catch (Zend_Mail_Storage_Exception $zmse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Folder could have been renamed / deleted by another client.');
            
            throw new Felamimail_Exception_IMAPFolderNotFound('Folder not found (error: ' . $zmse->getMessage() . ').');
        }
    }
    
    /**
     * rename folder in cache
     * 
     * @param Felamimail_Model_Account $_account
     * @param string $_newGlobalName
     * @param string $_oldGlobalName
     * @param string $_newLocalName
     * @return Felamimail_Model_Folder
     * @throws Tinebase_Exception_NotFound
     */
    protected function _renameFolderInCache(Felamimail_Model_Account $_account, $_newGlobalName, $_oldGlobalName, $_newLocalName)
    {
        try {
            $folder = $this->getByBackendAndGlobalName($_account, $_oldGlobalName);
            $folder->globalname = $_newGlobalName;
            $folder->parent = $this->_getParentGlobalname($_newGlobalName);
            $folder->localname = $_newLocalName;
            $folder = $this->update($folder);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Trying to rename non-existant folder ' . $_oldGlobalName);
            throw $tenf;
        }
        
        return $folder;
    }

    protected function _getParentGlobalname($globalname)
    {
        $globalNameParts = explode($this->_delimiter, $globalname);
        array_pop($globalNameParts);
        return implode($this->_delimiter, $globalNameParts);
    }

    /**
     * loop subfolders (recursive), replace new localname in globalname path and set new parent name
     * 
     * @param Felamimail_Model_Account $_account
     * @param string $_newGlobalName
     * @param string $_oldGlobalName
     */
    protected function _updateSubfoldersAfterRename(Felamimail_Model_Account $_account, $_newGlobalName, $_oldGlobalName)
    {
        $subfolders = $this->getSubfolders($_account, $_oldGlobalName);
        foreach ($subfolders as $subfolder) {
            if ($_newGlobalName != $subfolder->globalname) {
                $newSubfolderGlobalname = str_replace($_oldGlobalName, $_newGlobalName, $subfolder->globalname);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Renaming ... ' . $subfolder->globalname . ' -> ' . $newSubfolderGlobalname);
                
                $subfolder->globalname = $newSubfolderGlobalname;
                $subfolder->parent = str_replace($_oldGlobalName, $_newGlobalName, $subfolder->parent);
                $this->update($subfolder);
            }
        }
    }

    /**
     * update has_children of parents
     *
     * @param Felamimail_Model_Account $_account
     * @param string $_newGlobalName
     * @param string $_oldGlobalName
     */
    protected function _updateParentsAfterRename(Felamimail_Model_Account $_account, $_newGlobalName, $_oldGlobalName)
    {
        $parentName = $this->_getParentGlobalname($_newGlobalName);
        
        if ($parentName !== '') {
            $parentFolder = $this->getByBackendAndGlobalName($_account, $parentName);
            $parentFolder->has_children = true;
            $this->update($parentFolder);
        }

        $oldParentName = $this->_getParentGlobalname($_oldGlobalName);
        if ($oldParentName !== '') {
            if (count($this->getSubfolders($_account, $oldParentName)) === 0) {
                $oldParentFolder = $this->getByBackendAndGlobalName($_account, $oldParentName);
                $oldParentFolder->has_children = false;
                $this->update($oldParentFolder);
            }
        }
    }

    /**
     * delete all messages in one folder -> be careful, they are completely removed and not moved to trash
     * -> delete subfolders if param set
     *
     * @param string $_folderId
     * @param boolean $_deleteSubfolders
     * @return Felamimail_Model_Folder
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     */
    public function emptyFolder($_folderId, $_deleteSubfolders = FALSE)
    {
        $folder = $this->get($_folderId);
        $account = Felamimail_Controller_Account::getInstance()->get($folder->account_id);
        
        $imap = Felamimail_Backend_ImapFactory::factory($account);
        try {
            // try to delete messages in imap folder
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete all messages in folder ' . $folder->globalname);
            $imap->emptyFolder(Felamimail_Model_Folder::encodeFolderName($folder->globalname));

        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            throw new Felamimail_Exception_IMAPServiceUnavailable($zmpe->getMessage());
        } catch (Zend_Mail_Storage_Exception $zmse) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Folder could be empty (' . $zmse->getMessage() . ')');
        }
        
        if ($_deleteSubfolders) {
            $this->deleteSubfolders($folder);
        }
        
        return Felamimail_Controller_Cache_Message::getInstance()->clear($_folderId);
    }
    
    /**
     * delete subfolders recursivly
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Tinebase_Record_RecordSet $_subfolders if we know the subfolders already
     */
    public function deleteSubfolders(Felamimail_Model_Folder $_folder, $_subfolders = NULL)
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_folder->account_id);
        Felamimail_Controller_Account::getInstance()->checkAccountAcl($account);
        $subfolders = ($_subfolders === NULL) ? $this->getSubfolders($account, $_folder->globalname) : $_subfolders;
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete ' . count($subfolders) . ' subfolders of ' . $_folder->globalname);
        foreach ($subfolders as $subfolder) {
            $this->delete($account, $subfolder->globalname, TRUE);
        }
    }
    
    /**
     * get system folders
     * 
     * @param string $_accountId
     * @return array
     * 
     * @todo    update these from account settings
     */
    public function getSystemFolders($_accountId)
    {
        return $this->_systemFolders;
    }
    
    /************************************* protected functions *************************************/
    
    /**
     * extract values from folder filter
     *
     * @param Felamimail_Model_FolderFilter $_filter
     * @return array (assoc) with filter values
     * 
     * @todo add AND/OR conditions for multiple filters of the same field?
     */
    protected function _extractFilter(Felamimail_Model_FolderFilter $_filter)
    {
        $result = array(
            'account_id' => Tinebase_Core::getPreference('Felamimail')->{Felamimail_Preference::DEFAULTACCOUNT}, 
            'globalname' => ''
        );
        
        $filters = $_filter->getFilterObjects();
        foreach($filters as $filter) {
            switch($filter->getField()) {
                case 'account_id':
                    $result['account_id'] = $filter->getValue();
                    break;
                case 'globalname':
                    $result['globalname'] = $filter->getValue();
                    break;
            }
        }
        
        return $result;
    }

    /**
     * sort folder record set
     * - begin with INBOX + other standard/system folders, add other folders
     *
     * @param Tinebase_Record_RecordSet $_folders
     * @param string $_parentFolder
     * @return Tinebase_Record_RecordSet
     */
    protected function _sortFolders(Tinebase_Record_RecordSet $_folders, $_parentFolder)
    {
        $sortedFolders = new Tinebase_Record_RecordSet('Felamimail_Model_Folder');
        
        $_folders->sort('localname', 'ASC', 'asort', SORT_STRING);
        $_folders->addIndices(array('globalname'));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Sorting subfolders of "' . $_parentFolder . '".');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($_folders->globalname, TRUE));
        
        foreach ($this->_systemFolders as $systemFolderName) {
            $folders = $_folders->filter('globalname', '@^' . $systemFolderName . '$@i', TRUE);
            if (count($folders) > 0) {
                $sortedFolders->addRecord($folders->getFirstRecord());
            }
        }
        
        foreach ($_folders as $folder) {
            if (! in_array(strtolower($folder->globalname), $this->_systemFolders)) {
                $sortedFolders->addRecord($folder);
            }
        }

        return $sortedFolders;
    }

    /**
     * update folder value has_children
     * 
     * @param string $_globalname
     * @param integer $_value
     * @return null|Felamimail_Model_Folder
     */
    protected function _updateHasChildren($_accountId, $_globalname, $_value = NULL) 
    {
        if (empty($_globalname)) {
            return NULL;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking has_children for folder ' . $_globalname);
        
        $account = Felamimail_Controller_Account::getInstance()->get($_accountId);
        
        if (! $account->has_children_support) {
            return NULL;
        }

        try {
            $folder = $this->getByBackendAndGlobalName($_accountId, $_globalname);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' .
                $tenf->getMessage());
            return NULL;
        }
        if ($_value === NULL) {
            // check if folder has children by searching in db
            $subfolders = $this->getSubfolders($account, $_globalname);
            $value = (count($subfolders) > 0) ? 1 : 0;
        } else {
            $value = $_value;
        }
        
        if ($folder->has_children != $value) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Set new has_children value for folder ' . $_globalname . ': ' . $value);
            $folder->has_children = $value;
            $folder = $this->update($folder);
        }
        
        return $folder;
    }
    
    /**
     * get subfolders
     * 
     * @param string|Felamimail_Model_Account $_account
     * @param string $_globalname
     * @return Tinebase_Record_RecordSet
     */
    public function getSubfolders($_account, $_globalname)
    {
        $account = ($_account instanceof Felamimail_Model_Account) ? $_account : Felamimail_Controller_Account::getInstance()->get($_account);
        Felamimail_Controller_Account::getInstance()->checkAccountAcl($account);
        $globalname = (empty($_globalname)) ? '' : $_globalname . $account->delimiter;
        
        $filter = new Felamimail_Model_FolderFilter(array(
            array('field' => 'globalname', 'operator' => 'startswith',  'value' => $globalname),
            array('field' => 'account_id', 'operator' => 'equals',      'value' => $account->getId()),
        ));
        
        return $this->_backend->search($filter);
    }

    /**
     * reload folder cache on primary account - only do this once an hour (with caching)
     *
     * @param Felamimail_Model_Account $account
     * @return boolean
     */
    public function reloadFolderCacheOnAccount($account)
    {
        if (Tinebase_Core::getPreference($this->_applicationName)->{Felamimail_Preference::DEFAULTACCOUNT} !== $account->getId()) {
            return false;
        }

        $cache = Tinebase_Core::getCache();
        $cacheId = Tinebase_Helper::convertCacheId('_reloadFolderCacheOnPrimaryAccount' . $account->getId());
        if ($cache->test($cacheId)) {
            return $cache->load($cacheId);
        }

        try {
            Felamimail_Controller_Cache_Folder::getInstance()->update($account->getId(), '', TRUE);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' Could not update account folder cache: ' . $e->getMessage()
            );
        }
        $cache->save(true, $cacheId, [], 3600);
        return true;
    }
}
