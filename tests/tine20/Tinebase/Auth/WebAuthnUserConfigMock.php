<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * WebAuthn MFA UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Auth_WebAuthnUserConfigMock extends Tinebase_Model_MFA_WebAuthnUserConfig
{
    public function updateUserNewRecordCallback(Tinebase_Model_FullUser $newUser, ?Tinebase_Model_FullUser $oldUser, Tinebase_Model_MFA_UserConfig $userCfg)
    {
    }

    public function updateUserOldRecordCallback(Tinebase_Model_FullUser $newUser, Tinebase_Model_FullUser $oldUser, Tinebase_Model_MFA_UserConfig $userCfg)
    {
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
