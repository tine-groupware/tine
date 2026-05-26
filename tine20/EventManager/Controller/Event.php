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
    /** @use Tinebase_Controller_SingletonTrait<EventManager_Controller_Event> */
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

    protected function _inspectAfterSetRelatedDataCreate($updatedRecord, $_record)
    {
        $this->_createImageWatermarks($updatedRecord);
    }

    public function _inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterSetRelatedDataUpdate($updatedRecord, $record, $currentRecord);
        $this->_createImageWatermarks($updatedRecord);

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

        $mainScreen = Tinebase_Frontend_Http_SinglePageApplication::getClientHTML(
            $jsFiles,
            EventManager_Config::APP_NAME,
            context: $context
        );

        return $mainScreen->withHeader(
            "Content-Security-Policy",
            preg_replace(
                '/frame-ancestors.*;?/',
                'frame-ancestors *;',
                implode(" ", $mainScreen->getHeader('Content-Security-Policy'))
            )
        );
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

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                EventManager_Model_Event::class,
                [
                    [
                        'field' => EventManager_Model_Event::FLD_STATUS,
                        'operator' => 'equals',
                        'value' => '1' // active events
                    ],
                ],
            );
            $eventListOfRecords = EventManager_Controller_Event::getInstance()
                ->search($filter);
            $events = $eventListOfRecords->getFirstRecord();
            $converter = Tinebase_Convert_Factory::factory($events);
            $eventArray = $converter->fromTine20RecordSet($eventListOfRecords);
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

    public function publicApiGetEvent($event_id, $token = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $event = $this->get($event_id);
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($event);

            $converter = Tinebase_Convert_Factory::factory($event);
            $eventArray = $converter->fromTine20Model($event);

            if (!empty($eventArray['contact_fields']) && is_array($eventArray['contact_fields'])) {
                $contactModelConfig = Addressbook_Model_Contact::getConfiguration();
                $fields = $contactModelConfig->getFields();

                $enriched = [];
                $requiredContactFields = [];
                foreach ($eventArray['contact_fields'] as $fieldName => $fieldConfig) {
                    $optional = isset($fieldConfig['optional']) ? (bool)$fieldConfig['optional'] : false;
                    $required = isset($fieldConfig['required']) ? (bool)$fieldConfig['required'] : false;

                    $enriched[$fieldName] = [
                        'optional' => $optional,
                        'required' => $required,
                        'label'    => isset($fields[$fieldName]['label'])
                            ? Tinebase_Translation::getTranslation('Addressbook')
                                ->translate($fields[$fieldName]['label'])
                            : $fieldName,
                    ];

                    if ($required) {
                        $requiredContactFields[] = $fieldName;
                    }
                }
                $eventArray['contact_fields'] = $enriched;
                $eventArray['required_contact_fields'] = $requiredContactFields;
            }
            $eventArray['country_list'] = Tinebase_Translation::getCountryList()['results'];
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
                    $registrations_data = [];
                    $registrationIds = [];
                    $accountOwner = [];

                    $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                        EventManager_Model_Register_Contact::class,
                        [
                            [
                                'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE,
                                'operator' => 'equals',
                                'value' => 'participant'
                            ],
                        ],
                    );
                    $registerContacts = EventManager_Controller_Register_Contact::getInstance()
                        ->search($filter);

                    foreach ($registerContacts as $registerContact) {
                        $regId = $registerContact->registration_id;
                        if (!in_array($regId, $registrationIds)) {
                            $registrationIds[] = $regId;
                        }
                        if (count($accountOwner) === 0 && $registerContact->email === $email_registrant) {
                            $accountOwner[] = $registerContact->toArray();
                        }
                    }

                    foreach ($registrationIds as $registrationId) {
                        $registration = EventManager_Controller_Registration::getInstance()
                            ->get($registrationId);
                        $status = EventManager_Config::getInstance()
                            ->get(EventManager_Config::REGISTRATION_STATUS)->records
                            ->getById($registration->{EventManager_Model_Registration::FLD_STATUS});
                        $registration->{EventManager_Model_Registration::FLD_STATUS} = $status->value;
                        if (
                            $registration->{EventManager_Model_Registration::FLD_REGISTRANT}
                                ->email === $email_registrant
                        ) {
                            $registrations_data[] = $registration->toArray();
                        }
                    }

                    if (count($registrations_data) === 0) {
                        $registrations_data = $contact->toArray();
                    }

                    if (!empty($contact)) {
                        $contact = Addressbook_Controller_Contact::getInstance()
                            ->get($contact->getId()); // necessary to get relations
                        $dependant_participant = $this->getRelatedContacts($contact);
                    } else {
                        $registrant = [
                            'email' => $email_registrant,
                        ];
                        $registrations_data = $registrant;
                    }

                    if (count($accountOwner) === 0 && !empty($contact)) {
                        $accountOwner[] = $contact->toArray();
                    }

                    $response = new \Laminas\Diactoros\Response();
                    $response->getBody()->write(json_encode([
                        $accountOwner,
                        $registrations_data,
                        $dependant_participant
                    ]));
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
            $related_contacts[] = Addressbook_Controller_Contact::getInstance()
                ->get($related_contact->related_id)->toArray();
        }
        return $related_contacts;
    }

    protected function _createImageWatermarks(EventManager_Model_Event $event): void
    {
        // TODO do we always want to overwrite? we would need to check if source changed...
        $overwrite = true;
        foreach ($event->images as $image) {
            $imageAttachment = $event->attachments->getById($image->{EventManager_Model_ImageMetadata::FLD_NODE_ID});
            if (!$imageAttachment) {
                // attachment already deleted - do nothing
                continue;
            }
            $node = Tinebase_FileSystem::getInstance()->get($image->{EventManager_Model_ImageMetadata::FLD_NODE_ID});
            Tinebase_ActionQueue::getInstance()->queueAction(
                'Tinebase_FileSystem_RecordAttachments.createWatermark',
                $node,
                $image->source,
                $overwrite
            );
        }
    }

    public function publicApiGetImages()
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $response = new \Laminas\Diactoros\Response();
            $images = EventManager_Controller_ImageMetadata::getInstance()->search();
            foreach ($images as $image) {
                $image->image_vfs = EventManager_Controller_ImageMetadata::getImageUrl(
                    EventManager_Config::APP_NAME,
                    $image->node_id,
                    -1,
                    -1
                );
            }
            $imagesArray = [];
            foreach ($images as $image) {
                $converter = Tinebase_Convert_Factory::factory($image);
                $imagesArray[] = $converter->fromTine20Model($image);
            }
            $response->getBody()->write(json_encode($imagesArray));
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

    public function publicApiGetImage($imageId, $width = -1, $height = -1, $ratiomode = 0)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $image = Tinebase_Controller::getInstance()->getImage('Tinebase', $imageId, Tinebase_Model_Image::LOCATION_VFS_WATERMARK);
            if ($width != -1 && $height != -1) {
                Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
            }
            $response = new \Laminas\Diactoros\Response(headers: ['Content-Type' => $image->mime]);
            $response->getBody()->write($image->blob);
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
