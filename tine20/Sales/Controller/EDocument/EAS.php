<?php declare(strict_types=1);

/**
 * EAS controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * EAS controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_EDocument_EAS extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Sales_Controller_EDocument_EAS> */
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_EDocument_EAS::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_EDocument_EAS::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_EDocument_EAS::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = true;
    }

    public function getByCode(string $code): ?Sales_Model_EDocument_EAS
    {
        /** @var ?Sales_Model_EDocument_EAS $eas */
        $eas = $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
                [TMFA::FIELD => Sales_Model_EDocument_EAS::FLD_CODE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $code],
            ]))->getFirstRecord();
        return $eas;
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        return;
    }

    protected function _checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.', $_oldRecord = null)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }
        if ($_action === self::ACTION_GET) {
            return true;
        }
        if (!Tinebase_Core::getUser()->hasRight(Sales_Config::APP_NAME, Tinebase_Acl_Rights::ADMIN)) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }
        return true;
    }
}
