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
 * controller for BatchJobHistory
 *
 * @extends Tinebase_Controller_Record_Abstract<Tinebase_Model_BatchJobHistory>
 */
class Tinebase_Controller_BatchJobHistory extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_BatchJobHistory::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_BatchJobHistory::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_BatchJobHistory::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
        // default => $this->_purgeRecords = true;
        $this->_omitModLog = true;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_NotImplemented('should never be called');
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        throw new Tinebase_Exception_NotImplemented('should never be called');
    }
}
