<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Generic SMS SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_MockSmsAdapter extends Tinebase_Auth_MFA_GenericSmsAdapter
{
    public function __construct(Tinebase_Record_Interface $_config, string $id)
    {
        $smsConfig = Tinebase_Config::getInstance()->{Tinebase_Config::SMS}->{Tinebase_Config::SMS_ADAPTERS}
            ->{Tinebase_Model_Sms_AdapterConfigs::FLD_ADAPTER_CONFIGS}[0];
        if (!empty($smsConfig['adapter_config'])) {
            $smsAdapterConfig = $smsConfig['adapter_config'];
            $fields = $_config->getConfiguration()->getFields();
            foreach ($fields as $key => $value) {
                if (!empty($smsAdapterConfig[$key]) && empty($_config[$key])) {
                    $_config[$key] = $smsAdapterConfig[$key];
                }
            }
        }

        parent::__construct($_config, $id);
        $this->setHttpClientConfig([
            'adapter' => ($httpClientTestAdapter = new Tinebase_ZendHttpClientAdapter())
        ]);
        $httpClientTestAdapter->writeBodyCallBack = function($body) {
            Tinebase_Core::getLogger()->ERR(__METHOD__ . '::' . __LINE__ . ' sms request body: ' . $body);

            try {
                $mail = new Tinebase_Mail('UTF-8');
                $mail->setSubject('Tinebase Auth MFA MockSmsAdapter with test sms pin code');
                $mail->setBodyText($body);
                $mail->setBodyHtml($body);
                $mail->addHeader('X-Tine20-Type', 'Notification');
                $mail->addHeader('User-Agent', Tinebase_Core::getTineUserAgent('Notification Service'));
                $mail->setFrom('tine20admin@mail.test');
                $mail->addTo('tine20admin@mail.test');
                Tinebase_Smtp::getInstance()->sendMessage($mail);
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                Tinebase_Core::getLogger()->warn(
                    __METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage()
                );
            }
        };
        $httpClientTestAdapter->setResponse(new Zend_Http_Response(200, []));
    }
}
