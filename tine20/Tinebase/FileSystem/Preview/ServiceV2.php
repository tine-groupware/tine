<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Preview_ServiceV2 extends Tinebase_FileSystem_Preview_ServiceV1
{
    /**
     * Tinebase_FileSystem_Preview_ServiceV2 constructor.
     * @param $networkAdapter Tinebase_FileSystem_Preview_NetworkAdapter
     */
    public function __construct(protected $_networkAdapter)
    {
        parent::__construct();
    }

    /**
     * Uses the DocumentPreviewService to generate previews (images or pdf files) for multiple files of same type.
     *
     * {@inheritDoc}
     *
     * @param array $filePaths of file Paths to convert
     * @param array $config
     * @return array|bool
     * @throws Zend_Http_Client_Exception
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     */
    public function getPreviewsForFiles(array $filePaths, array $config)
    {
        if (isset($config['synchronRequest']) && $config['synchronRequest']) {
            $synchronRequest = true;
        } else {
            $synchronRequest = false;
        }

        $httpClient = $this->_getHttpClient($synchronRequest);
        $httpClient->setMethod(Zend_Http_Client::POST);
        $httpClient->setParameterPost('config', json_encode($config));

        foreach ($filePaths as $filePath) {
            $httpClient->setFileUpload($filePath, 'files[]');
        }

        return $this->_requestPreviews($httpClient, $synchronRequest);
    }

    /**
     * @param boolean $_synchronRequest
     * @return Zend_Http_Client
     */
    protected function _getHttpClient($_synchronRequest)
    {
        return $this->_networkAdapter->getHttpsClient($this->_getHttpClientConfig($_synchronRequest));
    }

    protected function _processJsonResponse(array $responseJson)
    {
        $response = array();
        foreach ($responseJson as $key => $files) {
            $response[$key] = array();
            foreach ($files as $file) {
                $blob = base64_decode((string) $file);
                if (false === $blob) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' couldn\'t base64decode response file ' . $key);
                    }
                    return false;
                }
                $response[$key][] = $blob;
            }
        }

        return $response;
    }

    /**
     * Uses the DocumentPreviewService to generate a pdf for a documentfile.
     *
     * @param $filePath
     * @param $synchronRequest bool should the request be prioritized
     * @param array $intermediateFormats
     * @return string file blob
     * @throws Tinebase_FileSystem_Preview_BadRequestException
     * @throws Zend_Http_Client_Exception
     */
    public function getPdfForFile($filePath, $synchronRequest = false, $intermediateFormats = [])
    {
        $intermediateFormats []= 'pdf';
        return $this->_getSingleFile($filePath, ['fileType' => $intermediateFormats,], $synchronRequest);
    }

    /**
     * Merges multiple pdf files into a single one.
     *
     * @param array $filePaths of file paths
     * @param bool $synchronousRequest
     * @return string path to file
     * @throws Zend_Http_Client_Exception
     * @throws Tinebase_Exception_UnexpectedValue preview service did not succeed
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function mergePdfFiles(array $filePaths, bool $synchronousRequest = false)
    {
        foreach ($filePaths as $filePath) {
            if (mime_content_type($filePath) != 'application/pdf') {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $filePath . 'is not a PDF file');
                }
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $filePath . ' has mimi type '. mime_content_type($filePath));
                }
                throw new Tinebase_Exception_InvalidArgument($filePath . " is not a PDF file");
            }
        }

        if (false === ($result = $this->getPreviewsForFiles($filePaths, ['synchronRequest' => $synchronousRequest, ['fileType' => 'pdf', 'merge' => true,]]))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . ' ' . __LINE__ . ' preview service did not succeed');
            }
            throw new Tinebase_Exception_UnexpectedValue('preview service did not succeed: service occupied');
        }
        return $result[0][0];
    }
}
