<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_FileSystem_Preview_NetworkAdapter
{
    protected $_url;
    protected $_licensePath;
    protected $_caPath;

    public function __construct($url, $licensePath, $caPath)
    {
        $this->_url = $url;
        $this->_licensePath = tempnam(Tinebase_core::getTempDir(), 'tine_tempfile_');
        $this->_caPath = $caPath;

        copy($licensePath, $this->_licensePath);
    }

    public function __destruct()
    {
        unlink($this->_licensePath);
    }

    public function getHttpsClient($config = null)
    {
        $proxyConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::INTERNET_PROXY);
        $curlOptions = array(
            CURLOPT_SSLCERT=>$this->_licensePath,
            CURLOPT_PROXY=>isset($proxyConfig['proxy_host']) ? $proxyConfig['proxy_host'] : '',
            CURLOPT_PROXYUSERPWD=>isset($proxyConfig['proxy_user']) && isset($proxyConfig['proxy_pass']) ? $proxyConfig['proxy_user'].':'.$proxyConfig['proxy_pass'] : '',
            CURLOPT_PROXYPORT=>isset($proxyConfig['proxy_port'])?$proxyConfig['proxy_port']:''
        );
        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_VERIFY_SSL}) {
            $curlOptions[CURLOPT_CAPATH] = $this->_caPath;
        }
        $config = array_merge($config, array('adapter' => 'Zend_Http_Client_Adapter_Curl', 'curloptions' => $curlOptions));

        return new Zend_Http_Client($this->_url, $config);
    }
}