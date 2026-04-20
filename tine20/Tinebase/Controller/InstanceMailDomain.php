<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * controller for InstanceMailDomain
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_InstanceMailDomain extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Tinebase_Controller_InstanceMailDomain> */
    use Tinebase_Controller_SingletonTrait;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_InstanceMailDomain::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_InstanceMailDomain::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_InstanceMailDomain::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
    }
}
