<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Abstract class for an Tine 2.0 application with Http interface
 * 
 * Note, that the Http interface in tine 2.0 is used to generate the base layouts
 * in new browser windows. 
 * 
 * @package     Tinebase
 * @subpackage  Application
 */
abstract class Tinebase_Frontend_Http_Abstract extends Tinebase_Frontend_Abstract
{
    /**
     * generic export function
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_options format/definition id
     * @param Tinebase_Controller_Record_Abstract $_controller
     * @return void
     * 
     * @todo support single ids as filter?
     */
    protected function _export(Tinebase_Model_Filter_FilterGroup $_filter, $_options, Tinebase_Controller_Record_Abstract $_controller = NULL)
    {
        // extend execution time to 30 minutes
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(1800);
        
        // get export object
        $export = Tinebase_Export::factory($_filter, $_options, $_controller);
        $format = $export->getFormat();
        if ('pdf' === $format && ! Tinebase_Export::doPdfLegacyHandling()) {
            $switchFormat = 'newPDF';
        } else {
            if ($export instanceof Tinebase_Export_CsvNew) {
                $switchFormat = 'newCsv';
            } else {
                $switchFormat = $format;
            }
        }

        if (strpos($format, 'new') === 0) {
            $format = strtolower(substr($format, 3));
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Exporting ' . $_filter->getModelName() . ' (' . get_class($export) . ')'
                . ' in format ' . $format .
            ' / options: ' . print_r($_options, TRUE)
        );

        $result = $this->_generateExport($export, $_controller, $_filter, $format, $switchFormat, $pdfOutput, $_options);

        if ($export->isDownload()) {
            $this->_writeExportDownloadHeaders($export, $_filter, $format);
            $this->_outputExportContent($switchFormat, $export, $result, $pdfOutput);
        } else {
            $this->_outputFileLocationJson($export, $result);
        }
        
        // reset max execution time to old value
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
    }

    /**
     * @param $export
     * @param $_controller
     * @param $_filter
     * @param $format
     * @param $switchFormat
     * @param $pdfOutput
     * @param array $_options
     * @return string|null
     * @throws Exception
     */
    protected function _generateExport(&$export, $_controller, $_filter, &$format, &$switchFormat, &$pdfOutput, $_options)
    {
        $result = null;

        try {
            switch ($switchFormat) {
                case 'pdf':
                    $ids = $_controller->search($_filter, NULL, FALSE, TRUE, 'export');

                    // loop records
                    foreach ($ids as $id) {
                        if (! empty($id)) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating pdf for ' . $_filter->getModelName() . '  id ' . $id);
                            $record = $_controller->get($id);
                            $export->generate($record);
                        } else {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $_filter->getModelName() . ' id empty!');
                        }
                    }

                    // render pdf
                    try {
                        $pdfOutput = $export->render();
                    } catch (Zend_Pdf_Exception $e) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error creating pdf: ' . $e->__toString());
                        exit;
                    }

                    break;

