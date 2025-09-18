<?php
/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Firebase\JWT\JWT;

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_Registration extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = EventManager_Model_Registration::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EventManager_Model_Registration::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EventManager_Model_Registration::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => false
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        $this->_handleRegistrationFileUpload($_record);
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord)
    {
        parent::_inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord);
        $this->_handleRegistrationFileUpload($_updatedRecord);
    }

    public function _handleRegistrationFileUpload(EventManager_Model_Registration $_registration)
    {
        if (!$_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}) {
            return;
        }

        foreach ($_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
            if (!$bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS} === EventManager_Model_FileOption::class) {
                continue;
            }

            $nodeId = $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                ->{EventManager_Model_Selections_File::FLD_NODE_ID};

            if (!is_string($nodeId)) {
                continue;
            }

            $fileName = $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                ->{EventManager_Model_Selections_File::FLD_FILE_NAME};

            $eventId = $_registration->{EventManager_Model_Registration::FLD_EVENT_ID};

            // Build participant-specific folder path
            $participantId = $_registration->{EventManager_Model_Registration::FLD_NAME};
            $participantName = $participantId;
            try {
                $participantName = Addressbook_Controller_Contact::getInstance()->get($participantId)->n_fileas;
            } catch (Tinebase_Exception_NotFound $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Exception: ' . $e->getMessage());
                }
            }

            $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            $folderPath = ['/' . $translation->_('Registrations'), "/$participantName"];

            $result = EventManager_Controller::processFileUpload($nodeId, $fileName, $eventId, $folderPath);

            // necessary to update nodeId to match id from tree_nodes and not temp file
            if ($result !== false) {
                $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_NODE_ID} = $result->getId();
                $event = EventManager_Controller_Event::getInstance()->get($eventId);
                foreach ($event->{EventManager_Model_Event::FLD_REGISTRATIONS} as $registration) {
                    if ($registration->id === $_registration->id) {
                        foreach (
                            $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption
                        ) {
                            $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                                ->{EventManager_Model_Selections_File::FLD_NODE_ID} = $result->getId();
                        }
                    }
                    EventManager_Controller_Event::getInstance()->update($event);
                }
            }
        }
    }

    /**
     * overwrite create function from Tinebase_Controller_Record_Abstract to add custom fields
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $record = parent::create($_record, $_duplicateCheck);
        foreach ($record->booked_options as $booked_option) {
            if ($booked_option->selection_config->booked) {
                if (
                     isset($booked_option->option->option_config->available_places)
                    && isset($booked_option->option->option_config->booked_places)
                ) {
                    $booked_option->option->option_config->booked_places++;
                    $booked_option->option->option_config->available_places--;
                    EventManager_Controller_Option::getInstance()->update($booked_option->option);
                }
            }
        }
        return $record;
    }

    /**
     * overwrite create function from Tinebase_Controller_Record_Abstract to update custom fields
     *
     * @param Tinebase_Record_Interface $_record
     * @param boolean $_duplicateCheck
     * @param boolean $_updateDeleted
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     *
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = true, $_updateDeleted = false)
    {
        $record_old = $this->get($_record->getId());
        $record = parent::update($_record, $_duplicateCheck, $_updateDeleted);
        $still_existing = [];
        $already_existing = false;
        if (!$record->booked_options) {
            $record->booked_options = [];
        }
        foreach ($record->booked_options as $booked_option) {
            if (
                isset($booked_option->option->option_config->available_places)
                && isset($booked_option->option->option_config->booked_places)
                && isset($booked_option->id)
                && isset($booked_option->selection_config)
            ) {
                // Value of existing booking has changed
                foreach ($record_old->booked_options as $old_booked_option) {
                    if ($old_booked_option->id == $booked_option->id) {
                        $old_booked = $old_booked_option->selection_config->booked;
                        $booked = $booked_option->selection_config->booked;
                        if ($booked !== $old_booked) {
                            if ($booked) {
                                $booked_option->option->option_config->booked_places++;
                                $booked_option->option->option_config->available_places--;
                            } else {
                                $booked_option->option->option_config->booked_places--;
                                $booked_option->option->option_config->available_places++;
                            }
                            try {
                                EventManager_Controller_Option::getInstance()->update($booked_option->option);
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                        . ' ' . $tenf->getMessage());
                                }
                            }
                        }
                        $already_existing = true;
                        $still_existing[] = $old_booked_option;
                    }
                }
                // New booking was added
                if (!$already_existing && $booked_option->selection_config->booked) {
                    if (!is_string($booked_option->option)) {
                        $booked_option->option->option_config->booked_places++;
                        $booked_option->option->option_config->available_places--;
                        try {
                            EventManager_Controller_Option::getInstance()->update($booked_option->option);
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                    . ' ' . $tenf->getMessage());
                            }
                        }
                    }
                }
            }
        }

        // Booked option was deleted
        foreach ($record_old->booked_options as $old_booked_option) {
            if (!array_key_exists($old_booked_option->option->id, $still_existing)) {
                try {
                    $option = EventManager_Controller_Option::getInstance()->get($old_booked_option->option->id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' ' . $tenf->getMessage());
                    }
                    continue;
                }
                if (
                    !empty($option->getData())
                    && isset($option->option_config->available_places)
                    && isset($option->option_config->booked_places)
                    && !$already_existing
                ) {
                    $option->option_config->booked_places--;
                    $option->option_config->available_places++;
                    EventManager_Controller_Option::getInstance()->update($option);
                }
            }
        }
        return $record;
    }

    /**
     * Deletes a set of records.
     *
     * If one of the records could not be deleted, no record is deleted
     *
     * @param  array|Tinebase_Record_Interface|Tinebase_Record_RecordSet $_ids array of record identifiers
     * @return Tinebase_Record_RecordSet
     * @throws Exception
     */
    public function delete($_ids)
    {
        $records = parent::delete($_ids);
        foreach ($records as $record) {
            foreach ($record->booked_options as $booked_option) {
                if (
                    isset($booked_option->option->option_config->available_places)
                    && isset($booked_option->option->option_config->booked_places)
                ) {
                    $booked_option->option->option_config->booked_places--;
                    $booked_option->option->option_config->available_places++;
                    EventManager_Controller_Option::getInstance()->update($booked_option->option);
                }
            }
        }
        return $records;
    }

    public function publicApiGetFile($nodeId)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $fileSystem = Tinebase_FileSystem::getInstance();
            $file = $fileSystem->get($nodeId);

            $filename = $fileSystem->getPathOfNode($file, true);
            $handle = $fileSystem->fopen($filename, 'r', $file->revision);

            if (false === $handle) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not open file by real path for file path ' . $filename);
                }
                throw new Tinebase_Exception_NotFound('Could not open file ' . $filename);
            }

            $content = fread($handle, $file->revision_size);
            fclose($handle);

            $response = new \Laminas\Diactoros\Response(headers: ['Content-Type' => $file->contenttype]);
            $response->getBody()->write($content);
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

    public function publicApiPostRegistration($eventId)
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);
            $response = new \Laminas\Diactoros\Response();

            $abContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($request['email']);
            $contact = null;

            if (empty($abContact)) {
                try {
                    $contactData = array_map(function ($value) {
                        return $value;
                    }, $request['contactDetails']);
                    $contactData['container_id'] = EventManager_Config::getInstance()
                        ->get(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER);

                    $contact = new Addressbook_Model_Contact($contactData);
                    $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
                } catch (Tinebase_Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Exception: ' . $e->getMessage());
                    }
                }
            } else {
                foreach ($request['contactDetails'] as $field => $value) {
                    if ($abContact->has($field)) {
                        $abContact->$field = $value;
                    }
                }
                $abContact->container_id = $abContact->getContainerId();
                $contact = Addressbook_Controller_Contact::getInstance()->update($abContact);
            }
            $attendee = EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_FUNCTION);
            $attendee = $attendee->records->getById('1');
            $online = EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_SOURCE);
            $online = $online->records->getById('1');
            $waitingList = EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_STATUS);
            $waitingList = $waitingList->records->getById('2');
            $options = $request['replies'];
            $bookedOption = [];
            foreach ($options as $optionId => $reply) {
                $option = EventManager_Controller_Option::getInstance()->get($optionId);
                if ($option->option_config_class === EventManager_Model_CheckboxOption::class) {
                    $selection_config = new EventManager_Model_Selections_Checkbox([
                        'booked' => boolval($reply),
                    ], true);
                    $bookedOption[] = new EventManager_Model_BookedOption([
                        'event_id' => $request['eventId'],
                        'option' => $option->getId(),
                        'selection_config' => $selection_config,
                        'selection_config_class' => EventManager_Model_Selections_Checkbox::class,
                    ], true);
                } elseif ($option->option_config_class === EventManager_Model_TextInputOption::class) {
                    $selection_config = new EventManager_Model_Selections_TextInput([
                        'response' => $reply,
                    ], true);
                    $bookedOption[] = new EventManager_Model_BookedOption([
                        'event_id' => $request['eventId'],
                        'option' => $option->getId(),
                        'selection_config' => $selection_config,
                        'selection_config_class' => EventManager_Model_Selections_TextInput::class,
                    ], true);
                } elseif ($option->option_config_class === EventManager_Model_FileOption::class) {
                    if (
                        isset($option->option_config->file_acknowledgement)
                        && $option->option_config->file_acknowledgement
                    ) {
                        $selection_config = new EventManager_Model_Selections_File([
                            'file_acknowledgement' => boolval($reply),
                        ], true);
                        $bookedOption[] = new EventManager_Model_BookedOption([
                            'event_id' => $request['eventId'],
                            'option' => $option->getId(),
                            'selection_config' => $selection_config,
                            'selection_config_class' => EventManager_Model_Selections_File::class,
                        ], true);
                    }
                }
            }
            $registration = new EventManager_Model_Registration([
                'event_id'          => EventManager_Controller_Event::getInstance()->get($eventId),
                'name'              => $contact,
                'function'          => $attendee,
                'source'            => $online,
                'status'            => $waitingList,
                'booked_options'    => $bookedOption,
                'description'       => '',
            ], true);
            $registration = $this->create($registration);
            $response->getBody()->write(json_encode($registration->toArray()));
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($terv->getMessage()));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiPostFileToFileManager($eventId, $optionId, $registrationId)
    {
        $assertAclUsage = $this->assertPublicUsage();
        header('Content-Type: application/json');
        try {
            $response = new \Laminas\Diactoros\Response();
            if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
                $fileCount = count($_FILES['files']['name']);
                $registration = $this->get($registrationId);
                $bookedOption = $registration->booked_options;
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $path = Tinebase_TempFile::getTempPath();
                        file_put_contents($path, $_FILES['files']['name'][$i]);
                        $tempFile = Tinebase_TempFile::getInstance()->createTempFile(
                            $path,
                            $_FILES['files']['name'][$i],
                            $_FILES['files']['type'][$i],
                            $_FILES['files']['size'][$i],
                            $_FILES['files']['error'][$i]
                        );
                        $selection_config = new EventManager_Model_Selections_File([
                            'node_id'   => $tempFile->getId(),
                            'file_name' => $_FILES['files']['name'][$i],
                            'file_type' => $_FILES['files']['type'][$i],
                            'file_size' => $_FILES['files']['size'][$i],
                        ], true);
                        $bookedOption[] = new EventManager_Model_BookedOption([
                            'event_id' => $eventId,
                            'option' => $optionId,
                            'selection_config' => $selection_config,
                            'selection_config_class' => EventManager_Model_Selections_File::class,
                        ], true);
                    }
                }
                $registration->booked_options = $bookedOption;
                $registration = $this->update($registration);
                $response->getBody()->write(json_encode($registration->toArray()));
            }
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($terv->getMessage()));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiPostDoubleOptIn($eventId)
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);

            if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                $e = new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
                Tinebase_Exception::log($e);
                throw $e;
            }

            $token = JWT::encode([
                'email' => $request['email'],
                'n_given'  => $request['n_given'],
                'n_family'  => $request['n_family'],
            ], $key, 'HS256');

            if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $request['email'])) {
                $abContact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($request['email']);
                if (!empty($abContact)) {
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
                                'value' => $abContact
                            ],
                        ],
                    );
                    $registerParticipant = $this->getInstance()->search($filter)->getFirstRecord();
                    if (!empty($registerParticipant)) {
                        $link = '/EventManager/view/#/event/' . $request['eventId'] . '/registration/' . $token;
                        $template = 'SendManageRegistrationLink';
                    } else {
                        $link = '/EventManager/view/#/event/' . $request['eventId'] . '/registration/' . $token;
                        $template = 'SendRegistrationLink';
                    }
                } else {
                    $link = '/EventManager/view/#/event/' . $request['eventId'] . '/registration/' . $token;
                    $template = 'SendRegistrationLink';
                }
                $this->_sendMessageWithTemplate($template, [
                    'link' => Tinebase_Core::getUrl() . $link,
                    'userName' => $request['name'],
                    'email' => $request['email'],
                ]);
            }
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (Exception $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($e->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    protected function _sendMessageWithTemplate($templateFileName, $context = [])
    {
        $locale = Tinebase_Core::getLocale();

        $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME));
        $htmlTemplate = $twig
            ->load(EventManager_Config::APP_NAME . '/views/emails/' . $templateFileName . '.html.twig');
        $textTemplate = $twig
            ->load(EventManager_Config::APP_NAME . '/views/emails/' . $templateFileName . '.text.twig');

        $html = $htmlTemplate->render($context);
        $text = $textTemplate->render($context);
        $subject = $htmlTemplate->renderBlock('subject', $context);
        $updater = Tinebase_Core::getUser();

        // using Tinebase_Notification_Backend_Smtp send method, but changing recipients,
        // they don't need to be contacts in this case
        $mail = new Tinebase_Mail('UTF-8');
        $mail->setSubject($subject);
        $mail->setBodyText($text);
        $mail->setBodyHtml($html);

        $mail->addHeader('X-Tine20-Type', 'Notification');
        $mail->addHeader('Precedence', 'bulk');
        $mail->addHeader('User-Agent', Tinebase_Core::getTineUserAgent('Notification Service'));

        $fromAddress = Tinebase_Notification_Backend_Smtp::getFromAddress();
        $fromName = 'Tine 2.0 notification service';

        if (empty($fromAddress)) {
            Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' No notification service address set. Could not send notification.'
            );
            return;
        }

        if ($updater !== null && ! empty($updater->accountEmailAddress)) {
            $mail->setFrom($updater->accountEmailAddress, $updater->accountFullName);
            $mail->setSender($fromAddress, $fromName);
        } else {
            $mail->setFrom($fromAddress, $fromName);
        }

        $preferredEmailAddress = $context['email'];

        // send
        if (! empty($preferredEmailAddress)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Send notification email to ' . $preferredEmailAddress
                );
            }
            $mail->addTo($preferredEmailAddress, $context['name']);
            try {
                Tinebase_Smtp::getInstance()->sendMessage($mail);
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                // TODO check Felamimail - there is a similar error handling. should be generalized!
                if (preg_match('/^5\.1\.1/', $zmpe->getMessage())) {
                    // User unknown in virtual mailbox table
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(
                            __METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage()
                        );
                    }
                } elseif (preg_match('/^5\.1\.3/', $zmpe->getMessage())) {
                    // Bad recipient address syntax
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                        Tinebase_Core::getLogger()->warn(
                            __METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage()
                        );
                    }
                } else {
                    throw $zmpe;
                }
            }
        } else {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Not sending notification email to ' . $context['name'] . '. No email address available.');
        }
    }
}
