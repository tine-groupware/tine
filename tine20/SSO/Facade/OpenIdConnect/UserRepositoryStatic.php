<?php declare(strict_types=1);
/**
 * facade for ClaimRepository
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OpenIdConnect_UserRepositoryStatic extends SSO_Facade_OpenIdConnect_UserRepository
{
    public function __construct(
        protected SSO_Facade_OAuth2_UserEntity $userEntity
    ) {}

    public function getUserByIdentifier($identifier): ?\League\OAuth2\Server\Entities\UserEntityInterface
    {
        return $this->userEntity;
    }
}
