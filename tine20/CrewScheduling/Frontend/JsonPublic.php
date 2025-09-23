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
class CrewScheduling_Frontend_JsonPublic extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = [];

    public function __construct()
    {
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
    }

    // is a public method, can be called without user set!
    public function searchEvents(string $pollId, ?string $participantId = null, array $additionalFilter = []): array
    {
        $poll = CrewScheduling_Controller_Poll::getInstance()->get($pollId);
        if (null === $participantId) { // no participantId => user must have manage_poll/assign_attendee
            if (null === Tinebase_Core::getUser()) {
                throw new Tinebase_Exception_AccessDenied('not allowed');
            }
            if (!CrewScheduling_Controller_Poll::getInstance()->checkGrant($poll, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, _throw: false) &&
                    !CrewScheduling_Controller_Poll::getInstance()->checkGrant($poll, CrewScheduling_Model_SchedulingRoleGrants::ASSIGN_ATTENDEE, _throw: false)) {
                throw new Tinebase_Exception_AccessDenied('not allowed');
            }
        } else {
            // participantId needs to exist on poll
            if (!($participant = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->getById($participantId))) {
                throw new Tinebase_Exception_AccessDenied('not allowed');
            }

            // if participant is an account, current user needs to be that account
            if (Addressbook_Model_Contact::CONTACTTYPE_USER === $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->type) {
                if (null === Tinebase_Core::getUser() || Tinebase_Core::getUser()->getId() !== $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->getIdFromProperty('account_id')) {
                    throw new Tinebase_Exception_AccessDenied('not allowed');
                }
            } else { // if participant is not an account and no user, set annonymous account!
                if (null === Tinebase_Core::getUser()) {
                    Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS));
                }
            }
        }

        $events = CrewScheduling_Controller_Poll::getInstance()->getEventsForPoll($poll);

        return [
            'results' => array_merge(
                $this->_multipleRecordsToJson($events->filter(Tinebase_Model_Grants::GRANT_READ, true)),
                $events->filter(Tinebase_Model_Grants::GRANT_READ, false)->toArray(),
            ),
            'totalcount' => count($events),
        ];
    }
}
