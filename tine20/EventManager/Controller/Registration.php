<?php

declare(strict_types=1);

/**
 * Registration controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

use Firebase\JWT\JWT;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Registration controller
 *
 * @package     EventManager
 * @subpackage  Controller
 *
 * @template T of Tinebase_Record_Interface
 */
class EventManager_Controller_Registration extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<EventManager_Controller_Registration> */
    use Tinebase_Controller_SingletonTrait;

    /**
     * Store participant data before deletion to use in _inspectAfterDelete
     * @var array
     */
    protected $_participantsBeforeDelete = [];

    /**
     * Store notification data (recipients, participant, registrant) before deletion
     * @var array
     */
    protected $_notificationDataBeforeDelete = [];

    private const PROTECTED_CONTACT_FIELDS = [
        'id', 'seq', 'registration_id', 'registration_type',
        'created_by', 'creation_time', 'last_modified_by', 'last_modified_time',
        'is_deleted', 'deleted_by', 'deleted_time',
    ];

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
                        TMFA::OPERATOR => TMFA::OPERATOR_NOT,
                        TMFA::VALUE => null
                    ],
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

    private function _copyContactData($target, iterable $source): void
    {
        foreach ($source as $field => $value) {
            if ($target->has($field) && !in_array($field, self::PROTECTED_CONTACT_FIELDS, true)) {
                $target->$field = $value;
            }
        }
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);
        $this->_handleRegistrationFileUpload($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        $this->_handleRegistrationFileUpload($_record);
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);
        $this->_processBookedOptionsAfterCreate($_record);
        $this->_updateParentStatistics($_record);
        $template = $this->_getTemplate($_record);
        $this->_sendProcessEmail($_record, $template);
    }

    protected function _inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord)
    {
        parent::_inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord);
        $this->_processBookedOptionsAfterUpdate($_updatedRecord, $_oldRecord);
        $bookedOptionsDeleted = false;
        if ($_record->{EventManager_Model_Registration::FLD_STATUS} === "3") {
            if (!empty($_record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS})) {
                foreach ($_record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
                    if (
                        $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                        === "EventManager_Model_Selections_File"
                    ) {
                        $participantName = EventManager_Controller_Register_Contact::getInstance()
                            ->get($_record->{EventManager_Model_Registration::FLD_PARTICIPANT}
                                ->{EventManager_Model_Register_Contact::ID})->n_fileas;
                        $this->createDeregisteredFolder($bookedOption, $participantName);
                    }
                }
                $this->_deleteBookedOptions($_record);
                $bookedOptionsDeleted = true;
            }
            if (!$bookedOptionsDeleted) {
                $template = 'SendDeregistrationEmail';
                $this->_sendProcessEmail($_record, $template);
            }
        }

        if (
            $_updatedRecord->{EventManager_Model_Registration::FLD_STATUS} !== "3"
            && $_oldRecord->{EventManager_Model_Registration::FLD_STATUS} === "3"
        ) {
            $template = $this->_getTemplate($_record);
            $this->_sendProcessEmail($_record, $template);
        }

        if (
            $_updatedRecord->{EventManager_Model_Registration::FLD_STATUS} === "1"
            && $_oldRecord->{EventManager_Model_Registration::FLD_STATUS} === "2"
        ) {
            $template = $this->_getTemplate($_record, true);
            $this->_sendProcessEmail($_record, $template);
        }

        $this->_updateParentStatistics($_updatedRecord, true);
    }

    public function delete($_ids)
    {
        if ($_ids instanceof Tinebase_Record_RecordSet) {
            $_ids = $_ids->getArrayOfIds();
        }
        $_ids = (array) $_ids;

        foreach ($_ids as $id) {
            $registration = $this->get($id);
            try {
                $this->_notificationDataBeforeDelete[$id] = [
                    'recipients'  => $this->_getNotificationRecipients($registration),
                    'participant' => $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}
                        ? clone $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}
                        : null,
                    'registrant'  => $registration->{EventManager_Model_Registration::FLD_REGISTRANT}
                        ? clone $registration->{EventManager_Model_Registration::FLD_REGISTRANT}
                        : null,
                ];
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }

            if ($registration->{EventManager_Model_Registration::FLD_PARTICIPANT}) {
                try {
                    $participantName = EventManager_Controller_Register_Contact::getInstance()
                        ->get($registration->{EventManager_Model_Registration::FLD_PARTICIPANT}
                            ->{EventManager_Model_Register_Contact::ID})->n_fileas;
                    $this->_participantsBeforeDelete[$id] = $participantName;
                } catch (Tinebase_Exception_NotFound $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Participant not found: ' . $e->getMessage());
                    }
                    $this->_participantsBeforeDelete[$id] = null;
                }
            }
        }
        return parent::delete($_ids);
    }

    protected function _inspectAfterDelete($_record)
    {
        parent::_inspectAfterDelete($_record);
        $this->_processBookedOptionsAfterDelete($_record);

        $id = $_record->getId();
        $participantName  = $this->_participantsBeforeDelete[$id] ?? null;
        $notificationData = $this->_notificationDataBeforeDelete[$id] ?? null;
        unset($this->_participantsBeforeDelete[$id], $this->_notificationDataBeforeDelete[$id]);

        if ($participantName && $_record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}) {
            foreach ($_record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} as $bookedOption) {
                $this->createDeregisteredFolder($bookedOption, $participantName);
            }
        }

        $this->_sendProcessEmail($_record, 'SendDeregistrationEmail', $notificationData);
        $this->_updateParentStatistics($_record);
    }

    public function _deleteBookedOptions($_record)
    {
        $_record->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = null;
        $this->update($_record);
    }

    public function _sendProcessEmail($_record, $template, ?array $preloaded = null)
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $recipients = $preloaded['recipients'] ?? $this->_getNotificationRecipients($_record);
            if (empty($recipients['to'])) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' No recipient with an email address for registration ' . $_record->getId());
                return;
            }

            $event = EventManager_Controller_Event::getInstance()
                ->get($_record->{EventManager_Model_Registration::FLD_EVENT_ID});
            $eventName = EventManager_Controller_Event::getInstance()->getEventName($event);

            $this->_sendMessageWithTemplate($template, [
                'link'        => Tinebase_Core::getUrl() . '/EventManager/view/events',
                'contact'     => $recipients['to'],
                'cc'          => $recipients['cc'],
                'email'       => $recipients['to']->email,
                'participant' => $preloaded['participant']
                    ?? $_record->{EventManager_Model_Registration::FLD_PARTICIPANT},
                'registrant'  => $preloaded['registrant']
                    ?? $_record->{EventManager_Model_Registration::FLD_REGISTRANT},
                'event'       => $event,
                'eventName'   => $eventName,
            ]);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        } finally {
            $assertAclUsage();
        }
    }

    private function _getNotificationContact($registerContact): ?Addressbook_Model_Contact
    {
        if (!$registerContact instanceof Tinebase_Record_Interface) {
            return null;
        }

        $email = trim((string)($registerContact->email ?? ''));
        if ($email === '') {
            return null;
        }

        $given  = trim((string)($registerContact->n_given ?? ''));
        $family = trim((string)($registerContact->n_family ?? ''));

        return new Addressbook_Model_Contact([
            'salutation' => $registerContact->salutation ?? null,
            'n_given'    => $given,
            'n_family'   => $family,
            'n_fn'       => trim($given . ' ' . $family),
            'email'      => $email,
        ], true);
    }

    private function _getNotificationRecipients( $_record): array
    {
        $participant = $this->_getNotificationContact(
            $_record->{EventManager_Model_Registration::FLD_PARTICIPANT}
        );
        $registrant = $_record->{EventManager_Model_Registration::FLD_HAS_REGISTRANT}
            ? $this->_getNotificationContact($_record->{EventManager_Model_Registration::FLD_REGISTRANT})
            : null;

        $cp = $_record->{EventManager_Model_Registration::FLD_COMMUNICATION_PREFERENCE};

        $commRegistrant = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->records->getById('2')->getId();
        $commParticipantCcRegistrant = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->records->getById('3')->getId();

        if (!$registrant) {
            $cp = $participant;
        }

        switch ($cp) {
            case $commRegistrant:
                $to = $registrant;
                $cc = null;
                break;
            case $commParticipantCcRegistrant:
                $to = $participant;
                $cc = $registrant;
                break;
            default:
                $to = $participant;
                $cc = null;
        }

        if (!$to) {
            $to = $participant ?: $registrant;
            $cc = null;
        }

        return [
            'to' => $to,
            'cc' => $cc ? [$cc] : [],
        ];
    }

    public function _getTemplate($_record, $sendConfirmationEmail = false)
    {
        $event = EventManager_Controller_Event::getInstance()
            ->get($_record->{EventManager_Model_Registration::FLD_EVENT_ID});
        $today = Tinebase_DateTime::today();
        if (!$sendConfirmationEmail) {
            if (
                $event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES} < 1
            ) {
                $template = 'SendWaitingListEmail';
            } elseif (
                $event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL}
                && $event->{EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL} < $today
            ) {
                $template = 'SendWaitingListAfterRegDayEmail';
            } else {
                $template = 'SendConfirmationEmail';
            }
        } else {
            $template = 'SendConfirmationEmail';
        }
        return $template;
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
        $registrationDate = $_record->{EventManager_Model_Registration::FLD_REGISTRATION_DATE};
        if (empty($registrationDate)) {
            $_record->{EventManager_Model_Registration::FLD_REGISTRATION_DATE} = Tinebase_DateTime::now();
        }

        try {
            $participantOriginalId = $_record->participant->original_id;
            $registrantOriginalId = $_record->registrant->original_id;
            if (
                !$_record->{EventManager_Model_Registration::FLD_HAS_REGISTRANT}
                && !empty($participantOriginalId)
                && empty($registrantOriginalId)
            ) {
                $_record->registrant->original_id = $_record->participant->original_id;
            }
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
            $participant = $_record->{EventManager_Model_Registration::FLD_PARTICIPANT};
            $registrant = $_record->{EventManager_Model_Registration::FLD_REGISTRANT};
            if (
                !$_record->{EventManager_Model_Registration::FLD_HAS_REGISTRANT}
                && $participant->original_id
                && $participant->original_id === $registrant->original_id
            ) {
                $this->_copyContactData($registrant, $participant);
            }

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
                        ->updateStatistics(
                            $_record->{EventManager_Model_Registration::FLD_EVENT_ID},
                            $_record->{EventManager_Model_Registration::ID},
                            $is_update
                        );
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
                        ->updateStatistics(
                            $_record->{EventManager_Model_Registration::FLD_EVENT_ID},
                            $_record->{EventManager_Model_Registration::ID},
                            $is_update
                        );
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
                $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                !== EventManager_Model_Selections_File::class
            ) {
                continue;
            }

            $file_acknowledgement = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                ->{EventManager_Model_Selections_File::FLD_FILE_ACKNOWLEDGMENT} ?? false;

            if ($file_acknowledgement) {
                // User acknowledged the document, no file upload needed
                continue;
            }

            $node_id = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                ->{EventManager_Model_Selections_File::FLD_NODE_ID};

            if (!is_string($node_id)) {
                continue;
            }

            try {
                Tinebase_FileSystem::getInstance()->get($node_id);
                $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_FILE_UPLOAD} = true;
            } catch (Tinebase_Exception_NotFound $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' First time uploading file, so it will not be found: ' . $e->getMessage());
                }

                $file_name = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_FILE_NAME};

                $event_id = $_registration->{EventManager_Model_Registration::FLD_EVENT_ID};

                // Build participant-specific folder path
                $participant_name = 'participant';
                try {
                    $regId = $_registration->getId();
                    $filter =  Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                        EventManager_Model_Register_Contact::class,
                        [
                            [
                                'field' => 'registration_id',
                                'operator' => 'equals',
                                'value' => $regId
                            ],
                            [
                                'field' => 'registration_type',
                                'operator' => 'equals',
                                'value' => 'participant'
                            ],
                        ],
                    );
                    $participant = EventManager_Controller_Register_Contact::getInstance()->search($filter)->getFirstRecord();
                    $participant_name = $participant->n_fileas;
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
                if ($result === false) {
                    throw new Tinebase_Exception_UnexpectedValue('Could not store uploaded file ' . $file_name);
                }

                $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_NODE_ID} = $result->getId();
                $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                    ->{EventManager_Model_Selections_File::FLD_FILE_UPLOAD} = true;
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
            $node_id = (string) $node_id;
            $eventId = (string) ($_GET['eventId'] ?? '');
            $registrationId = (string) ($_GET['registrationId'] ?? '');

            if ($registrationId !== '') {
                $email = $this->_getEmailFromToken((string) ($_GET['token'] ?? ''));
                $registration = $this->get($registrationId);
                $this->_assertRegistrationAccess($registration, $email, $eventId !== '' ? $eventId : null);
                if (!$this->_registrationReferencesNode($registration, $node_id)) {
                    throw new Tinebase_Exception_AccessDenied('File does not belong to this registration');
                }
            } elseif ($eventId !== '') {
                if (!$this->_eventOptionReferencesNode($eventId, $node_id)) {
                    throw new Tinebase_Exception_AccessDenied('File does not belong to this event');
                }
            } else {
                throw new Tinebase_Exception_AccessDenied('File access requires an event or registration');
            }

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
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            Tinebase_Exception::log($tesg);
            $response = new \Laminas\Diactoros\Response('php://memory', 500);
            $response->getBody()->write(json_encode('File access failed'));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiPostRegistration($event_id): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();

        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true);

            $email = $this->_getEmailFromToken((string) ($request['token'] ?? ''));
            $request['eventId'] = $event_id;

            $registration = $this->_processRegistration($request, $event_id, false, $email);

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($registration->toArray()));
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode(['error' => $tead->getMessage()]));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $response = new \Laminas\Diactoros\Response('php://memory', 422);
            $response->getBody()->write(json_encode(['error' => $tesg->getMessage()]));
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($terv->getMessage()));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode($tenf->getMessage()));
        } catch (Tinebase_Exception_Record_NotAllowed $terna) {
            $response = new \Laminas\Diactoros\Response('php://memory', 401);
            $response->getBody()->write(json_encode($terna->getMessage()));
        } catch (Tinebase_Exception_ConcurrencyConflict $tecc) {
            $response = new \Laminas\Diactoros\Response('php://memory', 409);
            $response->getBody()->write(json_encode($tecc->getMessage()));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    private function _processRegistration(array $request, $event_id, bool $parentConsentGiven, string $verifiedEmail)
    {
        $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);

        $isOnBehalf = $this->_resolveIsOnBehalf($request);

        $request['contactDetails'] = is_array($request['contactDetails'] ?? null) ? $request['contactDetails'] : [];
        $request['registrantDetails'] = is_array($request['registrantDetails'] ?? null)
            ? $request['registrantDetails']
            : [];
        $requestedRegistrationId = (string) ($request['contactDetails']['registration_id'] ?? '');
        unset($request['contactDetails']['registration_id'], $request['registrantDetails']['registration_id']);

        if ($isOnBehalf) {
            $request['registrantDetails']['email'] = $verifiedEmail;
        } else {
            $request['contactDetails']['email'] = $verifiedEmail;
        }

        if ($requestedRegistrationId !== '') {
            try {
                $existingRegistration = $this->get($requestedRegistrationId);
                $this->_assertRegistrationAccess($existingRegistration, $verifiedEmail, (string) $event_id);
            } catch (Tinebase_Exception_AccessDenied $tead) {
                $response = new \Laminas\Diactoros\Response('php://memory', 403);
                $response->getBody()->write(json_encode(['error' => $tead->getMessage()]));
            } catch (Tinebase_Exception_NotFound $tenf) {
                $response = new \Laminas\Diactoros\Response('php://memory', 404);
                $response->getBody()->write(json_encode(['error' => $tenf->getMessage()]));
            }
            $request['contactDetails']['registration_id'] = $requestedRegistrationId;
            $request['registrantDetails']['registration_id'] = $requestedRegistrationId;
        }

        $isLegalGuardian = $isOnBehalf
            && filter_var($request['isLegalGuardian'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $isUnderage = $this->isParticipantUnderage($request['contactDetails'] ?? []);

        $communicationPreference = $this->_resolveCommunicationPreference($request, $isOnBehalf, $isUnderage);

        $participant = $this->getOrCreateRegisterContact($request['contactDetails'], 'participant');
        $isUpdatingExistingRegistration = !empty($participant->registration_id);

        if ($isUnderage) {
            if ($isOnBehalf) {
                if (!$isLegalGuardian) {
                    throw new Tinebase_Exception_SystemGeneric(
                        $translate->_('Minors must be registered by a legal guardian!')
                    );
                }
            } elseif (!$isUpdatingExistingRegistration && !$parentConsentGiven) {
                throw new Tinebase_Exception_SystemGeneric(
                    sprintf(
                        $translate->_('Parent or guardian consent is required for participants under %d.'),
                        (int) EventManager_Config::getInstance()->get(EventManager_Config::GUARDIAN_REQUIRED_AGE)
                    )
                );
            }
        }

        $isSelfRegistration = !$isOnBehalf;

        $registrant = $isSelfRegistration
            ? $this->getOrCreateRegisterContact($request['contactDetails'], 'registrant')
            : $this->getOrCreateRegisterContact($request['registrantDetails'], 'registrant');

        //participant replies:
        $options = $request['replies'];
        $booked_options = [];
        foreach ($options as $option_id => $reply) {
            $option = EventManager_Controller_Option::getInstance()->get($option_id);
            if (
                $option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS}
                === EventManager_Model_CheckboxOption::class
            ) {
                $selection_config = new EventManager_Model_Selections_Checkbox([
                    'booked' => filter_var($reply, FILTER_VALIDATE_BOOLEAN),
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
                        'file_acknowledgement' => filter_var($reply, FILTER_VALIDATE_BOOLEAN),
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

        $default_values = $this->getDefaultRegistrationKeyFields();

        if ($participant->registration_id) {
            $registration = $this->get($participant->registration_id);

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                EventManager_Model_Register_Contact::class,
                [
                    [
                        'field' => EventManager_Model_Register_Contact::FLD_REGISTRATION_ID,
                        'operator' => 'equals',
                        'value' => $registration->getId()
                    ],
                ],
            );
            $regs = EventManager_Controller_Register_Contact::getInstance()
                ->search($filter);

            if (!empty($regs)) {
                if ($registration->{EventManager_Model_Registration::FLD_STATUS} === '3') {
                    $registration->{EventManager_Model_Registration::FLD_STATUS} = $default_values['status'];
                }
                $booked_options = $this->keepFilesAfterUpdate($registration, $booked_options);
                $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = $booked_options;
                $registration->{EventManager_Model_Registration::FLD_REGISTRANT_IS_LEGAL_GUARDIAN} = $isLegalGuardian;
                $registration->{EventManager_Model_Registration::FLD_COMMUNICATION_PREFERENCE} = $communicationPreference;

                $registration->{EventManager_Model_Registration::FLD_HAS_REGISTRANT} = !$isSelfRegistration;

                foreach ($regs as $reg) {
                    if ($reg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE} === 'participant') {
                        $registration->{EventManager_Model_Registration::FLD_PARTICIPANT} = $reg;
                    } elseif ($reg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE} === 'registrant') {
                        $registration->{EventManager_Model_Registration::FLD_REGISTRANT} = $reg;
                    }
                }
                $registration = $this->updateRegisterContact(
                    $registration,
                    $request['contactDetails'],
                    $request['registrantDetails'],
                    $isSelfRegistration
                );
                $registration = $this->update($registration);
            }
        } else {
            $has_registrant = !$isSelfRegistration;
            $registration = new EventManager_Model_Registration([
                'event_id' => EventManager_Controller_Event::getInstance()->get($event_id),
                'participant' => $participant,
                'registrant' => $registrant,
                'function' => $default_values['function'],
                'source' => $default_values['source'],
                'status' => $default_values['status'],
                'booked_options' => $booked_options,
                'description' => '',
                'has_registrant' => $has_registrant,
                'registration_date' => Tinebase_DateTime::now(),
                'registrant_is_legal_guardian' => $isLegalGuardian,
                'communication_preference' => $communicationPreference,
            ], true);
            $registration = $this->create($registration);
        }
        return $registration;
    }

    private function _resolveIsOnBehalf(array $request): bool
    {
        if (array_key_exists('isOnBehalf', $request)) {
            return filter_var($request['isOnBehalf'], FILTER_VALIDATE_BOOLEAN);
        }

        $participantId = $request['contactDetails']['id'] ?? null;
        $registrantId = $request['registrantDetails']['id'] ?? null;
        if ($participantId === $registrantId) {
            return false;
        }
        foreach ($request['registrantDetails'] ?? [] as $value) {
            if (is_scalar($value) && trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }

    private function _resolveCommunicationPreference(array &$request, bool $isOnBehalf, bool $isUnderage): int
    {
        $commParticipant = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->records->getById('1')->getId();
        $commRegistrant = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->records->getById('2')->getId();
        $commParticipantCcRegistrant = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->records->getById('3')->getId();

        if (!$isOnBehalf) {
            return $commParticipant;
        }

        $participantEmail = strtolower(trim((string)($request['contactDetails']['email'] ?? '')));
        $registrantEmail  = strtolower(trim((string)($request['registrantDetails']['email'] ?? '')));

        if ($participantEmail !== '' && $participantEmail === $registrantEmail) {
            $request['contactDetails']['email'] = '';
            $participantEmail = '';
        }

        if ($participantEmail === '') {
            return $commRegistrant;
        }

        if ($isUnderage) {
            return $commParticipantCcRegistrant;
        }

        $pref = (string)($request['communicationPreference'] ?? '');
        $allowed = [
            $commParticipant,
            $commRegistrant,
            $commParticipantCcRegistrant,
        ];

        return in_array($pref, $allowed, true)
            ? $pref
            : $commParticipantCcRegistrant;
    }

    public function publicApiPostParentConsentRequest($event_id): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true) ?: [];
            $parentEmail = trim((string)($request['parentEmail'] ?? ''));

            if (!$parentEmail || !preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $parentEmail)) {
                throw new Tinebase_Exception_SystemGeneric('A valid parent/guardian email is required');
            }
            if (!$this->isParticipantUnderage($request['contactDetails'] ?? [])) {
                throw new Tinebase_Exception_SystemGeneric('Parent consent is not required for this participant');
            }
            if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
                throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
            }

            $pendingId = Tinebase_Record_Abstract::generateUID();
            $ttl = 7 * 24 * 60 * 60;

            Tinebase_Core::getCache()->save(
                json_encode([
                    'eventId' => $event_id,
                    'contactDetails' => $request['contactDetails'] ?? [],
                    'replies' => $request['replies'] ?? [],
                    'registrantDetails' => [],
                    'isAlreadyRegistered' => false,
                    'isOnBehalf' => false,
                    'isLegalGuardian' => false,
                ]),
                'EventManagerParentConsent_' . $pendingId,
                ['eventmanager', 'parentConsent'],
                $ttl
            );

            $token = JWT::encode([
                'eventId' => $event_id,
                'parentEmail' => $parentEmail,
                'pendingId' => $pendingId,
                'exp' => time() + $ttl,
            ], $key, 'HS256');

            $event = EventManager_Controller_Event::getInstance()->get($event_id);
            $eventName = EventManager_Controller_Event::getInstance()->getEventName($event);
            $participantName = trim(
                ($request['contactDetails']['n_given'] ?? '') . ' ' . ($request['contactDetails']['n_family'] ?? '')
            );

            $this->_sendMessageWithTemplate('SendParentConsentEmail', [
                'link' => Tinebase_Core::getUrl() . '/EventManager/registration/parentConsent/confirm/' . $token,
                'contact' => new Addressbook_Model_Contact(['email' => $parentEmail]),
                'email' => $parentEmail,
                'event' => $event,
                'eventName' => $eventName,
                'participantName' => $participantName,
            ]);

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $response = new \Laminas\Diactoros\Response('php://memory', 400);
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiPostParentConsentConfirm($token): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            [$decoded] = $this->_loadPendingParentConsent($token);

            $request = json_decode(Tinebase_Core::get(Tinebase_Core::REQUEST)->getContent(), true) ?: [];

            // the guardian always registers on behalf of the minor, with the verified email address
            $request['eventId'] = $decoded->eventId;
            $request['isOnBehalf'] = true;
            $request['registrantDetails'] = is_array($request['registrantDetails'] ?? null)
                ? $request['registrantDetails']
                : [];
            $request['registrantDetails']['email'] = $decoded->parentEmail;

            $registration = $this->_processRegistration(
                $request,
                $decoded->eventId,
                true,
                strtolower(trim((string) $decoded->parentEmail))
            );

            Tinebase_Core::getCache()->remove('EventManagerParentConsent_' . $decoded->pendingId);

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode($registration->toArray()));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $response = new \Laminas\Diactoros\Response('php://memory', 422);
            $response->getBody()->write(json_encode(['error' => $tesg->getMessage()]));
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $response = new \Laminas\Diactoros\Response('php://memory', 400);
            $response->getBody()->write(json_encode(['error' => 'Registration failed']));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    public function publicApiGetParentConsentConfirm($token): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            [$decoded] = $this->_loadPendingParentConsent($token);

            $key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET};
            $accountToken = JWT::encode(['email' => $decoded->parentEmail], $key, 'HS256');

            $url = Tinebase_Core::getUrl()
                . '/EventManager/view/event/' . rawurlencode((string) $decoded->eventId)
                . '/registration/' . $accountToken
                . '?consent=' . rawurlencode($token);

            $response = new \Laminas\Diactoros\Response\RedirectResponse($url);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            $message = $e instanceof Tinebase_Exception_SystemGeneric
                ? $e->getMessage()
                : $translate->_('This confirmation link is invalid or has expired.');
            $response = new \Laminas\Diactoros\Response('php://memory', 400, ['Content-Type' => 'text/html']);
            $response->getBody()->write('<h1>' . htmlspecialchars($message) . '</h1>');
        } finally {
            $assertAclUsage();
        }
        return $response;
    }

    private function _loadPendingParentConsent(string $token): array
    {
        $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        $invalid = $translate->_('This confirmation link is invalid or has expired.');

        if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
            throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
        }
        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
        } catch (Exception $e) {
            throw new Tinebase_Exception_SystemGeneric($invalid);
        }
        if (empty($decoded->pendingId) || empty($decoded->parentEmail) || empty($decoded->eventId)) {
            throw new Tinebase_Exception_SystemGeneric($invalid);
        }

        $cached = Tinebase_Core::getCache()->load('EventManagerParentConsent_' . $decoded->pendingId);
        if ($cached === false) {
            throw new Tinebase_Exception_SystemGeneric($invalid);
        }

        return [$decoded, json_decode($cached, true) ?: []];
    }

    public function publicApiGetParentConsentData($token): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            [$decoded, $pending] = $this->_loadPendingParentConsent($token);

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode([
                'eventId'        => $decoded->eventId,
                'parentEmail'    => $decoded->parentEmail,
                'contactDetails' => $pending['contactDetails'] ?? [],
                'replies'        => $pending['replies'] ?? [],
            ]));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $response = new \Laminas\Diactoros\Response('php://memory', 400);
            $response->getBody()->write(json_encode(['error' => $tesg->getMessage()]));
        } finally {
            $assertAclUsage();
        }
        return $response;
    }
    private function updateRegisterContact($registration, $participantData, $registrantData, $isSelfRegistration)
    {
        $rcController = EventManager_Controller_Register_Contact::getInstance();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            EventManager_Model_Register_Contact::class,
            [[
                'field'    => EventManager_Model_Register_Contact::FLD_REGISTRATION_ID,
                'operator' => 'equals',
                'value'    => $registration->getId(),
            ]]
        );

        $existing = $rcController->search($filter);

        $participantReg = null;
        $registrantReg  = null;

        foreach ($existing as $reg) {
            if ($reg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE} === 'participant') {
                $participantReg = $reg;
            } elseif ($reg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE} === 'registrant') {
                $registrantReg = $reg;
            }
        }

        if (!$participantReg) {
            $participantReg = new EventManager_Model_Register_Contact([], true);
        }

        $this->_copyContactData($participantReg, $participantData ?? []);
        $participantReg->n_fileas = $this->getNFileas($participantReg);

        $participantReg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_ID}
            = $registration->getId();
        $participantReg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE}
            = 'participant';

        $participantReg = $participantReg->getId()
            ? $rcController->update($participantReg)
            : $rcController->create($participantReg);

        if (!$registrantReg) {
            $registrantReg = new EventManager_Model_Register_Contact([], true);
        }

        $sourceData = $isSelfRegistration ? $participantData : $registrantData;

        $this->_copyContactData($registrantReg, $sourceData ?? []);
        $registrantReg->n_fileas = $this->getNFileas($registrantReg);

        $registrantReg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_ID}
            = $registration->getId();
        $registrantReg->{EventManager_Model_Register_Contact::FLD_REGISTRATION_TYPE}
            = 'registrant';

        $registrantReg = $registrantReg->getId()
            ? $rcController->update($registrantReg)
            : $rcController->create($registrantReg);

        $registration->{EventManager_Model_Registration::FLD_HAS_REGISTRANT}
            = !$isSelfRegistration;

        $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}
            = $participantReg;

        $registration->{EventManager_Model_Registration::FLD_REGISTRANT}
            = $registrantReg;

        return $registration;
    }

    private function getNFileas($record)
    {
        $family = trim($record->n_family ?? '');
        $given  = trim($record->n_given ?? '');

        if ($family && $given) {
            return $family . ', ' . $given;
        }

        return $family ?: $given;
    }

    public function getContactByContactInformation($contactInformation, $registrationType)
    {
        $contact = null;
        if (!empty($contactInformation['registration_id'])) {
            $filter =  Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                EventManager_Model_Register_Contact::class,
                [
                    [
                        'field' => 'registration_id',
                        'operator' => 'equals',
                        'value' => $contactInformation['registration_id']
                    ],
                    [
                        'field' => 'registration_type',
                        'operator' => 'equals',
                        'value' => $registrationType
                    ],
                ],
            );
            $denormalized_contacts = EventManager_Controller_Register_Contact::getInstance()->search($filter);
            $contact = $denormalized_contacts->getFirstRecord();
        }
        return $contact;
    }

    public function getOrCreateRegisterContact($contactInformation, $registrationType)
    {
        $assertAclUsage = $this->assertPublicUsage();
        $contact = null;
        try {
            $contact = $this->getContactByContactInformation($contactInformation, $registrationType);
            if (!$contact) {
                $contactData = array_map(function ($value) {
                    return $value;
                }, $contactInformation);
                // create a new contact but do not save it for first time registration
                $contact = new Addressbook_Model_Contact($contactData);
            } elseif (!empty($contact->registration_id)) {
                return $contact;
            } else {
                $newDenormalizedContact = new EventManager_Model_Register_Contact();
                $this->_copyContactData($newDenormalizedContact, $contactInformation);
                $contact = $newDenormalizedContact;
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . $e->getMessage());
        } finally {
            $assertAclUsage();
        }
        return $contact;
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
            $email = $this->_getEmailFromToken((string) ($_POST['token'] ?? ''));
            $registration = $this->get($registration_id);
            $this->_assertRegistrationAccess($registration, $email, (string) $event_id);

            $isFileOptionOfEvent = false;
            foreach ($this->_getEventOptions((string) $event_id) as $option) {
                if (
                    $option->getId() === $option_id
                    && $option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS} === EventManager_Model_FileOption::class
                ) {
                    $isFileOptionOfEvent = true;
                    break;
                }
            }
            if (!$isFileOptionOfEvent) {
                throw new Tinebase_Exception_AccessDenied('Unknown file option');
            }

            $response = new \Laminas\Diactoros\Response();
            if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
                $file_count = count($_FILES['files']['name']);
                $old_booked_options = $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} ?: [];
                $booked_options = [];
                $nodes_to_delete = [];

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $uploadedTmp = $_FILES['files']['tmp_name'][$i];
                    if (!is_uploaded_file($uploadedTmp)) {
                        throw new Tinebase_Exception_Record_NotAllowed('Invalid upload');
                    }

                    $path = Tinebase_TempFile::getTempPath();
                    if (!move_uploaded_file($uploadedTmp, $path)) {
                        throw new Tinebase_Exception_UnexpectedValue('Could not move uploaded file');
                    }

                    $fileName = basename((string) $_FILES['files']['name'][$i]);
                    $fileType = $_FILES['files']['type'][$i];
                    $fileSize = filesize($path);

                    $temp_file = Tinebase_TempFile::getInstance()->createTempFile(
                        $path,
                        $fileName,
                        $fileType,
                        $fileSize,
                        UPLOAD_ERR_OK
                    );

                    $booked_options[] = new EventManager_Model_BookedOption([
                        'event_id' => $event_id,
                        'option' => $option_id,
                        'selection_config' => new EventManager_Model_Selections_File([
                            'node_id'   => $temp_file->getId(),
                            'file_name' => $fileName,
                            'file_type' => $fileType,
                            'file_size' => $fileSize,
                        ], true),
                        'selection_config_class' => EventManager_Model_Selections_File::class,
                    ], true);
                }

                if (empty($booked_options)) {
                    throw new Tinebase_Exception_Record_Validation('No valid file uploaded');
                }

                foreach ($old_booked_options as $booked_option) {
                    $option = $booked_option->{EventManager_Model_BookedOption::FLD_OPTION};
                    $old_option_id = is_object($option) ? $option->getId() : $option;

                    if ($old_option_id !== $option_id) {
                        $booked_options[] = $booked_option;
                        continue;
                    }

                    $node_id = $booked_option->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
                        ->{EventManager_Model_Selections_File::FLD_NODE_ID} ?? null;
                    if (!empty($node_id)) {
                        $nodes_to_delete[] = $node_id;
                    }
                }

                $registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = new Tinebase_Record_RecordSet(
                    EventManager_Model_BookedOption::class,
                    $booked_options
                );

                $tm = Tinebase_TransactionManager::getInstance();
                $transactionId = $tm->startTransaction(Tinebase_Core::getDb());
                try {
                    $fs = Tinebase_FileSystem::getInstance();
                    foreach ($nodes_to_delete as $node_id) {
                        try {
                            $fs->deleteFileNode($fs->get($node_id));
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            // already gone
                        }
                    }

                    $registration = $this->update($registration);
                    $tm->commitTransaction($transactionId);
                } catch (Throwable $t) {
                    $tm->rollBack();
                    throw $t;
                }

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
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode($tead->getMessage()));
        } catch (Throwable $t) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $t);
            $response = new \Laminas\Diactoros\Response('php://memory', 500);
            $response->getBody()->write(json_encode(['error' => 'Upload failed']));
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
                if (!empty($contact) && $event_id === "null") {
                    $link = '/EventManager/view/account/' . $token;
                    $template = 'SendRegistrationLink';
                    $this->_sendMessageWithTemplate($template, [
                        'link' => Tinebase_Core::getUrl() . $link,
                        'contact' => $contact,
                        'email' => $request['email'],
                    ]);
                } else {
                    $link = '/EventManager/view/event/' . $request['eventId'] . '/registration/' . $token;
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
                        $tempContact = new Addressbook_Model_Contact([
                            'email' => $request['email'],
                        ]);
                        $this->_sendMessageWithTemplate($template, [
                            'link' => Tinebase_Core::getUrl() . $link,
                            'contact' => $tempContact,
                            'email' => $request['email'],
                            'event' => $event,
                        ]);
                    }
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

    public function publicApiPostDeregistration($event_id, $token, $registration_id = null): \Laminas\Diactoros\Response
    {
        $assertAclUsage = $this->assertPublicUsage();
        try {
            $email = $this->_getEmailFromToken((string) $token);
            if (empty($registration_id)) {
                throw new Tinebase_Exception_Record_Validation('Missing registration id');
            }
            $registration = $this->get($registration_id);
            $this->_assertRegistrationAccess($registration, $email, (string) $event_id);

            if ($registration->{EventManager_Model_Registration::FLD_STATUS} !== '3') {
                $registration->{EventManager_Model_Registration::FLD_STATUS} = '3';
                $this->update($registration);
            }

            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(json_encode(['success' => true]));
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $response = new \Laminas\Diactoros\Response('php://memory', 400);
            $response->getBody()->write(json_encode(['error' => $terv->getMessage()]));
        } catch (Tinebase_Exception_NotFound $tenf) {
            $response = new \Laminas\Diactoros\Response('php://memory', 404);
            $response->getBody()->write(json_encode(['error' => 'Registration not found']));
        } catch (Tinebase_Exception_Record_NotAllowed | Tinebase_Exception_AccessDenied $e) {
            $response = new \Laminas\Diactoros\Response('php://memory', 403);
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            Tinebase_Exception::log($tesg);
            $response = new \Laminas\Diactoros\Response('php://memory', 500);
            $response->getBody()->write(json_encode(['error' => 'Deregistration failed']));
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

        $config = EventManager_Config::getInstance();
        $senderEmail = $config->get(EventManager_Config::EVENT_NOTIFICATION_EMAIL);
        $senderName  = $config->get(EventManager_Config::EVENT_NOTIFICATION_NAME);

        // no Cc -> unchanged path via the notification service
        if (empty($context['cc'])) {
            $sender = !empty($senderEmail)
                ? new Tinebase_Model_FullUser([
                    'accountEmailAddress' => $senderEmail,
                    'accountFullName'     => $senderName,
                ], true)
                : null;

            Tinebase_Notification::getInstance()->send(
                $sender,
                [$context['contact']],
                $subject,
                $text,
                $html
            );
            return;
        }

        // with CC build the mail ourselves (Tinebase_Notification only supports To)
        $this->_sendMailWithCc($context['contact'], $context['cc'], $subject, $text, $html, $senderEmail, $senderName);
    }

    protected function _sendMailWithCc(
        Addressbook_Model_Contact $to,
        array $ccRecipients,
        string $subject,
        string $text,
        string $html,
        ?string $senderEmail,
        ?string $senderName
    ): void {
        $notificationAddress = Tinebase_Notification_Backend_Smtp::getFromAddress();
        if (empty($notificationAddress) && empty($senderEmail)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' No notification service address set. Could not send notification.');
            return;
        }

        $mail = new Tinebase_Mail('UTF-8');
        $mail->setSubject($subject);
        $mail->setBodyText($text);
        $mail->setBodyHtml($html);

        $mail->addHeader('X-Tine20-Type', 'Notification');
        $mail->addHeader('Precedence', 'bulk');
        $mail->addHeader('User-Agent', Tinebase_Core::getTineUserAgent('Notification Service'));

        $notificationName = Tinebase_Config::getInstance()->get(Tinebase_Config::BRANDING_TITLE)
            . ' notification service';

        if (!empty($senderEmail)) {
            $mail->setFrom($senderEmail, $senderName ?: $notificationName);
            if (!empty($notificationAddress)) {
                $mail->setSender($notificationAddress, $notificationName);
            }
        } else {
            $mail->setFrom($notificationAddress, $notificationName);
        }

        $mail->addTo($to->email, $to->n_fn);
        foreach ($ccRecipients as $cc) {
            if (!empty($cc->email) && strcasecmp($cc->email, $to->email) !== 0) {
                $mail->addCc($cc->email);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Send registration email to ' . $to->email
                . ' (cc: ' . implode(', ', array_map(fn($c) => $c->email, $ccRecipients)) . ')');
        }

        Tinebase_Smtp::getInstance()->sendMessage($mail);
    }

    public function getDefaultRegistrationKeyFields(): array
    {
        $attendee = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_FUNCTION)->records->getById('1');
        $online = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_SOURCE)->records->getById('1');
        $confirmed = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_STATUS)->records->getById('1');
        $participant = EventManager_Config::getInstance()
            ->get(EventManager_Config::REGISTRATION_COMMUNICATION_PREFERENCE)->records->getById('1');
        return ['function' => $attendee, 'source' => $online, 'status' => $confirmed, 'communication_preference' => $participant];
    }

    public function isParticipantUnderage(array $contactDetails): bool
    {
        $bday = $contactDetails['bday'] ?? null;
        if (empty($bday)) {
            return false;
        }

        try {
            $birthDate = new DateTime($bday);
        } catch (Exception $e) {
            return false;
        }

        $requiredAge = (int) EventManager_Config::getInstance()
            ->get(EventManager_Config::GUARDIAN_REQUIRED_AGE);

        return (new DateTime('today'))->diff($birthDate)->y < $requiredAge;
    }

    private function _getEmailFromToken(?string $token): string
    {
        if (!$key = EventManager_Config::getInstance()->{EventManager_Config::JWT_SECRET}) {
            throw new Tinebase_Exception_SystemGeneric('EventManager JWT key is not configured');
        }
        if (empty($token)) {
            throw new Tinebase_Exception_AccessDenied('Missing token');
        }
        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
        } catch (Throwable $t) {
            throw new Tinebase_Exception_AccessDenied('Invalid or expired token');
        }
        $email = strtolower(trim((string) ($decoded->email ?? '')));
        if ($email === '') {
            throw new Tinebase_Exception_AccessDenied('Invalid or expired token');
        }
        return $email;
    }

    private function _assertRegistrationAccess(
        $registration,
        string $email,
        ?string $eventId = null
    ): void {
        if (
            $eventId !== null
            && (string) $registration->getIdFromProperty(EventManager_Model_Registration::FLD_EVENT_ID)
            !== (string) $eventId
        ) {
            throw new Tinebase_Exception_AccessDenied('Registration does not belong to this event');
        }

        $registrant = $this->getContactByContactInformation(
            ['registration_id' => $registration->getId()],
            'registrant'
        );
        $registrantEmail = strtolower(trim((string) ($registrant->email ?? '')));

        if ($registrantEmail === '' || !hash_equals($registrantEmail, $email)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to access this registration');
        }
    }


    private function _getEventOptions(string $eventId): array
    {
        $event = EventManager_Controller_Event::getInstance()->get($eventId);
        $options = [];
        foreach ($event->{EventManager_Model_Event::FLD_OPTIONS} ?? [] as $option) {
            $options[] = is_string($option)
                ? EventManager_Controller_Option::getInstance()->get($option)
                : $option;
        }
        return $options;
    }

    private static function _configNodeId($config): ?string
    {
        $nodeId = is_array($config) ? ($config['node_id'] ?? null) : ($config->node_id ?? null);
        return empty($nodeId) ? null : (string) $nodeId;
    }

    private function _eventOptionReferencesNode(string $eventId, string $nodeId): bool
    {
        foreach ($this->_getEventOptions($eventId) as $option) {
            if (
                $option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS} === EventManager_Model_FileOption::class
                && self::_configNodeId($option->{EventManager_Model_Option::FLD_OPTION_CONFIG}) === $nodeId
            ) {
                return true;
            }
        }
        return false;
    }

    private function _registrationReferencesNode($registration, string $nodeId): bool
    {
        foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} ?: [] as $bookedOption) {
            if (
                $bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS}
                === EventManager_Model_Selections_File::class
                && self::_configNodeId($bookedOption->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}) === $nodeId
            ) {
                return true;
            }
        }
        return false;
    }
}
