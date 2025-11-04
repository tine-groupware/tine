<?php declare(strict_types=1);

class SSO_OICAuthAdapterMock extends Tinebase_Auth_OpenIdConnect
{
    public function __construct(protected SSO_OICClientMock $clientMock)
    {
    }

    public function _getClient(): SSO_Facade_OpenIdConnect_Client
    {
        return $this->clientMock;
    }
}
