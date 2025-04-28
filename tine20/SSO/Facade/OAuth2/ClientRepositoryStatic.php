<?php declare(strict_types=1);
/**
 * facade for ClientRepository with static result
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class SSO_Facade_OAuth2_ClientRepositoryStatic implements ClientRepositoryInterface
{

    public function __construct(
        protected SSO_Facade_OAuth2_ClientEntity $clientEntity
    ) {}

    public function getClientEntity($clientIdentifier): ?SSO_Facade_OAuth2_ClientEntity
    {
        return $this->clientEntity;
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        return true;
    }
}
