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

        $errors = $this->callSvc('', $data);
        rewind($data);
        $errors = array_merge($errors, $this->callSvc('1', $data));


        if (!empty($errors)) {
            throw new Tinebase_Exception_Record_Validation(join(PHP_EOL, $errors));
        }
    }

    protected function callSvc(string $postfix, $data): array
    {
        $client = new Zend_Http_Client(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC} . $postfix,
            $postfix ? ['timeout' => 60] : null);
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

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' validation service response: ' . PHP_EOL
            . $response->getBody());

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

        return $errors;
    }

    public static $zendHttpClientAdapter = null;
}