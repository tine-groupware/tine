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

    public function updateStatistics(string $event_id, string $registration_id = null): void
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

            if ($event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} < 0) {
                foreach ($registrations as $registration) {
                    if (
                        $registration->id === $registration_id
                        && !($registration->{EventManager_Model_Registration::FLD_STATUS} === "3")
                    ) {
                        $registration->{EventManager_Model_Registration::FLD_STATUS} = 2;
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


    public function publicApiMainScreen()
    {
        $locale = Tinebase_Core::getLocale();
        $js_files[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=EventManager";
        $js_files[] = 'EventManager/js/eventManagerWebsite/src/index.es6.js';
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($js_files);
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

    public function publicApiGetEvent($event_id)
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

    public function publicApiGetEventContactDetails($token, $event_id)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            if ($token) {
                if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                    throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
                }
                try {
                    $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                    $email_participant = $decoded->email ?? '';
                    $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email_participant);
                    $registration_id = '';

                    if (!empty($contact)) {
                        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                            EventManager_Model_Registration::class,
                            [
                                [
                                    'field' => EventManager_Model_Registration::FLD_EVENT_ID,
                                    'operator' => 'equals',
                                    'value' => $event_id
                                ],
                                [
                                    'field' => EventManager_Model_Registration::FLD_NAME,
                                    'operator' => 'equals',
                                    'value' => $contact
                                ],
                            ],
                        );
                        $register_participant = EventManager_Controller_Registration::getInstance()
                            ->search($filter)->getFirstRecord();
                        if (!empty($register_participant)) {
                            $registration_id = $register_participant->getId();
                        }
                        $participant_data = [$contact->toArray(), $registration_id];
                        $response = new \Laminas\Diactoros\Response();
                        $response->getBody()->write(json_encode($participant_data));
                    }

                    if (empty($contact)) {
                        $contact = [
                            'email' => $email_participant,
                            'n_given' => $decoded->n_given ?? '',
                            'n_family' => $decoded->n_family ?? '',
                        ];
                        $participant_data = [$contact, $registration_id];
                        $response = new \Laminas\Diactoros\Response();
                        $response->getBody()->write(json_encode($participant_data));
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
