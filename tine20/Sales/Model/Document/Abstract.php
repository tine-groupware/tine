<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * abstract Document Model
 *
 * @package     Sales
 * @subpackage  Model
 *
 * @property Tinebase_Record_RecordSet $positions
 */
abstract class Sales_Model_Document_Abstract extends Tinebase_Record_NewAbstract
{
    //const MODEL_NAME_PART = ''; // als konkrete document_types gibt es Offer, Order, Delivery, Invoice (keine Gutschrift!)

    public const FLD_ID = 'id';
    public const FLD_DOCUMENT_NUMBER = 'document_number'; // kommt aus incrementable, in config einstellen welches incrementable fuer dieses model da ist!
    public const FLD_DOCUMENT_LANGUAGE = 'document_language';
    public const FLD_DOCUMENT_CATEGORY = 'document_category'; // keyfield - per default "standard". brauchen wir z.B. zum filtern, zur Auswahl von Textbausteinen, Templates etc.

    public const FLD_PRECURSOR_DOCUMENTS = 'precursor_documents'; // virtual, link
    public const FLD_BOILERPLATES = 'boilerplates';

    public const FLD_CUSTOMER_ID = 'customer_id'; // Kunde(Sales) (Optional beim Angebot, danach required). denormalisiert pro beleg, denormalierungs inclusive addressen, exklusive contacts
    public const FLD_CONTACT_ID = 'contact_id'; // Kontakt(Addressbuch) per default AP Extern, will NOT be denormalized
    // TODO FIXME denormalized.... as json in the document or as copy in the db?
    public const FLD_RECIPIENT_ID = 'recipient_id'; // Adresse(Sales) -> bekommt noch ein. z.Hd. Feld(text). denormalisiert pro beleg. muss nicht notwendigerweise zu einem kunden gehören. kann man aus kontakt übernehmen werden(z.B. bei Angeboten ohne Kunden)

    public const FLD_DOCUMENT_TITLE = 'document_title';
    public const FLD_DOCUMENT_DATE = 'date'; // Belegdatum,  defaults empty, today when booked and not set differently
    public const FLD_CUSTOMER_REFERENCE = 'customer_reference'; // varchar 255

    public const FLD_POSITIONS = 'positions'; // virtuell recordSet
    public const FLD_POSITIONS_NET_SUM = 'positions_net_sum';
    public const FLD_POSITIONS_DISCOUNT_SUM = 'positions_discount_sum';

    public const FLD_INVOICE_DISCOUNT_TYPE = 'invoice_discount_type'; // PERCENTAGE|SUM
    public const FLD_INVOICE_DISCOUNT_PERCENTAGE = 'invoice_discount_percentage'; // automatische Berechnung je nach tupe
    public const FLD_INVOICE_DISCOUNT_SUM = 'invoice_discount_sum'; // automatische Berechnung je nach type

    public const FLD_NET_SUM = 'net_sum';
    public const FLD_VAT_PROCEDURE = 'vat_procedure';
    public const FLD_SALES_TAX = 'sales_tax';
    public const FLD_SALES_TAX_BY_RATE = 'sales_tax_by_rate';

    public const FLD_GROSS_SUM = 'gross_sum';

    public const FLD_PAYMENT_TERMS = 'credit_term';

    public const FLD_COST_CENTER_ID = 'cost_center_id';
    public const FLD_COST_BEARER_ID = 'cost_bearer_id'; // ist auch ein cost center
    public const FLD_DESCRIPTION = 'description';

    public const FLD_REVERSAL_STATUS = 'reversal_status';

    // ORDER:
    //  - INVOICE_RECIPIENT_ID // abweichende Rechnungsadresse, RECIPIENT_ID wenn leer
    //  - INVOICE_CONTACT_ID // abweichender Rechnungskontakt, CONTACT_ID wenn leer
    //  - INVOICE_STATUS // // keyfield: offen, gebucht; berechnet sich automatisch aus den zug. Rechnungen
    //  - DELIVERY_RECIPIENT_ID // abweichende Lieferadresse, RECIPIENT_ID wenn leer
    //  - DELIVERY_CONTACT_ID // abweichender Lieferkontakt, CONTACT_ID wenn leer
    //  - DELIVERY_STATUS // keyfield: offen, geliefert; brechnet sich automatisch aus den zug. Lieferungen
    //  pro position:
    //    - 1:n lieferpositionen (verknüpfung zu LS positionen)
    //    - zu liefern (automatisch auf anzahl, kann aber geändert werden um anzahl für erzeugten LS zu bestimmen)
    //    - geliefert (berechnet sich automatisch)
    //    - 1:n rechnungspositionen (verknüpfung zu RG positionen)
    //    - zu berechnen (s.o.)
    //    - berechnet (s.o.)
    //  - ORDER_STATUS // keyfield: eingegangen (order änderbar, nicht erledigt), angenommen (nicht mehr änderbar (AB ist raus), nicht erledigt), abgeschlossen(nicht mehr änderbar, erledigt) -> feld berechnet sich automatisch! (ggf. lassen wir das abschließen doch zu aber mit confirm)

