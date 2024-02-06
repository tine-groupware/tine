<?php declare(strict_types=1);
/**
 * CostCenter controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CostCenter controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_CostCenter extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_CostCenter();
        $this->_modelName = 'HumanResources_Model_CostCenter';
        $this->_purgeRecords = FALSE;
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * check rights
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        if (self::ACTION_GET !== $_action && !$this->checkRight(HumanResources_Acl_Rights::ADMIN, FALSE)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' cost center.');
        }
        parent::_checkRight($_action);
    }
}
