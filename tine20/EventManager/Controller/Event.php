<?php
/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Firebase\JWT\JWT;

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_Event extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
        $this->_modelName = EventManager_Model_Event::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EventManager_Model_Event::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EventManager_Model_Event::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    public function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);

        // check if $currentrecord had options that have been deleted in $record
        // - those need to be removed from registrations
        $diff = $currentRecord->{EventManager_Model_Event::FLD_OPTIONS}
            ->diff($updatedRecord->{EventManager_Model_Event::FLD_OPTIONS});
        foreach ($diff->removed as $removedOption) {
            foreach ($currentRecord->{EventManager_Model_Event::FLD_REGISTRATIONS} as $registration) {
                foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
                    if (
                        $removedOption->getId() ===
                        $bookedOption->{EventManager_Model_BookedOption::FLD_OPTION}->getId()
                    ) {
                        $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}
                            ->removeRecord($bookedOption);
                        EventManager_Controller_Registration::getInstance()->update($registration);
                    }
                }
            }
        }
    }


    public function publicApiMainScreen()
    {
        $locale = Tinebase_Core::getLocale();
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=EventManager";
        $jsFiles[] = 'EventManager/js/eventManagerWebsite/src/index.es6.js';
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles);
    }

    public function publicApiSearchEvents()
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $events = $this->search();
            $events = $events->toArray();
            $response->getBody()->write(json_encode($events));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetEvent($eventId)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $event = $this->get($eventId);
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($event);

            $converter = Tinebase_Convert_Factory::factory($event);
            $eventArray = $converter->fromTine20Model($event);

            $response->getBody()->write(json_encode($eventArray));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetEventContactDetails($token, $eventId)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            if ($token) {
                if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                    throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
                }
                try {
                    $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                    $emailParticipant = $decoded->email ?? '';
                    $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($emailParticipant);
                    $registrationId = '';

                    if (!empty($contact)) {
                        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                            EventManager_Model_Registration::class,
                            [
                                [
                                    'field' => EventManager_Model_Registration::FLD_EVENT_ID,
                                    'operator' => 'equals',
                                    'value' => $eventId
                                ],
                                [
                                    'field' => EventManager_Model_Registration::FLD_NAME,
                                    'operator' => 'equals',
                                    'value' => $contact
                                ],
                            ],
                        );
                        $registerParticipant = EventManager_Controller_Registration::getInstance()
                            ->search($filter)->getFirstRecord();
                        if (!empty($registerParticipant)) {
                            $registrationId = $registerParticipant->getId();
                        }
                        $participantData = [$contact->toArray(), $registrationId];
                        $response = new \Laminas\Diactoros\Response();
                        $response->getBody()->write(json_encode($participantData));
                    }

                    if (empty($contact)) {
                        $contact = [
                            'email' => $emailParticipant,
                            'n_given' => $decoded->n_given ?? '',
                            'n_family' => $decoded->n_family ?? '',
                        ];
                        $participantData = [$contact, $registrationId];
                        $response = new \Laminas\Diactoros\Response();
                        $response->getBody()->write(json_encode($participantData));
                    }
                    return $response;
                } catch (Exception $jwtException) {
                    // Invalid or expired token
                    $response = new \Laminas\Diactoros\Response('php://memory', 400);
                    $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
                    return $response;
                }
            }
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode([]));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }
}
