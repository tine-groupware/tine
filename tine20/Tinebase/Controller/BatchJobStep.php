<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for BatchJobStep
 *
 * @extends Tinebase_Controller_Record_Abstract<Tinebase_Model_BatchJobStep>
 */
class Tinebase_Controller_BatchJobStep extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_BatchJobStep::class;
        $this->_backend = new Tinebase_Backend_BatchJobStep();
        // default => $this->_purgeRecords = true;
        $this->_omitModLog = true;
    }

    /** @param Tinebase_Model_BatchJobStep $_record */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        $_record->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS} = null;
        $_record->{Tinebase_Model_BatchJobStep::FLD_HISTORY} = null;
        if (is_array($_record->{Tinebase_Model_BatchJobStep::FLD_IN_DATA}) && $_record->{Tinebase_Model_BatchJobStep::FLD_IN_DATA}) {
            $_record->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS} = array_keys($_record->{Tinebase_Model_BatchJobStep::FLD_IN_DATA});
        } else {
            unset($_record->{Tinebase_Model_BatchJobStep::FLD_IN_DATA});
            unset($_record->{Tinebase_Model_BatchJobStep::FLD_TO_PROCESS});
        }
        if ($_record->{Tinebase_Model_BatchJobStep::FLD_NEXT_STEPS}) {
            $_record->{Tinebase_Model_BatchJobStep::FLD_NEXT_STEPS}->{Tinebase_Model_BatchJobStep::FLD_BATCH_JOB_ID} = $_record->{Tinebase_Model_BatchJobStep::FLD_BATCH_JOB_ID};
        }
        $_record->{Tinebase_Model_BatchJobStep::FLD_TICKS} = $_record->calcTickValue();
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        throw new Tinebase_Exception_NotImplemented('should never be called');
    }
}
