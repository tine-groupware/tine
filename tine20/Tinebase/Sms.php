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
 * SMS Interface
 *
 * @package     Tinebase
 * @subpackage  Sms
 */
class Tinebase_Sms
{
    public static function send(Tinebase_Model_Sms_SendConfig $config): bool
    {
        if (!$config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CONFIG}) {
            throw new Tinebase_Exception_UnexpectedValue('sms send config needs to be set');
        }

        return $config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CONFIG}->send($config);
    }
}