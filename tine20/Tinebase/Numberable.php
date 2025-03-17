<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Numberable
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Tine20 implementation for numberables
 *
 * @package     Tinebase
 * @subpackage  Numberable
 */
class Tinebase_Numberable extends Tinebase_Numberable_Abstract
{
    protected static $_baseConfiguration = array(
        self::TABLENAME        => 'numberable',
        self::NUMCOLUMN        => 'number',
        self::BUCKETCOLUMN     => 'bucket'
    );

    protected static $_numberableCache = array();

    /**
     * the constructor
     *
     * allowed numberableConfiguration:
     *  - stepsize (optional)
     *  - bucketkey (optional)
     *
     *
     * allowed options:
     * see parent class
     *
     * @param array $_numberableConfiguration
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_numberableConfiguration, $_dbAdapter = NULL, $_options = array())
    {
        parent::__construct(array_merge($_numberableConfiguration, self::$_baseConfiguration), $_dbAdapter, $_options);
    }

    public static function getCreateUpdateNumberableConfig(string $model, string $property, array $config): ?Tinebase_Model_NumberableConfig
    {
        if (null === ($numberableCfg = Tinebase_Controller_NumberableConfig::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_NumberableConfig::class, [
                    [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_MODEL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $model],
                    [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_PROPERTY, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $property],
                    [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY] ?? ''],
                ]))->getFirstRecord())) {
            if ($config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::NO_AUTOCREATE] ?? false) {
                return null;
            }
            $raii = new Tinebase_RAII(Tinebase_Controller_NumberableConfig::getInstance()->assertPublicUsage());
            $numberableCfg = Tinebase_Controller_NumberableConfig::getInstance()->create(new Tinebase_Model_NumberableConfig([
                Tinebase_Model_NumberableConfig::FLD_MODEL => $model,
                Tinebase_Model_NumberableConfig::FLD_PROPERTY => $property,
                Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY => $model . '#' . $property
                    . (($config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY] ?? false) ? '#' . $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY] : '')
                    . '#' . ($config[TMCC::CONFIG][Tinebase_Numberable_String::PREFIX] ?? ''),
                Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY => $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY] ?? '',
                Tinebase_Model_NumberableConfig::FLD_PREFIX => $config[TMCC::CONFIG][Tinebase_Numberable_String::PREFIX] ?? '',
                Tinebase_Model_NumberableConfig::FLD_ZEROFILL => $config[TMCC::CONFIG][Tinebase_Numberable_String::ZEROFILL] ?? 0,
                Tinebase_Model_NumberableConfig::FLD_START => $config[TMCC::CONFIG][Tinebase_Numberable_Abstract::START] ?? 1,
            ]));
            unset($raii);
        }

        /** @var Tinebase_Model_NumberableConfig $numberableCfg */
        return $numberableCfg;
    }

    public static function getNumberable(Tinebase_Record_Interface $_record, string $_class, string $_field, array $_config): ?Tinebase_Numberable_Abstract
    {
        $key = $_class . '_#_' . $_field;
        if (isset($_config['config'][Tinebase_Numberable::CONFIG_OVERRIDE])) {
            [$objectClass, $method] = explode('::', (string) $_config['config'][Tinebase_Numberable::CONFIG_OVERRIDE]);
            $object = call_user_func($objectClass . '::getInstance');
            if (method_exists($object, $method)) {
                $configOverride = call_user_func_array([$object, $method], [$_record]);
                $_config['config'] = array_merge($_config['config'], $configOverride);
                $key .= Tinebase_Helper::arrayHash($_config['config']);
            }
        }

        if (isset($_config['config']['skip']) && $_config['config']['skip']) {
            return null;
        }

        if (!isset(self::$_numberableCache[$key])) {
            if ($numberableCfg = static::getCreateUpdateNumberableConfig($_class, $_field, $_config)) {
                $_config[TMCC::CONFIG][Tinebase_Numberable_String::ZEROFILL] = (int)$numberableCfg->{Tinebase_Model_NumberableConfig::FLD_ZEROFILL};
                $_config[TMCC::CONFIG][Tinebase_Numberable_Abstract::START] = (int)$numberableCfg->{Tinebase_Model_NumberableConfig::FLD_START};
                $_config[TMCC::CONFIG][Tinebase_Numberable_String::PREFIX] = $numberableCfg->{Tinebase_Model_NumberableConfig::FLD_PREFIX};
                $_config[TMCC::CONFIG][Tinebase_Numberable_Abstract::BUCKETKEY] = $numberableCfg->{Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY};
            }

            if ($_config['type'] === TMCC::TYPE_NUMBERABLE_STRING) {
                self::$_numberableCache[$key] = new Tinebase_Numberable_String($_config['config']);
            } elseif($_config['type'] === TMCC::TYPE_NUMBERABLE_INT) {
                self::$_numberableCache[$key] = new Tinebase_Numberable($_config['config']);
            } else {
                throw new Tinebase_Exception_NotImplemented('field type "' . $_config['type'] . '" is not known');
            }
        }
        return self::$_numberableCache[$key];
    }

    public static function clearCache()
    {
        self::$_numberableCache = [];
    }
}
