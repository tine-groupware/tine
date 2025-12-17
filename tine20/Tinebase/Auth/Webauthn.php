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


use Base64Url\Base64Url;
use Cose\Algorithms;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\WebauthnException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

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
        $publicKeyCredential = self::getSerializer()->deserialize(
            $data,
            PublicKeyCredential::class,
            'json'
        );

        if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new WebauthnException('AuthenticatorAttestationResponse is not an authenticator response.');
        }

        $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create(self::_getCeremonyFactory()->creationCeremony())
            ->check(
                $publicKeyCredential->response,
                self::getWebAuthnCreationOptions(false),
                self::_getRPName()
            );

        (new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())->saveCredentialSource($publicKeyCredentialSource);

        return Base64Url::encode($publicKeyCredentialSource->publicKeyCredentialId);
    }

    public static function webAuthnAuthenticate(Tinebase_Model_MFA_WebAuthnConfig $config, string $data): Tinebase_Model_FullUser
    {
        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = self::getSerializer()->deserialize($data, PublicKeyCredential::class, 'json');
        if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            throw new WebauthnException('AuthenticatorAssertionResponse is not an assertion response.');
        }

        if (null === ($publicKeyCredentialSource = ($credentialRepo = new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())
                ->findOneByCredentialId($publicKeyCredential->rawId))) {
            throw new WebauthnException('PublicKeyCredentialSource does not exist.');
        }

        $assertionValidator = AuthenticatorAssertionResponseValidator::create(self::_getCeremonyFactory()->requestCeremony());

        $publicKeyCredentialSource = $assertionValidator->check(
            publicKeyCredentialSource: $publicKeyCredentialSource,
            authenticatorAssertionResponse: $publicKeyCredential->response,
            publicKeyCredentialRequestOptions: self::getWebAuthnRequestOptions($config, false),
            host: self::_getRPName(),
            userHandle: $publicKeyCredentialSource->userHandle
        );

        $credentialRepo->saveCredentialSource($publicKeyCredentialSource);
        return Tinebase_User::getInstance()->getFullUserById($publicKeyCredentialSource->userHandle);
    }

    public static function getWebAuthnCreationOptions(bool $createChallenge, ?Tinebase_Model_FullUser $user = null, ?Tinebase_Model_MFA_WebAuthnConfig $config = null): PublicKeyCredentialCreationOptions
    {
        if ($createChallenge) {
            if (null === $user) {
                $user = Tinebase_Core::getUser();
            }
            $criteria = null;
            if ($config) {
                $criteria = new AuthenticatorSelectionCriteria(
                    authenticatorAttachment: $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_AUTHENTICATOR_ATTACHMENT} ?: null,
                    userVerification: $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_USER_VERIFICATION_REQUIREMENT} ?: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                    residentKey: $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_RESIDENT_KEY_REQUIREMENT} ?: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_NO_PREFERENCE,
                );
            }

            $credentialCreationOptions = PublicKeyCredentialCreationOptions::create(
                self::_getRPEntity(),
                new PublicKeyCredentialUserEntity(
                    $user->accountLoginName, $user->getId(), $user->accountDisplayName
                ),
                challenge: random_bytes(16),
                pubKeyCredParams: [
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256K),
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_ES256),
                    PublicKeyCredentialParameters::create('public-key', Algorithms::COSE_ALGORITHM_RS256)
                ],
                authenticatorSelection: $criteria
            );

            Tinebase_Session::getSessionNamespace(self::class)->regchallenge = self::serializePublicKeyCredentialCreationOptions($credentialCreationOptions);
        } else {
            if (!($challenge = Tinebase_Session::getSessionNamespace(self::class)->regchallenge)) {
                throw new Tinebase_Exception_Backend('no registration challenge found');
            }
            Tinebase_Session::getSessionNamespace(self::class)->regchallenge = null;
            $credentialCreationOptions = self::getSerializer()->deserialize(
                $challenge,
                PublicKeyCredentialCreationOptions::class,
                'json'
            );
        }

        return $credentialCreationOptions;
    }

    public static function getWebAuthnRequestOptions(Tinebase_Model_MFA_WebAuthnConfig $config, bool $generateChallenge, ?Tinebase_Model_FullUser $account = null, ?string $mfaId = null): PublicKeyCredentialRequestOptions
    {
        if (false === $generateChallenge) {
            if (!($challenge = Tinebase_Session::getSessionNamespace(self::class)->authchallenge)) {
                throw new Tinebase_Exception_Backend('no authentication challenge found');
            }
            Tinebase_Session::getSessionNamespace(self::class)->authchallenge = null;
            $credentialRequestOptions = self::getSerializer()->deserialize(
                $challenge,
                PublicKeyCredentialRequestOptions::class,
                'json'
            );
        } else {
            $credDescriptors = [];
            $clientInputs = [];

            if (null !== $account) {
                $clientInputs[] = new AuthenticationExtension('userHandle', $account->getId());
                if (null !== $mfaId) {
                    if (($usrCfg = ($account->mfa_configs->getById($mfaId) ?: null)?->config) && $publicDesc = (new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())
                            ->findOneByCredentialId(Base64Url::decode($usrCfg->{Tinebase_Model_MFA_WebAuthnUserConfig::FLD_WEBAUTHN_ID}))?->getPublicKeyCredentialDescriptor()) {
                        $credDescriptors[] = $publicDesc;
                    }
                } else {
                    foreach ((new Tinebase_Auth_WebAuthnPublicKeyCredentialSourceRepository())->findAllForUserEntity(
                            new PublicKeyCredentialUserEntity(
                                $account->accountLoginName, $account->getId(), $account->accountDisplayName
                            )) as $val) {
                        $credDescriptors[] = $val->getPublicKeyCredentialDescriptor();
                    }
                }
            }

            $credentialRequestOptions = PublicKeyCredentialRequestOptions::create(
                challenge: random_bytes(32),
                rpId: self::_getRPName(),
                allowCredentials: $credDescriptors,
                userVerification: $config->{Tinebase_Model_MFA_WebAuthnConfig::FLD_USER_VERIFICATION_REQUIREMENT},
                extensions: AuthenticationExtensions::create($clientInputs)
            );

            Tinebase_Session::getSessionNamespace(self::class)->authchallenge =  self::serializePublicKeyCredentialRequestOptions($credentialRequestOptions);
        }

        return $credentialRequestOptions;
    }

    public static function serializePublicKeyCredentialRequestOptions(PublicKeyCredentialRequestOptions $authOptions): string
    {
        return self::serializeWebAuthnObject($authOptions);
    }

    public static function serializePublicKeyCredentialCreationOptions(PublicKeyCredentialCreationOptions $creationOptions): string
    {
        return self::serializeWebAuthnObject($creationOptions);
    }

    public static function serializePublicKeyCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): string
    {
        return self::serializeWebAuthnObject($publicKeyCredentialSource);
    }

    public static function deserializePublicKeyCredentialSource(string $publicKeyCredentialSource): PublicKeyCredentialSource
    {
        $publicKeyCredentialSource = self::getSerializer()->deserialize(
            $publicKeyCredentialSource,
            PublicKeyCredentialSource::class,
            'json'
        );
        /** @var PublicKeyCredentialSource $publicKeyCredentialSource */
        return $publicKeyCredentialSource;
    }

    protected static function serializeWebAuthnObject(Object $object): string
    {
        return static::getSerializer()->serialize(
            $object,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ]
        );
    }

    public static function getSerializer(): SerializerInterface
    {
        static $serializer = null;
        if (null === $serializer) {
            $attestationStatementSupportManager = AttestationStatementSupportManager::create();
            $attestationStatementSupportManager->add(NoneAttestationStatementSupport::create());
            $factory = new WebauthnSerializerFactory($attestationStatementSupportManager);
            $serializer = $factory->create();
        }
        return $serializer;
    }

    protected static function _getRPEntity(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create(
            name: self::_getRPName()/*,
            id: self::_getRPId()*/
        );
    }

    protected static function _getRPName(): string
    {
        static $name = null;
        if (null === $name) {
            $name = ltrim(Tinebase_Core::getUrl(Tinebase_Core::GET_URL_NO_PROTO), '/');
        }
        return $name;
    }

    protected static function _getCeremonyFactory(): CeremonyStepManagerFactory
    {
        $ceremonyFactory = new CeremonyStepManagerFactory();
        return $ceremonyFactory;
    }
}