                case 'ods':
                    $result = $export->generate();
                    break;
                case 'newOds':
                case 'newPDF':
                case 'newCsv':
                case 'csv':
                case 'ics':
                case 'vcf':
                case 'xls':
                case 'xlsx':
                case 'doc':
                case 'docx':
                    $result = $export->generate($_filter);
                    break;
                default:
                    throw new Tinebase_Exception_UnexpectedValue('Format ' . $format . ' not supported.');
            }
        } catch (Tinebase_Exception_UnexpectedValue $e) {
            if ($e->getMessage() === 'Format ' . $format . ' not supported.') {
                throw $e;
            }
            Tinebase_Exception::log($e);
            $export = new Tinebase_Export_ErrorReport($e, $_options);
            $format = 'txt';
            $switchFormat = 'error';
        } catch (Exception $e) {
            if (strpos(get_class($e), 'Zend_Db') === 0) {
                throw $e;
            }
            Tinebase_Exception::log($e);
            $export = new Tinebase_Export_ErrorReport($e, $_options);
            $format = 'txt';
            $switchFormat = 'error';
        }

        return $result;
    }

    protected function _writeExportDownloadHeaders($export, $filter, $format)
    {
        $contentType = $export->getDownloadContentType();
        $filename = $export->getDownloadFilename($filter->getApplicationName(), $format);

        if (!headers_sent()) {
            header("Pragma: public");
            header("Cache-Control: max-age=0");
            header("Content-Disposition: " . (($format == 'pdf') ? 'inline' : 'attachment') . '; filename=' . $filename);
            header("Content-Description: $format File");
            header("Content-type: $contentType");
        }
    }

    /**
     * @param $switchFormat
     * @param $export
     * @param $filename
     * @param $pdfOutput
     *
     * @todo use stream here instead of temp file?
     */
    protected function _outputExportContent($switchFormat, $export, $filename, $pdfOutput)
    {
        switch ($switchFormat) {
            case 'pdf':
                echo $pdfOutput;
                break;
            case 'newOds':
            case 'newCsv':
            case 'newPDF':
            case 'xls':
            case 'xlsx':
            case 'doc':
            case 'ics':
            case 'vcf':
            case 'docx':
            case 'error':
                // redirect output to client browser
                // TODO refactor function signature - write does not write content to file but to stdout/browser
                if (null === $filename) {
                    $export->write();
                } else {
                    $export->write($filename);
                }
                break;
            default:
                readfile($filename);
                unlink($filename);
        }
    }

    /**
     * @param $switchFormat
     * @param $export
     * @param $filename
     * @param $pdfOutput
     */
    protected function _outputFileLocationJson($export, $filename)
    {
        $tmpFile = [];
        if (! method_exists($export, 'getTargetFileLocation')) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' export does not support file location');
            $fileLocation = null;
            $filename = null;
        } else {
            if (method_exists($export, 'writeToFileLocation')) {
                $export->writeToFileLocation();
            }
            $fileLocation = $export->getTargetFileLocation($filename);
            if ($fileLocation && $fileLocation->tempfile_id) {
                $tmpFile = Tinebase_TempFile::getInstance()->getTempFile($fileLocation->tempfile_id);
            }
        }

        echo json_encode([
            'success' => ($fileLocation !== null),
            'file_location' => $fileLocation ? $fileLocation->toArray() : [],
            'file' => $tmpFile ? $tmpFile->toArray() : []
        ]);
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_type
     * @param int $_num
     */
    protected function _downloadPreview(Tinebase_Model_Tree_Node $_node, $_type, $_num = 0)
    {
        $fileSystem = Tinebase_FileSystem::getInstance();

        $request = Tinebase_Core::getRequest();
        $syncHeader = $request->getHeader('X-TINE20-PREVIEWSERVICE-SYNC');

        if ('regenerate' === $syncHeader) {
            Tinebase_FileSystem_Previews::getInstance()->deletePreviews([$_node->hash]);
            if (false === Tinebase_FileSystem_Previews::getInstance()->createPreviewsFromNode($_node)) {
                $this->_handleFailure(); // defaults 500
            }
        }

        $previewNode = null;
        try {
            $previewNode = Tinebase_FileSystem_Previews::getInstance()->getPreviewForNode($_node, $_type, $_num);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if ('true' === $syncHeader) {
                if (false === Tinebase_FileSystem_Previews::getInstance()->createPreviewsFromNode($_node)) {
                    $this->_handleFailure(); // defaults 500
                }
                try {
                    $previewNode = Tinebase_FileSystem_Previews::getInstance()->getPreviewForNode($_node, $_type, $_num);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $this->_handleFailure(404);
                }
            } else {
                $this->_handleFailure(404);
            }
        }

        if (false !== $syncHeader) {
            $additionalHeader = ['X-TINE20-PREVIEWSERICE-PREVIEW-COUNT' =>
                Tinebase_FileSystem_Previews::getInstance()->getPreviewCountForNodeAndType($_node, $_type)];
        } else {
            $additionalHeader = [];
        }

        $this->_prepareHeader($previewNode->name, $previewNode->contenttype, 'inline', $previewNode->size, $additionalHeader);

        $handle = $fileSystem->fopen($fileSystem->getPathOfNode($previewNode, true), 'r', $previewNode->revision);

        if (false === $handle) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' could not open preview by real path for hash');
            $this->_handleFailure();
        }

        fpassthru($handle);
        fclose($handle);
    }


    /**
     * download (fpassthru) tempfile
     *
     * @param Tinebase_Model_TempFile $tempFile
     * @param string $filesystemPath
     */
    protected function _downloadTempFile(Tinebase_Model_TempFile $tempFile, $filesystemPath)
    {
        Tinebase_Core::setExecutionLifeTime(0);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Download tempfile' . print_r($tempFile->toArray(), TRUE));

        $this->_prepareHeader($tempFile->name, $tempFile->contenttype, /* $disposition */ 'attachment', $tempFile->size);

        $handle = fopen($filesystemPath, 'r');

        fpassthru($handle);
        fclose($handle);
    }

    /**
     * download (fpassthru) file node
     * 
     * @param Tinebase_Model_Tree_Node $node
     * @param string $filesystemPath
     * @param int|null $revision
     * @param boolean $ignoreAcl
     */
    protected function _downloadFileNode(Tinebase_Model_Tree_Node $node, $filesystemPath, $revision = null, $ignoreAcl = false)
    {
        if (! $ignoreAcl && ! Tinebase_Core::getUser()->hasGrant($node, Tinebase_Model_Grants::GRANT_DOWNLOAD)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' User has no download grant for node ' . $node->getId());
            $this->_handleFailure(403);
        }

        Tinebase_Core::setExecutionLifeTime(0);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Download file node ' . print_r($node->toArray(), TRUE));

        $this->_prepareHeader($node->name, $node->contenttype, /* $disposition */ 'attachment', $node->size);

        if (null !== $revision) {
            $streamContext = stream_context_create(array(
                'Tinebase_FileSystem_StreamWrapper' => array(
                    'revision' => $revision
                )
            ));
            $handle = @fopen($filesystemPath, 'r', false, $streamContext);
        } else {
            $handle = @fopen($filesystemPath, 'r');
        }

        if ($handle) {
            fpassthru($handle);
            fclose($handle);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not open file: ' . $filesystemPath);
            $this->_handleFailure();
        }
    }

    /**
     * prepares the header for attachment download
     *
     * @param string $filename
     * @param string $contentType
     * @param string $disposition
     * @param string $length WILL BE IGNORED! webserver might apply compression -> content length might change
     * @param array $additionalHeaders
     *
     * TODO make length param work
     * @see 0010522: Anonymous download link - no or wrong filesize in header
     */
    protected function _prepareHeader($filename, $contentType, $disposition = 'attachment', $length = null, $additionalHeaders = [])
    {
        if (headers_sent()) {
            return;
        }

        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");

        // overwrite Pragma header from session
        header("Pragma: cache");

        if ($disposition) {
            $headerLine = 'Content-Disposition: ' . $disposition . '; filename="' . $filename . '"';
            $headerLine = str_replace(["\r\n", "\r", "\n"], '', $headerLine);
            header($headerLine);
        }
        if (empty($contentType)) {
            $contentType = Tinebase_Model_Tree_FileObject::DEFAULT_CONTENT_TYPE;
        }
        header("Content-Type: " . $contentType);

        foreach ($additionalHeaders as $key => $val) {
            header($key . ': ' . $val);
        }
    }

    /**
     * magic method for http api
     *
     * @param string $method
     * @param array  $args
     * @return mixed|null
     */
    public function __call($method, array $args)
    {
        // provides api for default application methods
        if (preg_match('/^(export)([a-z0-9_]+)/i', $method, $matches)) {
            $apiMethod = $matches[1];
            $model = in_array($apiMethod, array('export')) ? substr($matches[2],0,-1) : $matches[2];
            $modelController = Tinebase_Core::getApplicationInstance($this->_applicationName, $model);
            switch ($apiMethod) {
                case 'export':
                    $decodedParams = $this->_getDecodedFilterAndOptions($args[0], $args[1]);

                    $modelName = $this->_applicationName . '_Model_' . $model;
                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($modelName);
                    $filter->setFromArrayInUsersTimezone($decodedParams['filter']);

                    return $this->_export($filter, $decodedParams['options'], $modelController);
                    break;
            }
        }

        return null;
    }

    /**
     * @param string $filterString
     * @param string $optionsString
     * @return array
     */
    protected function _getDecodedFilterAndOptions($filterString, $optionsString)
    {
        $decodedFilter = Tinebase_Helper::is_json($filterString) ? $this->_prepareParameter($filterString) : $filterString;
        $decodedOptions = $this->_prepareParameter($optionsString);

        if (empty($decodedFilter) && isset($decodedOptions['recordData']['id'])) {
            // get contact id from $decodedOptions
            $decodedFilter = $decodedOptions['recordData']['id'];
        }

        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }

        return [
            'filter' => $decodedFilter,
            'options' => $decodedOptions,
        ];
    }

    /**
     * receives file uploads and stores it in the file_uploads db
     */
    protected function _uploadTempFile()
    {
        try {
            // close session to allow other requests
            Tinebase_Session::writeClose(true);

            $tempFile = Tinebase_TempFile::getInstance()->uploadTempFile();

            die(Zend_Json::encode(array(
                'status'   => 'success',
                'tempFile' => $tempFile->toArray(),
            )));
        } catch (Tinebase_Exception $exception) {
            Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__
                . " File upload could not be done, due to the following exception: \n" . $exception);
            $this->_handleFailure();
        }
    }

    /**
     * @param int $code
     */
    protected function _handleFailure($code = Tinebase_Server_Abstract::HTTP_ERROR_CODE_INTERNAL_SERVER_ERROR)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' HTTP request failed - code: ' . $code);

        Tinebase_Server_Abstract::setHttpHeader($code);

        die(Zend_Json::encode(array(
            'code' => $code,
            'status' => 'failed',
        )));
    }
}
