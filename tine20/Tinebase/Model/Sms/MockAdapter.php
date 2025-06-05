<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SMS Generic HTTP Adapter Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_Sms_MockAdapter extends Tinebase_Model_Sms_GenericHttpAdapter
{
    public const MODEL_NAME_PART = 'Sms_MockAdapter';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function send(Tinebase_Model_Sms_SendConfig $config): bool
    {
            // @TODO make me working
            $this->setHttpClientConfig([
                'adapter' => ($client = new Tinebase_ZendHttpClientAdapter())
            ]);

            $client->writeBodyCallBack = function($body) {
                $colorGreen = "\033[43m";
                $colorReset = "\033[0m";
                Tinebase_Core::getLogger()->warn($colorGreen . __METHOD__ . '::' . __LINE__ . ' sms request body: ' . $body . $colorReset . PHP_EOL);
            };
            $client->setResponse(new Zend_Http_Response(200, []));

            return true;
    }
}