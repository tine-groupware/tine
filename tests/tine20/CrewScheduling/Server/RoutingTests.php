<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jan Evers <j.evers@metaways.de>
 *
 */

/**
 * Test class for CrewScheduling
 */
class CrewScheduling_Server_RoutingTests extends CrewScheduling_TestCase
{
    public function setUp(): void
    {
        if (! Tinebase_Application::getInstance()->isInstalled('CrewScheduling')) {
            self::markTestSkipped('Tests need CrewScheduling app');
        }
        parent::setUp();
    }

    public function testPublicGetPoll()
    {
        $response = $this->sendRequest('/CrewScheduling/Poll/notreal');
        $this->assertEquals(404, $response->getStatusCode());

        $poll = CrewScheduling_ControllerTest::createPoll();
        $participant = \CrewScheduling_Controller_PollParticipant::getInstance()->create(
            new \CrewScheduling_Model_PollParticipant([
                \CrewScheduling_Model_PollParticipant::FLD_CONTACT => $this->_personas['pwulf']->contact_id,
                \CrewScheduling_Model_PollParticipant::FLD_POLL => $poll->getId(),
            ])
        );
        $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->addRecord($participant);

        $participants = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS};
        $this->assertNotNull($participants->getFirstRecord(), 'Poll must have participants');
        $participantId = $participants->getFirstRecord()->getId();

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId() . '/' . $participantId);
        $this->assertEquals(200, $response->getStatusCode());

        // as user
        Tinebase_core::setUser($this->_personas['pwulf']);

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId());
        $this->assertEquals(303, $response->getStatusCode());

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId() . '/' . $participantId);
        $this->assertEquals(200, $response->getStatusCode());

        // logged out
        Tinebase_Core::unsetUser();

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId() . '/' . $participantId);
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId());
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPublicPostPoll()
    {
        $poll = CrewScheduling_ControllerTest::createPoll();
        $participant = \CrewScheduling_Controller_PollParticipant::getInstance()->create(
            new \CrewScheduling_Model_PollParticipant([
                \CrewScheduling_Model_PollParticipant::FLD_CONTACT => $this->_personas['pwulf']->contact_id,
                \CrewScheduling_Model_PollParticipant::FLD_POLL => $poll->getId(),
            ])
        );
        $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->addRecord($participant);
        $participantId = $participant->getId();

        $event = \Calendar_Controller_Event::getInstance()->create(new \Calendar_Model_Event([
            'summary' => 'test event',
            'dtstart' => $poll->{CrewScheduling_Model_Poll::FLD_FROM}->getClone()->addHour(1),
            'dtend'   => $poll->{CrewScheduling_Model_Poll::FLD_FROM}->getClone()->addHour(2),
            'container_id' => $this->_getPersonalContainer(\Calendar_Model_Event::class)->getId(),
            \CrewScheduling_Config::EVENT_ROLES_CONFIGS => new \Tinebase_Record_RecordSet(\CrewScheduling_Model_EventRoleConfig::class, [[
                \CrewScheduling_Model_EventRoleConfig::FLD_ROLE => $poll->{CrewScheduling_Model_Poll::FLD_SCHEDULING_ROLE},
                \CrewScheduling_Model_EventRoleConfig::FLD_NUM_REQUIRED_ROLE_ATTENDEE => 1,
            ]], _bypassFilters: true)
        ]));

        $body = [
            \CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID => $participantId,
            \CrewScheduling_Model_PollReply::FLD_EVENT_REF => \CrewScheduling_Model_PollReply::getEventRef($event),
            \CrewScheduling_Model_PollReply::FLD_STATUS => \Calendar_Model_Attender::STATUS_ACCEPTED,
        ];

        Tinebase_core::setUser($this->_personas['pwulf']);

        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId() . '/' . $participantId, 'POST', $body);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getBody(), true)['success']);

        // verify reply was created
        $replies = \CrewScheduling_Controller_PollReply::getInstance()->search(\Tinebase_Model_Filter_FilterGroup::getFilterForModel(\CrewScheduling_Model_PollReply::class, [
            ['field' => 'poll_participant_id', 'operator' => 'equals', 'value' => $participantId],
            ['field' => 'event_ref', 'operator' => 'equals', 'value' => \CrewScheduling_Model_PollReply::getEventRef($event)],
        ]));
        $this->assertCount(1, $replies);
        $this->assertEquals(\Calendar_Model_Attender::STATUS_ACCEPTED, $replies->getFirstRecord()->status);

        // as different user (not the participant)
        Tinebase_Core::setUser($this->_personas['sclever']);
        $response = $this->sendRequest('/CrewScheduling/Poll/' . $poll->getId() . '/' . $participantId, 'POST', $body);
        $this->assertEquals(403, $response->getStatusCode());
    }

    protected function sendRequest($url, $method = 'GET', $body = null)
    {
        // sync registry user to session so Tinebase_Server_Expressive::handle -> startCoreSession doesn't overwrite it
        try {
            $session = \Tinebase_Session::getSessionNamespace();
            $user = Tinebase_Core::getUser();
            if ($user instanceof \Tinebase_Model_FullUser) {
                $session->currentAccount = $user;
            } else {
                unset($session->currentAccount);
            }
            unset($session->timezone);
        } catch (\Zend_Session_Exception $e) {
            // session might not be started yet, that's fine
        }

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);
        $tRequest = Tinebase_Http_Request::fromString(
            $method . ' ' . $url . ' HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Tine 2.0 UNITTEST' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . "\r\n"
        );
        if ($body !== null) {
            $contentString = json_encode($body, JSON_THROW_ON_ERROR);
            $tRequest->getHeaders()->addHeaderLine('Content-Length', strlen($contentString));
            $tRequest->setContent($contentString);
        }
        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($tRequest);

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle($tRequest);
        $emitter->response->getBody()->rewind();

        return $emitter->response;
    }
}
