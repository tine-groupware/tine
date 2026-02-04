<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_EDocument_Service_View
{
    public function viewXr(string $content): string
    {
        $client = new Zend_Http_Client(rtrim(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::EDOCUMENT_SVC_BASE_URL}, '/') . '/xrView');
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
        }

        $client->setRawData($content);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument xrView service failed with: ' . $response->getStatus());
        }

        return $response->getBody();
    }

    public function getXRechnungView(Tinebase_Model_Tree_Node $node): string
    {
        $client = new Zend_Http_Client(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_SVC});
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
        }

        $client->setParameterGet('format', 'xrechnung');
        $client->setRawData(file_get_contents(Tinebase_FileSystem::getInstance()->getRealPathForHash($node->hash)));
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument view service failed with: ' . $response->getStatus());
        }

        return $response->getBody();
    }

    public static $zendHttpClientAdapter = null;
}