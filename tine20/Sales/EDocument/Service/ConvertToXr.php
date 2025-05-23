<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_EDocument_Service_ConvertToXr
{
    public function convertUbl(string $ubl): string
    {
        $client = new Zend_Http_Client(rtrim(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::EDOCUMENT_SVC_BASE_URL}, '/') . '/ublXr');
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
        }

        $client->setRawData($ubl);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument ublXr service failed with: ' . $response->getStatus());
        }

        return $response->getBody();
    }

    public function convertCii(string $ubl): string
    {
        $client = new Zend_Http_Client(rtrim(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::EDOCUMENT_SVC_BASE_URL}, '/') . '/ciiXr');
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
        }

        $client->setRawData($ubl);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument ciiXr service failed with: ' . $response->getStatus());
        }

        return $response->getBody();
    }

    public static $zendHttpClientAdapter = null;
}