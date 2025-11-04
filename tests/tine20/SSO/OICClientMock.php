<?php declare(strict_types=1);

class SSO_OICClientMock extends SSO_Facade_OpenIdConnect_Client
{
    public function __construct(
        protected bool $authenticated = true,
        protected $verifiedClaims = [],
        protected ?stdClass $userInfo = null,
    )
    {
        parent::__construct();
        if (null === $this->userInfo) {
            $this->userInfo = new stdClass;
        }
    }

    public function authenticate(): bool
    {
        return $this->authenticated;
    }

    public function getVerifiedClaims(?string $attribute = null)
    {
        return $attribute ? ($this->verifiedClaims[$attribute] ?? null) : $this->verifiedClaims;
    }

    public function requestUserInfo(?string $attribute = null)
    {
        return $attribute ? ($this->userInfo->$attribute ?? null) : $this->userInfo;
    }
}
