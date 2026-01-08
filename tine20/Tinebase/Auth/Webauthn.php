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

use Base64Url\Base64Url;
use Webauthn\AuthenticationExtensions\AuthenticationExtension; 
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

/**
 * SecondFactor Auth Facade
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
final class Tinebase_Auth_Webauthn
{
    public static function webAuthnRegister(string $data): string
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $credentialSource = self::_getServer()->loadAndCheckAttestationResponse(
            $data,
            self::getWebAuthnCreationOptions(false),
            $request
        );

        (new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())->saveCredentialSource($credentialSource);

        return Base64Url::encode($credentialSource->getPublicKeyCredentialId());
    }

    public static function webAuthnAuthenticate(Tinebase_Model_MFA_WebAuthnConfig $config, string $data): Tinebase_Model_FullUser
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

        /** @var \Webauthn\PublicKeyCredentialSource $result */
        $result = self::_getServer()->loadAndCheckAssertionResponse(
            $data,
            self::getWebAuthnRequestOptions($config, false),
            null,
            $request
        );

        return Tinebase_User::getInstance()->getFullUserById($result->getUserHandle());
    }

    public static function getWebAuthnCreationOptions(bool $createChallenge, ?Tinebase_Model_FullUser $user = null, ?Tinebase_Model_MFA_WebAuthnConfig $config = null): \Webauthn\PublicKeyCredentialCreationOptions
    {
        if ($createChallenge) {
            if (null === $user) {
                $user = Tinebase_Core::getUser();
            }
            $credentialCreationOptions = self::_getServer()->generatePublicKeyCredentialCreationOptions(
                new \Webauthn\PublicKeyCredentialUserEntity(
                    $user->accountLoginName, $user->getId(), $user->accountDisplayName
                )
            );
            if ($config) {
                $criteria = new \Webauthn\AuthenticatorSelectionCriteria();
                if ($config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_AUTHENTICATOR_ATTACHMENT}) {
                    $criteria->setAuthenticatorAttachment(
                        $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_AUTHENTICATOR_ATTACHMENT}
                    );
                }
                if ($config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_RESIDENT_KEY_REQUIREMENT}) {
                    $criteria->setRequireResidentKey(
                        \Webauthn\AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED ===
                        $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_RESIDENT_KEY_REQUIREMENT}
                    );
                }
                if ($config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_USER_VERIFICATION_REQUIREMENT}) {
                    $criteria->setUserVerification(
                        $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_USER_VERIFICATION_REQUIREMENT}
                    );
                }
                $credentialCreationOptions->setAuthenticatorSelection($criteria);
            }
            Tinebase_Session::getSessionNamespace(self::class)->regchallenge = json_encode($credentialCreationOptions->jsonSerialize());
        } else {
            if (!($challenge = Tinebase_Session::getSessionNamespace(self::class)->regchallenge)) {
                throw new Tinebase_Exception_Backend('no registration challenge found');
            }
            Tinebase_Session::getSessionNamespace(self::class)->regchallenge = null;
            $credentialCreationOptions = \Webauthn\PublicKeyCredentialCreationOptions::createFromString($challenge);
        }

        return $credentialCreationOptions;
    }

    public static function getWebAuthnRequestOptions(Tinebase_Model_MFA_WebAuthnConfig $config, bool $generateChallenge, ?Tinebase_Model_FullUser $account = null, ?string $mfaId = null): \Webauthn\PublicKeyCredentialRequestOptions
    {
        if (false === $generateChallenge) {
            if (!($challenge = Tinebase_Session::getSessionNamespace(self::class)->authchallenge)) {
                throw new Tinebase_Exception_Backend('no authentication challenge found');
            }
            Tinebase_Session::getSessionNamespace(self::class)->authchallenge = null;
            $credentialRequestOptions = \Webauthn\PublicKeyCredentialRequestOptions::createFromString($challenge);
        } else {
            $credDescriptors = [];
            $clientInputs = new AuthenticationExtensionsClientInputs();

            if (null !== $account) {
                $clientInputs->add(new AuthenticationExtension('userHandle', $account->getId()));
                if (null !== $mfaId) {
                    if (($usrCfg = ($account->mfa_configs->getById($mfaId) ?: null)?->config) && $publicDesc = (new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())
                            ->findOneByCredentialId(Base64Url::decode($usrCfg->{Tinebase_Model_MFA_WebAuthnUserConfig::FLD_WEBAUTHN_ID}))?->getPublicKeyCredentialDescriptor()) {
                        $credDescriptors[] = $publicDesc;
                    }
                } else {
                    foreach ((new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())->findAllForUserEntity(
                        new \Webauthn\PublicKeyCredentialUserEntity(
                            $account->accountLoginName, $account->getId(), $account->accountDisplayName
                        )) as $val) {
                        $credDescriptors[] = $val->getPublicKeyCredentialDescriptor();
                    }
                }
            }

            $credentialRequestOptions = self::_getServer()->generatePublicKeyCredentialRequestOptions(
                $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_USER_VERIFICATION_REQUIREMENT},
                $credDescriptors,
                $clientInputs
            );

            Tinebase_Session::getSessionNamespace(self::class)->authchallenge = json_encode($credentialRequestOptions->jsonSerialize());
        }

        return $credentialRequestOptions;
    }

    protected static function _getServer(): \Webauthn\Server
    {
        static $server;
        if (null === $server) {
            $server = new \Webauthn\Server(
                new \Webauthn\PublicKeyCredentialRpEntity(
                    ltrim(Tinebase_Core::getUrl(Tinebase_Core::GET_URL_NO_PROTO), '/')),
                new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository()
            );
        }
        return $server;
    }
}
