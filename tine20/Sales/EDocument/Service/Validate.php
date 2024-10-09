<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_EDocument_Service_Validate
{
    /**
     * @param resource $data
     * @return void
     */
    public function validateXRechnung($data): void
    {
        if (!is_resource($data)) {
            throw new TypeError(__METHOD__ . ' expects data to be a resource');
        }

        $client = new Zend_Http_Client(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC});
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
            if(!static::$zendHttpClientAdapter instanceof Zend_Http_Client_Adapter_Stream) {
                $data = stream_get_contents($data);
            }
        }

        $client->setParameterGet('format', 'xrechnung');
        $client->setRawData($data);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument validation service failed with: ' . $response->getStatus());
        }

        if (false === ($xml = simplexml_load_string($response->getBody(), namespace_or_prefix: 'svrl', is_prefix: true)) || empty($xml->children('svrl', true))) {
            throw new Tinebase_Exception_Backend('edocument validation service didn\'t return xml');
        }

        $errors = [];
        $xml->rewind();
        foreach ($xml->children('svrl', true)->{'failed-assert'} as $node) {
            foreach ($node->children('svrl', true)->text as $errNode) {
                $err = (string)$errNode;
                if (strpos($err, 'soll eine korrekte IBAN enthalten') !== false) {
                    continue;
                }
                $errors[] = $err;
            }
        }

        if (!empty($errors)) {
            throw new Tinebase_Exception_Record_Validation(join(PHP_EOL, $errors));
        }
    }

    public static $zendHttpClientAdapter = null;
}