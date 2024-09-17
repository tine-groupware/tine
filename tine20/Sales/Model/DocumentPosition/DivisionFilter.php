<?php declare(strict_types=1);
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Model_DocumentPosition_DivisionFilter extends Tinebase_Model_Filter_ForeignRecords
{
    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        $_options['id_field'] = Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID;
        $_options[TMCC::REF_ID_FIELD] = [TMCC::ID, Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY]; // little hack to bypass array_keys in parent::appendFilterSql on the result of the foreignKeys query.... not ideal, but works for the time being. dont try to join this
        parent::_setOptions($_options);
    }

    public function setValue($_value)
    {
        $value = [
            [TMFA::FIELD => Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY, TMFA::OPERATOR => 'definedBy?condition=and&setOperator=oneOf', TMFA::VALUE => [
                [TMFA::FIELD => Sales_Model_Document_Category::FLD_DIVISION_ID, TMFA::OPERATOR => 'definedBy?condition=and&setOperator=oneOf', TMFA::VALUE => $_value],
            ]],
        ];
        parent::setValue($value);
    }

    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        if ($_valueToJson) {
            $result['value'] = $result['value'][0]['value'][0]['value'];
        }
        return $result;
    }
}