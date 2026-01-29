<?php

declare(strict_types=1);

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de> Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2020-2025 Metaways Infosystems GmbH (https://www.metaways.de)
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

    protected static array $updateStatisticsCache = [];

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

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        if ($_record->{EventManager_Model_Event::FLD_TOTAL_PLACES}) {
            $_record->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} =
                $_record->{EventManager_Model_Event::FLD_TOTAL_PLACES};
        }
    }

    public function updateStatistics(string $event_id, ?string $registration_id = null, bool $is_update = false): void
    {
        $this->_handleDependentRecords = false;
        try {
            if (static::$updateStatisticsCache[$event_id] ?? false) {
                return;
            }
            static::$updateStatisticsCache[$event_id] = true;

            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(fn()
            => $this->clearUpdateStatisticsCache());

            $event = $this->get($event_id);
            $registrations = $event->{EventManager_Model_Event::FLD_REGISTRATIONS};
            $accepted_registrations = [];
            foreach ($registrations as $registration) {
                if (
                    $registration->is_deleted !== 1
                    && $registration->{EventManager_Model_Registration::FLD_STATUS} !== "3"
                ) {
                    $accepted_registrations[] = $registration;
                }
            }

            $event->{EventManager_Model_Event::FLD_BOOKED_PLACES} = count($accepted_registrations);
            $event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} =
                intval($event->{EventManager_Model_Event::FLD_TOTAL_PLACES}) -
                intval($event->{EventManager_Model_Event::FLD_BOOKED_PLACES});
            $today = Tinebase_DateTime::today();

            if (
                $event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} < 0
                || ($event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL}
                && $event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL} < $today)
            ) {
                foreach ($registrations as $registration) {
                    if (
                        $registration->id === $registration_id
                        && $registration->{EventManager_Model_Registration::FLD_STATUS} !== "3"
                        && $registration->{EventManager_Model_Registration::FLD_STATUS} !== "2"
                    ) {
                        if ($event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} < 0) {
                            $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
                            $registration->{EventManager_Model_Registration::FLD_REASON_WAITING} = 1;
                        } elseif ($event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL} < $today) {
                            if (!$is_update) {
                                $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
                            }
                            $registration->{EventManager_Model_Registration::FLD_REASON_WAITING} = 2;
                        } else {
                            $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
                            $registration->{EventManager_Model_Registration::FLD_REASON_WAITING} = 3;
                        }
                        EventManager_Controller_Registration::getInstance()->update($registration);
                    }
                }
            }
            $this->update($event);
        } catch (Tinebase_Exception_NotFound $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . 'Record should not be found when deleted: ' . $e->getMessage());
            }
        } finally {
            $this->_handleDependentRecords = true;
        }
    }

    private function clearUpdateStatisticsCache(): void
    {
        static::$updateStatisticsCache = [];
    }

    public function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);

        // check if $currentrecord had options that have been deleted in $record
        // - those need to be removed from registrations
        $diff = $currentRecord->{EventManager_Model_Event::FLD_OPTIONS}
            ->diff($updatedRecord->{EventManager_Model_Event::FLD_OPTIONS});
        foreach ($diff->removed as $removed_option) {
            foreach ($updatedRecord->{EventManager_Model_Event::FLD_REGISTRATIONS} as $registration) {
                foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $booked_option) {
                    $option = $booked_option->{EventManager_Model_BookedOption::FLD_OPTION};
                    $option_id = is_object($option) ? $option->getId() : $option;
                    if ($removed_option->getId() === $option_id) {
                        $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}
                            ->removeRecord($booked_option);
                        EventManager_Controller_Registration::getInstance()->update($registration);
                    }
                }
            }
        }
    }


    public function publicApiMainScreen($path = null)
    {
        $locale = Tinebase_Core::getLocale();
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=EventManager";

        $context = [
            'lang' => $locale,
        ];

        $jsFiles[] = 'EventManager/js/eventManagerWebsite/src/index.es6.js';
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, EventManager_Config::APP_NAME, context: $context);
    }

    public function publicApiStatic()
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode(['success' => true]));
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

    public function publicApiEvents()
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

    public function publicApiGetEvent($event_id, $token = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $event = $this->get($event_id);
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

    public function publicApiGetAccountDetails($token)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            if ($token) {
                if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                    throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
                }
                try {
                    $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                    $email_registrant = $decoded->email ?? '';
                    $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email_registrant);
                    $dependant_participant = [];

                    if (!empty($contact)) {
                        $contact = Addressbook_Controller_Contact::getInstance()
                            ->get($contact->getId()); // necessary to get relations
                        $dependant_participant = $this->getRelatedContacts($contact);

                        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                            EventManager_Model_Register_Contact::class,
                            [
                                [
                                    'field' => EventManager_Model_Register_Contact::FLD_ORIGINAL_ID,
                                    'operator' => 'equals',
                                    'value' => $contact->getId()
                                ],
                            ],
                        );
                        $registerContacts = EventManager_Controller_Register_Contact::getInstance()
                            ->search($filter);

                        $registrationIds = [];
                        foreach ($registerContacts as $registerContact) {
                            $regId = $registerContact->registration_id;
                            if (!in_array($regId, $registrationIds)) {
                                $registrationIds[] = $regId;
                            }
                        }

                        $registrations_data = [];

                        foreach ($registrationIds as $registrationId) {
                            $registration = EventManager_Controller_Registration::getInstance()
                                ->get($registrationId);
                            $status = EventManager_Config::getInstance()
                                ->get(EventManager_Config::REGISTRATION_STATUS)->records
                                ->getById($registration->{EventManager_Model_Registration::FLD_STATUS});
                            $registration->{EventManager_Model_Registration::FLD_STATUS} = $status->value;
                            $registrationArray = $registration->toArray();

                            $participantFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                                EventManager_Model_Register_Contact::class,
                                [
                                    [
                                        'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_ID,
                                        'operator' => 'equals',
                                        'value' => $registration->getId()
                                    ],
                                    [
                                        'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE,
                                        'operator' => 'equals',
                                        'value' => 'participant'
                                    ],
                                ],
                            );
                            $participants = EventManager_Controller_Register_Contact::getInstance()
                                ->search($participantFilter);

                            if ($participants->count() > 0) {
                                $participant = $participants->getFirstRecord();
                                $registrationArray['participant'] = $participant->toArray();
                            }

                            $registrantFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                                EventManager_Model_Register_Contact::class,
                                [
                                    [
                                        'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_ID,
                                        'operator' => 'equals',
                                        'value' => $registration->getId()
                                    ],
                                    [
                                        'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE,
                                        'operator' => 'equals',
                                        'value' => 'registrant'
                                    ],
                                ],
                            );
                            $registrants = EventManager_Controller_Register_Contact::getInstance()
                                ->search($registrantFilter);

                            if ($registrants->count() > 0) {
                                $registrant = $registrants->getFirstRecord();
                                $registrationArray['registrant'] = $registrant->toArray();
                            }

                            $registrations_data[] = $registrationArray;
                        }

                        if (count($registrations_data) === 0) {
                            $registrations_data = $contact->toArray();
                        }
                    } else {
                        $registrant = [
                            'email' => $email_registrant,
                        ];
                        $registrations_data = $registrant;
                    }

                    $response = new \Laminas\Diactoros\Response();
                    $response->getBody()->write(json_encode([$registrations_data, $dependant_participant]));
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

    public function getRelatedContacts($contact)
    {
        $related_contacts = [];
        foreach ($contact->relations as $related_contact) {
            $related_contacts[] = Addressbook_Controller_Contact::getInstance()->get($related_contact->related_id)->toArray();
        }
        return $related_contacts;
    }
}
