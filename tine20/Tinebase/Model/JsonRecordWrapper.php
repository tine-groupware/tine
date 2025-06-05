<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * Json Record Wrapper Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                             $model_name
 * @property Tinebase_Record_Interface|string   $record
 */
class Tinebase_Model_JsonRecordWrapper extends Tinebase_Model_DynamicRecordWrapper
{
    public const MODEL_NAME_PART = 'JsonRecordWrapper';


    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_RECORD][self::CONFIG][self::PERSISTENT] = true;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function setFromArray(array &$_data)
    {
        if (!isset($_data[self::ID])) {
            $_data[self::ID] = Tinebase_Record_Abstract::generateUID();
        }
        parent::setFromArray($_data);
    }
}
