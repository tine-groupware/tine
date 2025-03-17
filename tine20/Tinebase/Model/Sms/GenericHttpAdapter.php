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
class Tinebase_Model_Sms_GenericHttpAdapter extends Tinebase_Record_NewAbstract implements Tinebase_Sms_AdapterInterface
{
    public const MODEL_NAME_PART = 'Sms_GenericHttpAdapter';

    public const FLD_BODY = 'body';
    public const FLD_HEADERS = 'headers';
    public const FLD_METHOD = 'method';
    public const FLD_URL = 'url';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_BODY                      => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_HEADERS                   => [
                self::TYPE                          => self::TYPE_JSON,
            ],
            self::FLD_METHOD                    => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            self::FLD_URL                       => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected array $_httpClientConfig = [];

    public function setHttpClientConfig(array $_config): void
    {
        $this->_httpClientConfig = $_config;
    }

    public function send(Tinebase_Model_Sms_SendConfig $config): bool
    {
        $client = Tinebase_Core::getHttpClient($this->{self::FLD_URL}, $this->_httpClientConfig);
        $client->setMethod($this->{self::FLD_METHOD});
        foreach ($this->{self::FLD_HEADERS} as $header => $value) {
            $client->setHeaders($header, $value);
        }

        $twig = new \Twig\Environment(new \Twig\Loader\ArrayLoader());
        $client->setRawData($twig->createTemplate($this->{self::FLD_BODY})
            ->render(array_merge($this->getTwigContext(), [
                'message' => $config->{Tinebase_Model_Sms_SendConfig::FLD_MESSAGE},
                'cellphonenumber' => $config->{Tinebase_Model_Sms_SendConfig::FLD_RECIPIENT_NUMBER},
            ])));

        try {
            $response = $client->request();
        } catch (Zend_Http_Client_Adapter_Exception $e) {
            Tinebase_Exception::log($e);
            return false;
        }

        if (200 === $response->getStatus()) {
            return true;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' failed with status ' .
                $response->getStatus() . ' body: '. $response->getBody());

        return false;
    }

    public function getTwigContext(): array
    {
        $tbConfig = Tinebase_Config::getInstance();
        return [
            'app' => [
                'websiteUrl'        => Tinebase_Config::getInstance()->get(Tinebase_Config::TINE20_URL),
                'branding'          => [
                    'logo'              => Tinebase_Core::getInstallLogo(),
                    'title'             => $tbConfig->{Tinebase_Config::BRANDING_TITLE},
                    'description'       => $tbConfig->{Tinebase_Config::BRANDING_DESCRIPTION},
                    'weburl'            => $tbConfig->{Tinebase_Config::BRANDING_WEBURL},
                ],
            ],
        ];
    }
}