<?php declare(strict_types=1);
/**
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_Controller_Document_Supplier extends Sales_Controller_Supplier
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Supplier::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Supplier::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Supplier::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
        $this->_duplicateCheckFields = null;
    }

    protected function _checkRight($_action)
    {
        Tinebase_Controller_Record_Abstract::_checkRight($_action);
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (!$_record->number) {
            $_record->number = 0;
        }
        Tinebase_Controller_Record_Abstract::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $_record->number = $_oldRecord->number;
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
