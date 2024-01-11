<?php declare(strict_types=1);

/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_PropertyClass_AccountGrants extends Tinebase_Record_Expander_Sub
{
    protected Tinebase_ModelConfiguration $parentMC;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($this->parentMC = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }

        parent::__construct($this->parentMC->grantsModel, $_expanderDefinition, $_rootExpander);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        if (0 === $_records->count()) {
            return;
        }

        $this->_addRecordsToProcess($_records);

        $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest_AccountGrants(
            self::DATA_FETCH_PRIO_ACCOUNT_GRANTS,
            Tinebase_Core::getApplicationInstance($this->parentMC->getAppName() . '_Model_' . $this->parentMC->getModelName(), '', true),
            $_records,
            $this->parentMC,
            function($a) {})
        );
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {}
}
