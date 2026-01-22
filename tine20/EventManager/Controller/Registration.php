<?php declare(strict_types=1);
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
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 *
 * @template T of Tinebase_Record_Interface
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
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
        $this->_duplicateCheckOnUpdate = true;
    }

    protected function _getDuplicateFilter(Tinebase_Record_Interface $_record)
    {
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
            [
                TMFA::FIELD => EventManager_Model_Registration::FLD_EVENT_ID,
                TMFA::OPERATOR => TMFA::OP_EQUALS,
                TMFA::VALUE => $_record->getIdFromProperty(EventManager_Model_Registration::FLD_EVENT_ID)
            ],
            [
                TMFA::FIELD => EventManager_Model_Registration::FLD_PARTICIPANT,
                TMFA::OPERATOR => 'definedBy',
                TMFA::VALUE => [
                    [TMFA::FIELD => EventManager_Model_Register_Contact::FLD_ORIGINAL_ID,
                        TMFA::OPERATOR => TMFA::OP_EQUALS,
                        TMFA::VALUE => $_record->{EventManager_Model_Registration::FLD_PARTICIPANT}
                            ->{EventManager_Model_Registration::FLD_ORIGINAL_ID}
                    ],
                    [TMFA::FIELD => EventManager_Model_Register_Contact::ID,
                        TMFA::OPERATOR => TMFA::OPERATOR_NOT,
                        TMFA::VALUE => $_record->{EventManager_Model_Registration::FLD_PARTICIPANT}->getId()
                    ],
                ]
            ],
        ]);
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);
        $this->_processBookedOptionsAfterCreate($_record);
        $this->_handleRegistrationFileUpload($_record);
        $this->_updateParentStatistics($_record);
    }

    protected function _inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord)
    {
        parent::_inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord);
        $this->_processBookedOptionsAfterUpdate($_updatedRecord, $_oldRecord);
        $this->_handleRegistrationFileUpload($_updatedRecord);
        if ($_updatedRecord->{EventManager_Model_Registration::FLD_STATUS} === "3") {
            foreach ($_updatedRecord->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
                if (
                    $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                    === "EventManager_Model_Selections_File"
                ) {
                    $ab_controller = Addressbook_Controller_Contact::getInstance();
                    $participantName = $ab_controller->get($_updatedRecord->name)->n_fileas;
                    $this->createDeregisteredFolder($bookedOption, $participantName);
                }
            }
        }
        $this->_updateParentStatistics($_updatedRecord, true);
    }

    protected function _inspectAfterDelete($_record)
    {
        parent::_inspectAfterDelete($_record);
        $this->_processBookedOptionsAfterDelete($_record);
        foreach ($_record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
            $participantName = $_record->name->n_fileas;
            $this->createDeregisteredFolder($bookedOption, $participantName);
        }
        $this->_updateParentStatistics($_record);
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  T
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        try {
            return parent::create($_record, $_duplicateCheck);
        } catch (Tinebase_Exception_Duplicate $ted) {
            $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            throw new Tinebase_Exception_SystemGeneric(
                $translate->_('It is not possible to add the same participant multiple times')
            );
        }
    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @param   boolean $_updateDeleted
     * @return  T
     * @throws  Tinebase_Exception_AccessDenied
     *
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = true, $_updateDeleted = false)
    {
        try {
            return parent::update($_record, $_duplicateCheck, $_updateDeleted);
        } catch (Tinebase_Exception_Duplicate $ted) {
            $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            throw new Tinebase_Exception_SystemGeneric(
                $translate->_('It is not possible to add the same participant multiple times')
            );
        }
    }

    public function _updateParentStatistics(EventManager_Model_Registration $_record, bool $is_update = false)
    {
        if ($is_update) { // relevant for waiting list
            Tinebase_TransactionManager::getInstance()->registerOnCommitCallback(
                function ($_record, $is_update = true) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Updating parent statistics...');
                    }
                    EventManager_Controller_Event::getInstance()
                        ->updateStatistics($_record->event_id, $_record->id, $is_update);
                },
                [$_record]
            );
        } else {
            Tinebase_TransactionManager::getInstance()->registerOnCommitCallback(
                function ($_record, $is_update = false) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Updating parent statistics...');
                    }
                    EventManager_Controller_Event::getInstance()
                        ->updateStatistics($_record->event_id, $_record->id, $is_update);
                },
                [$_record]
            );
        }
    }

    public function _handleRegistrationFileUpload(EventManager_Model_Registration $_registration)
    {
        if (!$_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}) {
            return;
        }

        foreach ($_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $booked_option) {
            if (
                !$booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                === EventManager_Model_FileOption::class
            ) {
                continue;
            }

            $node_id = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                ->{EventManager_Model_Selections_File::FLD_NODE_ID};

            if (!is_string($node_id)) {
                continue;
            }

            try {
                Tinebase_FileSystem::getInstance()->get($node_id);
            } catch (Tinebase_Exception_NotFound $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' First time uploading file, so it will not be found: ' . $e->getMessage());
                }

                $file_name = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_FILE_NAME};

                $event_id = $_registration->{EventManager_Model_Registration::FLD_EVENT_ID};

                // Build participant-specific folder path
                $participant_id = $_registration->{EventManager_Model_Registration::FLD_PARTICIPANT};
                $participant_name = $participant_id;
                try {
                    $participant_name = Addressbook_Controller_Contact::getInstance()->get($participant_id)->n_fileas;
                } catch (Tinebase_Exception_NotFound $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Exception: ' . $e->getMessage());
                    }
                }

                $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
                $folder_path = ['/' . $translation->_('Registrations'), "/$participant_name"];

                $result = EventManager_Controller::processFileUpload($node_id, $file_name, $event_id, $folder_path);

                // necessary to update nodeId to match id from tree_nodes and not temp file
                if ($result !== false) {
                    $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                        ->{EventManager_Model_Selections_File::FLD_NODE_ID} = $result->getId();
                    $event = EventManager_Controller_Event::getInstance()->get($event_id);
                    $option_id = $booked_option->{EventManager_Model_BookedOption::FLD_OPTION};
                    foreach ($event->{EventManager_Model_Event::FLD_REGISTRATIONS} as $registration) {
                        if ($registration->getId() === $_registration->getId()) {
                            foreach (
                                $registration
                                    ->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $event_bookedOption
                            ) {
                                if (
                                    $option_id === $event_bookedOption
                                        ->{EventManager_Model_BookedOption::FLD_OPTION}->getId()
                                ) {
                                    $event_bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                                        ->{EventManager_Model_Selections_File::FLD_NODE_ID} = $result->getId();
                                }
                            }
                        }
                    }
                    EventManager_Controller_Event::getInstance()->update($event);
                }
            }
        }
    }

    /**
     * Updates checkbox option places (booked/available counts)
     *
     * @param EventManager_Model_BookedOption $bookedOption
     * @param int $increment +1 to book, -1 to unbook
     * @return void
     */
    protected function _updateCheckboxOptionPlaces(
        EventManager_Model_BookedOption $bookedOption,
        int $increment
    ): void {
        $option = $bookedOption->{EventManager_Model_BookedOption::FLD_OPTION};

        if (is_string($option)) {
            $option = EventManager_Controller_Option::getInstance()->get($option);
        }

        $optionConfig = $option->{EventManager_Model_Option::FLD_OPTION_CONFIG};

        if (
            !isset($optionConfig->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES})
            || !isset($optionConfig->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES})
        ) {
            return;
        }

        if (
            $optionConfig->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES} === ''
            || $optionConfig->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES} === ''
        ) {
            $optionConfig->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES} = 0;
            $optionConfig->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES} = 0;
        }

        $optionConfig->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES} += $increment;
        $optionConfig->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES} -= $increment;

        try {
            EventManager_Controller_Option::getInstance()->update($option);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' ' . $tenf->getMessage());
            }
        }
    }

    /**
     * Processes booked options after registration creation
     * Books all checkbox options that are marked as booked
     *
     * @param EventManager_Model_Registration $registration
     * @return void
     */
    protected function _processBookedOptionsAfterCreate(EventManager_Model_Registration $registration): void
    {
        if (!$registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}) {
            return;
        }

        foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
            $selectionConfig = $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG};

            if (!$selectionConfig || !isset($selectionConfig->{EventManager_Model_Selections_Checkbox::FLD_BOOKED})) {
                continue;
            }

            if ($selectionConfig->{EventManager_Model_Selections_Checkbox::FLD_BOOKED}) {
                $this->_updateCheckboxOptionPlaces($bookedOption, 1);
            }
        }
    }

    /**
     * Processes booked options after registration update
     * Handles booking changes, new bookings, and removed bookings
     *
     * @param EventManager_Model_Registration $updatedRecord
     * @param EventManager_Model_Registration $oldRecord
     * @return void
     */
    protected function _processBookedOptionsAfterUpdate(
        EventManager_Model_Registration $updatedRecord,
        EventManager_Model_Registration $oldRecord
    ): void {
        $newOptions = $updatedRecord->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} ?: [];
        $oldOptions = $oldRecord->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} ?: [];
        $processedOldOptionIds = [];

        foreach ($newOptions as $bookedOption) {
            $option = $bookedOption->{EventManager_Model_BookedOption::FLD_OPTION};
            $optionId = is_object($option) ? $option->getId() : $option;

            if (!isset($option->id)) {
                continue;
            }

            $selectionConfig = $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG};
            if (!$selectionConfig || !isset($selectionConfig->{EventManager_Model_Selections_Checkbox::FLD_BOOKED})) {
                continue;
            }

            $oldBookedOption = $this->_findBookedOptionById($oldOptions, $optionId);

            if ($oldBookedOption) {
                $processedOldOptionIds[] = $optionId;
                $this->_handleBookingStatusChange($bookedOption, $oldBookedOption);
            } else {
                if ($selectionConfig->{EventManager_Model_Selections_Checkbox::FLD_BOOKED}) {
                    $this->_updateCheckboxOptionPlaces($bookedOption, 1);
                }
            }
        }

        foreach ($oldOptions as $oldBookedOption) {
            $option = $oldBookedOption->{EventManager_Model_BookedOption::FLD_OPTION};
            $optionId = is_object($option) ? $option->getId() : $option;

            if (!in_array($optionId, $processedOldOptionIds)) {
                $this->_unbookRemovedOption($optionId);
            }
        }
    }

    /**
     * Finds a booked option by option ID
     *
     * @param Tinebase_Record_RecordSet|array|null $bookedOptions
     * @param string $optionId
     * @return EventManager_Model_BookedOption|null
     */
    protected function _findBookedOptionById($bookedOptions, string $optionId): ?EventManager_Model_BookedOption
    {
        if (!$bookedOptions) {
            return null;
        }

        foreach ($bookedOptions as $bookedOption) {
            $option = $bookedOption->{EventManager_Model_BookedOption::FLD_OPTION};
            $currentOptionId = is_object($option) ? $option->getId() : $option;

            if ($currentOptionId === $optionId) {
                return $bookedOption;
            }
        }

        return null;
    }

    /**
     * Handles booking status changes between old and new booked options
     *
     * @param EventManager_Model_BookedOption $newBookedOption
     * @param EventManager_Model_BookedOption $oldBookedOption
     * @return void
     */
    protected function _handleBookingStatusChange(
        EventManager_Model_BookedOption $newBookedOption,
        EventManager_Model_BookedOption $oldBookedOption
    ): void {
        $oldConfig = $oldBookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG};
        $newConfig = $newBookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG};

        if (!$oldConfig || !$newConfig) {
            return;
        }

        $oldBooked = $oldConfig->{EventManager_Model_Selections_Checkbox::FLD_BOOKED};
        $newBooked = $newConfig->{EventManager_Model_Selections_Checkbox::FLD_BOOKED};

        if ($oldBooked !== $newBooked) {
            $increment = $newBooked ? 1 : -1;
            $this->_updateCheckboxOptionPlaces($newBookedOption, $increment);
        }
    }

    /**
     * Unbooks a removed option by ID
     *
     * @param string $optionId
     * @return void
     */
    protected function _unbookRemovedOption(string $optionId): void
    {
        try {
            $option = EventManager_Controller_Option::getInstance()->get($optionId);

            if (empty($option->getData())) {
                return;
            }

            $optionConfig = $option->{EventManager_Model_Option::FLD_OPTION_CONFIG};

            if (
                !isset($optionConfig->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES})
                || !isset($optionConfig->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES})
            ) {
                return;
            }

            $optionConfig->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES}--;
            $optionConfig->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES}++;

            EventManager_Controller_Option::getInstance()->update($option);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // Option was already deleted (cascade delete from Event) - this is expected
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Option already deleted: ' . $tenf->getMessage());
            }
        }
    }

    /**
     * Processes booked options after registration deletion
     * Unbooks all checkbox options
     *
     * @param EventManager_Model_Registration $record
     * @return void
     */
    protected function _processBookedOptionsAfterDelete(EventManager_Model_Registration $record): void
    {
        if (!$record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}) {
            return;
        }

        foreach ($record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
            $this->_updateCheckboxOptionPlaces($bookedOption, -1);
        }
    }

    public function createDeregisteredFolder(
        EventManager_Model_BookedOption $booked_option,
        string $participant_name
    ): void {
        if (isset($booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS})) {
            if (
                $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                === 'EventManager_Model_Selections_File'
            ) {
                $node_id = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_NODE_ID};
                if (!empty($node_id)) {
                    $file_system = Tinebase_FileSystem::getInstance();
                    $path_of_node = $file_system->getPathOfNode($node_id, true);
                    $path_of_node = explode('folders/', $path_of_node)[1];
                    $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
                    $new_folder_path = explode(($participant_name . '/'), $path_of_node)[0]
                        . $translation->_('Deregistered');
                    $prefix = Tinebase_FileSystem::getInstance()
                            ->getApplicationBasePath('Filemanager') . '/folders/';
                    $node_controller = Filemanager_Controller_Node::getInstance();
                    if (!Tinebase_FileSystem::getInstance()->isDir($prefix . $new_folder_path)) {
                        $node_controller->createNodes(
                            [$new_folder_path],
                            [Tinebase_Model_Tree_FileObject::TYPE_FOLDER]
                        );
                    }
                    $new_folder_path = $new_folder_path . '/' . $participant_name;
                    if (!Tinebase_FileSystem::getInstance()->isDir($prefix . $new_folder_path)) {
                        $node_controller->createNodes(
                            [$new_folder_path],
                            [Tinebase_Model_Tree_FileObject::TYPE_FOLDER]
                        );
                    }
                    $deregistered_participant = $file_system->copy(
                        $prefix . $path_of_node,
                        $prefix . $new_folder_path
                    );
                    if ($deregistered_participant) {
                        Filemanager_Controller_Node::getInstance()->deleteNodes([$path_of_node]);
                        $path_of_node = explode(($participant_name . '/'), $path_of_node)[0] . $participant_name;
                        Filemanager_Controller_Node::getInstance()->deleteNodes([$path_of_node]);
                    }
                }
            }
        }
    }

    public function publicApiGetFile($node_id): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $file_system = Tinebase_FileSystem::getInstance();
            $file = $file_system->get($node_id);

            $file_name = $file_system->getPathOfNode($file, true);
            $handle = $file_system->fopen($file_name, 'r', $file->revision);

            if (false === $handle) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not open file by real path for file path ' . $file_name);
                }
                throw new Tinebase_Exception_NotFound('Could not open file ' . $file_name);
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

    public function publicApiPostRegistration($event_id, $is_update = false): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);
            $response = new \Laminas\Diactoros\Response();

            $ab_contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($request['email']);
            $contact = null;

            if (empty($ab_contact)) {
                try {
                    $contact_data = array_map(function ($value) {
                        return $value;
                    }, $request['contactDetails']);
                    $contact_data['container_id'] = EventManager_Setup_Initialize::getContactEventContainer()->getId();

                    $contact = new Addressbook_Model_Contact($contact_data);
                    $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
                } catch (Tinebase_Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Exception: ' . $e->getMessage());
                    }
                }
            } else {
                foreach ($request['contactDetails'] as $field => $value) {
                    if ($ab_contact->has($field)) {
                        $ab_contact->$field = $value;
                    }
                }
                $ab_contact->container_id = $ab_contact->getContainerId();
                $contact = Addressbook_Controller_Contact::getInstance()->update($ab_contact);
            }
            $options = $request['replies'];
            $booked_options = [];
            foreach ($options as $option_id => $reply) {
                $option = EventManager_Controller_Option::getInstance()->get($option_id);
                if (
                    $option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS}
                    === EventManager_Model_CheckboxOption::class
                ) {
                    $selection_config = new EventManager_Model_Selections_Checkbox([
                        'booked' => boolval($reply),
                    ], true);
                    $booked_options[] = new EventManager_Model_BookedOption([
                        'event_id' => $request['eventId'],
                        'option' => $option->getId(),
                        'selection_config' => $selection_config,
                        'selection_config_class' => EventManager_Model_Selections_Checkbox::class,
                    ], true);
                } elseif (
                    $option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS}
                    === EventManager_Model_TextInputOption::class
                ) {
                    $selection_config = new EventManager_Model_Selections_TextInput([
                        'response' => $reply,
                    ], true);
                    $booked_options[] = new EventManager_Model_BookedOption([
                        'event_id' => $request['eventId'],
                        'option' => $option->getId(),
                        'selection_config' => $selection_config,
                        'selection_config_class' => EventManager_Model_Selections_TextInput::class,
                    ], true);
                } elseif (
                    $option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS}
                    === EventManager_Model_FileOption::class
                ) {
                    if (
                        isset(
                            $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                                ->{EventManager_Model_FileOption::FLD_FILE_ACKNOWLEDGMENT}
                        )
                        && $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                            ->{EventManager_Model_FileOption::FLD_FILE_ACKNOWLEDGMENT}
                    ) {
                        $selection_config = new EventManager_Model_Selections_File([
                            'file_acknowledgement' => boolval($reply),
                        ], true);
                        $booked_options[] = new EventManager_Model_BookedOption([
                            'event_id' => $request['eventId'],
                            'option' => $option->getId(),
                            'selection_config' => $selection_config,
                            'selection_config_class' => EventManager_Model_Selections_File::class,
                        ], true);
                    }
                }
            }
            $filter =  Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                EventManager_Model_Registration::class,
                [
                    [
                        'field' => EventManager_Model_Registration::FLD_EVENT_ID,
                        'operator' => 'equals',
                        'value' => $event_id
                    ],
                    [
                        'field' => EventManager_Model_Registration::FLD_PARTICIPANT,
                        'operator' => 'equals',
                        'value' => $contact
                    ],
                ],
            );
            $register_participant = $this->getInstance()->search($filter)->getFirstRecord();

            $default_values = $this->getDefaultRegistrationKeyFields();
            if (!empty($register_participant)) {
                if ($register_participant->{EventManager_Model_Registration::FLD_STATUS} === '3') {
                    $register_participant->{EventManager_Model_Registration::FLD_STATUS} = $default_values['status'];
                }
                $booked_options = $this->keepFilesAfterUpdate($register_participant, $booked_options);
                $register_participant->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = $booked_options;
                $registration = $this->update($register_participant);
            } else {
                $registration = new EventManager_Model_Registration([
                    'event_id'          => EventManager_Controller_Event::getInstance()->get($event_id),
                    'participant'       => $contact,
                    'function'          => $default_values['function'],
                    'source'            => $default_values['source'],
                    'status'            => $default_values['status'],
                    'booked_options'    => $booked_options,
                    'description'       => '',
                ], true);
                $registration = $this->create($registration);
            }

            $response->getBody()->write(json_encode($registration->toArray()));

            if ($is_update !== 'true') {
                if ($registration->{EventManager_Model_Registration::FLD_STATUS} === '2') {
                    $template = 'SendWaitingListEmail';
                } else {
                    $template = 'SendConfirmationEmail';
                }
                $event = EventManager_Controller_Event::getInstance()->get($event_id);
                $link = '/EventManager/view/#/event/';
                $this->_sendMessageWithTemplate($template, [
                    'link' => Tinebase_Core::getUrl() . $link,
                    'contact' => $request['contactDetails'],
                    'email' => $request['contactDetails']['email'],
                    'event' => $event,
                ]);
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

    public function keepFilesAfterUpdate($register_participant, $booked_options)
    {
        $old_booked_options = $register_participant->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS};
        foreach ($old_booked_options as $old_booked_option) {
            if (
                $old_booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                === 'EventManager_Model_Selections_File'
                && $old_booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_NODE_ID}
            ) {
                $booked_options[] = $old_booked_option;
            }
        }
        return new Tinebase_Record_RecordSet(EventManager_Model_BookedOption::class, $booked_options);
    }

    public function publicApiPostFileToFileManager($event_id, $option_id, $registration_id): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        header('Content-Type: application/json');
        try {
            $response = new \Laminas\Diactoros\Response();
            if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
                $file_count = count($_FILES['files']['name']);
                $registration = $this->get($registration_id);
                $old_booked_options = $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS};
                $booked_options = [];
                foreach ($old_booked_options as $booked_option) {
                    if ($booked_option->{EventManager_Model_BookedOption::FLD_OPTION}->id === $option_id) {
                        $node_id = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                            ->{EventManager_Model_Selections_File::FLD_NODE_ID};
                        $node = Tinebase_FileSystem::getInstance()->get($node_id);
                        Tinebase_FileSystem::getInstance()->deleteFileNode($node);
                    } else {
                        $booked_options[] = $booked_option;
                    }
                }
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $path = Tinebase_TempFile::getTempPath();
                        file_put_contents($path, $_FILES['files']['name'][$i]);
                        $temp_file = Tinebase_TempFile::getInstance()->createTempFile(
                            $path,
                            $_FILES['files']['name'][$i],
                            $_FILES['files']['type'][$i],
                            $_FILES['files']['size'][$i],
                            $_FILES['files']['error'][$i]
                        );
                        $selection_config = new EventManager_Model_Selections_File([
                            'node_id'   => $temp_file->getId(),
                            'file_name' => $_FILES['files']['name'][$i],
                            'file_type' => $_FILES['files']['type'][$i],
                            'file_size' => $_FILES['files']['size'][$i],
                        ], true);
                        $booked_options[] = new EventManager_Model_BookedOption([
                            'event_id' => $event_id,
                            'option' => $option_id,
                            'selection_config' => $selection_config,
                            'selection_config_class' => EventManager_Model_Selections_File::class,
                        ], true);
                    }
                }
                $booked_options = new Tinebase_Record_RecordSet(
                    EventManager_Model_BookedOption::class,
                    $booked_options
                );
                $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = $booked_options;
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

    public function publicApiPostDoubleOptIn($event_id): \Laminas\Diactoros\Response
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
            ], $key, 'HS256');

            if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $request['email'])) {
                $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($request['email']);
                $link = '/EventManager/view/#/event/' . $request['eventId'] . '/registration/' . $token;
                $template = 'SendRegistrationLink';
                $event = EventManager_Controller_Event::getInstance()->get($event_id);
                if (!empty($contact)) {
                    $this->_sendMessageWithTemplate($template, [
                        'link' => Tinebase_Core::getUrl() . $link,
                        'contact' => $contact,
                        'email' => $request['email'],
                        'event' => $event,
                    ]);
                } else {
                    $this->_sendMessageWithTemplate($template, [
                        'link' => Tinebase_Core::getUrl() . $link,
                        'email' => $request['email'],
                        'event' => $event,
                    ]);
                }
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

    public function publicApiPostDeregistration($event_id, $token)
    {
        $assertAclUsage = $this->assertPublicUsage();
        $response = new \Laminas\Diactoros\Response();
        try {
            if ($token) {
                if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                    throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
                }
                try {
                    $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                    $email = $decoded->email;
                    $contact = Addressbook_Controller_Contact::getInstance()->getContactByEmail($email);
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
                                    'field' => EventManager_Model_Registration::FLD_PARTICIPANT,
                                    'operator' => 'equals',
                                    'value' => $contact
                                ],
                            ],
                        );
                        $register_participant = $this->getInstance()->search($filter)->getFirstRecord();
                        if (!empty($register_participant)) {
                            $register_participant->{EventManager_Model_Registration::FLD_STATUS} = '3';
                            $this->getInstance()->update($register_participant);
                            $link = '/EventManager/view/#/event/';
                            $template = 'SendDeregistrationEmail';
                            $event = EventManager_Controller_Event::getInstance()->get($event_id);
                            $this->_sendMessageWithTemplate($template, [
                                'link' => Tinebase_Core::getUrl() . $link,
                                'contact' => $contact,
                                'email' => $contact->email,
                                'event' => $event,
                            ]);
                            $response = new \Laminas\Diactoros\Response();
                            $response->getBody()->write(json_encode([$link]));
                        }
                    }
                } catch (Exception $jwtException) {
                    // Invalid or expired token
                    $response = new \Laminas\Diactoros\Response('php://memory', 400);
                    $response->getBody()->write(json_encode(['error' => 'Invalid or expired token']));
                    return $response;
                }
            }
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

    public function getDefaultRegistrationKeyFields()
    {
        $attendee = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_FUNCTION)->records->getById('1');
        $online = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_SOURCE)->records->getById('1');
        $confirmed = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_STATUS)->records->getById('1');
        return ['function' => $attendee, 'source' => $online, 'status' => $confirmed];
    }
}
