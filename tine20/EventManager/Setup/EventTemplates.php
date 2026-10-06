<?php

declare(strict_types=1);

/**
 * tine Groupware
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * class for EventManager Templates initialization
 *
 * @package     Setup
 */
class EventManager_Setup_EventTemplates extends EventManager_Setup_DemoData
{
    /**
     * holds the instance of the singleton
     *
     * @var EventManager_Setup_EventTemplates
     */
    private static $_instance = null;

    /**
     * the application name to work on
     *
     * @var string
     */
    protected $_appName = EventManager_Config::APP_NAME;

    /**
     * required apps
     *
     * @var array
     */
    protected static array $_requiredApplications = ['Admin','Addressbook'];

    /**
     * models to work on
     * @var array
     */
    protected $_models = [
        EventManager_Model_Event::MODEL_NAME_PART,
        EventManager_Model_Option::MODEL_NAME_PART,
        EventManager_Model_Registration::MODEL_NAME_PART,
        EventManager_Model_Appointment::MODEL_NAME_PART,
        EventManager_Model_Selection::MODEL_NAME_PART,
    ];

    private ?array $_templateContext = null;

    private const OPTION_TYPES = [
        'checkbox'   => [EventManager_Model_CheckboxOption::class,  'setOptionConfigCheckboxDemoData'],
        'text_input' => [EventManager_Model_TextInputOption::class, 'setOptionConfigTextInputDemoData'],
        'text'       => [EventManager_Model_TextOption::class,      'setOptionConfigTextDemoData'],
        'file'       => [EventManager_Model_FileOption::class,      'setOptionConfigFileDemoData'],
    ];

    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     *
     * @return boolean
     */
    public static function hasBeenRun()
    {
        try {
            $containerName = EventManager_Config::getInstance()
                ->get(EventManager_Config::EVENT_TEMPLATES_CONTAINER_NAME);
            $container = Tinebase_Container::getInstance()->getContainerByName(
                EventManager_Model_Event::class,
                $containerName,
                Tinebase_Model_Container::TYPE_SHARED
            );

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(EventManager_Model_Event::class, [
                ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()],
            ]);

            return EventManager_Controller_Event::getInstance()->search($filter)->count() > 0;
        } catch (Tinebase_Exception_NotFound) {
            return false;
        }
    }

    public static function hasOptionTemplatesBeenRun()
    {
        try {
            $containerName = EventManager_Config::getInstance()
                ->get(EventManager_Config::EVENT_TEMPLATES_CONTAINER_NAME);
            $container = Tinebase_Container::getInstance()->getContainerByName(
                EventManager_Model_Event::class,
                $containerName,
                Tinebase_Model_Container::TYPE_SHARED
            );

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(EventManager_Model_Event::class, [
                ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()],
            ]);

            if (EventManager_Controller_Event::getInstance()->search($filter)->count() > 0) {
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    EventManager_Model_EventLocalization::class,
                    [
                        ['field' => 'type', 'operator' => 'equals', 'value' => 'name'],
                        ['field' => 'text', 'operator' => 'equals', 'value' => 'Veranstaltung für Optionsvorlagen'],
                    ]
                );
                return EventManager_Controller_EventLocalization::getInstance()->search($filter)->count() > 0;
            }

            return false;
        } catch (Tinebase_Exception_NotFound) {
            return false;
        }
    }

    /**
     * the singleton pattern
     *
     * @return EventManager_Setup_EventTemplates
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== null) {
            self::$_instance = null;
        }
    }

    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $this->createTemplates();
        //$this->createEventForOptionTemplates();
    }

    public function createTemplates(): void
    {
        if (self::hasBeenRun()) {
            return;
        }

        $config = EventManager_Config::getInstance();
        $notRequired = [
            EventManager_Model_Option::FLD_OPTION_REQUIRED =>
                $config->get(EventManager_Config::OPTION_REQUIRED_TYPE)->records->getById('2'),
        ];
        $conditional = [
            EventManager_Model_Option::FLD_OPTION_REQUIRED =>
                $config->get(EventManager_Config::OPTION_REQUIRED_TYPE)->records->getById('3'),
            EventManager_Model_Option::FLD_DISPLAY =>
                $config->get(EventManager_Config::DISPLAY_TYPE)->records->getById('2'),
        ];

        $templates = [];

        // template 1
        $gGemeinde = 'Hier mit melde ich mein Kind zur Erstkommunionsvorbereitung in folgender Gemeinde an:';
        $templates[] = $this->_createTemplate('Erstkommunion', [
            $this->_opt('checkbox', 'Hl. Familie', $gGemeinde, 1),
            $this->_opt('checkbox', 'St. Hedwig', $gGemeinde, 2),
            $this->_opt('checkbox', 'St. Annen', $gGemeinde, 3),
            $this->_opt('checkbox', 'Mein Kind wurde innerhalb der Pfarrei XXX getauft.', 'Taufe', 10),
            $this->_opt('text_input', 'Taufdatum', 'Taufe', 11, [false]),
            $this->_opt('text_input', 'Taufort', 'Taufe', 12, [false]),
            $this->_opt('text_input', 'Taufkirche', 'Taufe', 13, [false]),
            $this->_opt('text_input', 'Name der Mutter', 'Taufe', 14, [false]),
            $this->_opt('text_input', 'Name des vaters', 'Taufe', 15, [false]),
            $this->_opt('file', 'Kopie der Taufurkunde (wenn vorhanden)', 'Taufe', 16, [false]),
            $this->_opt('text', 'Ich bin damit einverstanden, dass mein Kind:', '', 20),
            $this->_opt('checkbox', 'mit Vornamen auf dem Liederzettel des Erstkommunionsgottesdienstes genannt wird', '', 21, extra: $notRequired),
            $this->_opt('checkbox', 'mit Namen und Foto auf Aushängen in der Kirche zu sehen ist', '', 22),
            $this->_opt('file', 'Datenschutzerklärung', 'Datenschutzinformation', 30, [true]),
        ]);

        // template 2
        $gGemeinde = 'Aus welcher Gemeinde kommen Sie?';
        $templates[] = $this->_createTemplate('Kinderbibeltag', [
            $this->_opt('checkbox', 'Vegetarisch', 'Verpflegung', 1),
            $this->_opt('checkbox', 'Mit Fleisch', 'Verpflegung', 2),
            $this->_opt('text_input', 'Allergien/Unverträglichkeiten', 'Verpflegung', 3),
            $this->_opt('checkbox', 'Heilige Familie', $gGemeinde, 10),
            $this->_opt('checkbox', 'St. Hedwig', $gGemeinde, 11),
            $this->_opt('checkbox', 'St. Annen', $gGemeinde, 12),
            $this->_opt('text_input', 'Andere Gemeinde:', $gGemeinde, 13, [false]),
            $this->_opt('text_input', '(optional)', 'Ihre Nachricht an uns', 14, extra: $notRequired),
        ], "Onkel Quentin wird uns am 19.09.2026 zwischen 10:00 und 16:00 Uhr mit auf seine Entdeckungsreise in die Geschichte von Petrus in seinem geheimnisvollen Buch nehmen. \n"
            . 'Wir freuen uns, dass Du beim Kinderbibeltag dabei sein möchtest!');

        // template 3
        $gNotfall = 'Notfallkontakt in der Zeit des Zeltlagers:';
        $gSchwimm = 'Schwimmkenntnisse des Kindes';
        $haftungIntro = 'Für uns gehört es zu einer guten Vorbereitung, auch alle rechtlichen Fragen vorher genau zu klären.
Da unsere Leiter ehrenamtlich tätig sind, können wir ihnen keine weitgehende persönliche Haftung
auferlegen. Unsere Haftung ist daher wie folgt auf die Versicherungsleistung beschränkt:';
        $haftungText = '• Die persönliche Haftung der Leiter ist über die Leistung der vorhandenen Versicherung hinaus
ausgeschlossen, soweit dies gesetzlich zulässig ist, also auch im Falle einer Fahrlässigkeit. Dies gilt
insbesondere, wenn ein Teilnehmer einen Schaden erleidet oder verursacht, nachdem er eine
Weisung der Leitung unbeachtet gelassen oder sich unerlaubt von der Gemeinschaft entfernt hat.

- Verursacht ein Teilnehmer einen Schaden, für welchen ein Leiter in Anspruch genommen wird, so ist
dieser verpflichtet, den in Anspruch Genommenen von der Haftung freizustellen, soweit keine
Versicherung den Schaden übernimmt.

Ich wurde über das Programm des Zeltlagers informiert und weiß, dass u. A. folgende
Programmpunkte durchgeführt werden: Stadttag, Nachtspiele, Sternlauf, Sauerei, Postenläufe,
Waldspiele, Schwimmen, Küchen- & Klodienst';

        $templates[] = $this->_createTemplate('Zeltlager', [
            $this->_opt('text_input', 'Jahre', 'Alter im Zeltlager:', 1, [false]),
            $this->_opt('text_input', 'Name', $gNotfall, 10, [false]),
            $this->_opt('text_input', 'Telefonnummer', $gNotfall, 11, [false]),
            $this->_opt('text_input', 'E-Mailadresse', $gNotfall, 12, [false]),
            $this->_opt('text', 'Wir benötigen die nachfolgenden Angaben, um Ihr Kind während der Fahrt vor gesundheitlichen Gefahren bewahren und in Notfallsituationen richtig handeln zu können!', '', 20),
            $this->_opt('checkbox', 'Unser/Mein Kind muss während der Fahrt Medikamente einnehmen', '', 21),
            $this->_opt('text_input', 'Arzneimittel / Einnahmeturnus / Menge:', '', 22, extra: $conditional),
            $this->_opt('checkbox', 'Es sind Allergien/Unverträglichkeiten zu beachten', '', 23),
            $this->_opt('text_input', 'Unser/Mein Kind leidet unter folgenden Allergien/Unverträglichkeiten:', '', 24, extra: $conditional),
            $this->_opt('text_input', 'Nahrungsgewohnheiten', '', 25),
            $this->_opt('checkbox', 'Unser/Mein Kind ist Vergetarier/in:', '', 26),
            $this->_opt('text', 'Ich bin damit einverstanden, dass...', '', 27),
            $this->_opt('checkbox', 'mein Kind für bestimmte Unternehmungen und im begrenztem Umfang (z.B. Stadtbesichtigung, Einkäufe ect.) in Kleingruppen (mind. 3 Personen) ohne Aufsichtsperson unterwegs sein darf.', '', 28),
            $this->_opt('checkbox', 'mein Kind an Badeausflügen unter Aufsicht teilnehmen kann.', '', 29),
            $this->_opt('checkbox', 'Mein Kind ist Nichtschwimmer', $gSchwimm, 30, extra: $conditional),
            $this->_opt('checkbox', 'Mein Kind ist Schwimmer/Schwimmerin', $gSchwimm, 31, extra: $conditional),
            $this->_opt('text_input', 'Mein Kind hat folgendes Schwimmabzeichen', $gSchwimm, 32, extra: $conditional),
            $this->_opt('checkbox', 'mein Kind in PKW/Kleinbus mit Leitenden als Fahrer/Fahrerin mitfahren darf.', '', 50),
            $this->_opt('checkbox', 'mein Kind im Falle einer kleineren Verletzung (z.B. Schnitt, Schürfwunde, ect.) ein handelsübliches Pflaster erhält.', '', 51),
            $this->_opt('checkbox', 'mein Kind bei einem Zeckenbiss die Zecke von einer Betreuungsperson entfernt bekommt.', '', 52),
            $this->_opt('checkbox', 'mein Kind, wenn es ständig oder in einer schwerwiegenden Sache gegen die Anordnung der GruppenleiterInnen verstößt, nach Rücksprache mit Frau Ann-Kathrin Berndmeyer - pastorale Mitarbeiterin - und mir auf eigene Kosten und Verantwortung vorzeitig nach Hause geschickt oder von mir abgeholt werden kann.', '', 53),
            $this->_opt('text_input', 'Sonstige Dinge, die beachtet werden müssen oder die Sie uns mitteilen möchten:', '', 60, extra: $notRequired),
            $this->_opt('text', $haftungIntro, '', 70, [$haftungText]),
            $this->_opt('checkbox', 'Hiermit erkläre ich/wir mich/uns einverstanden, dass ein geringfügiger Überschuss nicht an mich zurückgezahlt werden muss, sondern für die Zeltlager der folgenden Jahre verwendet werden darf.', 'Verzicht auf Rückerstattung Überschüsse', 80),
            $this->_opt('file', 'Ich/Wir haben die "Belehrung für Eltern und Sonstige Sorgeberechtigte gem. §34 Abs. 5, S.2 Infektionsschutzgesetz (IfSG)" zur Kenntnis genommen.', 'Belehrung nach Infektionsschutzgesetz', 90),
            $this->_opt('file', 'Einwilligungserklärung über die Erstellung von Foto und Videoaufnahmen meines Kindes während der Veranstaltung. (Bitte die Einwilligung ausfüllen und unterschrieben hier wieder hochladen)', 'Foto und Videoaufnahmen', 110, [false]),
            $this->_opt('file', 'Datenschutz Information für das Zeltlager', 'Datenschutzhinweis', 200),
        ]);

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Event Templates were created');

        $this->_applyOptionRules($templates, [
            'Arzneimittel / Einnahmeturnus / Menge:' =>
                'Unser/Mein Kind muss während der Fahrt Medikamente einnehmen',
            'Unser/Mein Kind leidet unter folgenden Allergien/Unverträglichkeiten:' =>
                'Es sind Allergien/Unverträglichkeiten zu beachten',
            'Mein Kind ist Nichtschwimmer' =>
                'mein Kind an Badeausflügen unter Aufsicht teilnehmen kann.',
            'Mein Kind ist Schwimmer/Schwimmerin' =>
                'mein Kind an Badeausflügen unter Aufsicht teilnehmen kann.',
            'Mein Kind hat folgendes Schwimmabzeichen' =>
                'Mein Kind ist Schwimmer/Schwimmerin',
        ]);
    }

    protected function _applyOptionRules(array $templates, array $rulesMap): void
    {
        foreach ($templates as $template) {
            $byName = [];
            foreach ($template->{EventManager_Model_Event::FLD_OPTIONS} as $opt) {
                $byName[$opt->{EventManager_Model_Option::FLD_NAME_OPTION}] = $opt;
            }

            $changed = false;
            foreach ($rulesMap as $dependentName => $triggerName) {
                if (!isset($byName[$dependentName], $byName[$triggerName])) {
                    continue;
                }

                $byName[$dependentName]->{EventManager_Model_Option::FLD_OPTION_RULE} = [
                    $this->setOptionsRuleConfigDemoData($byName[$triggerName]->getId(), 1, ''),
                ];
                $changed = true;
            }

            if ($changed) {
                EventManager_Controller_Event::getInstance()->update($template);
            }
        }
    }

    public function createEventForOptionTemplates(): void
    {
        if (self::hasOptionTemplatesBeenRun()) {
            return;
        }

        $options = array_map(
            fn(array $o) => $o + [EventManager_Model_Option::FLD_IS_OPTION_TEMPLATE => true],
            [
                $this->_opt('checkbox', 'Konventionell', 'Verpflegung', 1),
                $this->_opt('checkbox', 'Vegetarisch', 'Verpflegung', 2),
                $this->_opt('checkbox', 'Vegan', 'Verpflegung', 3),
                $this->_opt('checkbox', 'Ich nehme nicht an den Mahlzeiten teil', 'Verpflegung', 4),
                $this->_opt('text_input', 'Allergien und Unverträglichkeiten', 'Verpflegung', 5),
                $this->_opt('checkbox', 'Einzelzimmer', 'Unterbringung', 6),
                $this->_opt('checkbox', 'Doppelzimmer', 'Unterbringung', 7),
                $this->_opt('checkbox', 'Keine Übernachtung', 'Unterbringung', 8),
                $this->_opt('text_input', 'Doppelzimmer mit...', 'Unterbringung', 9),
                $this->_opt('file', 'Einwilligungserklärung über die Erstellung von Foto und Videoaufnahmen während der Veranstaltung. (Bitte die Einwilligung ausfüllen und unterschrieben hier wieder hochladen)', null, 20, [false]),
                $this->_opt('file', 'Datenschutz Information', null, 30),
            ]
        );

        $this->_createTemplate('Veranstaltung für Optionsvorlagen', $options);

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Event for Option Templates was created');
    }

    protected function _getTemplateContext(): array
    {
        if ($this->_templateContext === null) {
            $config = EventManager_Config::getInstance();
            $config->set(EventManager_Config::JWT_SECRET, 'jwtSecretCreatedFromEventManagerTemplates');

            $this->_templateContext = [
                'container_id' => EventManager_Setup_Initialize::_getOrCreateSharedEventContainer(
                    $config->get(EventManager_Config::EVENT_TEMPLATES_CONTAINER_NAME),
                    EventManager_Model_Event::class,
                    EventManager_Config::APP_NAME
                )->getId(),
                'type'           => $config->get(EventManager_Config::EVENT_TYPE)->records->getById('1'),
                'status'         => $config->get(EventManager_Config::EVENT_STATUS)->records->getById('2'),
                'contact_fields' => $this->_getDefaultContactFields(),
            ];
        }
        return $this->_templateContext;
    }

    protected function _localized(string $text, string $lang = 'de'): array
    {
        return [[
            EventManager_Model_EventLocalization::FLD_LANGUAGE => $lang,
            EventManager_Model_EventLocalization::FLD_TEXT     => $text,
        ]];
    }

    protected function _createTemplate(
        string $name,
        array $options,
        string $description = 'BESCHREIBUNG HIER'
    ): EventManager_Model_Event {
        $ctx = $this->_getTemplateContext();

        return EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_CONTAINER_ID                => $ctx['container_id'],
            EventManager_Model_Event::FLD_NAME                        => $this->_localized($name),
            EventManager_Model_Event::FLD_START                       => '',
            EventManager_Model_Event::FLD_END                         => '',
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL => '',
            EventManager_Model_Event::FLD_LOCATION_RECORD             => '',
            EventManager_Model_Event::FLD_TYPE                        => $ctx['type'],
            EventManager_Model_Event::FLD_STATUS                      => $ctx['status'],
            EventManager_Model_Event::FLD_FEE                         => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                => '',
            EventManager_Model_Event::FLD_BOOKED_PLACES               => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES            => '',
            EventManager_Model_Event::FLD_PARTICIPANT_CONTACT_FIELDS  => $ctx['contact_fields'],
            EventManager_Model_Event::FLD_REGISTRANT_CONTACT_FIELDS   => $ctx['contact_fields'],
            EventManager_Model_Event::FLD_IS_TEMPLATE                 => true,
            EventManager_Model_Event::FLD_OPTIONS                     => $options,
            EventManager_Model_Event::FLD_REGISTRATIONS               => [],
            EventManager_Model_Event::FLD_APPOINTMENTS                => [],
            EventManager_Model_Event::FLD_DESCRIPTION                 => $this->_localized($description),
        ]));
    }

    protected function _opt(
        string $type,
        string $name,
        ?string $group,
        int $sorting,
        array $configArgs = [],
        array $extra = []
    ): array {
        [$class, $configMethod] = self::OPTION_TYPES[$type];

        $option = [
            EventManager_Model_Option::FLD_NAME_OPTION         => $name,
            EventManager_Model_Option::FLD_OPTION_CONFIG       => $this->$configMethod(...$configArgs),
            EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => $class,
            EventManager_Model_Option::FLD_SORTING             => $sorting,
        ];
        if ($group !== null) {
            $option[EventManager_Model_Option::FLD_GROUP] = $group;
        }
        return $option + $extra;
    }
}
