<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * @property Sales_Model_EDocument_VATEX $vatex_id
 */
abstract class Sales_Model_Document_Abstract extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_Abstract'; // als konkrete document_types gibt es Offer, Order, Delivery, Invoice (keine Gutschrift!)

    public const EXCLUDE_FROM_DOCUMENT_SEQ = 'exclDocSeq';

    public const STATUS_REVERSAL = 'REVERSAL';
    public const STATUS_DISPATCHED = 'DISPATCHED';
    public const STATUS_MANUAL_DISPATCH = 'MANUAL_DISPATCH';

    public const TAX_RATE = 'tax_rate';
    public const TAX_SUM = 'tax_sum';
    public const NET_SUM = 'net_sum';

    public const FLD_ID = 'id';
    public const FLD_DOCUMENT_NUMBER = 'document_number'; // kommt aus incrementable, in config einstellen welches incrementable fuer dieses model da ist!
    public const FLD_DOCUMENT_LANGUAGE = 'document_language';
    public const FLD_DOCUMENT_CATEGORY = 'document_category'; // keyfield - per default "standard". brauchen wir z.B. zum filtern, zur Auswahl von Textbausteinen, Templates etc.

    public const FLD_PRECURSOR_DOCUMENTS = 'precursor_documents'; // virtual, link
    public const FLD_BOILERPLATES = 'boilerplates';

    public const FLD_CUSTOMER_ID = 'customer_id'; // Kunde(Sales) (Optional beim Angebot, danach required). denormalisiert pro beleg, denormalierungs inclusive addressen, exklusive contacts
    public const FLD_DEBITOR_ID = 'debitor_id';
    public const FLD_PAYMENT_MEANS = 'payment_means';
    public const FLD_RECIPIENT_ID = 'recipient_id'; // Adresse(Sales) -> bekommt noch ein. z.Hd. Feld(text). denormalisiert pro beleg. muss nicht notwendigerweise zu einem kunden gehören. kann man aus kontakt übernehmen werden(z.B. bei Angeboten ohne Kunden)
    public const FLD_CONTACT_ID = 'contact_id'; // Kontakt(Addressbuch) per default AP Extern, will NOT be denormalized

    public const FLD_DOCUMENT_TITLE = 'document_title';
    public const FLD_DOCUMENT_DATE = 'date'; // Belegdatum,  defaults empty, today when booked and not set differently
    public const FLD_BUYER_REFERENCE = 'buyer_reference'; // varchar 255

    public const FLD_POSITIONS = 'positions'; // virtuell recordSet
    public const FLD_POSITIONS_NET_SUM = 'positions_net_sum';
    public const FLD_POSITIONS_GROSS_SUM = 'positions_gross_sum';
    public const FLD_POSITIONS_DISCOUNT_SUM = 'positions_discount_sum';
    public const FLD_PROJECT_REFERENCE = 'project_reference';
    public const FLD_PURCHASE_ORDER_REFERENCE = 'purchase_order_reference';

    public const FLD_SERVICE_PERIOD_START = 'service_period_start';
    public const FLD_SERVICE_PERIOD_END = 'service_period_end';

    public const FLD_INVOICE_DISCOUNT_TYPE = 'invoice_discount_type'; // PERCENTAGE|SUM
    public const FLD_INVOICE_DISCOUNT_PERCENTAGE = 'invoice_discount_percentage'; // automatische Berechnung je nach tupe
    public const FLD_INVOICE_DISCOUNT_SUM = 'invoice_discount_sum'; // automatische Berechnung je nach type

    public const FLD_NET_SUM = 'net_sum';
    public const FLD_VAT_PROCEDURE = 'vat_procedure';
    public const FLD_VATEX_ID = 'vatex_id';
    public const FLD_SALES_TAX = 'sales_tax';
    public const FLD_SALES_TAX_BY_RATE = 'sales_tax_by_rate';

    public const FLD_GROSS_SUM = 'gross_sum';

    public const FLD_PAYMENT_TERMS = 'credit_term';

    public const FLD_EVAL_DIM_COST_CENTER = 'eval_dim_cost_center';
    public const FLD_EVAL_DIM_COST_BEARER = 'eval_dim_cost_bearer'; // ist auch ein cost center

    public const FLD_DESCRIPTION = 'description';

    public const FLD_REVERSAL_STATUS = 'reversal_status';

    public const FLD_CONTRACT_ID = 'contract_id';

    public const FLD_ATTACHED_DOCUMENTS = 'attached_documents';

    public const FLD_DISPATCH_HISTORY = 'dispatch_history';

    public const FLD_DOCUMENT_SEQ = 'document_seq';

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
        self::COPY_RELATIONS => false,
        self::HAS_TAGS => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        self::CONTAINER_PROPERTY        => null,
        self::DELEGATED_ACL_FIELD       => self::FLD_DEBITOR_ID,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DOCUMENT_CATEGORY => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Document_Category::FLD_DIVISION_ID => [],
                    ],
                ],
                self::FLD_CUSTOMER_ID       => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'cpextern_id'           => [],
                        'cpintern_id'           => [],
                    ],
                ],
                self::FLD_DEBITOR_ID        => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Debitor::FLD_EAS_ID => [],
                    ],
                ],
                self::FLD_PAYMENT_MEANS     => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => [],
                    ],
                ],
                self::FLD_RECIPIENT_ID      => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Address::FLD_DEBITOR_ID => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_Debitor::FLD_PAYMENT_MEANS => [
                                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                        Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                self::FLD_POSITIONS         => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION => [],
                    ],
                ],
                self::FLD_ATTACHED_DOCUMENTS => [],
                self::FLD_DISPATCH_HISTORY  => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        self::FLD_ATTACHMENTS => [],
                    ],
                ],
                self::FLD_CONTACT_ID => [],
                self::FLD_BOILERPLATES => [],
                self::FLD_VATEX_ID => [],
                self::FLD_SALES_TAX_BY_RATE => [],
            ]
        ],

        self::FILTER_MODEL => [
            Sales_Model_Debitor::FLD_DIVISION_ID => [
                self::LABEL => 'Division', // _('Division')
                self::FILTER => Sales_Model_Document_DivisionFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => Sales_Model_Division::MODEL_NAME_PART,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Division::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'equals'
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_DOCUMENT_NUMBER => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::LABEL                     => 'Document Number', //_('Document Number')
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
//                    Tinebase_Numberable::BUCKETKEY         => self::class . '#' . self::FLD_DOCUMENT_NUMBER,
                    //Tinebase_Numberable_String::PREFIX     => 'XX-',
                    Tinebase_Numberable_String::ZEROFILL   => 7,
                    Tinebase_Model_NumberableConfig::NO_AUTOCREATE => true,
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
                self::CONFIG                        => [
                    self::NO_DEFAULT_VALIDATOR          => true,
                ],
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
                self::TYPE                  => self::TYPE_RECORD,
                self::SHY                   => true,
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Category::MODEL_NAME_PART,
                ],
                // not null! mandatory property
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
            self::FLD_SERVICE_PERIOD_START => [
                self::TYPE                  => self::TYPE_DATE,
                self::LABEL                 => 'Service Period Start', //_('Service Period Start')
                self::NULLABLE              => true,
            ],
            self::FLD_SERVICE_PERIOD_END =>  [
                self::TYPE                  => self::TYPE_DATE,
                self::LABEL                 => 'Service Period End', //_('Service Period End')
                self::NULLABLE              => true,
            ],

            self::FLD_BUYER_REFERENCE        => [
                self::LABEL                         => 'Buyer Reference', //_('Buyer Reference')
                self::DESCRIPTION                   => 'An identifier assigned by the acquirer and used for internal control purposes (BT-10 [EN 16931]).', // _('An identifier assigned by the acquirer and used for internal control purposes (BT-10 [EN 16931]).')
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
            self::FLD_DEBITOR_ID                => [
                self::TYPE                          => self::TYPE_RECORD,
                self::LABEL                         => 'Debitor', // _('Debitor')
                self::SHY                           => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_Debitor::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => Sales_Model_Document_Debitor::FLD_DOCUMENT_ID,
                ],
                // not null! mandatory property
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
                self::UI_CONFIG             => [
                    'recordEditPluginConfig'    => [
                        'allowCreateNew'            => true,
                    ],
                ],
            ],
            self::FLD_CONTACT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Reference Person', //_('Reference Person')
                // TODO add resolve deleted flag? guess that would be nice
                self::CONFIG                => [
                    self::APP_NAME              => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_NAME_PART,
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
                    self::PAGING                        => ['sort' => [Sales_Model_DocumentPosition_Abstract::FLD_GROUPING, Sales_Model_DocumentPosition_Abstract::FLD_SORTING]],
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
            self::FLD_POSITIONS_GROSS_SUM                   => [
                self::LABEL                         => 'Positions Gross Sum', //_('Positions Gross Sum')
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
            self::FLD_VAT_PROCEDURE             => [
                self::LABEL                         => 'VAT Procedure', // _('VAT Procedure')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::VAT_PROCEDURES,
            ],
            self::FLD_VATEX_ID                  => [
                self::LABEL                         => 'VAT Exemption', // _('VAT Exemption')
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_EDocument_VATEX::MODEL_NAME_PART,
                ],
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
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
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::DEPENDENT_RECORDS             => true,
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_SalesTax::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => Sales_Model_Document_SalesTax::FLD_DOCUMENT_ID,
                    /* set by model config hook
                     * self::ADD_FILTERS                   => [
                        [TMFA::FIELD => Sales_Model_Document_SalesTax::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => static::class],
                    ],
                    self::FORCE_VALUES                  => [
                        Sales_Model_Document_SalesTax::FLD_DOCUMENT_ID => static::class,
                    ],*/
                ],
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
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Internal Note', //_('Internal Note')
                self::TYPE                          => self::TYPE_TEXT,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::SHY                           => true,
            ],
            self::FLD_CONTRACT_ID               => [
                self::TYPE                          => self::TYPE_RECORD,
                self::LABEL                         => 'Contract', //_('Contract')
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Contract::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_PROJECT_REFERENCE         => [
                self::LABEL                         => 'Project Reference', // _('Project Reference')
                self::DESCRIPTION                   => 'The identifier of a project to which the invoice refers (BT-11 [EN 16931]).', // _('The identifier of a project to which the invoice refers (BT-11 [EN 16931]).')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_PURCHASE_ORDER_REFERENCE  => [
                self::LABEL                         => 'Purchase Order Reference', // _('Purchase Order Reference')
                self::DESCRIPTION                   => 'An identifier issued by the purchaser for a referenced order (BT-13 [EN 16931]).', // _('An identifier issued by the purchaser for a referenced order (BT-13 [EN 16931]).')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_ATTACHED_DOCUMENTS        => [
                self::LABEL                         => 'Attached Documents', // _('Attached Documents')
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::DEPENDENT_RECORDS             => true,
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_AttachedDocument::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => Sales_Model_Document_AttachedDocument::FLD_DOCUMENT_ID,
                    /* set by model config hook
                     * self::ADD_FILTERS                   => [
                        [TMFA::FIELD => Sales_Model_Document_AttachedDocument::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => static::class],
                    ],
                    self::FORCE_VALUES                  => [
                        Sales_Model_Document_AttachedDocument::FLD_DOCUMENT_ID => static::class,
                    ],*/
                ],
            ],
            self::FLD_PAYMENT_MEANS             => [
                self::LABEL                         => 'Payment Means', // _('Payment Means')
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_PaymentMeans::MODEL_NAME_PART,
                    self::STORAGE                       => self::TYPE_JSON,
                    // only Invoice needs one default set (means selected)
                ],
                // important not so set any filter / validation defaults as default handling is done in the controller!
                self::DEFAULT_VAL                   => '[]',
                self::CONVERTERS                    => [
                    [Tinebase_Model_Converter_JsonRecordSetDefault::class, []],
                ],
            ],
            self::FLD_DISPATCH_HISTORY          => [
                self::LABEL                         => 'Dispatch History', // _('Dispatch History')
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::DEPENDENT_RECORDS             => true,
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_DispatchHistory::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID,
                    self::EXCLUDE_FROM_DOCUMENT_SEQ     => true,
                    /* set by model config hook
                     * self::ADD_FILTERS                   => [
                        [TMFA::FIELD => Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => static::class],
                    ],
                    self::FORCE_VALUES                  => [
                        Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_ID => static::class,
                    ],*/
                ],
            ],
            self::FLD_DOCUMENT_SEQ              => [
                self::TYPE                          => self::TYPE_INTEGER,
                self::DEFAULT_VAL                   => 1,
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
                ],
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

        $_definition[self::FIELDS][self::FLD_DOCUMENT_CATEGORY][self::VALIDATORS][Zend_Filter_Input::DEFAULT_VALUE] =
            Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY_DEFAULT};

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable_String::PREFIX] =
            Tinebase_Translation::getDefaultTranslation(Sales_Config::APP_NAME)->_(static::$_documentNumberPrefix);
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE] =
            'Sales_Controller_' . static::MODEL_NAME_PART . '::documentNumberConfigOverride';

        $_definition[self::FIELDS][self::FLD_ATTACHED_DOCUMENTS][self::CONFIG][self::FORCE_VALUES] = [
            Sales_Model_Document_AttachedDocument::FLD_DOCUMENT_TYPE => static::class,
        ];
        $_definition[self::FIELDS][self::FLD_ATTACHED_DOCUMENTS][self::CONFIG][self::ADD_FILTERS] = [
            [TMFA::FIELD => Sales_Model_Document_SalesTax::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => static::class],
        ];
        $_definition[self::FIELDS][self::FLD_DISPATCH_HISTORY][self::CONFIG][self::FORCE_VALUES] = [
            Sales_Model_Document_DispatchHistory::FLD_DOCUMENT_TYPE => static::class,
        ];
        $_definition[self::FIELDS][self::FLD_DISPATCH_HISTORY][self::CONFIG][self::ADD_FILTERS] = [
            [TMFA::FIELD => Sales_Model_Document_SalesTax::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => static::class],
        ];
        $_definition[self::FIELDS][self::FLD_SALES_TAX_BY_RATE][self::CONFIG][self::FORCE_VALUES] = [
            Sales_Model_Document_SalesTax::FLD_DOCUMENT_TYPE => static::class,
        ];
        $_definition[self::FIELDS][self::FLD_SALES_TAX_BY_RATE][self::CONFIG][self::ADD_FILTERS] = [
            [TMFA::FIELD => Sales_Model_Document_SalesTax::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => static::class],
        ];
    }

    public function isBooked(): bool
    {
        if (null === ($status = $this->{static::$_statusField})) {
            return false;
        }
        return (bool)(Sales_Config::getInstance()->{static::$_statusConfigKey}->records->getById($status)
            ->{Sales_Model_Document_Status::FLD_BOOKED});
    }

    public static function getStatusField(): string
    {
        return static::$_statusField;
    }

    public static function getStatusConfigKey(): string
    {
        return static::$_statusConfigKey;
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

        if (($isReversal = (null !== $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->find(Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL, true)))
                && null !== $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->find(Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL, false)) {
            throw new Tinebase_Exception_UnexpectedValue('source documents must be either all reversals or not');
        }

        // since source documents might have different models, you can't expand all at once, you will have to "sort" them by model ... or do each individually
        // check all source documents are booked
        // check that either all source documents where reversals (status === reversal) or not
        $sourcesAreReversals = null; // true means "Reversal of Reversal" -> FollowUp Document
        $srcDocs = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT};
        array_walk($srcDocs, function (Sales_Model_Document_Abstract $doc) use(&$sourcesAreReversals): void {
            if (!$doc->isBooked()) {
                throw new Tinebase_Exception_Record_Validation('source document is not booked');
            }
            $docReversed = $doc->{$doc::getStatusField()} === Sales_Config::getInstance()->{$doc::getStatusConfigKey()}->records->find(Sales_Model_Document_Status::FLD_REVERSAL, true)?->getId();
            if (null === $sourcesAreReversals) {
                $sourcesAreReversals = $docReversed;
            } elseif ($sourcesAreReversals !== $docReversed) {
                throw new Tinebase_Exception_Record_Validation('source documents reversal status mixed');
            }

            Tinebase_Record_Expander::expandRecord($doc);
        });

        if ($sourcesAreReversals && $isReversal) {
            $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->{Sales_Model_Document_TransitionSource::FLD_IS_REVERSAL} = false;
            $isReversal = false;
            // reversal of reversal are followups, thus is_reversal must be false
        }

        /** @var Sales_Model_Document_TransitionSource $record */
        foreach ($transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS} as $record) {
            $addedPositions = 0;

            // if the positions for this document are not specified, we take all of them
            if (empty($record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS}) ||
                    $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS}->count() === 0) {

                if ($isReversal) {
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
                        Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL => $isReversal,
                    ]);
                    /** @var Sales_Model_DocumentPosition_Abstract $position */
                    $position = new $positionClass([], true);
                    try {
                        $position->transitionFrom($sourcePosition, $sourcesAreReversals);
                        $this->{self::FLD_POSITIONS}->addRecord($position);
                        $position->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} = null;
                        ++$addedPositions;
                    } catch (Tinebase_Exception_Record_Validation $e) {
                        $e->setLogLevelMethod('info');
                        $e->setLogToSentry(false);
                        Tinebase_Exception::log($e);
                    }
                }

            } else {
                foreach ($record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_POSITIONS} as $sourcePosition) {

                    if (!($sPosition = $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
                            ->{Sales_Model_Document_Abstract::FLD_POSITIONS}->getById($sourcePosition
                            ->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}->getID()))) {
                        throw new Tinebase_Exception_UnexpectedValue('sourcePosition in transition not found in source document!');
                    }
                    if ((bool)$sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL} !== $isReversal) {
                        throw new Tinebase_Exception_UnexpectedValue('transition source position needs to have same is_reversal state as transition source document');
                    }
                    $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION} = $sPosition;

                    /** now this is important! we need to reference the same object here, so it gets dirty and we can update it if required */
                    $sourcePosition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}
                        ->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} =
                            $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT};

                    /** @var Sales_Model_DocumentPosition_Abstract $position */
                    $position = new $positionClass([], true);
                    $position->transitionFrom($sourcePosition, $sourcesAreReversals);
                    $this->{self::FLD_POSITIONS}->addRecord($position);
                    $position->{Sales_Model_DocumentPosition_Abstract::FLD_DOCUMENT_ID} = null;
                    ++$addedPositions;

                    if ($isReversal && $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
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
                    $record->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}->getId(),
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
            self::FLD_BUYER_REFERENCE,
            self::FLD_VAT_PROCEDURE,
            self::FLD_INVOICE_DISCOUNT_PERCENTAGE,
            self::FLD_INVOICE_DISCOUNT_SUM,
            self::FLD_INVOICE_DISCOUNT_TYPE,
            self::FLD_DESCRIPTION,
            Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID,
            Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID,
        ];

        $cfc = new Tinebase_CustomField_Config();
        $cfc->setAllCFs();
        $properties = array_merge($properties, array_unique($cfc->search(new Tinebase_Model_CustomField_ConfigFilter([
            ['field' => 'model', 'operator' => 'startswith', 'value' => 'Sales_Model_Document_'],
            ['field' => 'name', 'operator' => 'startswith', 'value' => 'eval_dim_'],
        ], '', ['ignoreAcl' => true]))->name));


        foreach ($properties as $property) {
            if ($this->has($property) && $sourceDocument->has($property)) {
                $this->{$property} = $sourceDocument->{$property};
            }
        }

        $translation = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
            new Zend_Locale($this->{self::FLD_DOCUMENT_LANGUAGE}));
        if ($isReversal) {
            $this->{self::FLD_DOCUMENT_TITLE} =
                $translation->_('Reversal') . ' ' .  implode(', ',
                    array_reduce($transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}, function($carry, $document) {
                        array_push($carry, $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER});
                        return $carry;
                    }, [])) .
                ': ' . $this->{self::FLD_DOCUMENT_TITLE};

            /** @var Sales_Model_Document_TransitionSource $record */
            foreach ($transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}
                         ->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT} as $record) {
                if (get_class($record) !== static::class) {
                    throw new Tinebase_Exception_UnexpectedValue('reversal transitions need to to have same source and target document class');
                }
            }

            $this->{static::$_statusField} = Sales_Config::getInstance()->{static::$_statusConfigKey}->records->find(Sales_Model_Document_Status::FLD_REVERSAL, true)->getId();
        } else {
            if ($sourcesAreReversals) {
                $this->{self::FLD_DOCUMENT_TITLE} =
                    preg_replace("/^{$translation->_('Reversal')}/", $translation->_('Followup'), $this->{self::FLD_DOCUMENT_TITLE});
            }
            $this->{static::$_statusField} = Sales_Config::getInstance()->{static::$_statusConfigKey}->default;
        }

        $this->{self::FLD_DOCUMENT_DATE} = Tinebase_DateTime::today(Tinebase_Core::getUserTimezone());

        $this->calculatePrices();
    }

    protected function _checkProductPrecursorPositionsComplete()
    {
        if (Sales_Config::INVOICE_DISCOUNT_SUM !== $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            return;
        }

        foreach ($this->{self::FLD_PRECURSOR_DOCUMENTS} as $preDoc) {
            /** @var Tinebase_Controller_Record_Abstract $ctrl */
            $ctrl = Tinebase_Core::getApplicationInstance($preDoc->{Tinebase_Model_DynamicRecordWrapper::FLD_MODEL_NAME});
            $preDoc = $ctrl->get($preDoc->getIdFromProperty(Tinebase_Model_DynamicRecordWrapper::FLD_RECORD));
            /** @var Sales_Model_DocumentPosition_Abstract $position */
            foreach ($preDoc->{self::FLD_POSITIONS} as $position) {
                if (!$position->isProduct()) continue;
                if (null === ($pos = $this->{self::FLD_POSITIONS}->find(
                    function(Sales_Model_DocumentPosition_Abstract $val) use($position) {
                        return $position->getId() ===
                            $val->getIdFromProperty(Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION);
                        }, null)) || (float)$pos->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY} !== (float)$position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY}) {
                    throw new Tinebase_Exception_Record_Validation('partial facturation not supported');
                }
            }
        }
    }

    public function calculatePricesIncludingPositions()
    {
        /** @var Sales_Model_DocumentPosition_Abstract $position */
        foreach ($this->{self::FLD_POSITIONS} ?? [] as $position) {
            if ($this->{self::FLD_VAT_PROCEDURE} && $this->{self::FLD_VAT_PROCEDURE} !== Sales_Config::VAT_PROCEDURE_STANDARD
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
        $this->{self::FLD_POSITIONS_GROSS_SUM} = 0;
        $this->{self::FLD_POSITIONS_DISCOUNT_SUM} = 0;
        $oldSalesTaxByRate = null;
        if ($this->{self::FLD_SALES_TAX_BY_RATE} instanceof Tinebase_Record_RecordSet) {
            $oldSalesTaxByRate = $this->{self::FLD_SALES_TAX_BY_RATE};
        }
        $this->{self::FLD_SALES_TAX_BY_RATE} = new Tinebase_Record_RecordSet(Sales_Model_Document_SalesTax::class);
        $this->{self::FLD_NET_SUM} = 0;
        $netSumByTaxRate = [];
        $grossSumByTaxRate = [];
        $salesTaxByRate = [];
        $documentPriceType = Sales_Config::PRICE_TYPE_GROSS;

        /** @var Sales_Model_DocumentPosition_Abstract $position */
        foreach ($this->{self::FLD_POSITIONS} ?? [] as $position) {
            $this->{self::FLD_POSITIONS_NET_SUM} = $this->{self::FLD_POSITIONS_NET_SUM}
                + floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_NET_PRICE});
            $this->{self::FLD_POSITIONS_GROSS_SUM} = $this->{self::FLD_POSITIONS_GROSS_SUM}
                + floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_GROSS_PRICE});
            $this->{self::FLD_POSITIONS_DISCOUNT_SUM} = $this->{self::FLD_POSITIONS_DISCOUNT_SUM}
                + floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_POSITION_DISCOUNT_SUM});

            $documentPriceType = $documentPriceType === Sales_Config::PRICE_TYPE_GROSS &&
                $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE_TYPE} === Sales_Config::PRICE_TYPE_GROSS ?
                Sales_Config::PRICE_TYPE_GROSS : Sales_Config::PRICE_TYPE_NET;

            $taxRate = $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX_RATE} ?: 0;
            if (!isset($salesTaxByRate[$taxRate])) {
                $salesTaxByRate[$taxRate] = 0;
            }
            $salesTaxByRate[$taxRate] += floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX});
            if (!isset($netSumByTaxRate[$taxRate])) {
                $netSumByTaxRate[$taxRate] = 0;
            }
            $netSumByTaxRate[$taxRate] += floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_NET_PRICE});
            if (!isset($grossSumByTaxRate[$taxRate])) {
                $grossSumByTaxRate[$taxRate] = 0;
            }
            $grossSumByTaxRate[$taxRate] += floatval($position->{Sales_Model_DocumentPosition_Abstract::FLD_POSITION_PRICE});
        }

        if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            $this->{self::FLD_INVOICE_DISCOUNT_SUM} = (float)$this->{self::FLD_INVOICE_DISCOUNT_SUM};
        } else {
            $posSumFld = $documentPriceType === Sales_Config::PRICE_TYPE_GROSS ?
                self::FLD_POSITIONS_GROSS_SUM : self::FLD_POSITIONS_NET_SUM;
            $discount = round(($this->{$posSumFld} / 100) *
                (float)$this->{self::FLD_INVOICE_DISCOUNT_PERCENTAGE}, 2);
            $this->{self::FLD_INVOICE_DISCOUNT_SUM} = $discount;
        }

        if ($documentPriceType === Sales_Config::PRICE_TYPE_GROSS) {
            $this->{self::FLD_SALES_TAX} = $this->{Sales_Model_Document_Abstract::FLD_POSITIONS_GROSS_SUM} ?
                array_reduce(array_keys($grossSumByTaxRate), function($carry, $taxRate) use($salesTaxByRate, $netSumByTaxRate, $oldSalesTaxByRate) {
                    $tax = round($salesTaxByRate[$taxRate] * ($discountModifier = ( 1 -
                            $this->{Sales_Model_Document_Abstract::FLD_INVOICE_DISCOUNT_SUM} /
                            $this->{Sales_Model_Document_Abstract::FLD_POSITIONS_GROSS_SUM} )), 2);
                    $this->{self::FLD_SALES_TAX_BY_RATE}->addRecord($smdst = new Sales_Model_Document_SalesTax([
                        Sales_Model_Document_SalesTax::FLD_TAX_RATE => $taxRate,
                        Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => $tax,
                        Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => round($netSumByTaxRate[$taxRate] * $discountModifier, 2),
                        Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => round($netSumByTaxRate[$taxRate] * $discountModifier, 2) + $tax,
                    ], true));
                    if ($oldSalesTaxByRate && ($oldRate = $oldSalesTaxByRate->find(Sales_Model_Document_SalesTax::FLD_TAX_RATE, $taxRate))) {
                        $smdst->setId($oldRate->getId());
                        $smdst->seq = $oldRate->seq;
                        $smdst->last_modified_time = $oldRate->last_modified_time;
                    }
                    return $carry + $tax;
                }, 0) : 0;
            $this->{self::FLD_GROSS_SUM} = $this->{self::FLD_POSITIONS_GROSS_SUM} - $this->{self::FLD_INVOICE_DISCOUNT_SUM};
            $this->{self::FLD_NET_SUM} = $this->{self::FLD_GROSS_SUM} - $this->{self::FLD_SALES_TAX};
        } else {
            $this->{self::FLD_SALES_TAX} = $this->{Sales_Model_Document_Abstract::FLD_POSITIONS_NET_SUM} ?
                array_reduce(array_keys($netSumByTaxRate), function($carry, $taxRate) use($netSumByTaxRate, $oldSalesTaxByRate) {
                    $tax =
                        round(($netSum = round(($netSumByTaxRate[$taxRate] - $this->{Sales_Model_Document_Abstract::FLD_INVOICE_DISCOUNT_SUM} *
                            $netSumByTaxRate[$taxRate] / $this->{Sales_Model_Document_Abstract::FLD_POSITIONS_NET_SUM}), 2))
                        * $taxRate / 100, 2);
                    $this->{self::FLD_SALES_TAX_BY_RATE}->addRecord($smdst = new Sales_Model_Document_SalesTax([
                        Sales_Model_Document_SalesTax::FLD_TAX_RATE => $taxRate,
                        Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => $tax,
                        Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => $netSum,
                        Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => $netSum + $tax,
                    ], true));
                    if ($oldSalesTaxByRate && ($oldRate = $oldSalesTaxByRate->find(Sales_Model_Document_SalesTax::FLD_TAX_RATE, $taxRate))) {
                        $smdst->setId($oldRate->getId());
                        $smdst->seq = $oldRate->seq;
                        $smdst->last_modified_time = $oldRate->last_modified_time;
                    }
                    return $carry + $tax;
                }, 0) : 0;

            $this->{self::FLD_NET_SUM} = $this->{self::FLD_POSITIONS_NET_SUM} - $this->{self::FLD_INVOICE_DISCOUNT_SUM};
            $this->{self::FLD_GROSS_SUM} = $this->{self::FLD_NET_SUM} + $this->{self::FLD_SALES_TAX};
        }
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

        unset($_data[self::FLD_DISPATCH_HISTORY]);
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

    public function isValid($_throwExceptionOnInvalidData = false)
    {
        if (array_key_exists(self::FLD_REVERSAL_STATUS, $this->_data) && empty($this->_data[self::FLD_REVERSAL_STATUS])) {
            $this->_data[self::FLD_REVERSAL_STATUS] = Sales_Config::getInstance()->{Sales_Config::DOCUMENT_REVERSAL_STATUS}->default;
        }
        return parent::isValid($_throwExceptionOnInvalidData);
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
                    $quantity += (float)$qty;
                }
                if (null === $quantity) {
                    if (Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED === $status) {
                        $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_NONE;
                    }
                    continue;
                }
                if ($quantity < (float)$position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY}) {
                    $status = Sales_Config::DOCUMENT_FOLLOWUP_STATUS_PARTIALLY;
                    break;
                }
                if ($quantity === (float)$position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY} &&
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

    public function getDivisionId(): string
    {
        if (! $this->_data[self::FLD_DOCUMENT_CATEGORY] instanceof Sales_Model_Document_Category) {
            $this->_data[self::FLD_DOCUMENT_CATEGORY] = Sales_Controller_Document_Category::getInstance()->get(
                $this->getIdFromProperty(self::FLD_DOCUMENT_CATEGORY));
        }
        return $this->_data[self::FLD_DOCUMENT_CATEGORY]->getIdFromProperty(Sales_Model_Document_Category::FLD_DIVISION_ID);
    }

    public function setFromArray(array &$_data)
    {
        static $evalDimProperties = [];
        if (!isset($evalDimProperties[static::class])) {
            $evalDimProperties[static::class] = [];
            foreach (static::getConfiguration()->recordFields as $prop => $conf) {
                if (Tinebase_Model_EvaluationDimensionItem::class === ($conf[self::CONFIG][self::RECORD_CLASS_NAME] ?? null)) {
                    $evalDimProperties[static::class][] = $prop;
                }
            }
        }

        if (isset($_data[self::FLD_DOCUMENT_CATEGORY])) {
            if (is_string($_data[self::FLD_DOCUMENT_CATEGORY])) {
                $_data[self::FLD_DOCUMENT_CATEGORY] = Sales_Controller_Document_Category::getInstance()->get($_data[self::FLD_DOCUMENT_CATEGORY]);
            }
            foreach ($evalDimProperties[static::class] as $evalDimProperty) {
                if (!array_key_exists($evalDimProperty, $_data) && isset($_data[self::FLD_DOCUMENT_CATEGORY][$evalDimProperty])) {
                    $_data[$evalDimProperty] = $_data[self::FLD_DOCUMENT_CATEGORY][$evalDimProperty];
                }
            }
        }

        parent::setFromArray($_data);
    }

    public function createPositionFromProduct(Sales_Model_Product $product, string $lang): Sales_Model_DocumentPosition_Abstract
    {
        /** @var Sales_Model_DocumentPosition_Abstract $positionClass */
        $positionClass = str_replace('_Document_', '_DocumentPosition_', static::class);
        $position = new $positionClass([], true);

        $prodFlds = $product::getConfiguration()->fields;
        foreach (array_diff(array_intersect($product::getConfiguration()->fieldKeys, $positionClass::getConfiguration()->fieldKeys), Tinebase_ModelConfiguration::$genericProperties) as $property) {
            if (($prodFlds[$property][self::CONFIG][self::SPECIAL_TYPE] ?? null) === self::TYPE_LOCALIZED_STRING) {
                $position->{$property} = ($product->{$property}?->find(Tinebase_Record_PropertyLocalization::FLD_LANGUAGE, $lang)
                    ?: $product->{$property}?->getFirstRecord())?->{Tinebase_Record_PropertyLocalization::FLD_TEXT};
            } else {
                $position->{$property} = $product->{$property};
            }
        }

        if (! $product->{Sales_Model_Product::FLD_NAME}) {
            throw new Tinebase_Exception_Record_Validation('Name cannot be empty - we need an expanded product');
        }
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_TITLE} = ($product->{Sales_Model_Product::FLD_NAME}
            ->find(Tinebase_Record_PropertyLocalization::FLD_LANGUAGE, $lang)
            ?: $product->{Sales_Model_Product::FLD_NAME}->getFirstRecord())->{Tinebase_Record_PropertyLocalization::FLD_TEXT};
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_TYPE} = Sales_Model_DocumentPosition_Abstract::POS_TYPE_PRODUCT;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_PRODUCT_ID} = $product;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_QUANTITY} = 1;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_POSITION_DISCOUNT_TYPE} = Sales_Config::INVOICE_DISCOUNT_SUM;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_POSITION_DISCOUNT_SUM} = 0;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_POSITION_DISCOUNT_PERCENTAGE} = 0;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE_TYPE} = $product->{Sales_Model_Product::FLD_SALESPRICE_TYPE} ?: Sales_Config::PRICE_TYPE_NET;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE} = $product->{Sales_Model_Product::FLD_SALESPRICE} ?: 0;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX_RATE} = $product->{Sales_Model_Product::FLD_SALESTAXRATE} ?: 0;
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_GROUPING} = $product->{Sales_Model_Product::FLD_DEFAULT_GROUPING};
        $position->{Sales_Model_DocumentPosition_Abstract::FLD_SORTING} = $product->{Sales_Model_Product::FLD_DEFAULT_SORTING};

        if ($this->{self::FLD_VAT_PROCEDURE} && $this->{self::FLD_VAT_PROCEDURE} !== Sales_Config::VAT_PROCEDURE_STANDARD && Sales_Config::PRICE_TYPE_GROSS ===
                $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE_TYPE}) {
           $position->computePrice();
           $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE_TYPE} = Sales_Config::PRICE_TYPE_NET;
           $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE} =
               $position->{Sales_Model_DocumentPosition_Abstract::FLD_UNIT_PRICE} - $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX};
           $position->{Sales_Model_DocumentPosition_Abstract::FLD_SALES_TAX_RATE} = 0;
        }

        $position->computePrice();

        $this->{self::FLD_POSITIONS}->addRecord($position);
        return $position;
    }

    public function prepareForCopy(): void
    {
        /** @var Sales_Model_Document_AttachedDocument $attachedDoc */
        foreach ($this->{self::FLD_ATTACHED_DOCUMENTS} as $attachedDoc) {
            switch ($attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_TYPE}) {
                case Sales_Model_Document_AttachedDocument::TYPE_PAPERSLIP:
                case Sales_Model_Document_AttachedDocument::TYPE_EDOCUMENT:
                    $this->{self::FLD_ATTACHED_DOCUMENTS}->removeById($attachedDoc->getId());
                    if ($this->{self::FLD_ATTACHMENTS} instanceof Tinebase_Record_RecordSet) {
                        $this->{self::FLD_ATTACHMENTS}->removeById($attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_NODE_ID});
                    }
                    break;
            }
        }

        $this->{self::FLD_PRECURSOR_DOCUMENTS} = null;
        $this->{static::getStatusField()} = null;
        $this->{self::FLD_REVERSAL_STATUS} = null;
        $this->{self::FLD_DISPATCH_HISTORY} = null;
        $this->{self::FLD_DOCUMENT_SEQ} = 1;

        parent::prepareForCopy();

        $this->isValid();
    }

    public function getCurrentAttachedDocuments(): Tinebase_Record_RecordSet
    {
        return $this->{self::FLD_ATTACHED_DOCUMENTS}->filter(Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ, $this->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ});
    }
}
