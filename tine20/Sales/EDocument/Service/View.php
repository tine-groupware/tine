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

    public function getXRechnungView(string $xrechnung): string
    {
        if ($zugPferd = Sales_EDocument_ZUGFeRD::isStringZugFeRD($xrechnung)) {
            $xrechnung = $zugPferd->getXml();
        }

        $isCii = $this->isCiiXml($xrechnung);

        if ($isCii) {
            $viewSvc = Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_CII_SVC};
        } else {
            $viewSvc = Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_SVC};
        }

        $client = new Zend_Http_Client($viewSvc);
        if (null !== static::$zendHttpClientAdapter) {
            $client->setAdapter(static::$zendHttpClientAdapter);
        }

        $client->setRawData($xrechnung);
        $response = $client->request(Zend_Http_Client::POST);

        if ($response->getStatus() !== 200) {
            throw new Tinebase_Exception_Backend('edocument view service failed with: ' . $response->getStatus());
        }

        return $response->getBody();
    }

    /**
     * Check if the given XML content is a CII (CrossIndustryInvoice) document.
     */
    protected function isCiiXml(string $content): bool
    {
        return str_contains($content, '<rsm:CrossIndustryInvoice')
            || str_contains($content, '<CrossIndustryInvoice');
    }

    public static $zendHttpClientAdapter = null;
}