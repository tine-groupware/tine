<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for AppPassword
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_AppPassword extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    public const PWD_SUFFIX = '~ApP\}';
    public const PWD_SUFFIX_LENGTH = 6;
    public const PWD_LENGTH = 26;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_AppPassword::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_AppPassword::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_AppPassword::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => false,
        ]);
        $this->_purgeRecords = true;
        $this->_omitModLog = true;
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.',
        /** @noinspection PhpUnusedParameterInspection */ $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }
        if (Tinebase_Core::getUser()->hasRight(Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::ADMIN)) {
            return true;
        }

        if ($_record->getIdFromProperty(Tinebase_Model_AppPassword::FLD_ACCOUNT_ID) !== Tinebase_Core::getUser()->getId()) {
            if ($_throw) {
                new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }

        return true;
    }

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks || Tinebase_Core::getUser()->hasRight(Tinebase_Config::APP_NAME, Tinebase_Acl_Rights::ADMIN)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return;
        }

        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            $_filter->andWrapItself();
        }

        $_filter->addFilter($_filter->createFilter(
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID,
            Tinebase_Model_Filter_Abstract::OP_EQUALS,
            Tinebase_Core::getUser()->getId()
        ));
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        $this->_inspectPwd($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        $this->_inspectPwd($_record);
    }

    protected function _inspectPwd(Tinebase_Model_AppPassword $_record): void
    {
        $appPwd = $_record->{Tinebase_Model_AppPassword::FLD_AUTH_TOKEN};
        if (strlen((string) $appPwd) !== Tinebase_Controller_AppPassword::PWD_LENGTH || strpos((string) $appPwd, Tinebase_Controller_AppPassword::PWD_SUFFIX) !== Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH) {
            return;
        }
        $_record->{Tinebase_Model_AppPassword::FLD_AUTH_TOKEN} = sha1((string) $appPwd);
    }
}
