<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

class Tinebase_Auth_WebAuthnAdapterMock extends Tinebase_Auth_MFA_WebAuthnAdapter
{
    public function validate($_data, Tinebase_Model_MFA_UserConfig $_userCfg): bool
    {
        return true;
    }
}
