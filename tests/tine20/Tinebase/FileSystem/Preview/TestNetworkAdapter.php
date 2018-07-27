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

class TestNetworkAdapter
{
    protected $_response;

    public function __construct($response)
    {
        $this->_response = $response;
    }

    public function getHttpsClient()
    {
        $adapter = new Zend_Http_Client_Adapter_Test();
        $httpClient = new Zend_Http_Client('https://127.0.0.1', array('adapter' => $adapter));
        $adapter->setResponse($this->_response);
        return $httpClient;
    }
}