<?php declare(strict_types=1);
/**
 *
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/** @extends Tinebase_Controller_Record_Abstract<Calendar_Model_FreeBusyUrl> */
class Calendar_Controller_FreeBusyUrl extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Calendar_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Calendar_Model_FreeBusyUrl::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Calendar_Model_FreeBusyUrl::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Calendar_Model_FreeBusyUrl::class;
        $this->_purgeRecords = false;
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

        $_filter->addFilterGroup($group = Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            [
                Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                Tinebase_Model_Filter_FilterGroup::FILTERS => [
                    [TMFA::FIELD => Calendar_Model_FreeBusyUrl::FLD_OWNER_CLASS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_Model_User::class],
                    [TMFA::FIELD => Calendar_Model_FreeBusyUrl::FLD_OWNER_ID,    TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Tinebase_Core::getUser()->getId()],
                ],
            ], [
                Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                Tinebase_Model_Filter_FilterGroup::FILTERS => [
                    [TMFA::FIELD => Calendar_Model_FreeBusyUrl::FLD_OWNER_CLASS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Calendar_Model_Resource::class],
                ],
            ]
        ], Tinebase_Model_Filter_FilterGroup::CONDITION_OR));
        $group->isImplicit(true);
        $group->getFilterObjects()[1]->addFilter(
            $aclFilter = new Tinebase_Model_Filter_DelegatedAcl(Calendar_Model_FreeBusyUrl::FLD_OWNER_ID, null, null, [
                'modelName' => $this->_modelName,
                'refModel' => Calendar_Model_Resource::class,
                'isOptional' => true,
            ])
        );
        match ($_action) {
            self::ACTION_GET => $aclFilter->setRequiredGrants($this->_requiredFilterACLget),
            self::ACTION_UPDATE => $aclFilter->setRequiredGrants($this->_requiredFilterACLupdate),
            'export' => $aclFilter->setRequiredGrants($this->_requiredFilterACLexport),
            'sync' => $aclFilter->setRequiredGrants($this->_requiredFilterACLsync),
            default => throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action),
        };
    }

    protected function _checkGrant($_record, $_action, $_throw = true, $_errorMessage = 'No Permission.', $_oldRecord = null)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        switch ($_record->{Calendar_Model_FreeBusyUrl::FLD_OWNER_CLASS}) {
            case Tinebase_Model_User::class:
                $hasGrant = $_record->getIdFromProperty(Calendar_Model_FreeBusyUrl::FLD_OWNER_ID) === Tinebase_Core::getUser()->getId();
                break;

            case Calendar_Model_Resource::class:
                $resource = Calendar_Controller_Resource::getInstance()->get(
                    $_record->getIdFromProperty(Calendar_Model_FreeBusyUrl::FLD_OWNER_ID));
                $hasGrant = Calendar_Controller_Resource::getInstance()->checkGrant($resource, $_action, $_throw, $_errorMessage);
                break;

            default:
                $hasGrant = false;
                break;
        }

        if (!$hasGrant && $_throw) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        return $hasGrant;
    }
}