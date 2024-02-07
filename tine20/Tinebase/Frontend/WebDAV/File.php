<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for Tinebase
 * 
 * @package     Tinebase
 */
class Tinebase_Frontend_WebDAV_File extends Tinebase_Frontend_WebDAV_Node implements Sabre\DAV\IFile
{
    /**
     * @return bool|false|mixed|resource|null
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws \Sabre\DAV\Exception\Forbidden
     * @throws \Sabre\DAV\Exception\NotFound
     */
    public function get() 
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! $pathRecord->isRecordPath() && ! Tinebase_FileSystem::getInstance()->checkPathACL(
                $pathRecord->getParent(),
                'get',
                true, false
            )
        ) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to download file: ' . $this->_path);
        }

        try {
            $node = $pathRecord->getNode();
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\NotFound($tenf->getMessage());
        }
        if ($node->is_quarantined) {
            throw new Sabre\DAV\Exception\Forbidden('File is quarantined: ' . $this->_path);
        }

        if (false === ($handle = Tinebase_FileSystem::getInstance()->fopen($this->_path, 'r'))) {
            // if we have a file without content / revision yet
            if (empty($node->hash) || $node->size == 0) {
                return fopen('php://memory', 'r');
            }
            // possible race condition
            throw new Sabre\DAV\Exception\NotFound('could not open ' . $this->_path);
        }
         
        return $handle;
    }
    
    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     */ 
    public function getContentType() 
    {
        return $this->_node->contenttype;
    }
    
    /**
     * Deleted the current node
     *
     * @throws Sabre\DAV\Exception\Forbidden
     * @return void 
     */
    public function delete() 
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! Tinebase_FileSystem::getInstance()->checkPathACL(
                $pathRecord->getParent(),
                'delete',
                true, false
            )
        ) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to edit file: ' . $this->_path);
        }
        
        Tinebase_FileSystem::getInstance()->unlink($this->_path);
    }

    /**
     * @param mixed $data
     * @return string|null
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Backend
     * @throws Tinebase_Exception_NotFound
     * @throws \Sabre\DAV\Exception\Forbidden
     * @throws \Sabre\DAV\Exception\NotFound
     */
    public function put($data)
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! Tinebase_FileSystem::getInstance()->checkPathACL(
                $pathRecord->getParent(),
                'update',
                true, false
            )) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to edit file: ' . $this->_path);
        }

        Tinebase_Frontend_WebDAV_Directory::checkQuota($pathRecord->getNode());

        if (($_SERVER['HTTP_OC_CHUNKED'] ?? false) && is_resource($data)) {
            $name = urldecode(basename(ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/')));
            $completeFile = Tinebase_Frontend_WebDAV_Directory::handleOwnCloudChunkedFileUpload($name, $data);

            if (!$completeFile instanceof Tinebase_Model_TempFile) {
                return null;
            }

            if (false === ($data = fopen($completeFile->path, 'r'))) {
                throw new Sabre\DAV\Exception('fopen on temp file path failed ' . $completeFile->path);
            }
        }

        if (false === ($handle = Tinebase_FileSystem::getInstance()->fopen($this->_path, 'w'))) {
            throw new Tinebase_Exception_Backend('Tinebase_FileSystem::fopen failed for path ' . $this->_path);
        }

        if (is_resource($data)) {
            if (false === stream_copy_to_stream($data, $handle)) {
                throw new Tinebase_Exception_Backend('stream_copy_to_stream failed');
            }
        } else {
            throw new Tinebase_Exception_UnexpectedValue('data should be a resource');
        }

        // save file object
        try {
            if (true !== Tinebase_FileSystem::getInstance()->fclose($handle)) {
                throw new Tinebase_Exception_Backend('Tinebase_FileSystem::fclose failed for path ' . $this->_path);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\NotFound($tenf->getMessage());
        }

        // refetch data
        $this->_node = Tinebase_FileSystem::getInstance()->stat($this->_path);

        return $this->getETag();
    }
}
