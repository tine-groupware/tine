<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_FileSystem_Preview_AuthNetworkAdapter implements Tinebase_FileSystem_Preview_NetworkAdapter
{
    protected $_url;
    protected $_licensePath;
    protected $_caPath;


    /**
     * Tinebase_FileSystem_Preview_NetworkAdapter constructor.
     * @param $url
     * @param $licensePath
     * @param $caPath
     */
    public function __construct($url, $licensePath, $caPath)
    {
        $this->_url = $url;

        $this->_licensePath = tempnam(Tinebase_core::getTempDir(), 'tine_tempfile_');
        $this->_caPath = $caPath;

        copy($licensePath, $this->_licensePath);
    }

    /**
     * @throws Tinebase_Exception_NotFound if license file was not copied
     */
    protected function _checkLicenseTempFile()
    {
        for ($i = 0; $i < 5; $i++) {
            if (file_exists($this->_licensePath)) {
                return;
            }
            sleep(1);
        }
        throw new Tinebase_Exception_NotFound("License temp file not found.");
    }

    /**
     * cleanup (license temp file)
     */
    public function __destruct()
    {
        if (file_exists($this->_licensePath)) {
            unlink($this->_licensePath);
        }
    }

    /**
     * @param null $config
     * @return Zend_Http_Client
     * @throws Tinebase_Exception_NotFound license file not found
     */
    public function getHttpsClient($config = null)
    {
        $this->_checkLicenseTempFile();

        $proxyConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::INTERNET_PROXY);
        $curlOptions = array(
            CURLOPT_SSLCERT=>
                $this->_licensePath,
            CURLOPT_PROXY=>
                isset($proxyConfig['proxy_host']) ? $proxyConfig['proxy_host'] : '',
            CURLOPT_PROXYUSERPWD=>
                isset($proxyConfig['proxy_user']) && isset($proxyConfig['proxy_pass']) ? $proxyConfig['proxy_user'].':'.$proxyConfig['proxy_pass'] : '',
            CURLOPT_PROXYPORT=>
                isset($proxyConfig['proxy_port']) ? $proxyConfig['proxy_port'] : ''
        );

        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERIFY_SSL}) {
            $curlOptions[CURLOPT_CAPATH] = $this->_caPath;
        }
        $config = array_merge($config, array('adapter' => 'Zend_Http_Client_Adapter_Curl', 'sslcert' => $this->_licensePath, 'curloptions' => $curlOptions));

        return new Zend_Http_Client($this->_url, $config);
    }
}
