<?php
/**
 * tine Groupware
 * 
 * MAIN controller for CrewScheduling, does event and container handling
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * 
 */

use Psr\Http\Message\ResponseInterface;

/**
 * main controller for CrewScheduling
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller extends Tinebase_Controller_Event implements
    Felamimail_Controller_MassMailingPluginInterface
{
    /**
     * holds the instance of the singleton
     *
     * @var CrewScheduling_Controller
     */
    private static $_instance = NULL;


    
    /**
     * constructor (get current user)
     */
    private function __construct() {
        $this->_applicationName = 'CrewScheduling';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return CrewScheduling_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new CrewScheduling_Controller;
        }
        
        return self::$_instance;
    }


    /**
     * get core data for this application
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();

        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => 'cs_scheduling_role',
            'application_id' => $application,
            'model' => 'CrewScheduling_Model_SchedulingRole',
            'label' => 'Scheduling Roles' // _('Scheduling Roles')
        )));

        return $result;
    }

    public static function addFastRoutes(\FastRoute\RouteCollector $r): void
    {
        $r->addGroup('/CrewScheduling', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->addRoute(['GET'], '/view/Poll/{pollId}[/{participantId}]', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicGetPollClient', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true,
                Tinebase_Expressive_RouteHandler::UNAUTHORIZED_REDIRECT_LOGIN => true,
            ]))->toArray());
            $routeCollector->addRoute(['GET'], '/Poll/{pollId}[/{participantId}]', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicGetPoll', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['POST'], '/Poll/{pollId}/{participantId}', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicPostPoll', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
    }

    public static function publicGetPollClient(string $pollId, ?string $participantId = null): ResponseInterface
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles = [
            "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all",
            'CrewScheduling/js/pollClient/src/index.es6.js'
        ];

        $path = Tinebase_Core::get(Tinebase_Core::REQUEST)->getRequestUri();
        Tinebase_Expressive_Middleware_CheckRouteAuth::loginFor($path);

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, context: [
            'initialData' => [
                'pollId' => $pollId,
                'participantId' => $participantId
            ]
        ]);
    }

    public static function publicGetPoll(string $pollId, ?string $participantId = null): ResponseInterface
    {
        $oldDoAcl = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        $aclRaii = new Tinebase_RAII(fn() => Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($oldDoAcl));

        $poll = CrewScheduling_Controller_Poll::getInstance()->get($pollId);
        if (null === $participantId) { // no participantId => user must habe manage_poll
            if (null === Tinebase_Core::getUser()) {
                throw new Tinebase_Exception_AccessDenied('not allowed');
            }
            if (!CrewScheduling_Controller_Poll::getInstance()->checkGrant($poll, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, false)) {
                return self::getFixedParticipantResponse($poll);
            }
        } else {
            // participantId needs to exist on poll
            if (!($participant = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->getById($participantId))) {
                throw new Tinebase_Exception_AccessDenied('not allowed');
            }

            // if participant is an account, current user needs to be that account
            if (Addressbook_Model_Contact::CONTACTTYPE_USER === $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->type) {
                if (null === Tinebase_Core::getUser()) {
                    throw new Tinebase_Exception_AccessDenied('not allowed');
                }
                if (Tinebase_Core::getUser()->getId() !== $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->getIdFromProperty('account_id') &&
                        !CrewScheduling_Controller_Poll::getInstance()->checkGrant($poll, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, false)
                ) {
                    return self::getFixedParticipantResponse($poll);
                }
            } else { // if participant is not an account only anonymous usage!
                if (null === Tinebase_Core::getUser()) {
                    Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS));
                }
            }
        }
        unset($aclRaii);

        $events = CrewScheduling_Controller_Poll::getInstance()->getEventsForPoll($poll);

        $response = new \Laminas\Diactoros\Response(headers: [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
        $response->getBody()->write(json_encode([
            'poll' => $poll->toArray(), // TODO FIXME should we resolve created_by / last_modified_by? also for public usage?
            'events' => $events->toArray(),
        ]));

        return $response;
    }

    public static function publicPostPoll(string $pollId, string $participantId): ResponseInterface
    {
        Tinebase_Core::getLogger()->debug('Poll posted');
        /** @var \Psr\Http\Message\RequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

        if (null === ($pollReplyJson = json_decode($request->getBody()->getContents(), true))) {
            throw new Tinebase_Exception_Expressive_HttpStatus('no json body', 400);
        }
        $pollReply = new CrewScheduling_Model_PollReply(_bypassFilters: true);
        $pollReply->setFromJsonInUsersTimezone($pollReplyJson);

        $poll = CrewScheduling_Controller_Poll::getInstance()->get($pollId);
        if (!($participant = $poll->{CrewScheduling_Model_Poll::FLD_PARTICIPANTS}->getById($participantId))) {
            throw new Tinebase_Exception_AccessDenied('not allowed');
        }
        // if participant is an account, current user needs to be that account
        if (Addressbook_Model_Contact::CONTACTTYPE_USER === $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->type) {
            if (null === Tinebase_Core::getUser() || (Tinebase_Core::getUser()->getId() !== $participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->getIdFromProperty('account_id') &&
                !CrewScheduling_Controller_Poll::getInstance()->checkGrant($poll, CrewScheduling_Model_SchedulingRoleGrants::MANAGE_POLL, false))
            ) {
                throw new Tinebase_Exception_AccessDenied('not allowed');
            }
        } else { // if participant is not an account only annonymous usage!
            if (null === Tinebase_Core::getUser()) {
                Tinebase_Core::setUser(Tinebase_User::getInstance()->getFullUserById($poll->getIdFromProperty('created_by')));
            }
        }
        $transaction = Tinebase_RAII::getTransactionManagerRAII();

        $pollReply->{CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID} = $participant->getId();
        $pollReplyCtrl = CrewScheduling_Controller_PollReply::getInstance();
        if (!$pollReply->getId() || !$pollReplyCtrl->has([$pollReply->getId()])) {
            $pollReplyCtrl->create($pollReply);
        } else {
            $pollReplyCtrl->update($pollReply);
        }

        $transaction->release();

        $response = new \Laminas\Diactoros\Response(headers: [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
        $response->getBody()->write(json_encode([
            'success' => true,
        ]));

        return $response;
    }

    /**
     * @param Felamimail_Model_Message $_message
     * @return null
     */
    public function prepareMassMailingMessage(Felamimail_Model_Message $_message, Tinebase_Twig $_twig)
    {
        return CrewScheduling_Controller_Poll::getInstance()->prepareMassMailingMessage($_message, $_twig);
    }

    /**
     * Get response for corrected poll URL
     *
     * @param Tinebase_Record_Interface $poll
     * @return ResponseInterface
     * @throws Tinebase_Exception_AccessDenied
     */
    private static function getFixedParticipantResponse(Tinebase_Record_Interface $poll): ResponseInterface
    {
        $user = Tinebase_Core::getUser();
        $newParticipantId = null;
        foreach ($poll->participants as $participant) {
            if ($participant->{CrewScheduling_Model_PollParticipant::FLD_CONTACT}->getId() === $user->contact_id) {
                $newParticipantId = $participant->getId();
            }
        }
        if (null === $newParticipantId) {
            throw new Tinebase_Exception_AccessDenied('not allowed');
        }

        $response = new \Laminas\Diactoros\Response(headers: [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
        $response->getBody()->write(json_encode([
            'participantId' => $newParticipantId,
        ]));

        // See Other
        return $response->withStatus(303);
    }
}
