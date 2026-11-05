<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Export
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * Class EventManager_Export_Document
 */
class EventManager_Export_Document extends Tinebase_Export_DocV2
{
    /** @var EventManager_Model_Event|null */
    protected $_event = null;

    protected function _loadTwig()
    {
        $this->_records = $this->_controller->search($this->_filter);
        if ($this->_records->count() !== 1) {
            throw new Tinebase_Exception_Record_Validation('can only export exactly one event at a time');
        }

        $event = $this->_records->getFirstRecord();
        $eventSet = new Tinebase_Record_RecordSet(EventManager_Model_Event::class, [$event]);
        $this->_resolveRecords($eventSet);
        $resolvedEvent = $eventSet->getFirstRecord();
        $this->_event = $this->_buildEventData($resolvedEvent);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            EventManager_Model_Registration::class,
            [
                [
                    'field' => EventManager_Model_Registration::FLD_EVENT_ID,
                    'operator' => 'equals',
                    'value' => $event->getId()
                ],
            ],
        );
        $registrations = EventManager_Controller_Registration::getInstance()->search($filter);

        (new Tinebase_Record_Expander(EventManager_Model_Registration::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                EventManager_Model_Registration::FLD_PARTICIPANT => [],
                EventManager_Model_Registration::FLD_REGISTRANT  => [],
                EventManager_Model_Registration::FLD_BOOKED_OPTIONS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        EventManager_Model_BookedOption::FLD_OPTION           => [],
                        EventManager_Model_BookedOption::FLD_SELECTION_CONFIG => [],
                    ],
                ],
            ],
        ]))->expand($registrations);

        $this->_records = [
            'REGISTRATIONS' => $this->_buildRegistrationRows($registrations, $event),
        ];

        parent::_loadTwig();
    }

    protected function _buildEventData(EventManager_Model_Event $event): array
    {
        $data = $event->toArray();

        $data[EventManager_Model_Event::FLD_NAME] =
            $this->_resolveLocalizedString($event->{EventManager_Model_Event::FLD_NAME});
        $data[EventManager_Model_Event::FLD_SUBHEADING] =
            $this->_resolveLocalizedString($event->{EventManager_Model_Event::FLD_SUBHEADING});
        $data[EventManager_Model_Event::FLD_DESCRIPTION] =
            $this->_resolveLocalizedString($event->{EventManager_Model_Event::FLD_DESCRIPTION});

        $start = $event->{EventManager_Model_Event::FLD_START};
        $end   = $event->{EventManager_Model_Event::FLD_END};
        $data['start'] = $start instanceof Tinebase_DateTime ? $start->format('d.m.Y') : '';
        $data['end']   = $end instanceof Tinebase_DateTime ? $end->format('d.m.Y') : '';
        $data['days']  = $this->_calculateDays($start, $end);

        return $data;
    }

    protected function _resolveLocalizedString($value): string
    {
        if ($value instanceof Tinebase_Record_RecordSet) {
            $value = $value->toArray();
        }

        if (!is_array($value)) {
            return (string)$value;
        }

        $locale = (string)Tinebase_Core::getLocale();
        $localeShort = substr($locale, 0, 2);

        $fallback = null;
        foreach ($value as $item) {
            $lang = is_array($item) ? ($item['language'] ?? null) : ($item->language ?? null);
            $text = is_array($item) ? ($item['text'] ?? null)     : ($item->text ?? null);

            if (null === $fallback) {
                $fallback = $text;
            }
            if ($lang === $locale || $lang === $localeShort) {
                return (string)$text;
            }
        }

        return (string)$fallback;
    }
    protected function _calculateDays($start, $end){
        if (!$start instanceof Tinebase_DateTime || !$end instanceof Tinebase_DateTime) {
            return '';
        }
        $startDate = new DateTime($start->format('Y-m-d'));
        $endDate   = new DateTime($end->format('Y-m-d'));
        return $startDate->diff($endDate)->days + 1;
    }

    protected function _buildRegistrationRows(Tinebase_Record_RecordSet $registrations, EventManager_Model_Event $event): Tinebase_Record_RecordSet
    {
        $rowSet = new Tinebase_Record_RecordSet(Tinebase_Record_Generic::class, []);
        $lfdNr = 1;

        foreach ($registrations as $registration) {
            $row = $registration->toArray();
            $participant = $registration->{EventManager_Model_Registration::FLD_PARTICIPANT};

            // names in German so we don't confuse them with model variables
            $row['email'] = $this->_calculateEmail($registration);
            $row['telefon'] = $this->_calculatePhone($registration);
            $row['lfd_nr'] = $lfdNr++;
            $row['geschlecht'] = $this->_calculateGender($participant);
            $row['alter'] = $this->_calculateAge($participant, $event);
            $row['ich_bin'] = $this->_calculateFunction($registration);
            $row['anmeldestatus'] = $this->_resolveKeyField(
                EventManager_Config::REGISTRATION_STATUS,
                $registration->{EventManager_Model_Registration::FLD_STATUS}
            );
            $row['geburtsdatum'] = $this->_formatDate($participant->bday ?? null);
            $row['anschrift'] = $this->_calculateAddress($registration);
            $row['antworten_liste'] = $this->_buildAnswerList($registration);

            // keep the original nested participant data available for the template
            $row['participant'] = $participant instanceof Tinebase_Record_Interface
                ? $participant->toArray()
                : [];

            $genericRecord = new Tinebase_Record_Generic([], true);
            $genericRecord->setValidators(array_fill_keys(array_keys($row), []));
            $genericRecord->setFromArray($row);

            $rowSet->addRecord($genericRecord);
        }

        return $rowSet;
    }

    protected function _calculateEmail(EventManager_Model_Registration $registration): string
    {
        $participant = $registration->{EventManager_Model_Registration::FLD_PARTICIPANT};
        $registrant  = $registration->{EventManager_Model_Registration::FLD_REGISTRANT};

        $participantEmail = $participant instanceof Tinebase_Record_Interface ? trim((string)$participant->email) : '';
        $registrantEmail  = $registrant instanceof Tinebase_Record_Interface ? trim((string)$registrant->email) : '';

        switch ((int)$registration->{EventManager_Model_Registration::FLD_COMMUNICATION_PREFERENCE}) {
            case 2: // Registrant E-mail
                $emails = [$registrantEmail ?: $participantEmail];
                break;
            case 3: // Participant E-mail, Registrant E-mail in CC
                $emails = [
                    $participantEmail,
                    $registrantEmail && $registrantEmail !== $participantEmail ? 'Anmelder: ' . $registrantEmail : ''
                ];
                break;
            case 1: // Participant E-mail
            default:
                $emails = [$participantEmail ?: $registrantEmail];
                break;
        }

        return implode("\n", array_unique(array_filter($emails)));
    }

    protected function _calculatePhone(EventManager_Model_Registration $registration): string
    {
        $participantPhones = $this->_getPhones($registration->{EventManager_Model_Registration::FLD_PARTICIPANT});
        $registrantPhones  = $this->_getPhones($registration->{EventManager_Model_Registration::FLD_REGISTRANT});

        $registrantPhones = array_values(array_diff($registrantPhones, $participantPhones));

        $lines = [];
        if (!empty($participantPhones)) {
            $lines[] = implode(', ', $participantPhones);
        }
        if (!empty($registrantPhones)) {
            $lines[] = 'Anmelder: ' . implode(', ', $registrantPhones);
        }

        return implode("\n", $lines);
    }

    protected function _getPhones($contact): array
    {
        if (!$contact instanceof Tinebase_Record_Interface) {
            return [];
        }
        $phones = [];
        foreach (['tel_prefer_normalized', 'tel_cell_normalized', 'tel_work_normalized'] as $field) {
            $phone = trim((string)$contact->{$field});
            if ('' !== $phone) {
                $phones[] = $phone;
            }
        }
        return array_values(array_unique($phones));
    }

    protected function _calculateGender($participant): string
    {
        if (!$participant instanceof Tinebase_Record_Interface) {
            return '';
        }
        switch ((string)$participant->salutation) {
            case 'MR':
                return 'm';
            case 'MS':
                return 'w';
            default:
                return '';
        }
    }

    protected function _calculateAge($participant, EventManager_Model_Event $event)
    {
        if (!$participant instanceof Tinebase_Record_Interface || empty($participant->bday)) {
            return '';
        }
        $bday = $participant->bday instanceof Tinebase_DateTime
            ? $participant->bday
            : new Tinebase_DateTime($participant->bday);
        $reference = $event->{EventManager_Model_Event::FLD_START} instanceof Tinebase_DateTime
            ? $event->{EventManager_Model_Event::FLD_START}
            : Tinebase_DateTime::now();
        $birthDate = new DateTime($bday->format('Y-m-d'));
        $referenceDate = new DateTime($reference->format('Y-m-d'));
        return $birthDate->diff($referenceDate)->y;
    }

    protected function _calculateFunction(EventManager_Model_Registration $registration): string
    {
        $function = (string)$registration->{EventManager_Model_Registration::FLD_FUNCTION};
        if ($function == 1) { //Teilnehmende
            return 'S';
        }
        return $function;
    }

    protected function _resolveKeyField(string $configName, $id): string
    {
        if (null === $id || '' === $id) {
            return '';
        }

        $keyField = EventManager_Config::getInstance()->get($configName);
        $record = $keyField->records->getById($id);
        if (!$record) {
            return (string)$id;
        }
        return Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME)
            ->_((string)$record->value);
    }

    protected function _formatDate($date): string
    {
        if (empty($date)) {
            return '';
        }

        if (!$date instanceof Tinebase_DateTime) {
            $date = new Tinebase_DateTime($date);
        }

        return $date->format('d.m.Y');
    }

    protected function _calculateAddress(EventManager_Model_Registration $registration): string
    {
        $participantAddress = $this->_formatAddress($registration->{EventManager_Model_Registration::FLD_PARTICIPANT});
        $registrantAddress  = $this->_formatAddress($registration->{EventManager_Model_Registration::FLD_REGISTRANT});

        $blocks = [];
        if ('' !== $participantAddress) {
            $blocks[] = $participantAddress;
        }
        if ('' !== $registrantAddress && $registrantAddress !== $participantAddress) {
            $blocks[] = "Anmelder:\n" . $registrantAddress;
        }
        return implode("\n\n", $blocks);
    }

    protected function _formatAddress($contact): string
    {
        if (!$contact instanceof Tinebase_Record_Interface) {
            return '';
        }
        $lines = [
            trim((string)$contact->adr_one_street),
            trim(trim((string)$contact->adr_one_postalcode) . ' ' . trim((string)$contact->adr_one_locality)),
            trim((string)$contact->adr_one_countryname),
        ];
        return implode("\n", array_filter($lines));
    }

    protected function _buildAnswerList(EventManager_Model_Registration $registration): array
    {
        $items = [];
        foreach ($registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} ?? [] as $booked) {
            $class      = $booked->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG_CLASS};
            $isCheckbox = $class === EventManager_Model_Selections_Checkbox::class;
            $isText     = $class === EventManager_Model_Selections_TextInput::class;
            $option     = $booked->{EventManager_Model_BookedOption::FLD_OPTION};
            if ((!$isCheckbox && !$isText) || !$option instanceof Tinebase_Record_Interface) {
                continue;
            }

            $config   = $booked->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG};
            $name     = rtrim((string)$option->{EventManager_Model_Option::FLD_NAME_OPTION}, ': ');
            $group    = rtrim(trim((string)$option->{EventManager_Model_Option::FLD_GROUP}), ': ');
            $isBooked = $isCheckbox && $config && $config->booked;
            $response = $isText && $config ? trim((string)$config->response) : '';

            if ('' !== $group && $isCheckbox) {
                // grouped checkbox: only the chosen one, "Group: Option"
                if ($isBooked) {
                    $items[] = ['label' => $group, 'value' => $name];
                }
            } else {
                $items[] = ['label' => $name, 'value' => $isCheckbox ? ($isBooked ? 'Ja' : 'Nein') : $response];
            }
        }
        return $items;
    }

    protected function _getTwigContext(array $context)
    {
        $context = parent::_getTwigContext($context);
        $context['EVENT'] = $this->_event;
        return $context;
    }

    protected function _renderTwigTemplate($_record = null)
    {
        if (null === $_record) {
            $_record = $this->_records['REGISTRATIONS']->getFirstRecord();
        }
        parent::_renderTwigTemplate($_record);
    }
}
