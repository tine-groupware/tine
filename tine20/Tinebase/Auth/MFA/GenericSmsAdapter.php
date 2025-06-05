<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Generic SMS SecondFactor Auth Adapter
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_MFA_GenericSmsAdapter implements Tinebase_Auth_MFA_AdapterInterface
{
    /** @var Tinebase_Model_MFA_GenericSmsConfig */
    protected $_config;
    protected $_mfaId;
    protected $_httpClientConfig = [];

    public function __construct(Tinebase_Record_Interface $_config, string $id)
    {
        $this->_config = $_config;
        $this->_mfaId = $id;
    }

    public function setHttpClientConfig(array $_config)
    {
        $this->_httpClientConfig = $_config;
    }

    public function getClientPasswordLength(): ?int
    {
        return (int)$this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH};
    }

    public function sendOut(Tinebase_Model_MFA_UserConfig $_userCfg, Tinebase_Model_FullUser $user): bool
    {
        $pinLength = (int)$this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH};
        if ($pinLength < 3 || $pinLength > 10) throw new Tinebase_Exception('pin length needs to be between 3 and 10');
        $pin = sprintf('%0' . $pinLength .'d', random_int(1, 10 ** $pinLength - 1));

        $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->isValid();

        if (!$this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_SYSTEM_SMS_NAME} ||
                !($genericHttpAdapter = Tinebase_Config::getInstance()->{Tinebase_Config::SMS}?->{Tinebase_Config::SMS_ADAPTERS}
                    ?->{Tinebase_Model_Sms_AdapterConfigs::FLD_ADAPTER_CONFIGS}
                    ?->find(Tinebase_Model_Sms_AdapterConfig::FLD_NAME, $this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_SYSTEM_SMS_NAME})
                    ?->{Tinebase_Model_Sms_AdapterConfig::FLD_ADAPTER_CONFIG}) || !$genericHttpAdapter instanceof Tinebase_Model_Sms_GenericHttpAdapter) {
            $genericHttpAdapter = new Tinebase_Model_Sms_GenericHttpAdapter([
                Tinebase_Model_Sms_GenericHttpAdapter::FLD_URL => $this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_URL},
                Tinebase_Model_Sms_GenericHttpAdapter::FLD_METHOD => $this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_METHOD},
                Tinebase_Model_Sms_GenericHttpAdapter::FLD_BODY => $this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_BODY},
                Tinebase_Model_Sms_GenericHttpAdapter::FLD_HEADERS => $this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_HEADERS},
            ]);
        }

        $message = Tinebase_Translation::getTranslation()->_('{{ code }} is your {{ app.branding.title }} security code.');
        $message .= '\n\n@{{ app.websiteUrl }} {{ code }}';

        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader());
        $twig->addFilter(new \Twig\TwigFilter('alnum', fn($data) => preg_replace('/[^0-9a-zA-Z]+/', '', $data)));
        $twig->addFilter(new \Twig\TwigFilter('gsm7', function(string $data) {
            static $converter = null;
            if (null === $converter) $converter = new BenMorel\GsmCharsetConverter\Converter();
            return $converter->cleanUpUtf8String($data, true);
        }));
        $twig->addFilter(new \Twig\TwigFilter('ucs2', fn(string $data) => iconv('ucs-2', 'utf-8', iconv('utf-8', 'ucs-2//TRANSLIT', $data))));

        $message = $twig->createTemplate($message)->render(array_merge($genericHttpAdapter->getTwigContext(), [
            'code' => $pin
        ]));

        $smsSendConfig = new Tinebase_Model_Sms_SendConfig([
            Tinebase_Model_Sms_SendConfig::FLD_MESSAGE => $message,
            Tinebase_Model_Sms_SendConfig::FLD_RECIPIENT_NUMBER => $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}
                ->{Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER},
            Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CLASS => Tinebase_Model_Sms_GenericHttpAdapter::class,
            Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CONFIG => $genericHttpAdapter,
        ]);
        $genericHttpAdapter->setHttpClientConfig($this->_httpClientConfig);

        if (Tinebase_Sms::send($smsSendConfig)) {
            try {
                Tinebase_Session::getSessionNamespace()->{static::class} = [
                    'pin' => $pin,
                    'ttl' => time() + (int)$this->_config->{Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_TTL}
                ];
            } catch (Zend_Session_Exception $zse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zse->getMessage()) ;
                return false;
            }

            $userRaii = null;
            if (!Tinebase_Core::getUser()) {
                $userRaii = new Tinebase_RAII(Admin_Controller_JWTAccessRoutes::getInstance()->assertPublicUsage());
            }

            $_userCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->{Tinebase_Model_MFA_SmsUserConfig::FLD_AUTH_TOKEN} =
                Admin_Controller_JWTAccessRoutes::getInstance()->getNewJWT([
                    Admin_Model_JWTAccessRoutes::FLD_ACCOUNTID => $user->getId(),
                    Admin_Model_JWTAccessRoutes::FLD_ROUTES => [
                        Tinebase_Controller::class . '::postSendSupportRequest',
                    ],
                    Admin_Model_JWTAccessRoutes::FLD_TTL => Tinebase_DateTime::now()->addMinute(30),
                ], keyBits: 1024); // 1024 bits are significantly faster than 2048 and we are only valid for 30 minutes

            unset($userRaii);
            return true;
        }
        return false;
    }

    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        if (!is_array($sessionData = Tinebase_Session::getSessionNamespace()->{static::class}) ||
                $sessionData['ttl'] < time() || $sessionData['pin'] !== $_data) {
            return false;
        }
        Tinebase_Session::getSessionNamespace()->{static::class} = null;
        return true;
    }
}
