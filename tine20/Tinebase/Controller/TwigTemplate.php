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

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * controller for TwigTemplate
 *
 * @package     Tinebase
 * @subpackage  Controller
 *
 * @extends Tinebase_Controller_Record_Abstract<Tinebase_Model_TwigTemplate>
 */
class Tinebase_Controller_TwigTemplate extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = Tinebase_Model_TwigTemplate::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Tinebase_Model_TwigTemplate::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Tinebase_Model_TwigTemplate::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
        $this->_purgeRecords = false;
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.',
        /** @noinspection PhpUnusedParameterInspection */ $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }
        if (Tinebase_Core::getUser()->hasRight($_record->{Tinebase_Model_TwigTemplate::FLD_APPLICATION_ID}, Tinebase_Acl_Rights::TWIG)) {
            return true;
        }

        return false;
    }

    /**
     * Removes containers where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        if (!$this->_doContainerACLChecks) {
            return;
        }

        if ($_filter->getCondition() !== Tinebase_Model_Filter_FilterGroup::CONDITION_AND) {
            $_filter->andWrapItself();
        }

        $appIds = [];
        foreach (Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED) as $app) {
            if (Tinebase_Core::getUser()->hasRight($app, Tinebase_Acl_Rights_Abstract::TWIG)) {
                $appIds[] = $app->getId();
            }
        }

        $_filter->addFilter($_filter->createFilter(
            Tinebase_Model_TwigTemplate::FLD_APPLICATION_ID,
            'in',
            $appIds
        ));
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    public function getByPath(string $path, bool $skipAcl = false): ?Tinebase_Model_TwigTemplate
    {
        $oldAcl = null;
        if ($skipAcl) {
            $oldAcl = $this->doContainerACLChecks(false);
        }
        try {
            return $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
                [TMFA::FIELD => Tinebase_Model_TwigTemplate::FLD_PATH, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $path],
            ]))->getFirstRecord();
        } finally {
            if (null !== $oldAcl) {
                $this->doContainerACLChecks($oldAcl);
            }
        }
    }
}
