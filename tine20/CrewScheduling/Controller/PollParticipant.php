<?php declare(strict_types=1);

/**
 * PollParticipant controller for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * PollParticipant controller class for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller_PollParticipant extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => CrewScheduling_Model_PollParticipant::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => CrewScheduling_Model_PollParticipant::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = CrewScheduling_Model_PollParticipant::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = true;
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

        // for GET we do not need to check filter acl
        if (self::ACTION_GET === $_action) {
            return;
        }

        parent::checkFilterACL($_filter, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL);
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // everybody can GET
        if (self::ACTION_GET === $_action) {
            return true;
        }

        return parent::_checkGrant($_record, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, $_throw, $_errorMessage, $_oldRecord);
    }
}
