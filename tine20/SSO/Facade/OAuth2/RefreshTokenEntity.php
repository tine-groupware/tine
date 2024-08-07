<?php declare(strict_types=1);

/**
 * facade for RefreshTokenEntity
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OAuth2_RefreshTokenEntity implements \League\OAuth2\Server\Entities\RefreshTokenEntityInterface
{
    use \League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;
    use \League\OAuth2\Server\Entities\Traits\EntityTrait;
}
