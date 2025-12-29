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
        $smsAdapterConfigs = Tinebase_Config::getInstance()->{Tinebase_Config::SMS}->{Tinebase_Config::SMS_ADAPTERS}
            ?->{Tinebase_Model_Sms_AdapterConfigs::FLD_ADAPTER_CONFIGS};

        if (empty($config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CONFIG})) {
            if (empty($smsAdapterConfigs) || count($smsAdapterConfigs) === 0) {
                throw new Tinebase_Exception_UnexpectedValue('sms send config adapter_config needs to be set');
            }
            $config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CONFIG} = $smsAdapterConfigs->getFirstRecord()->{Tinebase_Model_Sms_AdapterConfig::FLD_ADAPTER_CONFIG};
        }

        if (empty($config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CLASS})) {
            if (empty($smsAdapterConfigs) || count($smsAdapterConfigs) === 0) {
                throw new Tinebase_Exception_UnexpectedValue('sms send config adapter_class needs to be set');
            }
            $config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CLASS} = $smsAdapterConfigs->getFirstRecord()->{Tinebase_Model_Sms_AdapterConfig::FLD_ADAPTER_CLASS};
        }

        return $config->{Tinebase_Model_Sms_SendConfig::FLD_ADAPTER_CONFIG}->send($config);
    }
}