    // DELIVERY
    // - DELIVERY_STATUS // keyfield erstellt(Ungebucht, offen), geliefert(gebucht, abgeschlossen)
    //    NOTE: man könnte einen ungebuchten Status als Packliste einführen z.B. Packliste(ungebucht, offen)

    // INVOICE:
    //  - IS_REVERSED bool // storno
    //  - INVOICE_REPORTING enum (AUTO|MANU) // Rechnungslegung
    //  - INVOICE_TYPE (jetziger TYPE) // Rechnungsart (Rechnung/Storno)
    //  - INVOICE_STATUS: keyfield: proforma(Ungebucht, offen), gebucht(gebucht, offen),  Verschickt(gebucht, offen), Bezahlt(gebucht, geschlossen)
    //  obacht: bei storno rechnung wird der betrag (pro zeile) im vorzeichen umgekehrt

    // Achtung: Hinter den status keyfields verbergen sich jeweils noch fachliche workflows die jeweils noch (dazu) konfiguiert werden können
    //  -> staus übergänge definieren
    //  -> Ungebucht/Gebucht merkmal (Gebucht ist nicht mehr änderbar) (siehe CRM status merkmale)
    //  -> Offen/Abgeschlossen  merkmal (Abgeschlossen fällt aus der standar-filterung) (siehe CRM staus merkmale)
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::RECORD_NAME                   => 'Document', // ngettext('Document', 'Documents', n)
        self::RECORDS_NAME                  => 'Documents', // gettext('GENDER_Document')
        self::TITLE_PROPERTY                => self::FLD_DOCUMENT_NUMBER,
        self::MODLOG_ACTIVE                 => true,
        self::EXPOSE_JSON_API               => true,
        self::EXPOSE_HTTP_API               => true,

        self::HAS_ATTACHMENTS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::HAS_NOTES => false,
        self::HAS_RELATIONS => true,
        self::HAS_TAGS => true,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_CUSTOMER_ID       => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'delivery'              => [],
                        'billing'               => [],
                        'postal'                => [],
                        'cpextern_id'           => [],
                        'cpintern_id'           => [],
                    ],
                ],
                self::FLD_RECIPIENT_ID      => [],
                self::FLD_POSITIONS         => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION => [],
                    ],
                ],
            ]
        ],

        self::FIELDS                        => [
            self::FLD_DOCUMENT_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::LABEL                     => 'Document Number', //_('Document Number')
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    //Tinebase_Numberable::BUCKETKEY         => self::class . '#' . self::FLD_DOCUMENT_NUMBER,
                    //Tinebase_Numberable_String::PREFIX     => 'XX-',
                    Tinebase_Numberable_String::ZEROFILL   => 7,
                    //Tinebase_Numberable::CONFIG_OVERRIDE   => '',
                    // these values will be set dynamically below in inheritModelConfigHook
                ],
                /*self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ]*/
            ],
            self::FLD_REVERSAL_STATUS           => [
                self::LABEL                         => 'Reversal', // _('Reversal')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::DOCUMENT_REVERSAL_STATUS,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_DOCUMENT_LANGUAGE => [
                self::LABEL                 => 'Language', // _('Language')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => Sales_Config::LANGUAGES_AVAILABLE,
                self::SHY                   => true,
            ],
            self::FLD_DOCUMENT_CATEGORY => [
                self::LABEL                 => 'Category', // _('Category')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => Sales_Config::DOCUMENT_CATEGORY,
                self::SHY                   => true,
            ],
            self::FLD_PRECURSOR_DOCUMENTS => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::DISABLED                  => true,
                self::FILTER_DEFINITION         => [self::FILTER => Tinebase_Model_Filter_Text::class],
                self::CONFIG                    => [
                    self::STORAGE                   => self::TYPE_JSON,
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_DynamicRecordWrapper::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_BOILERPLATES      => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::DISABLED              => true,
//                self::LABEL                 => 'Boilerplates', // _('Boilerplates')
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Boilerplate::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Boilerplate::FLD_DOCUMENT_ID,
                ],
