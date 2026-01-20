<?php declare(strict_types=1);

/**
 * ContactProperties Definition controller for Addressbook application
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Addressbook_Model_ContactProperties_Definition as AMCPD;

/**
 * ContactProperties Address controller class for Addressbook application
 *
 * @extends Tinebase_Controller_Record_Abstract<Addressbook_Model_ContactProperties_Definition>
 */
class Addressbook_Controller_ContactProperties_Definition extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Addressbook_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => AMCPD::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => AMCPD::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = AMCPD::class;
        $this->_purgeRecords = false;
    }

    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks || self::ACTION_GET === $_action) {
            return;
        }
        throw new Tinebase_Exception_AccessDenied('filter actions other than get are not allowed');
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks || self::ACTION_GET === $_action) {
            return true;
        }
        if (! is_object(Tinebase_Core::getUser())) {
            throw new Tinebase_Exception_AccessDenied('User object required to check grants');
        }
        if (!Tinebase_Core::getUser()->hasRight(Addressbook_Config::APP_NAME, Tinebase_Model_Grants::GRANT_ADMIN)) {
            if ($_throw) {
                throw new Tinebase_Exception_AccessDenied($_errorMessage);
            }
            return false;
        }
        return true;
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        $_record->{AMCPD::FLD_IS_APPLIED} = 0;
        $_record->{AMCPD::FLD_LAST_ERROR} = null;
        $_record->{AMCPD::FLD_IS_SYSTEM} = $_oldRecord->{AMCPD::FLD_IS_SYSTEM};
        if ($_oldRecord->{AMCPD::FLD_IS_SYSTEM}) {
            $_record->{AMCPD::FLD_MODEL} = $_oldRecord->{AMCPD::FLD_MODEL};
            $_record->{AMCPD::FLD_NAME} = $_oldRecord->{AMCPD::FLD_NAME};
            $_record->{AMCPD::FLD_LINK_TYPE} = $_oldRecord->{AMCPD::FLD_LINK_TYPE};
        }
    }

    protected function _inspectAfterSetRelatedDataCreate($createdRecord, $record)
    {
        parent::_inspectAfterSetRelatedDataCreate($createdRecord, $record);

        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(
            [$createdRecord, 'applyToContactModel'], []);
    }

    protected function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);

        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(
            [$updatedRecord, 'applyToContactModel'], []);

    }

    protected function _inspectDelete(array $_ids)
    {
        $_ids = parent::_inspectDelete($_ids);
        return $this->getMultiple($_ids)->filter(AMCPD::FLD_IS_SYSTEM, false)
            ->getArrayOfIds();
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);

        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(
            [$record, 'removeFromContactModel'], []);
    }

    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $_modification): void
    {
        Addressbook_Model_ContactProperties_Definition::$doNotApplyToContactModel = true;
        $oldAcl = $this->doContainerACLChecks(false);
        $raii = new Tinebase_RAII(function() use($oldAcl) {
            // do not reset Addressbook_Model_ContactProperties_Definition::$doNotApplyToContactModel here!
            $this->doContainerACLChecks($oldAcl);
        });
        Tinebase_Timemachine_ModificationLog::defaultApply($_modification, $this);
        unset($raii);
    }
}