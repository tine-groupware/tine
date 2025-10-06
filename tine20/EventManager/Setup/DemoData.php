<?php

/**
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de> <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for EventManager initialization
 *
 * @package     Setup
 */
class EventManager_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var EventManager_Setup_DemoData
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
    protected static $_requiredApplications = ['Admin','Addressbook'];

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
        $c = EventManager_Controller_Event::getInstance();
        return $c->getAll()->count() > 1;
    }

    /**
     * the singleton pattern
     *
     * @return EventManager_Setup_DemoData
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
        $this->_createEvents();
    }

    protected function _createEvents()
    {
        EventManager_Config::getInstance()->set(EventManager_Config::JWT_SECRET, 'jwtSecretCreatedFromEventManagerDemoData');

        $location = $this->getLocation(
            'Familienferienstätte St. Ursula',
            'Ribnitzer Str. 1',
            '18181',
            'Graal-Müritz'
        );

        $event_type = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_TYPE)->records->getById('1');
        $event_status = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_STATUS)
            ->records->getById('1');

        $erwachsene = $this->setOptionConfigCheckboxDemoData(225, 'Übernachtung');
        $kinder17 = $this->setOptionConfigCheckboxDemoData(120, 'Übernachtung');
        $kinder9 = $this->setOptionConfigCheckboxDemoData(90, 'Übernachtung');
        $kinder3 = $this->setOptionConfigCheckboxDemoData(0, 'Übernachtung kostenlos');

        $christoph = $this->getContact('Christoph', 'Riethmüller');
        $daniela = $this->getContact('Daniela', 'Braker');
        $heiner = $this->getContact('Heiner', 'Arden');
        $alexandra = $this->getContact('Alexandra', 'Avermiddig');

        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Familienexerzitien 2025',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-10-20"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-10-24"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => new Tinebase_DateTime("2025-09-21"),
            EventManager_Model_Event::FLD_LOCATION                      => $location,
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 39,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [
                [
                EventManager_Model_Option::FLD_NAME_OPTION => 'Erwachsene',
                EventManager_Model_Option::FLD_OPTION_CONFIG => $erwachsene,
                EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                EventManager_Model_Option::FLD_GROUP => 'Kosten',
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Kinder 10-17 Jahre',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $kinder17,
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Kosten',
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Kinder 4-9 Jahre',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $kinder9,
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Kosten',
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Kinder 0-3 Jahre',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $kinder3,
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Kosten',
                ],
            ],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $christoph,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'Wir laden Sie herzlich zu unseren Familienexerzitien im Erzbistum Hamburg ein!
HIER EINIGE STICHWORTE, WAS SIE ERWARTET
Die Familienexerzitien richten sich an alle Familien – Eltern mit Kindern, Ein-Eltern-Familien, Patchworkfamilien und 
Kinder mit ihren Großeltern oder Pat_innen –, die sich eine Auszeit vom Alltag nehmen möchten, um Zeit füreinander und für den eigenen Glauben zu finden.

IN DIESEM JAHR STEHEN DIE FAMILIENEXERZITIEN UNTER DEM TITEL „ICH SEGNE DICH UND DU SOLLST EIN SEGEN SEIN“.
Diese Zusage gibt Gott Abraham mit auf den Weg, als er seine Heimat verlässt und in das verheißene Land aufbricht.
Wir können diese Zusage Gottes aber auch für uns selbst hören, wenn wir daran denken, dass Gott die ganze Schöpfung segnet.
Aus dem Segen Gottes dürfen wir leben und Kraft schöpfen. Dadurch gestärkt können wir zum Segen für andere werden.
Während der Familienexerzitien soll uns das Thema „Segen“ begleiten. Wir schauen, welchen Segen wir im Leben schon empfangen haben.
Welcher Segen ist ausgeblieben? Wie kann ich zum Segen werden?

KINDER UND ERWACHSENE – PERSÖNLICH UND IN DER FAMILIE GLAUBEN
Die Familien werden Zeit zur gemeinsamen Gestaltung haben. Darüber hinaus wird es Zeitfenster geben,
in denen sich die Kinder in Begleitung von qualifizierten Betreuungspersonen dem Thema der Woche altersgerecht nähern.
Die Erwachsenen haben Zeit zum Austausch, zur Vertiefung und zum Gebet.


### Geplanter Verlauf

Montag, 20. Oktober, ab 17 Uhr

* Ankommen, Zimmerbezug, Kennenlernen

Dienstag, 21. Oktober, bis Donnerstag, 23. Oktober

* Morgenimpuls
* gemeinsames Frühstück
* Zeit für die Erwachsenen und die Kinder unter sich
* Mittagsgebet
* gemeinsames Mittagessen
* freie Gestaltungszeit für die Familie
* Kaffeetrinken
* Zeit für die Erwachsenen und die Kinder unter sich (am Donnerstag gemeinsame Zeit)
* gemeinsames Abendessen
* Gute-Nacht-Geschichte und Segen
* gemütlicher Ausklang des Tages

Freitag, 24. Oktober

* Abschluss der Exerzitien
* Reflexion und Abreise nach dem Mittagessen

Nach Ihrer Anmeldung erhalten Sie von uns eine Rechnung mit dem genauen Preis und den Zahlungsinformationen.

Ausfallgebühren:

* Ab 4 Wochen vor der Veranstaltung: 40 % des Teilnehmendenbetrags
* Ab 2 Wochen vor der Veranstaltung: 70 % des Teilnehmendenbetrags

### Zusatzinformationen

Da die Anzahl der Teilnehmenden begrenzt ist, bitten wir Sie, sich frühzeitig anzumelden.
Für alle Fragen rund um die Familienexerzitien melden Sie sich gern bei:
Christoph Riethmüller; Telefon: 0151 65020455; christoph.riethmueller@erzbistum-hamburg.org',
        ]));

        // event 2
        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Ausbildungskurs für Gottesdienstbeauftragte',
            EventManager_Model_Event::FLD_START                         => '',
            EventManager_Model_Event::FLD_END                           => '',
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => new Tinebase_DateTime("2025-09-15"),
            EventManager_Model_Event::FLD_LOCATION                      => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => 250,
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => '',
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $daniela,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'Das gottesdienstliche Leben hat seit dem II. Vatikanischen Konzil eine grundlegende Veränderung erfahren - mit ihr auch die Rolle der Gläubigen, da das Konzil eine volle, bewusste und tätige Teilnahme an den liturgischen Feiern unterstützt, wie sie das Wesen der Liturgie selbst verlangt und zu der das christliche Volk - kraft der Taufe - berechtigt und verpflichtet ist. (SC 14)

Anmeldung:

Dieser Kompaktkurs richtet sich an Ehrenamtliche, die sich im Bereich der Liturgie bereits engagieren... z.B. als Kommunionhelfer_innen oder Küster_innen. 

Die Teilnahme am gesamten Ausbildungskurs ist Voraussetzung für die Beauftragung.
Bei der Anmeldung sind folgende Unterlagen abzugeben:
- schriftliche Bestätigung des Pfarrers (nach vorheriger Rücksprache mit Pfarrpastoralrat)
- kurzes Motivationsschreiben

Das Mindestalter beträgt 25 Jahre (Jüngere Interessierte melden sich bitte im Referat Liturgie).
Die Teilnehmer_innen müssen im Besitz der kirchlichen Rechte sein, getauft und gefirmt, sowie in Familie, Gemeinde und Beruf bewährt sein.',
        ]));

        // event 3

        $location = $this->getLocation(
            'St. Ansgarhaus',
            'Schmilinskystraße 78',
            '20099',
            'Hamburg'
        );

        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Modul 1',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-10-17"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-10-18"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => new Tinebase_DateTime("2025-09-15"),
            EventManager_Model_Event::FLD_LOCATION                      => $location,
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 13,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $daniela,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'folgt.',
        ]));


        // event 4

        $location = $this->getLocation(
            'Kloster Nütschau',
            'Schloßstraße 26',
            '23843',
            'Travenbrück'
        );

        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Modul 2',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-11-07"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-11-09"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => '',
            EventManager_Model_Event::FLD_LOCATION                      => $location,
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 13,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $daniela,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'folgt.',
        ]));

        // event 5

        $location = $this->getLocation(
            'St. Ansgarhaus',
            'Schmilinskystraße 78',
            '20099',
            'Hamburg'
        );

        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Modul 3',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-10-17"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-10-18"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => '',
            EventManager_Model_Event::FLD_LOCATION                      => $location,
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 13,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $daniela,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'folgt.',
        ]));

        // event 6
        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Gesänge für die Advents- und Weihnachtszeit',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-11-08"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-11-08"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => new Tinebase_DateTime("2025-09-15"),
            EventManager_Model_Event::FLD_LOCATION                      => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 20,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $heiner,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'In der Messfeier gibt es vielfältige Einsatzmöglichkeiten für eine Kantorin / einen Kantor bzw. eine kl. Ansingegruppe / Schola.
Für die Advents- und Weihnachtszeit werden Wechselgesänge aus dem Gebet- und Gesangbuch GOTTESLOB erarbeitet und Gestaltungsmöglichkeiten aus dem 
- Münchener Kantorale, 
- den Freiburger Kantorenbüchern, 
- dem St. Galler Kantorenbuch 
und aus weiteren Materialien vorgestellt.',
        ]));

        // event 7
        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Eingeladen zum Fest des Glaubens',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-11-20"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-11-20"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => new Tinebase_DateTime("2025-11-20"),
            EventManager_Model_Event::FLD_LOCATION                      => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 20,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'Herzliche Einladung zu einem nächsten Online-Abend zum Thema "Familiengottesdienste".
Heute im Mittelpunkt: der Eröffnungsteil. Was kann man da eigentlich machen - was darf ich und welche Ideen gibt es dazu? 
Eine Veranstaltung des Netzwerks "Kindergottesdienst katholisch" 
www.kindergottesdienst-katholisch.de',
        ]));

        // event 8
        EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_NAME                          => 'Religiöse Vielfalt in der Kita religionssensibel begegnen',
            EventManager_Model_Event::FLD_START                         => new Tinebase_DateTime("2025-11-08"),
            EventManager_Model_Event::FLD_END                           => new Tinebase_DateTime("2025-11-08"),
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => new Tinebase_DateTime("2025-09-15"),
            EventManager_Model_Event::FLD_LOCATION                      => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => 20,
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_OPTIONS                       => [],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [[
                EventManager_Model_Registration::FLD_NAME => $alexandra,
            ]],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => 'Unsere katholischen Kitas sind in der heutigen Zeit in vielen Bereichen heterogen.
Uns begegnen Familien in verschiedenen Beziehungsformen, aus unterschiedlichen Milieus und aus unterschiedlichen Religionen.

Dieser Selbstlernkurs soll besonders für den letzten Punkt sensibilisieren. Die große Frage dabei ist:
Wie gehen wir respektvoll und religionssensibel mit den unterschiedlichen Religions- und Glaubensformen in unserer Kita um,
ohne das katholische, das christliche Profil zu verwässern?

Dafür ist sowohl eine Kenntnis der Situation vor Ort,
ein Grundwissen zu den unterschiedlichen Religionen und eine Vergewisserung des eigenen Glaubens notwendig.
Und das soll Ihnen dieser Kurs auch bieten. Hinzukommt die intensive Auseinandersetzung mit unterschiedlichen Schwerpunkten,
die zum praktischen Umsetzen von Ideen in der eigenen Kita führen soll.',
        ]));

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating 8 test events'
            . EventManager_Model_Event::MODEL_NAME_PART);
    }

    protected function getContact($n_given, $n_family): Addressbook_Model_Contact
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
                ['field' => 'n_given', 'operator' => 'equals', 'value' => $n_given],
                ['field' => 'n_family', 'operator' => 'equals', 'value' => $n_family],
            ]);
        $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
        if (!$contact) {
            $adbController = Addressbook_Controller_Contact::getInstance();
            $contact = $adbController->create(new Addressbook_Model_Contact([
                'n_given' => $n_given,
                'n_family' => $n_family,
            ]));
        }
        return $contact;
    }

    protected function getLocation(
        $org_name,
        $adr_one_street = '',
        $adr_one_postalcode = '',
        $adr_one_locality = ''
    ): Addressbook_Model_Contact {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
            ['field' => 'org_name', 'operator' => 'equals', 'value' => $org_name],
        ]);
        $location = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
        if (!$location) {
            $adbController = Addressbook_Controller_Contact::getInstance();
            $location = $adbController->create(new Addressbook_Model_Contact([
                'org_name' => $org_name,
                'adr_one_street' => $adr_one_street,
                'adr_one_postalcode' => $adr_one_postalcode,
                'adr_one_locality' => $adr_one_locality,
            ]));
        }
        return $location;
    }

    protected function setOptionConfigCheckboxDemoData(
        $price = '',
        $description = '',
        $total_places = ''
    ): EventManager_Model_CheckboxOption {
        return new EventManager_Model_CheckboxOption([
            'price' => $price,
            'total_places' => $total_places,
            'booked_places' => '',
            'available_places' => '',
            'description' => $description,
        ]);
    }
}
