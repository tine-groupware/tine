<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_Preview_ServiceV2 implements Tinebase_FileSystem_Preview_ServiceInterface
{
    protected $_networkAdapter;

    public function __construct($networkAdapter)
    {
        $this->_networkAdapter = $networkAdapter;
    }
    /**
     * @param $_filePath
     * @param array $_config
     * @return array|bool
     */
    public function getPreviewsForFile($_filePath, array $_config)
    {
        if (isset($_config['synchronRequest']) && $_config['synchronRequest']) {
            $synchronRequest = true;
        } else {
            $synchronRequest = false;
        }
        $httpClient = $this->_networkAdapter->getHttpsClient(array('timeout' => ($synchronRequest ? 10 : 300)));
        $httpClient->setMethod(Zend_Http_Client::POST);
        $httpClient->setParameterPost('config', json_encode($_config));
        $httpClient->setFileUpload($_filePath, 'file');

        $tries = 0;
        $timeStarted = time();
        $responseJson = null;
        do {
            $lastRun = time();
            try {
                $response = $httpClient->request();
            } catch (Zend_Http_Client_Exception $zhce) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                    . __LINE__ . ' request failed: ' . $zhce->getMessage());
                if ($synchronRequest) {
                    return false;
                }
            }
            if ((int)$response->getStatus() === 200) {
                $responseJson = json_decode($response->getBody(), true);
                break;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                    . __LINE__ . ' STATUS CODE: ' . $response->getStatus() . ' MESSAGE: ' . $response->getMessage());
                if ($synchronRequest) {
                    return false;
                }
            }
            $run = time() - $lastRun;
            if ($run < 5) {
                sleep(5 - $run);
            }
        } while (++$tries < 4 && time() - $timeStarted < 180);

        if (is_array($responseJson)) {
            $response = array();
            foreach($responseJson as $key => $files) {
                $response[$key] = array();
                foreach($files as $file) {
                    $blob = base64_decode($file);
                    if (false === $blob) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__
                            . '::' . __LINE__ . ' couldn\'t read converted fileblob from: ' . $_filePath);
                        return false;
                    }
                    $response[$key][] = $blob;
                }
            }
            return $response;
        }

        return false;
    }
}
