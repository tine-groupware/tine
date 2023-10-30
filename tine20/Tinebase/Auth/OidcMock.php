<?php declare(strict_types=1);

class Tinebase_Auth_OidcMock implements Tinebase_Auth_Interface
{
    protected $_identity;
    public function authenticate()
    {
        return new Zend_Auth_Result(Zend_Auth_Result::SUCCESS, $this->_identity);
    }

    public function setIdentity($value)
    {
        $this->_identity = $value;
        return $this;
    }

    public function setCredential($credential)
    {
        return $this;
    }

    public function supportsAuthByEmail()
    {
        return false;
    }

    public function getAuthByEmailBackend()
    {
        return null;
    }
}
