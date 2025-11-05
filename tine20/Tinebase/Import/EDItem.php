<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the evaluation dimension item
 *
 * @package     Tinebase
 * @subpackage  Import
 *
 */
class Tinebase_Import_EDItem extends Tinebase_Import_Csv_Abstract
{

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        $filter =  Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Tinebase_Model_EvaluationDimension::class,
            [[
                'field' => Tinebase_Model_EvaluationDimension::FLD_NAME,
                'operator' => 'equals',
                'value' => $result['evaluationdimension']
            ],],
        );
        $ed_id = Tinebase_Controller_EvaluationDimension::getInstance()->search($filter)->getFirstRecord()->getId();
        $result['evaluation_dimension_id'] = $ed_id;
        return $result;
    }
}

