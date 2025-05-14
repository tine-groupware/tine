<?php
/**
 * Filemanager Http frontend
 *
 * This class handles all Http requests for the Filemanager application
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Filemanager_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * download file
     * 
     * @param string $path
     * @param string $id
     * @param string $revision
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function downloadFile($path, $id, $revision = null, $disposition = 'attachment')
    {
        $this->_downloadFileNodeByPathOrId($path, $id, $revision, $disposition);
        exit;
    }

    /**
     * @param string $path
     * @param bool $recursive
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function downloadFolder(string $path, bool $recursive = false): void
    {
        $path = Filemanager_Controller_Node::getInstance()->addBasePath($path);
        $fs = Tinebase_FileSystem::getInstance();
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($path);
        if (!$fs->isDir($pathRecord->statpath)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' ' . $path . ' is not a directory'
                );
            }
            $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
        }
        $node = $fs->stat($pathRecord->statpath);

        if ($recursive) {
            $ids = $fs->getAllChildIds([$node->getId()], [], false, [Tinebase_Model_Grants::GRANT_DOWNLOAD]);
            $nodes = $fs->searchNodes(new Tinebase_Model_Tree_Node_Filter([
                ['field' => 'id', 'operator' => 'in', 'value' => $ids],
            ], '', ['ignoreAcl' => true]));
        } else {
            $filter = new Tinebase_Model_Tree_Node_Filter([
                ['field' => 'parent_id', 'operator' => 'equals', 'value' => $node->getId()],
                ['field' => 'type', 'operator' => 'not', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER],
            ]);
            $filter->setRequiredGrants([Tinebase_Model_Grants::GRANT_DOWNLOAD]);
            $nodes = $fs->searchNodes($filter);
        }

        if ($nodes->count() === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $path . ' is empty');
            $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
        }

        $tmpPath = Tinebase_Core::getTempDir() . '/' . uniqid('tine20_') . '.zip';
        try {
            $z = new ZipArchive();
            $z->open($tmpPath, ZipArchive::CREATE);

            $fun = function(Tinebase_Model_Tree_Node $node, string $path = '') use($nodes, $z, &$fun): void {
                $children = $nodes->filter('parent_id', $node->getId());
                $nodes->removeRecords($children);
                foreach ($children as $child) {
                    $childPath = $path . $child->name;
                    if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $child->type) {
                        $z->addEmptyDir($childPath);
                        $fun($child, $childPath . '/');
                    } elseif (is_file($realPath = $child->getFilesystemPath())) {
                        $z->addFile($realPath, $childPath);
                    }
                }
            };
            try {
                $fun($node);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage()
                    );
                }
                $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
            }
            if (!$z->close()) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                    __METHOD__ . '::' . __LINE__ . ' Could not create zip file');
                $this->_handleFailure();
            }

            $node = new Tinebase_Model_Tree_Node([
                'name' => $node->name . '.zip',
                'contenttype' => 'application/zip',
                'size' => filesize($tmpPath),
            ], true);
            $this->_downloadFileNode($node, $tmpPath, null, true);

        } finally {
            unlink($tmpPath);
        }
    }

    /**
     * _downloadFileNodeByPathOrId
     *
     * @param      $path
     * @param      $id
     * @param null $revision
     * @throws Filemanager_Exception
     */
    protected function _downloadFileNodeByPathOrId($path, $id, $revision = null, $disposition = 'attachment')
    {
        $revision = $revision ?: null;

        $nodeController = Filemanager_Controller_Node::getInstance();
        if ($path) {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($path));
            try {
                $node = $nodeController->getFileNode($pathRecord);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_FORBIDDEN);
            }
        } elseif ($id) {
            $node = $nodeController->get($id);
            $nodeController->resolveMultipleTreeNodesPath($node);
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));
        } else {
            $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
        }

        $this->_downloadFileNode($node, $pathRecord->streamwrapperpath, $revision, false, $disposition);
    }
}
