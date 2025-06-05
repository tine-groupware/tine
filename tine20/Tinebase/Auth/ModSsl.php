<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2013 Serpro (http://www.serpro.gov.br)
 * @copyright   Copyright (c) 2013-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Mário César Kolling <mario.koling@serpro.gov.br>
 */

/**
 * DigitalCertificate authentication backend
 * 
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_ModSsl extends Zend_Auth_Adapter_ModSsl implements Tinebase_Auth_Interface
{
    /**
     * set loginname
     *
     * TODO function probably doesnt work
     *
     * @param  string  $identity
     * @return Tinebase_Auth_ModSsl
     */
    public function setIdentity($identity)
    {
        return $this;
    }
    
    /**
     * set password
     *
     * TODO function probably doesnt work
     *
     * @param  string  $credential
     * @return Tinebase_Auth_ModSsl
     */
    public function setCredential($credential)
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function supportsAuthByEmail()
    {
        return false;
    }

    /**
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getAuthByEmailBackend(): never
    {
        throw new Tinebase_Exception_NotImplemented('do not call ' . __METHOD__);
    }
}