//                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => []],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    //Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],

            self::FLD_DOCUMENT_TITLE => [
                self::LABEL                         => 'Title', // _('Title')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER              => true,
            ],
            self::FLD_DOCUMENT_DATE             => [
                self::LABEL                         => 'Document Date', //_('Document Date')
                self::TYPE                          => self::TYPE_DATE,
                self::NULLABLE                      => true,
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
            ],
            self::FLD_CUSTOMER_REFERENCE        => [
                self::LABEL                         => 'Customer Reference', //_('Customer Reference')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::SHY                           => true,
            ],

            self::FLD_CUSTOMER_ID       => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Customer', // _('Customer')
                self::QUERY_FILTER          => true,
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Customer::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Customer::FLD_DOCUMENT_ID,
                ],
                self::VALIDATORS            => [ // only for offers this is allow empty true, by default its false
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_RECIPIENT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Recipient', //_('Recipient')
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Address::FLD_DOCUMENT_ID,
                    self::TYPE                  => Sales_Model_Document_Address::TYPE_POSTAL,
                ],
                self::SHY                   => true,
            ],
            self::FLD_CONTACT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Reference Person', //_('Reference Person')
                // TODO add resolve deleted flag? guess that would be nice
                self::CONFIG                => [
                    self::APP_NAME              => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_PART_NAME,
                ],
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],

            self::FLD_POSITIONS                 => [
                // needs to be set by concret implementation
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::REF_ID_FIELD                  => Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID,
                    self::DEPENDENT_RECORDS             => true,
                    self::PAGING                        => ['sort' => Sales_Model_DocumentPosition_Abstract::FLD_SORTING],
                ],
            ],
            self::FLD_POSITIONS_NET_SUM                   => [
                self::LABEL                         => 'Positions Net Sum', //_('Positions Net Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_POSITIONS_DISCOUNT_SUM   => [
                self::LABEL                         => 'Positions Discount Sum', //_('Positions Discount Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],

            self::FLD_INVOICE_DISCOUNT_TYPE     => [
                self::LABEL                         => 'Invoice Discount Type', //_('Invoice Discount Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::INVOICE_DISCOUNT_TYPE,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::DEFAULT_VAL                   => 'SUM'
            ],
            self::FLD_INVOICE_DISCOUNT_PERCENTAGE => [
                self::LABEL                         => 'Invoice Discount Percentage', //_('Invoice Discount Percentage')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::NULLABLE                      => true,
            ],
            self::FLD_INVOICE_DISCOUNT_SUM      => [
                self::LABEL                         => 'Invoice Discount Sum', //_('Invoice Discount Sum')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DISCOUNT,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    'price_field'   => self::FLD_POSITIONS_NET_SUM,
                    'net_field'     => self::FLD_NET_SUM
                ],
            ],

            self::FLD_NET_SUM                   => [
                self::LABEL                         => 'Net Sum', //_('Net Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_VAT_PROCEDURE => [
                self::LABEL => 'VAT Procedure', // _('VAT Procedure')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::VAT_PROCEDURES,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            self::FLD_SALES_TAX                 => [
                self::LABEL                         => 'Sales Tax', //_('Sales Tax')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SALES_TAX_BY_RATE         => [
                self::LABEL                         => 'Sales Tax by Rate', //_('Sales Tax by Rate')
                self::TYPE                          => self::TYPE_JSON,
                self::NULLABLE                      => true,
                self::DISABLED                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_GROSS_SUM                 => [
                self::LABEL                         => 'Gross Sum', //_('Gross Sum')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],

            self::FLD_PAYMENT_TERMS             => [
                self::LABEL                         => 'Credit Term (days)', // _('Credit Term (days)')
                self::TYPE                          => self::TYPE_INTEGER,
                self::UNSIGNED                      => true,
                self::SHY                           => TRUE,
                self::NULLABLE                      => true,
            ],

            self::FLD_COST_CENTER_ID            => [
                self::LABEL                         => 'Cost Center', //_('Cost Center')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_CostCenter::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_COST_BEARER_ID            => [
                self::LABEL                         => 'Cost Bearer', //_('Cost Bearer')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_CostUnit::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Internal Note', //_('Internal Note')
                self::TYPE                          => self::TYPE_TEXT,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::SHY                           => true,
            ],
        ]
    ];

    protected static string $_statusField = '';
    protected static string $_statusConfigKey = '';
    protected static string $_documentNumberPrefix = '';
    protected static array $_followupCreatedStatusFields = [];
    protected static array $_followupBookedStatusFields = [];

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        if (!static::$_statusConfigKey || !static::$_statusField || !static::$_documentNumberPrefix) {
            throw new Tinebase_Exception_Record_DefinitionFailure(static::class . ' needs to set its abstract statics');
        }

        parent::inheritModelConfigHook($_definition);

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::BUCKETKEY] =
            static::class . '#' . self::FLD_DOCUMENT_NUMBER;
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable_String::PREFIX] =
            Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
                new Zend_Locale(Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE})
            )->_(static::$_documentNumberPrefix);
    }

    public function isBooked(): bool
    {
        return (bool)(Sales_Config::getInstance()->{static::$_statusConfigKey}->records->getById($this->{static::$_statusField})
            ->{Sales_Model_Document_Status::FLD_BOOKED});
    }

    protected function _getPositionClassName(string $class): string
    {
        static $positionClasses = [];
        if (!isset($positionClasses[$class])) {
            if (!preg_match('/^(Sales_Model_Document)(_.*)$/', $class, $m)) {
                throw new Tinebase_Exception_Record_DefinitionFailure('unexpected class name ' . $class);
            }
            $positionClass = $m[1] . 'Position' . $m[2];
            if (!class_exists($positionClass)) {
                throw new Tinebase_Exception_Record_DefinitionFailure('position class name ' . $positionClass . ' doesn\'t exist');
            }
            $positionClasses[$class] = $positionClass;
        }
        return $positionClasses[$class];
    }

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        $positionClass = $this->_getPositionClassName(static::class);

        $this->{self::FLD_PRECURSOR_DOCUMENTS} = new Tinebase_Record_RecordSet(Tinebase_Model_DynamicRecordWrapper::class, []);
        $this->{self::FLD_POSITIONS} = new Tinebase_Record_RecordSet($positionClass, []);
        $isReversal = false;

        /** @var Sales_Model_Document_TransitionSource $record */
        foreach ($transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS} as $record) {
            if (!$record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}->isBooked()) {
                throw new Tinebase_Exception_Record_Validation('source document is not booked');
            }

            Tinebase_Record_Expander::expandRecord($record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT});

            $addedPositions = 0;
            $isReversal = $isReversal || (bool)$record->{Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL};

            // if the positions for this document are not specified, we take all of them
            if (empty($record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS}) ||
                    $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS}->count() === 0) {
                $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS} =
                    new Tinebase_Record_RecordSet(Sales_Model_DocumentPosition_TransitionSource::class, []);

                if ($record->{Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL}) {
                    $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                        ->{Sales_Model_Document_Abstract::FLD_REVERSAL_STATUS} = Sales_Config::DOCUMENT_REVERSAL_STATUS_REVERSED;
                }

                foreach ($record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                             ->{Sales_Model_Document_Abstract::FLD_POSITIONS} as $position) {

                    /** now this is important! we need to reference the same object here, so it gets dirty, and we can update it if required */
                    $position->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} =
                        $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT};

                    $sourcePosition = new Sales_Model_DocumentPosition_TransitionSource([
                        Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION => $position,
                        Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION_MODEL => get_class($position),
                        Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL => $record->{Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL},
                    ]);
                    /** @var Sales_Model_DocumentPosition_Abstract $position */
                    $position = new $positionClass([], true);
                    try {
                        $position->transitionFrom($sourcePosition);
                        $this->{self::FLD_POSITIONS}->addRecord($position);
                        $position->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} = null;
                        ++$addedPositions;
                    } catch (Tinebase_Exception_Record_Validation $e) {
                    }
                    $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}
                        ->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} = null;
                }

            } else {
                foreach ($record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS} as $sourcePosition) {

                    if (!$record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                            ->{Sales_Model_Document_Abstract::FLD_POSITIONS}->getById($sourcePosition
                            ->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}->getID())) {
                        throw new Tinebase_Exception_UnexpectedValue('sourcePosition in transition not found in source document!');
                    }

                    /** now this is important! we need to reference the same object here, so it gets dirty and we can update it if required */
                    $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}
                        ->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} =
                            $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT};

                    /** @var Sales_Model_DocumentPosition_Abstract $position */
                    $position = new $positionClass([], true);
                    $position->transitionFrom($sourcePosition);
                    $this->{self::FLD_POSITIONS}->addRecord($position);
                    $position->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} = null;
                    $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}
                        ->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} = null;
                    ++$addedPositions;
                    $isReversal = $isReversal || (bool)$sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL};

                    if ($sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL} && $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                                ->{Sales_Model_Document_Abstract::FLD_REVERSAL_STATUS} !== Sales_Config::DOCUMENT_REVERSAL_STATUS_REVERSED) {
                        $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                            ->{Sales_Model_Document_Abstract::FLD_REVERSAL_STATUS} = Sales_Config::DOCUMENT_REVERSAL_STATUS_PARTIALLY_REVERSED;
                    }
                }
            }

            if (0 === $addedPositions) {
                throw new Tinebase_Exception_SystemGeneric('No source positions found that could be transitioned');
            }

            $this->{self::FLD_PRECURSOR_DOCUMENTS}->addRecord(new Tinebase_Model_DynamicRecordWrapper([
                Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME =>
                    $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL},
                Tinebase_Model_DynamicRecordWrapper::FLD_RECORD =>
                    $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT},
            ]));
        }

        // for the time being we keep this simple, this is a TODO FIXME!!!
        $sourceDocument = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->getFirstRecord()
            ->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT};
        $properties = [
            self::FLD_DOCUMENT_LANGUAGE,
            self::FLD_DOCUMENT_CATEGORY,
            self::FLD_CUSTOMER_ID,
            self::FLD_CONTACT_ID,
            self::FLD_RECIPIENT_ID,
            self::FLD_DOCUMENT_TITLE,
            self::FLD_CUSTOMER_REFERENCE,
            self::FLD_VAT_PROCEDURE,
            self::FLD_INVOICE_DISCOUNT_PERCENTAGE,
            self::FLD_INVOICE_DISCOUNT_SUM,
            self::FLD_INVOICE_DISCOUNT_TYPE,
            self::FLD_COST_BEARER_ID,
            self::FLD_COST_CENTER_ID,
            self::FLD_DESCRIPTION,
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID,
            Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID,
        ];

        $thisCFs = Tinebase_CustomField::getInstance()->searchConfig(new Tinebase_Model_CustomField_ConfigFilter([
            ['field' => 'model', 'operator' => 'equals', 'value' => get_class($this)]
        ], '', ['ignoreAcl' => true]))->name;
        $sourceCFs = Tinebase_CustomField::getInstance()->searchConfig(new Tinebase_Model_CustomField_ConfigFilter([
            ['field' => 'model', 'operator' => 'equals', 'value' => get_class($sourceDocument)]
        ], '', ['ignoreAcl' => true]))->name;
        $properties = array_merge($properties, array_intersect($thisCFs, $sourceCFs));

        foreach ($properties as $property) {
            if ($this->has($property) && $sourceDocument->has($property)) {
                $this->{$property} = $sourceDocument->{$property};
            }
        }

        if ($isReversal) {
            $translation = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
                new Zend_Locale($this->{self::FLD_DOCUMENT_LANGUAGE}));
            $this->{self::FLD_DOCUMENT_TITLE} = $translation->_('Reversal') . ': ' . $this->{self::FLD_DOCUMENT_TITLE};

            /** @var Sales_Model_Document_TransitionSource $record */
            foreach ($transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}
                         ->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT} as $record) {
                if (get_class($record) !== static::class) {
                    throw new Tinebase_Exception_UnexpectedValue('reversal transitions need to to have same source and target document class');
                }
            }
        }

        $this->{static::$_statusField} = Sales_Config::getInstance()->{static::$_statusConfigKey}->default;
        $this->{self::FLD_DOCUMENT_DATE} = Tinebase_DateTime::today(Tinebase_Core::getUserTimezone());

        $this->calculatePrices();
    }

    protected function _checkProductPrecursorPositionsComplete()
    {
        if (Sales_Config::INVOICE_DISCOUNT_SUM !== $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            return;
        }

        foreach ($this->{self::FLD_PRECURSOR_DOCUMENTS} as $preDoc) {
            /** @var Sales_Model_DocumentPosition_Abstract $position */
            foreach ($preDoc->{Tinebase_Model_DynamicRecordWrapper::FLD_RECORD}->{self::FLD_POSITIONS} as $position) {
                if (!$position->isProduct()) continue;
                if (null === ($pos = $this->{self::FLD_POSITIONS}->find(
                    function(Sales_Model_DocumentPosition_Abstract $val) use($position) {
                        return $position->getId() ===
                            $val->getIdFromProperty(Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION);
                        }, null)) || $pos->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY} !== $position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY}) {
                    throw new Tinebase_Exception_Record_Validation('partial facturation not supported');
                }
            }
        }
    }

    public function calculatePricesIncludingPositions()
    {
        if (!$this->{self::FLD_POSITIONS}) {
            return;
        }
        
        /** @var Sales_Model_DocumentPosition_Abstract $position */
        foreach ($this->{self::FLD_POSITIONS} as $position) {
            if ($this->{self::FLD_VAT_PROCEDURE} !== Sales_Config::VAT_PROCEDURE_TAXABLE
                && $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX_RATE}) {
                $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX_RATE} = 0;
            }
            $position->computePrice();
        }

        $this->calculatePrices();
    }

    public function calculatePrices()
    {
        // see AbstractMixin.computePrice
        // Sales/js/Model/DocumentPosition/AbstractMixin.js

        // see Tine.Sales.Document_AbstractEditDialog.checkStates
        // Sales/js/Document/AbstractEditDialog.js

        $this->{self::FLD_POSITIONS_NET_SUM} = 0;
        $this->{self::FLD_POSITIONS_DISCOUNT_SUM} = 0;
        $this->{self::FLD_SALES_TAX_BY_RATE} = [];
        $this->{self::FLD_NET_SUM} = 0;
        $netSumByTaxRate = [];
        $salesTaxByRate = [];
        /** @var Sales_Model_DocumentPosition_Abstract $position */
        foreach ($this->{self::FLD_POSITIONS} as $position) {
            $this->{self::FLD_POSITIONS_NET_SUM} = $this->{self::FLD_POSITIONS_NET_SUM}
                + floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_NET_PRICE});
            $this->{self::FLD_POSITIONS_DISCOUNT_SUM} = $this->{self::FLD_POSITIONS_DISCOUNT_SUM}
                + floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_POSITION_DISCOUNT_SUM});

            $taxRate = $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX_RATE} ?: 0;
            if (!isset($salesTaxByRate[$taxRate])) {
                $salesTaxByRate[$taxRate] = 0;
            }
            $salesTaxByRate[$taxRate] += floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX});
            if (!isset($netSumByTaxRate[$taxRate])) {
                $netSumByTaxRate[$taxRate] = 0;
            }
            $netSumByTaxRate[$taxRate] += floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_NET_PRICE});
        }

        if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            $this->{self::FLD_INVOICE_DISCOUNT_SUM} = (float)$this->{self::FLD_INVOICE_DISCOUNT_SUM};
        } else {
            $discount = ($this->{self::FLD_POSITIONS_NET_SUM} / 100) *
                (float)$this->{self::FLD_INVOICE_DISCOUNT_PERCENTAGE};
            $this->{self::FLD_INVOICE_DISCOUNT_SUM} = $discount;
        }

        $this->{self::FLD_SALES_TAX} = $this->{Sales_Model_Document_Abstract::FLD_POSITIONS_NET_SUM} ?
            array_reduce(array_keys($netSumByTaxRate), function($carry, $taxRate) use($netSumByTaxRate) {
                $tax =
                    ($netSumByTaxRate[$taxRate] - $this->{Sales_Model_Document_Abstract::FLD_INVOICE_DISCOUNT_SUM} *
                        $netSumByTaxRate[$taxRate] / $this->{Sales_Model_Document_Abstract::FLD_POSITIONS_NET_SUM})
                    * $taxRate / 100;
                if ($tax) {
                    $this->xprops(self::FLD_SALES_TAX_BY_RATE)[] = [
                        'tax_rate' => $taxRate,
                        'tax_sum' => $tax,
                    ];
                }
                return $carry + $tax;
            }, 0) : 0;

        $this->{self::FLD_GROSS_SUM} = $this->{self::FLD_POSITIONS_NET_SUM} - $this->{self::FLD_INVOICE_DISCOUNT_SUM}
            + $this->{self::FLD_SALES_TAX};
        $this->{self::FLD_NET_SUM} = $this->{self::FLD_POSITIONS_NET_SUM} - $this->{self::FLD_INVOICE_DISCOUNT_SUM};
    }

    /**
     * can be reimplemented by subclasses to modify values during setFromJson
     * @param array $_data the json decoded values
     * @return void
     *
     * @todo remove this
     * @deprecated
     */
    protected function _setFromJson(array &$_data)
    {
        parent::_setFromJson($_data);

        unset($_data[self::FLD_PRECURSOR_DOCUMENTS]);
        unset($_data[self::FLD_REVERSAL_STATUS]);
    }

    public function updateFollowupStati(bool $booked): void
    {
        $isDirty = $this->_isDirty;

        $this->_updateFollowupStatusFields('_followupCreatedStatusFields');
        if ($booked) {
            $this->_updateFollowupStatusFields('_followupBookedStatusFields', true);
        }

        $this->_isDirty = $isDirty;
    }

    protected function _updateFollowupStatusFields(string $statusFields, bool $onlyBooked = false): void
    {
        if (empty(static::$$statusFields)) {
            return;
        }

        $positionClass = $this->_getPositionClassName(static::class);
        /** @var Tinebase_Controller_Record_Abstract $positionCtrl */
        $positionCtrl = Tinebase_Core::getApplicationInstance($positionClass);
        $this->{self::FLD_POSITIONS} = $positionCtrl->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel($positionClass, [
                [TMFA::FIELD => Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID, TMFA::OPERATOR => 'equals', TMFA::VALUE => $this->getId()],
            ])
        );

        $this->_isDirty = false;

        /** @var string $statusField */
        foreach (static::$$statusFields as $statusField => $followupConfig) {
            $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED;
            $foundProduct = false;
            /** @var Tinebase_Controller_Record_Abstract $followupDocCtrl */
            $followupDocCtrl = Tinebase_Core::getApplicationInstance($followupConfig[self::MODEL_NAME]);
            $followUpDocs = [];
            $followupPositionClass = $this->_getPositionClassName($followupConfig[self::MODEL_NAME]);
            /** @var Tinebase_Controller_Record_Abstract $followupPositionCtrl */
            $followupPositionCtrl = Tinebase_Core::getApplicationInstance($followupPositionClass);
            /** @var Sales_Model_DocumentPosition_Abstract $position */
            foreach ($this->{self::FLD_POSITIONS} as $position) {
                if (!$position->isProduct()) {
                    continue;
                }
                $foundProduct = true;
                $quantity = null;
                foreach ($followupPositionCtrl->search(
                            Tinebase_Model_Filter_FilterGroup::getFilterForModel($followupPositionClass, [
                                [TMFA::FIELD => Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION, TMFA::OPERATOR => 'equals', TMFA::VALUE => $position->getId()],
                                [TMFA::FIELD => Sales_Model_DocumentPosition_Abstract::FLD_IS_REVERSED, TMFA::OPERATOR => 'equals', TMFA::VALUE => false],
                            ]), null, false, [Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID, Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY]
                        ) as $docId => $qty) {
                    if ($onlyBooked) {
                        if (!isset($followUpDocs[$docId])) {
                            $followUpDocs[$docId] = $followupDocCtrl->get($docId);
                        }
                        /** @var array<Sales_Model_Document_Abstract> $followUpDocs */
                        if (!$followUpDocs[$docId]->isBooked()) {
                            continue;
                        }
                    }
                    $quantity += (int)$qty;
                }
                if (null === $quantity) {
                    if (Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED === $status) {
                        $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE;
                    }
                    continue;
                }
                if ($quantity < (int)$position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY}) {
                    $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_PARTIALLY;
                    break;
                }
                if ($quantity === (int)$position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY} &&
                        Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE === $status) {
                    $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_PARTIALLY;
                    break;
                }
            }

            if (!$foundProduct) {
                $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE;
            }
            if ($this->{$statusField} !== $status) {
                $this->{$statusField} = $status;
            }
        }

        if ($this->isDirty()) {
            $this->{self::FLD_POSITIONS} = null;
            /** @var Tinebase_Controller_Record_Abstract $ownCtrl */
            $ownCtrl = Tinebase_Core::getApplicationInstance(static::class);
            $updated = $ownCtrl->update($this);
            $this->seq = $updated->seq;
        }
    }
}
