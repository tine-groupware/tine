<?php declare(strict_types=1);
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;
use Tinebase_Model_Filter_FilterGroup as TMFFG;

class Sales_Model_Document_CustomerDocumentFilter extends Tinebase_Model_Filter_ForeignRecords
{
    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        $_options[TMCC::REF_ID_FIELD] = TMCC::FLD_ORIGINAL_ID;
        $_options[TMCC::RECORD_CLASS_NAME] = Sales_Model_Document_Customer::class;
        $_options[TMCC::CONTROLLER_CLASS_NAME] = Sales_Controller_Document_Customer::class;
        parent::_setOptions($_options);
    }

    public function setValue($_value)
    {
        $value = [
            [TMFA::FIELD => Sales_Model_Document_Customer::FLD_DOCUMENT_ID, TMFA::OPERATOR => 'definedBy?condition=and&setOperator=oneOf', TMFA::VALUE => $_value],
        ];
        Sales_Model_Document_Customer::$documentIdModel = $this->_options[TMCC::MODEL_NAME];
        Sales_Model_Document_Customer::resetConfiguration();
        parent::setValue($value);
    }

    public function appendFilterSql($_select, $_backend)
    {
        Sales_Model_Document_Customer::$documentIdModel = $this->_options[TMCC::MODEL_NAME];
        Sales_Model_Document_Customer::resetConfiguration();
        parent::appendFilterSql($_select, $_backend);
    }

    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        $result['value'] = $result['value'][0]['value'];
        return $result;
    }
}