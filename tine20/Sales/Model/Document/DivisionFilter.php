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

class Sales_Model_Document_DivisionFilter extends Tinebase_Model_Filter_ForeignRecords
{
    protected $_orgOrgOperator = null;

    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        $_options[TMCC::REF_ID_FIELD] = Sales_Model_Document_Debitor::FLD_DOCUMENT_ID;
        $_options[TMCC::RECORD_CLASS_NAME] = Sales_Model_Document_Debitor::class;
        $_options[TMCC::CONTROLLER_CLASS_NAME] = Sales_Controller_Document_Debitor::class;
        parent::_setOptions($_options);
    }

    public function setOperator($_operator)
    {
        $this->_orgOrgOperator = $_operator;

        parent::setOperator('definedBy');
    }

    public function setValue($_value)
    {
        $value = [
            [TMFA::FIELD => Sales_Model_Debitor::FLD_DIVISION_ID, TMFA::OPERATOR => $this->_orgOrgOperator, TMFA::VALUE => $_value],
        ];
        Sales_Model_Document_Debitor::$documentIdModel = $this->_options[TMCC::MODEL_NAME];
        Sales_Model_Document_Debitor::resetConfiguration();
        parent::setValue($value);
    }

    public function appendFilterSql($_select, $_backend)
    {
        Sales_Model_Document_Debitor::$documentIdModel = $this->_options[TMCC::MODEL_NAME];
        Sales_Model_Document_Debitor::resetConfiguration();
        parent::appendFilterSql($_select, $_backend);
    }

    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        $result['operator'] = $this->_orgOrgOperator;
        if ($_valueToJson) {
            $result['value'] = $result['value'][0]['value'];
        }
        return $result;
    }
}