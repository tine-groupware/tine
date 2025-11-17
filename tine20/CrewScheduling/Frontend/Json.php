<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * This class handles all Json requests for the Crew Scheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Frontend
 */
class CrewScheduling_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = [
        CrewScheduling_Model_SchedulingRole::MODEL_NAME_PART,
        CrewScheduling_Model_SchedulingRoleGrants::MODEL_NAME_PART,
        CrewScheduling_Model_RequiredGroups::MODEL_NAME_PART,
        CrewScheduling_Model_EventTypeConfig::MODEL_NAME_PART,
        CrewScheduling_Model_EventRoleConfig::MODEL_NAME_PART,
        CrewScheduling_Model_AttendeeRole::MODEL_NAME_PART,
        CrewScheduling_Model_Poll::MODEL_NAME_PART,
        CrewScheduling_Model_PollParticipant::MODEL_NAME_PART,
        CrewScheduling_Model_PollReply::MODEL_NAME_PART,
        CrewScheduling_Model_PollSite::MODEL_NAME_PART,
        CrewScheduling_Model_PollEventType::MODEL_NAME_PART,
    ];

    public function __construct()
    {
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
    }

    public function saveEvent(array $event): array
    {
        if (!($event['id'] ?? false)) {
            throw new Tinebase_Exception_UnexpectedValue('id missing on event');
        }

        $oldDoAcl = Calendar_Controller_Event::getInstance()->doContainerACLChecks(false);
        $aclRaii = new Tinebase_RAII(fn() => Calendar_Controller_Event::getInstance()->doContainerACLChecks($oldDoAcl));
        $orgEvent = Calendar_Controller_Event::getInstance()->get($event['id']);

        $allowedRoles = [];
        $schedulingRoleCtrl =  CrewScheduling_Controller_SchedulingRole::getInstance();
        /** @var CrewScheduling_Model_EventRoleConfig $roleCfg */
        foreach ($orgEvent->{CrewScheduling_Config::EVENT_ROLES_CONFIGS} as $roleCfg) {
            $role = $roleCfg->{CrewScheduling_Model_EventRoleConfig::FLD_ROLE};

            if ($schedulingRoleCtrl->checkGrant($role, CrewScheduling_Model_SchedulingRoleGrants::ASSIGN_ATTENDEE, false) ||
                    $schedulingRoleCtrl->checkGrant($role, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, false)) {
                $allowedRoles[$role->getId()] = $role;
            }
        }

        $update = false;

        // byRef true! so we can directly edit the records, we just want the recordset it self to be cloned
        $orgAttendees = $orgEvent->attendee->getClone(recordsByRef: true);
        foreach ($event['attendee'] ?? [] as $attendee) {
            $attendee = $this->_jsonToRecord($attendee, Calendar_Model_Attender::class);
            $this->_dependentRecordsFromJson($attendee);

            $schedulingRoles = $attendee->{CrewScheduling_Config::CREWSHEDULING_ROLES}?->filter(fn($rec) => isset($allowedRoles[$rec->getIdFromProperty(CrewScheduling_Model_AttendeeRole::FLD_ROLE)]));
            if ($attendee->getId() && ($orgAttendee = $orgAttendees->getById($attendee->getId()))) {
                $orgAttendee->{CrewScheduling_Config::CREWSHEDULING_ROLES} =
                    $orgAttendee->{CrewScheduling_Config::CREWSHEDULING_ROLES}->filter(fn($rec) => !isset($allowedRoles[$rec->getIdFromProperty(CrewScheduling_Model_AttendeeRole::FLD_ROLE)]));
                if ($schedulingRoles) {
                    $orgAttendee->{CrewScheduling_Config::CREWSHEDULING_ROLES}->merge($schedulingRoles);
                }
                $update = true;
            } else {
                if (null === $schedulingRoles || 0 === $schedulingRoles->count()) {
                    continue;
                }

                $orgEvent->attendee->addRecord(new Calendar_Model_Attender([
                    Calendar_Model_Attender::FLD_USER_ID => $attendee->{Calendar_Model_Attender::FLD_USER_ID},
                    Calendar_Model_Attender::FLD_USER_TYPE => $attendee->{Calendar_Model_Attender::FLD_USER_TYPE},
                    Calendar_Model_Attender::FLD_USER_EMAIL => $attendee->{Calendar_Model_Attender::FLD_USER_EMAIL},
                    Calendar_Model_Attender::FLD_USER_DISPLAYNAME => $attendee->{Calendar_Model_Attender::FLD_USER_DISPLAYNAME},
                    CrewScheduling_Config::CREWSHEDULING_ROLES => $schedulingRoles,
                ], true));
                $update = true;
            }
        }

        if ($update) {
            if ($orgEvent->isRecurInstance()) {
                $orgEvent = Calendar_Controller_Event::getInstance()->createRecurException($orgEvent);
            } elseif ($orgEvent->rrule) {
                throw new Tinebase_Exception_Record_Validation('can\'t save recuring events, client needs to create recure instance');
            } else {
                $orgEvent = Calendar_Controller_Event::getInstance()->update($orgEvent);
            }
        }

        unset($aclRaii);

        // checkGrant will remove properties, it will call \Calendar_Model_Event::doFreeBusyCleanup
        if (Calendar_Controller_Event::getInstance()->checkGrant(clone $orgEvent, Calendar_Controller_Event::ACTION_GET, _throw: false)) {
            return $this->_recordToJson($orgEvent);
        } else {
            return CrewScheduling_Controller_Poll::cleanEventForUnpriviledgedAccess($orgEvent, $allowedRoles)->toArray();
        }
    }

    public function searchPollReplys($filter, $paging)
    {
        return $this->_search($filter, $paging, CrewScheduling_Controller_PollReply::getInstance(),
            CrewScheduling_Model_PollReply::class, true);
    }

    public function getPollMessage($template, $pollId)
    {
        $poll = CrewScheduling_Controller_Poll::getInstance()->get($pollId['id'] ?? $pollId);
        $expander = new Tinebase_Record_Expander(CrewScheduling_Model_Poll::class, CrewScheduling_Model_Poll::getConfiguration()->jsonExpander);
        $expander->expandRecord($poll);

        if (!CrewScheduling_Controller_SchedulingRole::getInstance()->checkGrant($poll->{CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE}, CrewScheduling_Model_SchedulingRoleGrants::SEND_EMAILS, false) &&
            !CrewScheduling_Controller_SchedulingRole::getInstance()->checkGrant($poll->{CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE}, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, false)) {
            throw new Tinebase_Exception_AccessDenied('not allowed to send emails');
        }

        $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(CrewScheduling_Config::APP_NAME));
        $htmlTemplate = $twig->load(CrewScheduling_Config::APP_NAME . '/views/emails/'. basename($template) .'.html.twig');
        $context = [
            'poll' => $poll
        ];

        return [
            'subject' => $htmlTemplate->renderBlock('subject', $context),
            'html' => $htmlTemplate->render($context),
        ];
    }
}
