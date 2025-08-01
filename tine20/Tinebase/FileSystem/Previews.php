<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview images for file revisions
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Previews
{
    public static ?string $unittestTransactionId = null;

    /**
     * @var Tinebase_FileSystem_Preview_ServiceInterface
     */
    protected $_previewService = NULL;

    /**
     * filesystem controller
     *
     * @var Tinebase_FileSystem
     */
    protected $_fsController = NULL;

    /**
     * @var string
     */
    protected $_basePath = NULL;

    /**
     * @var Tinebase_Model_Tree_Node
     */
    protected $_basePathNode = NULL;

    /**
     * @var array
     */
    protected $_supportedDocumentFileExtensions = array(
        'txt', 'rtf', 'odt', 'ods', 'odp', 'doc', 'xls', 'xlsx', 'doc', 'docx', 'ppt', 'pptx', 'pdf',
    );

    /**
     * @var array
     */
    protected $_supportedImageFileExtensions = array(
        'jpg', 'jpeg', 'gif', 'tiff', 'png'
    );

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_FileSystem_Previews
     */
    private static $_instance = NULL;

    /**
     * the constructor
     */
    protected function __construct()
    {
        $this->_fsController = Tinebase_FileSystem::getInstance();
        $this->_previewService = Tinebase_Core::getPreviewService();
    }

    /**
     * sets the preview service to be used. returns the old service
     *
     * @param Tinebase_FileSystem_Preview_ServiceInterface $_service
     * @return Tinebase_FileSystem_Preview_ServiceInterface
     */
    public function setPreviewService(Tinebase_FileSystem_Preview_ServiceInterface $_service)
    {
        $return = $this->_previewService;
        $this->_previewService = $_service;
        return $return;
    }

    /**
     * the singleton pattern
     *
     * @return Tinebase_FileSystem_Previews
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_FileSystem_Previews();
        }

        return self::$_instance;
    }

    /**
     * @return string
     */
    protected function _getBasePath()
    {
        if (null === $this->_basePath) {
            $this->_basePath = $this->_fsController->getApplicationBasePath(
                Tinebase_Application::getInstance()->getApplicationByName('Tinebase'),
                Tinebase_FileSystem::FOLDER_TYPE_PREVIEWS);
            if (!$this->_fsController->fileExists($this->_basePath)) {
                $this->_basePathNode = $this->_fsController->mkdir($this->_basePath);
            }
        }

        return $this->_basePath;
    }

    /**
     * @return Tinebase_Model_Tree_Node
     */
    public function getBasePathNode()
    {
        if (null === $this->_basePathNode) {
            $this->_basePathNode = $this->_fsController->stat($this->_getBasePath());
        }
        return $this->_basePathNode;
    }

    /**
     * @param string $_fileExtension
     * @return bool
     */
    public function isSupportedFileExtension($_fileExtension)
    {
        return in_array(mb_strtolower($_fileExtension), $this->_supportedDocumentFileExtensions)
               || in_array(mb_strtolower($_fileExtension), $this->_supportedImageFileExtensions);
    }

    /**
     * @param string $_fileExtension
     * @return bool true: is image file extension
     */
    public function isImageFileExtension($_fileExtension)
    {
        return in_array(mb_strtolower($_fileExtension), $this->_supportedImageFileExtensions);
    }

    /**
     * @param bool $_image true: config for image preview, false: config for document preview
     * @return array[] config for DocumentPreviewService
     */
    protected function _getConfig($_image = false)
    {
        if ($_image === true) {
            $previewMaxX = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_IMAGE_PREVIEW_SIZE_X};
            $previewMaxY = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_IMAGE_PREVIEW_SIZE_Y};
        } else {
            $previewMaxX = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_DOCUMENT_PREVIEW_SIZE_X};
            $previewMaxY = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_DOCUMENT_PREVIEW_SIZE_Y};
        }

        return array(
            'thumbnail' => array(
                'firstPage' => true,
                'filetype'  => 'jpg',
                'x'         => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_THUMBNAIL_SIZE_X},
                'y'         => Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_THUMBNAIL_SIZE_Y},
                'color'     => 'white'
            ),
            'previews'  => array(
                'firstPage' => false,
                'filetype'  => 'jpg',
                'x'         => $previewMaxX,
                'y'         => $previewMaxY,
                'color'     => 'white'
            )
        );
    }

    /**
     * @param string|Tinebase_Model_Tree_Node $_id
     * @param int $_revision
     * @return bool
     * @throws Zend_Db_Statement_Exception|Tinebase_Exception
     */
    public function createPreviews($_id, $_revision = null): bool
    {
        try {
            $node = $_id instanceof Tinebase_Model_Tree_Node ? $_id : $this->_fsController->get($_id, $_revision);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
            return false;
        }

        try {
            return $this->createPreviewsFromNode($node);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // this might throw Deadlock exceptions - ignore those
            if (str_contains($zdse->getMessage(), 'Deadlock')) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' Ignoring deadlock / skipping preview generation - Error: '
                    . $zdse->getMessage());
                return false;
            } else {
                throw $zdse;
            }
        }
    }

    /**
     * @param Tinebase_Model_Tree_Node $node
     * @return bool
     */
    public function canNodeHavePreviews(Tinebase_Model_Tree_Node $node)
    {
        if ($node->type !== Tinebase_Model_Tree_FileObject::TYPE_FILE
            || empty($node->hash)
            || $node->size == 0
            || Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->
                {Tinebase_Config::FILESYSTEM_PREVIEW_MAX_FILE_SIZE} < $node->size
            || Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->
                {Tinebase_Config::FILESYSTEM_PREVIEW_MAX_ERROR_COUNT} < $node->preview_error_count
            || ! $this->_checkMinimalImageSize($node)
        ) {
            return false;
        }

        $fileExtension = pathinfo($node->name, PATHINFO_EXTENSION);
        return $this->isSupportedFileExtension($fileExtension);
    }

    /**
     * check if node is image, if image has dimensions < 5px, (for example tracking pixel or horizontal lines)
     * => do not create preview
     *
     * @param Tinebase_Model_Tree_Node $node
     * @return bool
     */
    protected function _checkMinimalImageSize(Tinebase_Model_Tree_Node $node): bool
    {
        if (! in_array($node->contenttype, Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
            return true;
        }
        $path = Tinebase_FileSystem::getInstance()->getFilesystemPathByHash($node->hash);
        $imageSize = @getimagesize($path);
        if (!$imageSize) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Could not read image file from path: '
                    . $path
                );
            }
            return false;
        } else if ($imageSize[0] < 10 || $imageSize[1] < 10) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Image too small for preview ('
                    . $imageSize[0] . 'x' . $imageSize[1] . ')'
                );
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param Tinebase_Model_Tree_Node $node
     * @return bool
     * @throws Tinebase_Exception
     */
    public function createPreviewsFromNode(Tinebase_Model_Tree_Node $node)
    {
        if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions() &&
                (null === static::$unittestTransactionId || array_diff(Tinebase_TransactionManager::getInstance()->getOpenTransactionIds(), [static::$unittestTransactionId]))) {
            throw new Tinebase_Exception(__METHOD__ . ' must not be called with transaction open');
        }

        if (!$this->canNodeHavePreviews($node) || $this->hasPreviews($node)) {
            return true;
        }

        $flySystem = $node->flysystem ? Tinebase_Controller_Tree_FlySystem::getFlySystem($node->flysystem) : null;
        if (!$flySystem) {
            $path = $node?->tempFile?->path ?? $this->_fsController->getRealPathForHash($node->hash);
            if (!is_file($path)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' file ' . $node->getId() . ' '
                        . $node->name . ' is not present in filesystem: ' . $path);
                }
                return false;
            }
        }

        $lockId = __METHOD__ . $node->getId();
        $lock = Tinebase_Core::getMultiServerLock($lockId);
        $lockRaii = new Tinebase_RAII(fn() => $lock->isLocked() && $lock->release());
        if (false === Tinebase_Core::acquireMultiServerLock($lockId)) {
            // we wait until the other job is done, then return
            Tinebase_Core::acquireMultiServerLock($lockId, false);
            return true;
        }

        $ext = pathinfo($node->name, PATHINFO_EXTENSION);

        try {
            $tempPath = Tinebase_TempFile::getTempPath() . '.' . $ext;
            if ($flySystem) {
                $tmpFh = null;
                try {
                    if (!($tmpFh = fopen($tempPath, 'w')) || !stream_copy_to_stream($flySystem->readStream($node->flypath), $tmpFh)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' could not copy file '
                                . $node->getId() . ' ' . $node->name . ' ' . $node->flypath . ' to temp path: ' . $tempPath);
                        }
                        return false;
                    }
                } finally {
                    if ($tmpFh) @fclose($tmpFh);
                }
            } elseif (false === copy($path, $tempPath)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' could not copy file '
                        . $node->getId() . ' ' . $node->name . ' ' . $path . ' to temp path: ' . $tempPath);
                }
                return false;
            }

            $config = $this->_getConfig($this->isImageFileExtension($ext));

            if (false === ($result = $this->_previewService->getPreviewsForFile($tempPath, $config))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' preview creation for file ' . $node->getId() . ' timed out');
                }

                $this->writePreviewError($node);

                return false;
            }

        } catch (Tinebase_FileSystem_Preview_BadRequestException $exception) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' preview creation for file ' . $node->getId() . ' failed');
            }

            $this->writePreviewError($node, (int)$exception->getHttpStatus());

            return false;

        } catch (Exception $exception) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' preview creation for file ' . $node->getId() . ' failed');
            }
            Tinebase_Exception::log($exception);

            return false;

        } finally {
            unlink($tempPath);
        }

        foreach ($config as $key => $cnf) {
            if (!isset($result[$key])) {
                return false;
            }
        }

        $transactionTime = 0;
        $transactionId = null;
        $resetTransaction = function() use(&$transactionId, &$transactionTime) {
            if ((microtime(true)) - $transactionTime > 1.0) {
                // restart transaction, this also resets the aquiredWriteLock
                if (null !== $transactionId) {
                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                }
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                $this->_fsController->acquireWriteLock();
                $transactionTime = microtime(true);
            }
        };

        $oldAvScan = $this->_fsController->doAVScan(false);
        try {
            $resetTransaction();

            $basePath = $this->_getBasePath() . '/' . substr($node->hash, 0, 3) . '/' . substr($node->hash, 3);
            if (!$this->_fsController->isDir($basePath)) {
                $this->_fsController->mkdir($basePath);
            } else {
                if ($this->_fsController->fileExists($basePath)) {
                    $this->_fsController->rmdir($basePath, true);
                }
                $this->_fsController->mkdir($basePath);
            }

            $this->_fsController->updatePreviewCount($node->hash, 0);

            $files = [];
            $maxCount = 0;
            foreach ($config as $key => $cnf) {
                $i = 0;
                foreach ($result[$key] as $blob) {
                    $files[$basePath . '/' . $key . '_' . ($i++) . '.' . $cnf['filetype']] = $blob;
                }
                if ($i > $maxCount) {
                    $maxCount = $i;
                }
            }
            unset($result);

            foreach ($files as $name => &$blob) {
                $resetTransaction();
                $tempFile = Tinebase_TempFile::getTempPath();
                if (false === file_put_contents($tempFile, $blob)) {
                    throw new Tinebase_Exception('could not write content to temp file');
                }
                try {
                    $blob = null;
                    if (false === ($fh = fopen($tempFile, 'r'))) {
                        throw new Tinebase_Exception('could not open temp file for reading');
                    }

                    // this means we create a file node of type preview
                    $this->_fsController->setStreamOptionForNextOperation(
                        Tinebase_FileSystem::STREAM_OPTION_CREATE_PREVIEW, true);
                    $this->_fsController->copyTempfile($fh, $name);
                    fclose($fh);
                } finally {
                    unlink($tempFile);
                }
            }

            if ((int)$node->preview_count !== $maxCount) {
                $this->_fsController->updatePreviewCount($node->hash, $maxCount);
            }
            if ($node?->tempFile?->path) {
                $node->preview_count = $maxCount;
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

        } finally {
            $this->_fsController->doAVScan($oldAvScan);
            if (null !== $transactionId) {
                // this only happens if an exception is thrown, no need to return false
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        unset($lockRaii);
        return true;
    }

    protected function writePreviewError(Tinebase_Model_Tree_Node $node, ?int $httpStatus = null): void
    {
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $this->_fsController->acquireWriteLock();

        if (null !== $httpStatus) {
            $this->_fsController->updatePreviewStatus($node->hash, $httpStatus);
        }
        $this->_fsController->updatePreviewErrorCount($node->hash, $node->preview_error_count + 1);
        $this->_fsController->updatePreviewCount($node->hash, 0);

        $transaction->release();
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_type
     * @return int
     * @throws Tinebase_Exception_NotFound
     */
    public function getPreviewCountForNodeAndType(Tinebase_Model_Tree_Node $_node, $_type)
    {
        if (empty($_node->hash) || strlen($_node->hash) < 4) {
            throw new Tinebase_Exception_NotFound('node needs to have proper hash set');
        }

        $config = $this->_getConfig();
        if (!isset($config[$_type])) {
            throw new Tinebase_Exception_NotFound('type ' . $_type . ' not configured');
        }

        $previewCount = (int)($_node->preview_count);

        if ($previewCount < 1) return 0;

        $basePath = $this->_getBasePath() . '/' . substr($_node->hash, 0, 3) . '/' . substr($_node->hash, 3)
            . '/' . $_type . '_';
        $ending = '.' . $config[$_type]['filetype'];

        if ($this->_fsController->fileExists($basePath . ($previewCount - 1) . $ending)) {
            return $previewCount;
        }
        if (!$this->_fsController->fileExists($basePath . '0' . $ending)) {
            return 0;
        }

        $count = 1;
        do {
            if (!$this->_fsController->fileExists($basePath . ($count) . $ending)) {
                return $count;
            }
        } while (++$count < $previewCount);
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_type
     * @param int $_num
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    public function getPreviewForNode(Tinebase_Model_Tree_Node $_node, $_type, $_num)
    {
        if (empty($_node->hash) || strlen($_node->hash) < 4) {
            throw new Tinebase_Exception_NotFound('node needs to have proper hash set');
        }

        $config = $this->_getConfig();
        if (!isset($config[$_type])) {
            throw new Tinebase_Exception_NotFound('type ' . $_type . ' not configured');
        }

        $path = $this->_getBasePath() . '/' . substr($_node->hash, 0, 3) . '/' . substr($_node->hash, 3)
                . '/' . $_type . '_' . $_num . '.' . $config[$_type]['filetype'];

        return $this->_fsController->stat($path);
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function hasPreviews(Tinebase_Model_Tree_Node $_node)
    {
        if (empty($_node->hash) || strlen($_node->hash) < 4) {
            throw new Tinebase_Exception_InvalidArgument('node needs to have proper hash set');
        }

        try {
            $this->_fsController->stat($this->_getBasePath() . '/' . substr($_node->hash, 0, 3) . '/' . substr($_node->hash, 3));
        } catch (Tinebase_Exception_NotFound) {
            return false;
        }

        return $_node->preview_count > 0;
    }

    /**
     * @param array $_hashes
     */
    public function deletePreviews(array $_hashes)
    {
        $basePath = $this->_getBasePath();
        foreach($_hashes as $hash) {
            try {
                $this->_fsController->rmdir($basePath . '/' . substr((string) $hash, 0, 3) . '/' . substr((string) $hash, 3), true);
                // these hashes are unchecked, there may not be previews for them! => catch, no logging (debug at most)
            } catch(Tinebase_Exception_NotFound) {}
        }
    }

    /**
     * @return bool
     */
    public function deleteAllPreviews()
    {
        return $this->_fsController->rmdir($this->_getBasePath(), true);
    }

    public function resetErrorCount()
    {
        $foBackend = new Tinebase_Tree_FileObject();
        $foBackend->resetPreviewErrorCount();
    }
}
