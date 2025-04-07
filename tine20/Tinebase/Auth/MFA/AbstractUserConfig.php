<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Abstract MFA UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
abstract class Tinebase_Auth_MFA_AbstractUserConfig extends Tinebase_Record_NewAbstract
    implements Tinebase_Auth_MFA_UserConfigInterface
{
    public function toFEArray(?Tinebase_Model_FullUser $user = null): array
    {
        return [];
    }

    public function getClientPasswordLength(): ?int
    {
        return null;
    }
}
