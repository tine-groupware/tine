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
 * @extends Tinebase_Controller_Record_Abstract<Tinebase_Model_CloudAccount>
 */
class Tinebase_Controller_CloudAccount extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_CloudAccount::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_CloudAccount::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_CloudAccount::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
        $this->_purgeRecords = false;
    }

    protected function _checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.', $_oldRecord = null)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        if ($_record->getIdFromProperty(Tinebase_Model_CloudAccount::FLD_OWNER_ID) !== Tinebase_Core::getUser()->getId()) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }

        return true;
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks) {
            return;
        }

        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            $_filter->andWrapItself();
            $_filter->isImplicit(true);
        }
        $aclFilter = new Tinebase_Model_Filter_Id(
            Tinebase_Model_CloudAccount::FLD_OWNER_ID,
            Tinebase_Model_Filter_Abstract::OP_EQUALS,
            Tinebase_Core::getUser()->getId()
        );
        $aclFilter->isImplicit(true);
        $_filter->addFilter($aclFilter);
    }
}
