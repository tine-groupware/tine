<?php declare(strict_types=1);

/**
 * Poll controller for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Poll controller class for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 *
 * @method CrewScheduling_Model_Poll create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
 */
class CrewScheduling_Controller_Poll extends Tinebase_Controller_Record_Abstract implements
    Felamimail_Controller_MassMailingPluginInterface
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * @var array contains polls cached e. g. during prepareMassMailingMessage
     */
    protected $_cachedPolls = [];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => CrewScheduling_Model_Poll::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => CrewScheduling_Model_Poll::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = CrewScheduling_Model_Poll::class;
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

    public function getEventsForPoll(CrewScheduling_Model_Poll $poll, array $additionalFilters = []): Tinebase_Record_RecordSet
    {
        $schedulingRoleId = $poll->getIdFromProperty(CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE);
        $from = $poll->{CrewScheduling_Model_Poll::FLD_FROM}->getClone();
        $from->hasTime(true);
        $from->setTime(0, 0);
        $until = $poll->{CrewScheduling_Model_Poll::FLD_UNTIL}->getClone();
        $until->hasTime(true);
        $until->setTime(23, 59, 59);

        $oldDoAcl = Calendar_Controller_Event::getInstance()->doContainerACLChecks(false);
        $aclRaii = new Tinebase_RAII(fn() => Calendar_Controller_Event::getInstance()->doContainerACLChecks($oldDoAcl));

        $result = Calendar_Controller_Event::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
            [TMFA::FIELD => 'period', TMFA::OPERATOR => 'within', TMFA::VALUE => [
                'from' => $from->getClone(),
                'until' => $until->getClone(),
            ]],
            [
                Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_OR,
                Tinebase_Model_Filter_FilterGroup::FILTERS => [
                    [TMFA::FIELD => CrewScheduling_Config::EVENT_ROLES_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                        [TMFA::FIELD => CrewScheduling_Model_EventRoleConfig::FLD_ROLE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $schedulingRoleId],
                    ]],
                    [
                        Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                        Tinebase_Model_Filter_FilterGroup::FILTERS => [
                            // baaah, this filter actually means: no EVENT_ROLES_CONFIGS set!!!
                            [TMFA::FIELD => CrewScheduling_Config::EVENT_ROLES_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                                [TMFA::FIELD => 'id', TMFA::OPERATOR => 'not', TMFA::VALUE => null],
                            ]],
                            [TMFA::FIELD => 'event_types', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                                [TMFA::FIELD => Calendar_Model_EventTypes::FLD_EVENT_TYPE, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                                    [TMFA::FIELD => CrewScheduling_Config::CS_ROLE_CONFIGS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                                        [TMFA::FIELD => CrewScheduling_Model_EventTypeConfig::FLD_SCHEDULING_ROLE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $schedulingRoleId],
                                    ]],
                                ]],
                            ]],
                        ],
                    ],
                ],
            ],
            [
                Tinebase_Model_Filter_FilterGroup::CONDITION => Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
                Tinebase_Model_Filter_FilterGroup::FILTERS => $additionalFilters,
            ],
        ]));

        $expander = new Tinebase_Record_Expander(Calendar_Model_Event::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'event_site' => []
            ],
        ]);
        $expander->expand($result);

        Calendar_Model_Rrule::mergeRecurrenceSet($result, $from, $until);
        /** @var Calendar_Model_Event $event */
        foreach ($result as $event) {
            if ($event->dtend->isEarlier($from) || $event->dtstart->isLater($until)) {
                $result->removeById($event->getId());
            }
        }

        unset($aclRaii);

        // check grants, sanitize event properties
        if (null === Tinebase_Core::getUser() || Tinebase_User::SYSTEM_USER_ANONYMOUS === Tinebase_Core::getUser()->accountLoginName) {
            $toClean = $result;
            $result = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
        } else {
            $toClean = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
            foreach ($result as $event) {
                if (!Calendar_Controller_Event::getInstance()->checkGrant($event, Tinebase_Controller_Record_Abstract::ACTION_GET, _throw: false)) {
                    $toClean->addRecord($event);
                }
            }
            $result->removeRecords($toClean);
        }

        /** @var Calendar_Model_Event $event */
        foreach ($toClean as $event) {
            $result->addRecord(static::cleanEventForUnpriviledgedAccess($event, [$schedulingRoleId => true]));
        }

        return $result;
    }

    public static function cleanEventForUnpriviledgedAccess(Calendar_Model_Event $event, array $schedulingRoleIds): Calendar_Model_Event
    {
        return new Calendar_Model_Event([
            'id' => $event->getId(),
            'dtstart' => $event->dtstart,
            'dtend' => $event->dtend,
            'is_all_day_event' => $event->is_all_day_event,
            'summary' => $event->summary,
            'description' => $event->description,
            'event_types' => $event->event_types,
            'event_site' => $event->event_site,
            'location' => $event->location,
            'attendee' => $event->attendee?->filter(fn($rec) => $rec->{CrewScheduling_Config::CREWSHEDULING_ROLES}?->find(fn($rec) => $schedulingRoleIds[$rec->getIdFromProperty(CrewScheduling_Model_AttendeeRole::FLD_ROLE)] ?? null, null)),
        ], true);
    }

    /**
     * @param Felamimail_Model_Message $_message
     * @param Tinebase_Twig $_twig
     * @return null
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function prepareMassMailingMessage(Felamimail_Model_Message $_message, Tinebase_Twig $_twig)
    {
        if (!preg_match('#/CrewScheduling/view/Poll/([a-z0-9]+)#', $_message->body, $matches)) {
            // nothing do do here
            return null;
        }
        $pollId = $matches[1];

        if (!is_array($_message->to) || !isset($_message->to[0])) {
            throw new Tinebase_Exception_UnexpectedValue('bad message, no to[0] set');
        }
        // new recipient structure is array and should always have email field
        $contactId = $_message->to[0]['contact_record']['id'] ?? null;
        if (! $contactId) {
            return;
        }
        if (!isset($this->_cachedPolls[$pollId])) {
            /** @var CrewScheduling_Model_Poll $poll */
            $poll = $this->get($pollId);
            $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS} =
                CrewScheduling_Controller_PollParticipant::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                        CrewScheduling_Model_PollParticipant::class, [
                ['field' => CrewScheduling_Model_PollParticipant::FLD_POLL, 'operator' => 'equals', 'value' => $pollId],
            ]));
            $this->_cachedPolls[$pollId] = $poll;
        } else {
            $poll = $this->_cachedPolls[$pollId];
        }

        /** @var CrewScheduling_Model_Poll $participant */
        $participant = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->find(CrewScheduling_Model_PollParticipant::FLD_CONTACT, $contactId);
        if (! $participant) {
            return;
        }

        $_message->body = str_replace($poll->getUrl(), $poll->getUrl($participant), $_message->body);
    }
}
