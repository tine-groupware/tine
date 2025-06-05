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
     * @return array
     */
    public function validateXRechnung($data): array
    {
        if (!is_resource($data)) {
            throw new TypeError(__METHOD__ . ' expects data to be a resource');
        }

        return $this->callSvc($data);
    }

    protected function callSvc($data): array
    {
        $client = new Zend_Http_Client(Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC});
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
            if(!static::$zendHttpClientAdapter instanceof Zend_Http_Client_Adapter_Stream) {
                $data = stream_get_contents($data);
            }
        }

        $client->setRawData($data);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument validation service failed with: ' . $response->getStatus());
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' validation service response: ' . PHP_EOL
            . $response->getBody());

        if (false === ($xml = simplexml_load_string($response->getBody(), namespace_or_prefix: 'rep', is_prefix: true))) {
            throw new Tinebase_Exception_Backend('edocument validation service didn\'t return xml');
        }

        if (! $xml->children('rep', true)?->scenarioMatched?->validationStepResult ||
                (! $xml->children('rep', true)?->assessment?->accept?->explanation?->children()?->html &&
                    ! $xml->children('rep', true)?->assessment?->reject?->explanation?->children()?->html)) {
            throw new Tinebase_Exception_Backend('edocument validation service didn\'t return expected xml');
        }

        $errors = [];
        if (null === ($xml->children('rep', true)->assessment->accept->explanation ?? null)) {
            foreach ($xml->children('rep', true)->scenarioMatched->validationStepResult as $node) {
                foreach ($node->attributes() as $attribute) {
                    if ($attribute->getName() === 'valid' && (string)$attribute === 'true') {
                        continue 2;
                    }
                }
                foreach ($node->children('rep', true) as $errNode) {
                    $err = (string)$errNode;
                    $errors[] = $err;
                }
            }
        }

        return [
            'html' => $xml->children('rep', true)->assessment->accept->explanation?->children()->html->asXML() ??
                $xml->children('rep', true)->assessment->reject->explanation->children()->html->asXML(),
            'errors' => $errors,
        ];
    }

    public static $zendHttpClientAdapter = null;
}