<?php declare(strict_types=1);

/**
 * Poll Reply controller for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Poll Reply controller class for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller_PollReply extends Tinebase_Controller_Record_Abstract
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => CrewScheduling_Model_PollReply::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => CrewScheduling_Model_PollReply::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = CrewScheduling_Model_PollReply::class;
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

        $participant = CrewScheduling_Controller_PollParticipant::getInstance()
            ->get($_record->getIdFromProperty(CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID));

        if (Addressbook_Model_Contact::CONTACTTYPE_USER !== $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->type) {
            return true;
        }

        if (Tinebase_Core::getUser()->getId() === $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->getIdFromProperty('account_id')) {
            return true;
        }

        return parent::_checkGrant($_record, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, $_throw, $_errorMessage, $_oldRecord);
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        /** @var CrewScheduling_Model_PollReply $_createdRecord */
        $this->_touchPollParticipantLastResponseTime($_createdRecord);
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        /** @var CrewScheduling_Model_PollReply $updatedRecord */
        $this->_touchPollParticipantLastResponseTime($updatedRecord);
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);

        /** @var CrewScheduling_Model_PollReply $record */
        $this->_touchPollParticipantLastResponseTime($record);
    }

    protected function _touchPollParticipantLastResponseTime(CrewScheduling_Model_PollReply $reply): void
    {
        $participant = CrewScheduling_Controller_PollParticipant::getInstance()
            ->get($reply->getIdFromProperty(CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID));
        $participant->{CrewScheduling_Model_PollParticipant::FLD_LAST_RESPONSE_TIME} = Tinebase_DateTime::now();
        $oldAcl = CrewScheduling_Controller_PollParticipant::getInstance()->doContainerACLChecks(false);
        $oldHandleDependent = CrewScheduling_Controller_PollParticipant::getInstance()->setHandleDependentRecords(false);
        try {
            CrewScheduling_Controller_PollParticipant::getInstance()->update($participant);
        } finally {
            CrewScheduling_Controller_PollParticipant::getInstance()->doContainerACLChecks($oldAcl);
            CrewScheduling_Controller_PollParticipant::getInstance()->setHandleDependentRecords($oldHandleDependent);
        }
    }
}
