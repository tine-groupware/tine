<?php declare(strict_types=1);
/**
 * facade for IdToken
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Idaas\OpenID\Entities\IdToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Signer\Key\InMemory;
use League\OAuth2\Server\CryptKey;

class SSO_Facade_OpenIdConnect_IdToken extends IdToken
{
    public function convertToJWT(CryptKey $privateKey)
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Lcobucci\JWT\Signer\Rsa\Sha256(),
            InMemory::plainText($privateKey->getKeyContents()),
            InMemory::plainText($privateKey->getKeyContents())
        );

        $token = $configuration->builder(ChainedFormatter::withUnixTimestampDates())
            ->withHeader('kid', method_exists($privateKey, 'getKid') ? $privateKey->getKid() : null)
            ->issuedBy($this->getIssuer() ?? "none")
            ->withHeader('sub', $this->getSubject())
            ->relatedTo($this->getSubject())
            ->permittedFor($this->getAudience())
            ->expiresAt($this->getExpiration())
            ->issuedAt($this->getIat())
            ->identifiedBy($this->identifier)
            ->withClaim('auth_time', $this->getAuthTime()->getTimestamp())
            ->withClaim('nonce', $this->getNonce())
            ->withClaim('azp', $this->getAudience());

        foreach ($this->extra as $key => $value) {
            $token = $token->withClaim($key, $value);
        }

        return $token->getToken($configuration->signer(), $configuration->signingKey());
    }
}