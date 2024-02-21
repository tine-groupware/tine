<?php declare(strict_types=1);

/**
 * class to hold External Idp Domain data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold External Idp Domain data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_ExIdp_OIdConfig extends Tinebase_Record_NewAbstract implements SSO_ExIdpConfigInterface
{
    public const MODEL_NAME_PART = 'ExIdp_OIdConfig';

    public const FLD_CLIENT_ID = 'client_id';
    public const FLD_CLIENT_SECRET = 'client_secret';
    public const FLD_CLIENT_SECRET_CCID = 'client_secret_ccid';
    public const FLD_ISSUER = 'issuer';
    public const FLD_NAME = 'name';
    public const FLD_PROVIDER_URL = 'provider_url';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::TITLE_PROPERTY => self::FLD_NAME,
        self::RECORD_NAME => 'External Identity Provider Config',
        self::RECORDS_NAME => 'External Identity Provider Configs', // ngettext('External Identity Provider Config', 'External Identity Provider Configs', n)

        self::FIELDS => [
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Name', // _('Name')
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_PROVIDER_URL      => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Provider URL', // _('Provider URL')
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ISSUER            => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Issuer', // _('Issuer')
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_CLIENT_ID         => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Client ID', // _('Client ID')
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_CLIENT_SECRET     => [
                self::TYPE                  => self::TYPE_STRING,
                self::LABEL                 => 'Client Secret', // _('Client Secret')
            ],
            self::FLD_CLIENT_SECRET_CCID=> [
                self::TYPE                  => self::TYPE_STRING,
                // FE disable?
                self::DISABLED              => true,
            ],
        ],
    ];

    public function runConvertToData()
    {
        $this->saveClientSecret();
        parent::runConvertToData();
    }

    public function saveClientSecret(): void
    {
        if (strlen($this->{self::FLD_CLIENT_SECRET} ?? '') === 0) {
            return;
        }

        $cc = Tinebase_Auth_CredentialCache::getInstance();
        $adapter = explode('_', get_class($cc->getCacheAdapter()));
        $adapter = end($adapter);
        try {
            $cc->setCacheAdapter('Shared');
            $sharedCredentials = Tinebase_Auth_CredentialCache::getInstance()->cacheCredentials('',
                $this->{self::FLD_CLIENT_SECRET}, null, true /* save in DB */, Tinebase_DateTime::now()->addYear(100));

            if ($this->{self::FLD_CLIENT_SECRET_CCID}) {
                $cc->delete($this->{self::FLD_CLIENT_SECRET_CCID});
            }

            $this->{self::FLD_CLIENT_SECRET_CCID} = $sharedCredentials->getId();

        } finally {
            $cc->setCacheAdapter($adapter);
        }

        $this->{self::FLD_CLIENT_SECRET} = null;
    }

    public function getClientSecret(): string
    {
        if (!$this->{self::FLD_CLIENT_SECRET_CCID}) {
            throw new Tinebase_Exception('credential cache id not set');
        }

        /** @var Tinebase_Model_CredentialCache $cc */
        $cc = Tinebase_Auth_CredentialCache::getInstance()->get($this->{self::FLD_CLIENT_SECRET_CCID});
        $cc->key = Tinebase_Auth_CredentialCache_Adapter_Shared::getKey();
        Tinebase_Auth_CredentialCache::getInstance()->getCachedCredentials($cc);

        return $cc->password;
    }

    public function initAuthProcess(): bool
    {
        /** @var Tinebase_Auth_OpenIdConnect $oidc */
        $oidc = Tinebase_Auth_Factory::factory(Tinebase_Auth_OpenIdConnect::TYPE, [
            Tinebase_Auth_OpenIdConnect::IDP_CONFIG => $this
        ]);
        return $oidc->providerAuthRequest();
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
