<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Sms
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * SMS Config Model
 *
 * @package     Tinebase
 * @subpackage  Sms
 */
interface Tinebase_Sms_AdapterInterface
{
    public function send(Tinebase_Model_Sms_SendConfig $config): bool;
}