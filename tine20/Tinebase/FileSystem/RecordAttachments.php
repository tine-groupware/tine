<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2013-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * filesystem attachments for records
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_RecordAttachments
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * filesystem controller
     * 
     * @var Tinebase_FileSystem
     */
    protected $_fsController = NULL;
    
    /**
     * the constructor
     */
    protected function __construct()
    {
        $this->_fsController  = Tinebase_FileSystem::getInstance();
    }
    
    /**
     * fetch all file attachments of a record
     * 
     * @param Tinebase_Record_Interface $record
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function getRecordAttachments(Tinebase_Record_Interface $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Fetching attachments of ' . $record::class . ' record with id ' . $record->getId() . ' ...');
        }
        
        $parentPath = $this->getRecordAttachmentPath($record);
        
        $record->attachments = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class);
        try {
            $attachments = $this->_fsController->scanDir($parentPath);
            /** @var Tinebase_Model_Tree_Node $node */
            foreach ($attachments as $node) {
                if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                    $nodePath = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_fsController->getPathOfNode($node,
                        true));
                    $node->path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($nodePath->flatpath,
                        $record->getApplication());
                    $record->attachments->addRecord($node);
                }
            }

            // to resolve grants... but as not needed currently we save the effort
            //Filemanager_Controller_Node::getInstance()->resolveGrants($record->attachments);
        } catch (Tinebase_Exception_NotFound) {
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && count($record->attachments) > 0) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Found ' . count($record->attachments) . ' attachment(s).');
        }

        $record->attachments->sort('name');
        
        return $record->attachments;
    }
    
    /**
     * fetches attachments for multiple records at once
     * 
     * @param Tinebase_Record_RecordSet $records
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function getMultipleAttachmentsOfRecords($records)
    {
        if ($records instanceof Tinebase_Record_Interface) {
            $records = new Tinebase_Record_RecordSet($records::class, array($records));
        }

        if ($records->count() === 0) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' Fetching attachments for ' . $records->count() . ' record(s)');

        $recordNodeMapping = array();
        $className = $records->getRecordClassName();
        $recordIds = [];
        
        foreach ($records as $record) {
            $recordIds[] = $record->getId();
            $record->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        $classPathName = $this->_fsController->getApplicationBasePath($record->getApplication(),
                Tinebase_FileSystem::FOLDER_TYPE_RECORDS) . '/' . $className;

        // top folder for record attachments
        try {
            $classPathNode = $this->_fsController->stat($classPathName);
        } catch (Tinebase_Exception_NotFound) {
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }

        // subfolders for all records attachments
        $searchFilter = new Tinebase_Model_Tree_Node_Filter([
            [
                'field'     => 'parent_id',
                'operator'  => 'equals',
                'value'     => $classPathNode->getId()
            ], [
                'field'     => 'name',
                'operator'  => 'in',
                'value'     => $recordIds
            ]
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
        $recordNodes = $this->_fsController->searchNodes($searchFilter);
        if ($recordNodes->count() === 0) {
            // nothing to be done
            return new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        }
        foreach ($recordNodes as $recordNode) {
            $recordNodeMapping[$recordNode->getId()] = $recordNode->name;
        }

        $attachmentNodes = $this->_fsController->getTreeNodeChildren($recordNodes);
        $attachmentNodes->sort('name');

        // add attachments to records
        foreach ($attachmentNodes->filter('type', Tinebase_Model_Tree_FileObject::TYPE_FILE) as $attachmentFileNode) {
            $record = $records->getById($recordNodeMapping[$attachmentFileNode->parent_id]);
            $nodePath = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_fsController->getPathOfNode($attachmentFileNode,true));
            $attachmentFileNode->path = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($nodePath->flatpath, $record->getApplication());
            $record->attachments->addRecord($attachmentFileNode);
        }

        return $attachmentNodes;
    }
    
    /**
     * set file attachments of a record
     * 
     * @param Tinebase_Record_Interface $record
     */
    public function setRecordAttachments(Tinebase_Record_Interface $record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' Record: ' . print_r($record->toArray(), TRUE));
        
        $currentAttachments = ($record->getId()) ? $this->getRecordAttachments(clone $record) : new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $attachmentsToSet = ($record->attachments instanceof Tinebase_Record_RecordSet) 
            ? $record->attachments
            : new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node',
                empty($record->attachments) ? [] : (array)$record->attachments, TRUE);
        
        $attachmentDiff = $currentAttachments->diff($attachmentsToSet);

        foreach ($attachmentDiff->removed as $removed) {
            $this->_deleteAttachment($removed);
        }

        foreach ($attachmentDiff->modified as $modified) {
            $this->_fsController->update($attachmentsToSet->getById($modified->getId()));
        }

        foreach ($attachmentDiff->added as $added) {
            try {
                $this->addRecordAttachment($record, $added->name, $added);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' Could not add new attachment ' . print_r($added->toArray(), TRUE) . ' to record: ' . $record->getId()
                    . ' / Error Message: ' . $teia->getMessage());
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ .
                    ' Could not add new attachment ' . print_r($added->toArray(), TRUE) . ' to record: ' . $record->getId()
                    . ' / Error Message: ' . $tenf->getMessage());
            }
        }
    }

    /**
    * add attachment to record
    *
    * @param  Tinebase_Record_Interface $record
    * @param  string $name
        @see Tinebase_FileSystem::copyTempfile
    * @return null|Tinebase_Model_Tree_Node
    * @throws Tinebase_Exception_Duplicate
    */
    public function addRecordAttachment(Tinebase_Record_Interface $record, $name, mixed $attachment)
    {
        if (!$name && isset($attachment->tempFile) && ! is_resource($attachment->tempFile)) {
            $attachment = Tinebase_TempFile::getInstance()->getTempFile($attachment->tempFile);
            if ($attachment) {
                $name = $attachment->name;
            }
        }

        if ($attachment instanceof Tinebase_Model_Tree_Node && !isset($attachment->tempFile)) {
            if (isset($attachment->id)) {
                try {
                    $tmpNode = $this->_fsController->get($attachment->id, true);
                    $tmpPath = $this->_fsController->getPathOfNode($tmpNode, true);
                    $attachment = $this->_fsController->stat($tmpPath, null, true);
                } catch (Tinebase_Exception_NotFound) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                            __LINE__ . ' could not find attachment record with id: ' . $attachment->id);
                }
            } else {
                // this comes from \Calendar_Frontend_CalDAV_PluginManagedAttachments::httpPOSTHandler
                // it sends a file node with only hash and name and a bit set
                if (empty($attachment->hash)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                            __LINE__ . ' attachment record is missing an id');
                    return null;
                }
            }
        }

        if ($attachment instanceof Tinebase_Model_Tree_Node && empty($name)) {
            $name = $attachment->name;
        }

        if (empty($name)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Could not evaluate attachment name.');
            }
            return null;
        }

        if (mb_strlen((string) $name) > 255) {
            $name = mb_substr((string) $name, 0, 255);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Attachment name too long,
                 truncating to ' . $name);
            }
        }
        $attachmentsDir = $this->getRecordAttachmentPath($record, TRUE);
        $attachmentPath = $attachmentsDir . '/' . $name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug( __METHOD__ . ':: ' . __LINE__ . ' Creating new record attachment '
                . $attachmentPath);
        }
        if ($this->_fsController->fileExists($attachmentPath)) {
            throw new Tinebase_Exception_Duplicate('File already exists');
        }
        
        $this->_fsController->copyTempfile($attachment, $attachmentPath);
        
        return $this->_fsController->stat($attachmentPath);
    }
    
    /**
     * delete attachments of record
     * 
     * @param Tinebase_Record_Interface $record
     */
    public function deleteRecordAttachments($record)
    {
        $attachments = ($record->attachments instanceof Tinebase_Record_RecordSet)
            ? $record->attachments
            : $this->getRecordAttachments($record);
        foreach ($attachments as $node) {
            $this->_deleteAttachment($node);
        }
    }

    protected function _deleteAttachment(Tinebase_Model_Tree_Node $node)
    {
        if ($this->hasWatermark($node)) {
            $this->_deleteWatermark($node);
        }
        $this->_fsController->deleteFileNode($node);
    }

    protected function _deleteWatermark(Tinebase_Model_Tree_Node $node)
    {
        $watermark = $this->getWatermark($node);
        $this->_fsController->deleteFileNode($watermark);
    }

    /**
     * get path for record attachments
     * 
     * @param Tinebase_Record_Interface $record
     * @param boolean $createDirIfNotExists
     * @throws Tinebase_Exception_InvalidArgument
     * @return string
     */
    public function getRecordAttachmentPath(Tinebase_Record_Interface $record, $createDirIfNotExists = false)
    {
        if (! $record->getId()) {
            throw new Tinebase_Exception_InvalidArgument('record needs an identifier');
        }
        
        $parentPath = $this->_fsController->getApplicationBasePath($record->getApplication(),
            Tinebase_FileSystem::FOLDER_TYPE_RECORDS);
        $recordPath = $parentPath . '/' . $record::class . '/' . $record->getId();
        if ($createDirIfNotExists && ! $this->_fsController->fileExists($recordPath)) {
            $this->_fsController->mkdir($recordPath);
        }
        
        return $recordPath;
    }

    /**
     * get base path for record attachments (without the record id)
     *
     * @param Tinebase_Record_Interface $record
     * @param boolean $createDirIfNotExists
     * @return string
     */
    public function getRecordAttachmentBasePath(Tinebase_Record_Interface $record, $createDirIfNotExists = false)
    {
        $parentPath = $this->_fsController->getApplicationBasePath($record->getApplication(),
            Tinebase_FileSystem::FOLDER_TYPE_RECORDS);
        $recordPath = $parentPath . '/' . $record::class;
        if ($createDirIfNotExists && ! $this->_fsController->fileExists($recordPath)) {
            $this->_fsController->mkdir($recordPath);
        }

        return $recordPath;
    }

    public function createWatermark(Tinebase_Model_Tree_Node $attachment, $text, $overwrite = false)
    {
        if ((! $overwrite
            && Tinebase_FileSystem_RecordAttachments::getInstance()->hasWatermark($attachment))
            || $attachment->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER
        ) {
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Create Watermark for attachment ' . $attachment->getId() . ' ...');
        }

        $font = dirname(dirname(dirname(__FILE__))) . '/fonts/LiberationMono-Regular.ttf';
        $img = Tinebase_Controller::getInstance()->getImage('Tinebase', $attachment->getId(), 'vfs');
        if ($img->width > 1920 || $img->height > 1080) {
            $newWidth = $img->width;
            $newHeight = $img->height;
            if ($img->width > 1920) {
                $newWidth = 1920;
                $newHeight = intval($img->height * ($newWidth / $img->width));
            }
            if ($img->height > 1080) {
                $newHeight = 1080;
                $newWidth = intval($img->width * ($newHeight / $img->height));
            }
            Tinebase_ImageHelper::resize($img, $newWidth, $newHeight, 1);
        }
        $configWatermark = ['x' => ($img->width), 'y' => ($img->height - 2)];
        $fontSizePX = $img->height * 0.07;
        $fontSizePT = intval(($fontSizePX * 3) / 4);
        Tinebase_ImageHelper::createWatermark($img, $font, $fontSizePT, $text, $configWatermark);

        // create node with updated $img->blob
        $path = $this->_getWatermarkPath($attachment);
        $imgWatermark = Tinebase_FileSystem::getInstance()->fopen($path, 'w');
        if (! $imgWatermark) {
            throw new Tinebase_Exception('File watermark could not be created');
        }
        fwrite($imgWatermark, $img->blob);
        Tinebase_FileSystem::getInstance()->fclose($imgWatermark);
    }

    public function hasWatermark(Tinebase_Model_Tree_Node $attachment): bool
    {
        $path = $this->_getWatermarkPath($attachment);
        return Tinebase_FileSystem::getInstance()->fileExists($path);
    }

    public function getWatermark(Tinebase_Model_Tree_Node $attachment): Tinebase_Model_Tree_Node
    {
        $path = $this->_getWatermarkPath($attachment);
        return Tinebase_FileSystem::getInstance()->stat($path);
    }

    protected function _getWatermarkPath(Tinebase_Model_Tree_Node $attachment): string
    {
        $attachmentPath = Tinebase_FileSystem::getInstance()->getPathOfNode($attachment, getPathAsString: true);
        $filename = $attachment->name;
        $attachmentPath = (explode($filename, $attachmentPath))[0];
        if (!str_contains($attachmentPath, '/watermarks/')) {
            if (!Tinebase_FileSystem::getInstance()->fileExists($attachmentPath . '/watermarks')) {
                Tinebase_FileSystem::getInstance()->mkdir($attachmentPath . '/watermarks');
            }
            $attachmentPath .= 'watermarks/' . $filename;
        }
        return $attachmentPath;
    }
}
