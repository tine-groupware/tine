<?php

class CrewScheduling_Import_PollReply_Csv extends Tinebase_Import_Csv_Abstract
{
    protected function _doConversions($_data)
    {
        $_data = parent::_doConversions($_data);

        if (!isset($_data['poll_participant_id']) || !is_string($_data['poll_participant_id']) || $_data['poll_participant_id'] === '') {
            return $_data;
        }

        $contactRecord = \Addressbook_Controller_Contact::getInstance()->getRecordByTitleProperty(trim($_data['poll_participant_id']));
        if (!$contactRecord) {
            throw new Exception('Could not find contact ' . $_data['poll_participant_id']);
        }

        if (!isset($_data['poll_id']) || !is_string($_data['poll_id']) || $_data['poll_id'] === '') {
            throw new Exception('poll_id not specified');
        }

        $poll = CrewScheduling_Controller_Poll::getInstance()->getRecordByTitleProperty(trim($_data['poll_id']));
        if (!$poll) {
            throw new Exception('Could not find poll ' . $_data['poll_id']);
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_PollParticipant::class);
        $filter->addFilter(new Tinebase_Model_Filter_Text('contact_id', 'equals', $contactRecord->getId()));
        $filter->addFilter(new Tinebase_Model_Filter_Text('poll_id', 'equals', $poll->getId()));
        $participantRecords = CrewScheduling_Controller_PollParticipant::getInstance()->search($filter);
        if ($participantRecords->count() == 0) {
            throw new Exception('Could not find poll participant ' . $contactRecord->n_fn);
        }
        $participantRecord = $participantRecords[0];
        $_data['poll_participant_id'] = $participantRecord->getId();

        if (!isset($_data['event_ref']) || !is_string($_data['event_ref']) || $_data['event_ref'] === '') {
            return $_data;
        }

        $parts = explode('_', $_data['event_ref']);
        if (count($parts) !== 3) {
            throw new Exception('bad id format: ' . $_data['event_ref']);
        }
        $dtstart = new Tinebase_DateTime(null, 'UTC');
        $dtstart->modify($parts[1]);

        $timeParts = explode(':', $parts[2]);
        $dtstart->setTime((int)$timeParts[0], (int)$timeParts[1], (int)$timeParts[2]);

        $result = Calendar_Controller_Event::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
            [
                Tinebase_Model_Filter_Abstract::FIELD => 'period', Tinebase_Model_Filter_Abstract::OPERATOR => 'within',
                Tinebase_Model_Filter_Abstract::VALUE => [
                    'from' => $dtstart,
                    'until' => $dtstart,
                ]
            ],
            [
                Tinebase_Model_Filter_Abstract::FIELD => 'summary', Tinebase_Model_Filter_Abstract::OPERATOR => 'equals',
                Tinebase_Model_Filter_Abstract::VALUE => trim($parts[0])
            ]
        ]));

        if ($result->count() == 0) {
            throw new Exception('Could not find event ' . trim($parts[0]) . ' ' . $dtstart->format('Y-m-d H:i:s'));
        }

        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($result->getFirstRecord(),
            new Tinebase_Record_RecordSet(Calendar_Model_Event::class),
            $dtstart, $dtstart
        );

        $event = $recurSet->getFirstRecord();
        if (!$event) {
            throw new Exception('Could not find recurrence set for event ' . $result->getFirstRecord()->summary . ' at ' . $dtstart->format('Y-m-d H:i:s'));
        }

        $_data['event_ref'] = CrewScheduling_Model_PollReply::getEventRef($event);
        unset($_data['poll_id']);

        return $_data;
    }

    /**
     * Generates random replies for polls
     *
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _afterImport(): void
    {
        parent::_afterImport();

        $polls = CrewScheduling_Controller_Poll::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_Poll::class, [[
                Tinebase_Model_Filter_Abstract::FIELD => CrewScheduling_Model_Poll::FLD_DESCRIPTION,
                Tinebase_Model_Filter_Abstract::OPERATOR => Tinebase_Model_Filter_Abstract::OPERATOR_EQUALS,
                Tinebase_Model_Filter_Abstract::VALUE => 'Demodata: %'
            ]])
        );

        $participants = CrewScheduling_Controller_PollParticipant::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_PollParticipant::class, [[
                Tinebase_Model_Filter_Abstract::FIELD => CrewScheduling_Model_PollParticipant::FLD_POLL,
                Tinebase_Model_Filter_Abstract::OPERATOR => Tinebase_Model_Filter_Abstract::OPERATOR_EQUALS,
                Tinebase_Model_Filter_Abstract::VALUE => $polls->getArrayOfIds()
            ]])
        );

        $replies = CrewScheduling_Controller_PollReply::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(CrewScheduling_Model_PollReply::class, [[
                Tinebase_Model_Filter_Abstract::FIELD => CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID,
                Tinebase_Model_Filter_Abstract::OPERATOR => Tinebase_Model_Filter_Abstract::OPERATOR_EQUALS,
                Tinebase_Model_Filter_Abstract::VALUE => $participants->getArrayOfIds()
            ]])
        );

        $replyMap = [];
        foreach ($replies as $reply) {
            if (!$replyMap[$reply->{CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID}]) {
                $replyMap[$reply->{CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID}] = [];
            }
            if (!$replyMap[$reply->{CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID}][$reply->{CrewScheduling_Model_PollReply::FLD_EVENT_REF}]) {
                $replyMap[$reply->{CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID}][$reply->{CrewScheduling_Model_PollReply::FLD_EVENT_REF}] = [];
            }
            $replyMap[$reply->{CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID}][$reply->{CrewScheduling_Model_PollReply::FLD_EVENT_REF}] = $reply;
        }

        $pollParticipants = [];
        foreach ($participants as $participant) {
            if (!$pollParticipants[$participant->{CrewScheduling_Model_PollParticipant::FLD_POLL}]) {
                $pollParticipants[$participant->{CrewScheduling_Model_PollParticipant::FLD_POLL}] = [];
            }
            $pollParticipants[$participant->{CrewScheduling_Model_PollParticipant::FLD_POLL}][] = $participant;
        }

        // use duplicates to weight values
        $status = [
            Calendar_Model_Attender::STATUS_NEEDSACTION,
            Calendar_Model_Attender::STATUS_ACCEPTED,
            Calendar_Model_Attender::STATUS_ACCEPTED,
            Calendar_Model_Attender::STATUS_DECLINED,
            Calendar_Model_Attender::STATUS_DECLINED,
            Calendar_Model_Attender::STATUS_TENTATIVE,
        ];

        foreach ($polls as $poll) {
            if (!$pollParticipants[$poll->getId()]) {
                continue;
            }
            $events = CrewScheduling_Controller_Poll::getInstance()->getEventsForPoll($poll);
            foreach ($pollParticipants[$poll->getId()] as $participant) {
                foreach ($events as $event) {
                    if (!isset($replyMap[$participant->getId()][$event->getId()])) {
                        $newStatus = $status[rand(0, count($status)-1)];
                        CrewScheduling_Controller_PollReply::getInstance()->create(new CrewScheduling_Model_PollReply([
                            CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID => $participant->getId(),
                            CrewScheduling_Model_PollReply::FLD_EVENT_REF => $event->getId(),
                            CrewScheduling_Model_PollReply::FLD_STATUS => $newStatus,
                        ]));
                    }
                }
            }
        }
    }
}
