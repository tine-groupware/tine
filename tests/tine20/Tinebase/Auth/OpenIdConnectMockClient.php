<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */
class Tinebase_Auth_OpenIdConnectMockClient extends SSO_Facade_OpenIdConnect_Client
{
    public function requestUserInfo($attribute = null)
    {
        return json_decode('{"email":"test@example.org","email_verified":true}');
    }

    public function setAccessToken($token)
    {
        // only mocking
    }
}
