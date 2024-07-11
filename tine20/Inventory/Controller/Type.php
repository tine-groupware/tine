<?php
/**
 * Type controller for Inventory application
 *
 * @package     Inventory
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Type controller class for Inventory application
 *
 * @package     Inventory
 * @subpackage  Controller
 */
class Inventory_Controller_Type extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Inventory_Config::APP_NAME;
        $this->_modelName = Inventory_Model_Type::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => $this->_modelName,
            'tableName'     => Inventory_Model_Type::TABLE_NAME,
            'modlogActive'  => true
        ));
        $this->_purgeRecords = FALSE;
    }
}
