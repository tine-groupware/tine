<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Tinebase
 * @subpackage  Ldap
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2016-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_User_Ldap
 */
class Tinebase_LdapTest extends TestCase
{
    /**
     * @see 0011844: decodeSid fails for some encoded SIDs
     *
     * TODO write a test for a decoded sid, like this: ^A^E^@^@^@^@^@^E^U^@^@^@^A.z<F4>^W<B0>Ot^^<DC>^O^V<DE>-^@^@
     *
     * we could dump the encoded sid like this:
     *  $str = pack('c*', $data);
     *  for ($i=0; $i < strlen($str); ++$i) {
     *    echo '\x' . ord($str[$i]);
     *  }
     */
    public function testDecodeAlreadyDecodedSid()
    {
        $sid = "S-1-5-21-2127521184-1604012920-1887927527";

        $decodedSid = Tinebase_Ldap::decodeSid($sid);
        $this->assertEquals($decodedSid, $sid);
    }

    /**
     * test that the ldap network timeout from config is applied on connect
     */
    public function testLdapNetworkTimeoutAppliedOnConnect()
    {
        $config = Tinebase_Config::getInstance();
        $config->set(Tinebase_Config::LDAP_NETWORK_TIMEOUT, 7);
        try {
            $ldap = new Tinebase_Ldap(['host' => 'localhost', 'port' => 389]);
            $ldap->connect();

            // ldap_connect() is lazy, so no network i/o happened here;
            // read the (global) option back via a plain connection resource
            $probe = ldap_connect('ldap://localhost:389');
            $timeout = null;
            $this->assertTrue(ldap_get_option($probe, LDAP_OPT_NETWORK_TIMEOUT, $timeout));
            $this->assertSame(7, $timeout);
        } finally {
            $config->delete(Tinebase_Config::LDAP_NETWORK_TIMEOUT);
        }
    }
}
