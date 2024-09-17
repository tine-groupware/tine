<?php declare(strict_types=1);
/**
 * facade for AuthCodeGrant
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Idaas\OpenID\Grant\AuthCodeGrant;

class SSO_Facade_OpenIdConnect_AuthCodeGrant extends AuthCodeGrant
{
    protected function makeIdTokenInstance()
    {
        return new SSO_Facade_OpenIdConnect_IdToken();
    }
}