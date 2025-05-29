<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  MFA
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * MFA_UserConfig Model
 *
 * @package     Tinebase
 * @subpackage  MFA
 */
class Sales_Model_DocumentPosition_Abstract extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'DocumentPosition_Abstract';

    const FLD_DOCUMENT_ID = 'document_id';
    const FLD_PARENT_ID = 'parent_id';
    const FLD_TYPE = 'type';
    const FLD_SORTING = 'sorting'; // automatisch in 10000er schritten, shy
    const FLD_GROUPING = 'grouping'; // gruppierte darstellung, automatische laufende nummern pro gruppe(nicht persistiert)

    // guess this is not necessary const FLD_SUBPRODUCT_MAPPING = 'subproduct_mapping'; // "kreuztabelle" Sales_Model_SubproductMapping (nur für bundles nicht für set's?)

    const FLD_PRECURSOR_POSITION_MODEL = 'precursor_position_model'; // z.B. angebotsposition bei auftragsposition (virtual, link?)
    const FLD_PRECURSOR_POSITION = 'precursor_position'; // z.B. angebotsposition bei auftragsposition (virtual, link?)

    const FLD_POS_NUMBER = 'pos_number';
    const FLD_PRODUCT_ID = 'product_id';  // optional, es gibt auch textonlypositionen

    const FLD_TITLE = 'title'; // einzeiler/überschrift(fett) aus product übernommen sind änderbar
    const FLD_DESCRIPTION = 'description'; // aus product übernommen sind idr. änderbar
    const FLD_QUANTITY = 'quantity'; // Anzahl - aus produkt übernehmen, standard 1
    const FLD_USE_ACTUAL_QUANTITY  = 'use_actual_quantity'; // boolean, wenn true muss es eine verknüpfung mit n leistungsnachweisen (accountables) geben
    const FLD_UNIT = 'unit'; // Einheit - aus product übernehmen
    const FLD_UNIT_PRICE_TYPE = 'unit_price_type'; // unit price is net or gross
    const FLD_UNIT_PRICE = 'unit_price'; // Einzelpreis - aus product übernehmen
    const FLD_POSITION_PRICE = 'position_price'; // Preis - anzahl * einzelpreis

    const FLD_POSITION_DISCOUNT_TYPE = 'position_discount_type'; // PERCENTAGE|SUM
    const FLD_POSITION_DISCOUNT_PERCENTAGE = 'position_discount_percentage'; // automatische Berechnung je nach tupe
    const FLD_POSITION_DISCOUNT_SUM = 'position_discount_sum'; // automatische Berechnung je nach type

    const FLD_NET_PRICE = 'net_price'; // Nettopreis - Preis - Discount

    const FLD_SALES_TAX_RATE = 'sales_tax_rate';
    const FLD_SALES_TAX = 'sales_tax'; // Mehrwertssteuer
    const FLD_GROSS_PRICE= 'gross_price'; // Bruttopreis - berechnen

    const FLD_SERVICE_PERIOD_START = 'service_period_start';
    const FLD_SERVICE_PERIOD_END = 'service_period_end';

    const FLD_EVAL_DIM_COST_CENTER = 'eval_dim_cost_center'; // aus document od. item übernehmen, config bestimmt wer vorfahrt hat und ob user überschreiben kann
    const FLD_EVAL_DIM_COST_BEARER = 'eval_dim_cost_bearer'; // aus document od. item übernehmen, config bestimmt wer vorfahrt hat, und ob user überschreiben kann
    const FLD_REVERSAL = 'reversal';
    const FLD_IS_REVERSED = 'is_reversed';

    //const FLD_XPROPS = 'xprops'; // z.B. entfaltungsart von Bundle od. Set merken



    // Produkte:
    // - shortcut intern (wird als kurzbez. in subproduktzuordnung übernommen, in pos nicht benötigt) - varchar 20
    // - title - varchar -> NAME! the field is called name ... fix the FE code to deal with it
    // - description - text
    // - gruppierung (Gruppierung von Positionen z.B. A implementierung, B regelm., C zusatz) - varchar - autocomplete
    // why? this is done once its added to a position no?

    // - anzahl
    // part of the position? not the product?

    // - anzahl aus accounting (ja/nein)
    // not sure, can we do when we do it?

    // - einheit - varchar
    // - verkaufspreis (pro einheit)
    // - steuersatz - percentage
    // - kostenstelle
    // - kostenträger
    // - ist verkaufsprodukt (bool) [nur verkaufsprodukte können in positionen direkt gewählt werden]
    // - Entfaltungsmodus (unfold type) (Nur aktiv wenn subprodukte ansonsten leer)
    //    Bundle -> Hauptprodukt wird übernommen und hat den Gesamtpreis, subprodukte werden nicht als belegpositionen übernommen (variablen beleg pos. werden trotzdem erzeugt!! + schattenpositionen die nicht angedruckt werden)
    //              die subproduktzuordnung wird übernommen!
    //    Set -> jedes subprodukt wird mit preis einzeln übernommen, hauptprodukt wird ohne preis übernommen
    //           gruppe aus hauptprodukt wird in jedes subprodukt übernommen
    //
    //    Zur Sicherheit: Bundles/Sets dürfen keine Bundles/Sets enthalten!
    // - subproduktzuordnung (eigene Tabelle)
    //   - shortcut z.B. vcpu, supportstunden, ... eindeutig pro hauptprodukt wird aus produkt übernommen (wird gebraucht für variablennamen im text des hauptproduktes)
    //   - hauptproduct_id (referenz für subprod tabelle)
    //   - product_id (referenz auf einzelprodukt)
    //   - inclusive_anzahl
    //   - variable_anzahl_position (frage nach variablen zusatzleistungen)
    //      - keine: inclusiveprodukt bekommt keine eigene belegposition (z.B. nur teil des positionstextes des Hauptproduktes, keine variable/zusatz anzahl in folge belegen (z.B. lieferung/rechnung) möglich)
    //      - gemeinsam: wenn mehrere hauptprodukte das referenzierte inclusive produkt beinhalten, entsteht im Beleg nur eine variable/zusatz position
    //      - eigene: wenn mehrere hauptprodukte das referenzierte inclusive produkt beinhalten, entsteht im Beleg je eine variable/zusatz position pro Hauptprodukt
    //     NOTE: in produkt ENUM, in position id zur position (entsteht beim zufügen des produktes in den Beleg automatisch)
    // - accountable (wie bisher)

    // in beschreibung des produktes. können variblen verwendet werden um auf die subprodukte zuzugreifen
    // {{ <shortcut>.<field> }} {{ <shortcut>.record.<productfield> }}
    // BSP: VM mit {{ cpu.inclusive }} vcpus und {{ ram.inclusive }} vram

    // Autrags belegposition die use_actual_quantity haben müssen verknüpfung zum konkreten leistungsnachweis (accountable)
    // im rahmen der leistungserfassung werden die tatsächlichen "Anzahl-Werte" ermittelt


    // Belegdruck:
    //  - im Hintergrund word export + pdf konvertierung
    //  - im UI nicht export btn sondern spezial btn mit eigener API (speichert, generiert syncron, liefert ganzen record zurück, ...)
    //    - vermutlich zwei buttons: drucken (proforma solang ungebucht, buchen und drucken)
    //  - pro documenttype ein template und eine export-definition (namenskonvention) (btn parametrisiert den export)
    //  - erst mal keine separaten templates pro kategorie, wenn wir das brauchen z.B. definition entsprechend benamen (namenskonvention)

    // @TODO: Vertäge - wie passt das rein? Klammer / auch im Standard? Muss es den geben?
    // @TODO: Preisstaffeln

    // @TODO: durchdenken zusatzfelder unten und velo sachen
    // zusatzfelder wegen 'dauerschuldverhältnissen'
    // laufzeit, start, ende etc.
    // abrechnungsperiode once, monthly, 3monthly, quarterly, yearly -> mehr gedanken machen
    // abrechnungszeitpunkt

    // @TODO: Schnittstelle zum accounting // Rechnungserzeugung (MW) -> passt grob!
    //  -> Sales.createAutoInvoices, getBillableContractIds (geht über Verträge)
    //    -> vertrags positionen (product_aggregate) "wissen" wann sie abgerechnet werden müssen (eigenschaft - rechnet ab)
    //     [produkt->accountable] "accountables" liste von (SalesModelAccountableAbstract)
    //          accountables können nach billables gefragt werden (z.B. speicherplatzpfad (accountable), speicherplatzaggregat (billable)
    //          accountables
    //

    // @TODO: MW Rechnbung reviewn -> migration zu neuer Rechnung hinschreiben!
    //  oder doch erst später? Erst mal KB ans laufen bringen?!

    const FLD_INTERNAL_NOTE = 'internal_note';

    const POS_TYPE_PRODUCT = 'PRODUCT';
    const POS_TYPE_HEADING = 'HEADING';
    const POS_TYPE_TEXT = 'TEXT';
    const POS_TYPE_ALTERNATIVE = 'ALTERNATIVE';
    const POS_TYPE_OPTIONAL = 'OPTIONAL';
    const POS_TYPE_PAGEBREAK = 'PAGEBREAK';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        //self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Position', // ngettext('Position', 'Positions', n)
        self::RECORDS_NAME                  => 'Positions', // gettext('GENDER_Position')
        self::MODLOG_ACTIVE                 => true,
        self::HAS_XPROPS                    => true,
        self::EXPOSE_JSON_API               => true,
        self::EXPOSE_HTTP_API               => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS      => true,

        self::TABLE                         => [
            self::INDEXES                       => [
                self::FLD_DOCUMENT_ID               => [
                    self::COLUMNS                       => [self::FLD_DOCUMENT_ID],
                ],
                self::FLD_PARENT_ID                 => [
                    self::COLUMNS                       => [self::FLD_PARENT_ID],
                ],
                self::DESCRIPTION => [
                    self::COLUMNS       => [self::DESCRIPTION],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::JSON_EXPANDER                 => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DOCUMENT_ID                       => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY  => [],
                        Sales_Model_Document_Abstract::FLD_CUSTOMER_ID        => [],
                    ],
                ],
                self::FLD_PRODUCT_ID                        => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Product::FLD_SUBPRODUCTS        => [],
                    ],
                ],
            ],
        ],

        self::FILTER_MODEL => [
            'category'                  => [
                self::LABEL => 'Category', // _('Category')
                self::FILTER => Sales_Model_DocumentPosition_CategoryFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => null,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Document_Category::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
            'customer'                  => [
                self::LABEL => 'Customer', // _('Customer')
                self::FILTER => Sales_Model_DocumentPosition_CustomerFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => null,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Customer::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
            'division'                  => [
                self::LABEL => 'Division', // _('Division')
                self::FILTER => Sales_Model_DocumentPosition_DivisionFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => null,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Division::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_DOCUMENT_ID               => [
                // needs to be set by concrete model
                self::LABEL                         => 'Document', // _('Document')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    //self::MODEL_NAME                    => Sales_Model_Document_Abstract::MODEL_NAME_PART,
                    self::FOREIGN_FIELD                 => Sales_Model_Document_Abstract::FLD_POSITIONS,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
                // to prevent circular loop in (sub)diff
                self::OMIT_MOD_LOG                  => true,
            ],
            self::FLD_PARENT_ID                 => [
                // needs to be set by concrete model (but will actually be done here in abstract static inherit hook)
                self::TYPE                          => self::TYPE_RECORD,
                self::DISABLED                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    //self::MODEL_NAME                    => Sales_Model_DocumentPosition_Abstract::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_TYPE                      => [
                self::LABEL                         => 'Type', // _('Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::DOCUMENT_POSITION_TYPE,
            ],
            self::FLD_POS_NUMBER                => [
                self::LABEL                         => 'Pos.', // _('Pos.')
                self::TYPE                          => self::TYPE_STRING,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_PRODUCT_ID                => [
                self::LABEL                         => 'Product', // _('Product')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Product::MODEL_NAME_PART,
                ],
                self::SHY                           => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_GROUPING                  => [
                self::LABEL                         => 'Grouping', // _('Grouping')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_SORTING                   => [
                self::LABEL                         => 'Sorting', // _('Sorting')
                self::TYPE                          => self::TYPE_INTEGER,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_PRECURSOR_POSITION_MODEL  => [
                self::TYPE                          => self::TYPE_STRING,
                self::DISABLED                      => true,
                self::SHY                           => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_PRECURSOR_POSITION        => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::DISABLED                      => true,
                self::SHY                           => true,
                self::NULLABLE                      => true,
                self::FILTER_DEFINITION             => [self::FILTER => Tinebase_Model_Filter_Text::class],
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_PRECURSOR_POSITION_MODEL,
                ],
            ],
            self::FLD_TITLE                     => [
                self::LABEL                         => 'Product / Service', // _('Product / Service')
                self::TYPE                          => self::TYPE_STRING,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::LENGTH                        => 255,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Description', // _('Description')
                self::TYPE                          => self::TYPE_FULLTEXT,
                self::QUERY_FILTER                  => true,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
            ],
            self::FLD_QUANTITY                  => [
                self::LABEL                         => 'Quantity', // _('Quantity')
                self::TYPE                          => self::TYPE_FLOAT,
                self::NULLABLE                      => true,
            ],
            self::FLD_USE_ACTUAL_QUANTITY       => [
                self::LABEL                         => 'Use Actual Quantity', // _('Use Actual Quantity')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_UNIT                      => [
                self::LABEL                         => 'Unit', // _('Unit')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NULLABLE                      => true,
                self::NAME                          => Sales_Config::PRODUCT_UNIT,
            ],
            self::FLD_UNIT_PRICE_TYPE     => [
                self::LABEL                         => 'Price Type', //_('Price Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::PRICE_TYPE,
                self::NULLABLE                      => true,
                self::DEFAULT_VAL                   => Sales_Config::PRICE_TYPE_NET
            ],
            self::FLD_UNIT_PRICE                => [
                self::LABEL                         => 'Unit Price', // _('Unit Price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
            ],
            self::FLD_POSITION_PRICE                 => [
                self::LABEL                         => 'Price', // _('Price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_POSITION_DISCOUNT_TYPE    => [
                self::LABEL                         => 'Position Discount Type', // _('Position Discount Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NULLABLE                      => true,
                self::NAME                          => Sales_Config::INVOICE_DISCOUNT_TYPE,
                self::DISABLED                      => true,
                self::SHY                           => true,
            ],
            self::FLD_POSITION_DISCOUNT_PERCENTAGE => [
                self::LABEL                         => 'Position Discount Percentage', // _('Position Discount Percentage')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::NULLABLE                      => true,
                self::DISABLED                      => true,
                self::SHY                           => true,
            ],
            self::FLD_POSITION_DISCOUNT_SUM     => [
                self::LABEL                         => 'Position Discount Sum', // _('Position Discount Sum')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_DISCOUNT,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    'singleField'   => true,
                    'price_field'   => self::FLD_POSITION_PRICE,
                    'net_field'     => self::FLD_NET_PRICE
                ],
            ],
            self::FLD_NET_PRICE                 => [
                self::LABEL                         => 'Net price', // _('Net price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SALES_TAX_RATE                 => [
                self::LABEL                         => 'Sales Tax Rate', // _('Sales Tax Rate')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::DEFAULT_VAL_CONFIG            => [
                    self::APP_NAME  => Tinebase_Config::APP_NAME,
                    self::CONFIG => Tinebase_Config::SALES_TAX
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_SALES_TAX               => [
                self::LABEL                         => 'Sales Tax', // _('Sales Tax')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_GROSS_PRICE               => [
                self::LABEL                         => 'Gross Price', // _('Gross Price')
                self::TYPE                          => self::TYPE_MONEY,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_SERVICE_PERIOD_START => [
                self::TYPE                  => self::TYPE_DATE,
                self::LABEL                 => 'Service Period Start', //_('Service Period Start')
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],
            self::FLD_SERVICE_PERIOD_END =>  [
                self::TYPE                  => self::TYPE_DATE,
                self::LABEL                 => 'Service Period End', //_('Service Period End')
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],
            self::FLD_REVERSAL                  => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_IS_REVERSED               => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    protected static $_exportContextLocale = null;

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        [, $suffix] = explode('_', static::MODEL_NAME_PART, 2);
        $modelNamePart = 'Document_' . $suffix;
        $_definition[self::FLD_DOCUMENT_ID][self::CONFIG][self::MODEL_NAME] = $modelNamePart;

        $_definition[self::FILTER_MODEL]['customer'][self::OPTIONS][self::MODEL_NAME] = $modelNamePart;
        $_definition[self::FILTER_MODEL]['customer'][self::OPTIONS][self::RECORD_CLASS_NAME] = 'Sales_Model_' . $modelNamePart;
        $_definition[self::FILTER_MODEL]['customer'][self::OPTIONS][self::CONTROLLER_CLASS_NAME] = 'Sales_Controller_' . $modelNamePart;

        $_definition[self::FILTER_MODEL]['category'][self::OPTIONS][self::RECORD_CLASS_NAME] = 'Sales_Model_' . $modelNamePart;
        $_definition[self::FILTER_MODEL]['category'][self::OPTIONS][self::CONTROLLER_CLASS_NAME] = 'Sales_Controller_' . $modelNamePart;

        $_definition[self::FILTER_MODEL]['division'][self::OPTIONS][self::RECORD_CLASS_NAME] = 'Sales_Model_' . $modelNamePart;
        $_definition[self::FILTER_MODEL]['division'][self::OPTIONS][self::CONTROLLER_CLASS_NAME] = 'Sales_Controller_' . $modelNamePart;
    }

    public static function setExportContextLocale(?Zend_Locale $locale)
    {
        static::$_exportContextLocale = $locale;
    }

    public static function getExportContextLocale(): ?Zend_Locale
    {
        return static::$_exportContextLocale;
    }

    public function getLocalizedDiscountString(): string
    {
        // exports arrive here with resolved values, so the value of the keyfield, not its id
        switch ($this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
            // exports arrive here with resolved values, so the value of the keyfield, not its id
            case 'Percentage':
            case Sales_Config::INVOICE_DISCOUNT_PERCENTAGE:
                $type = Sales_Config::INVOICE_DISCOUNT_PERCENTAGE;
                break;

            case 'Sum':
            case Sales_Config::INVOICE_DISCOUNT_SUM:
                $type = Sales_Config::INVOICE_DISCOUNT_SUM;
                break;

            default:
                $locale = static::getExportContextLocale() ?: Tinebase_Core::getLocale();
                switch (Tinebase_Translation::getTranslation(Sales_Config::APP_NAME, $locale)->getMessageId($this->{self::FLD_POSITION_DISCOUNT_TYPE})) {
                    case 'Percentage':
                        $type = Sales_Config::INVOICE_DISCOUNT_PERCENTAGE;
                        break;
                    case 'Sum':
                        $type = Sales_Config::INVOICE_DISCOUNT_SUM;
                        break;
                    default:
                        return '';
                }
        }

        switch ($type) {
            case Sales_Config::INVOICE_DISCOUNT_PERCENTAGE:
                $value = $this->{self::FLD_POSITION_DISCOUNT_PERCENTAGE};
                $value = round((float)$value, 2);
                return sprintf('%01.2f %%', $value);

            case Sales_Config::INVOICE_DISCOUNT_SUM:
                $value = $this->{self::FLD_POSITION_DISCOUNT_SUM};
                $value = round((float)$value, 2);
                return sprintf('%01.2f ', $value) . Tinebase_Config::getInstance()->get(Tinebase_Config::CURRENCY_SYMBOL);
        }
        return '';
    }

    public function transitionFrom(Sales_Model_DocumentPosition_TransitionSource $transition, bool $reversalOfReversal): void
    {
        $source = $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION};
        foreach (static::getConfiguration()->fieldKeys as $property) {
            if ($source->has($property)) {
                $this->{$property} = $source->{$property};
            }
        }

        $this->{self::FLD_IS_REVERSED} = false;
        $this->{self::FLD_REVERSAL} =
            (bool)$transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL};
        $this->{self::FLD_PRECURSOR_POSITION_MODEL} =
            $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION_MODEL};
        $this->{self::FLD_PRECURSOR_POSITION} =
            $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_SOURCE_DOCUMENT_POSITION}->getId();

        if ($this->{self::FLD_REVERSAL}) {
            $translation = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
                new Zend_Locale($this->{self::FLD_DOCUMENT_ID}->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE}));
            $this->{self::FLD_TITLE} = $translation->_('Reversal') . ': ' . $this->{self::FLD_TITLE};
            $source->{Sales_Model_DocumentPosition_Abstract::FLD_IS_REVERSED} = true;

            // make document_id dirty
            $source->{self::FLD_DOCUMENT_ID}->{Sales_Model_Document_Abstract::FLD_REVERSAL_STATUS} = $source->{self::FLD_DOCUMENT_ID}->{Sales_Model_Document_Abstract::FLD_REVERSAL_STATUS};
        } elseif ($reversalOfReversal) {
            $translation = Tinebase_Translation::getTranslation(Sales_Config::APP_NAME,
                new Zend_Locale($this->{self::FLD_DOCUMENT_ID}->{Sales_Model_Document_Abstract::FLD_DOCUMENT_LANGUAGE}));
            $this->{self::FLD_TITLE} =
                preg_replace("/^{$translation->_('Reversal')}: /", '', $this->{self::FLD_TITLE});
        }

        $this->__unset($this->getIdProperty());

        if (!$this->isProduct()) {
            return;
        }

        if ($reversalOfReversal || $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL}) {
            $this->{self::FLD_UNIT_PRICE} = 0 - $this->{self::FLD_UNIT_PRICE};
            if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
                $this->{self::FLD_POSITION_DISCOUNT_SUM} = 0 - $this->{self::FLD_POSITION_DISCOUNT_SUM};
            }
        }

        // we need to check if there are followup positions for our precursor position already
        $existingQuantities = null;
        /** @var Tinebase_Controller_Record_Abstract $ctrl */
        $ctrl = Tinebase_Core::getApplicationInstance(static::class);
        foreach ($ctrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(static::class, [
                    ['field' => Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION,
                        'operator' => 'equals', 'value' => $this->getIdFromProperty(self::FLD_PRECURSOR_POSITION)],
                    ['field' => Sales_Model_DocumentPosition_Abstract::FLD_PRECURSOR_POSITION_MODEL,
                        'operator' => 'equals', 'value' => $this->{self::FLD_PRECURSOR_POSITION_MODEL}],
                    ['field' => Sales_Model_DocumentPosition_Abstract::FLD_IS_REVERSED, 'operator' => 'equals', 'value' => false],
                ])) as $existingFollowUp) {
            $existingQuantities += $existingFollowUp->{self::FLD_QUANTITY};
        }

        if (null !== $existingQuantities) {
            $this->canCreatePartialFollowUp();

            if ($existingQuantities >= $this->{self::FLD_QUANTITY}) {
                throw new Tinebase_Exception_Record_Validation('no quantity left for partial followup position');
            }

            $this->{self::FLD_QUANTITY} = $this->{self::FLD_QUANTITY} - $existingQuantities;

            $this->computePrice();
        } elseif ($reversalOfReversal || $transition->{Sales_Model_DocumentPosition_TransitionSource::FLD_IS_REVERSAL}) {
            $this->computePrice();
        }
    }

    public function isProduct(): bool
    {
        return self::POS_TYPE_PRODUCT === $this->{self::FLD_TYPE} ||
            self::POS_TYPE_ALTERNATIVE === $this->{self::FLD_TYPE} ||
            self::POS_TYPE_OPTIONAL === $this->{self::FLD_TYPE};
    }

    protected function canCreatePartialFollowUp(): void
    {
    }

    /**
     * AbstractMixin.computePrice Zeile 62ff
     *
     * @param bool $onlyProductType
     * @return void
     *
     * TODO not sure if we need the param & the "only product" check ...
     */
    public function computePrice(bool $onlyProductType = true): void
    {
        if ($onlyProductType && $this->{self::FLD_TYPE} && self::POS_TYPE_PRODUCT !== $this->{self::FLD_TYPE}) {
            return;
        }
        $this->{self::FLD_POSITION_PRICE} = is_null($this->{self::FLD_UNIT_PRICE}) || is_null($this->{self::FLD_QUANTITY}) ? null
            : $this->{self::FLD_UNIT_PRICE} * $this->{self::FLD_QUANTITY};
        if ($this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
            if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_POSITION_DISCOUNT_TYPE}) {
                $discount = (float)$this->{self::FLD_POSITION_DISCOUNT_SUM};
            } else {
                $discount = round(($this->{self::FLD_POSITION_PRICE} / 100) *
                    (float)$this->{self::FLD_POSITION_DISCOUNT_PERCENTAGE}, 2);
                $this->{self::FLD_POSITION_DISCOUNT_SUM} = $discount;
            }
        } else {
            $discount = 0;
        }

        if (null === $this->{self::FLD_SALES_TAX_RATE}) {
            $this->{self::FLD_SALES_TAX_RATE} = Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX};
        }

        $total = is_null($this->{self::FLD_POSITION_PRICE}) ? null
            : $this->{self::FLD_POSITION_PRICE} - $discount;

        if ($this->{self::FLD_UNIT_PRICE_TYPE} === Sales_Config::PRICE_TYPE_GROSS) {
            $this->{self::FLD_GROSS_PRICE} = $total;
            // tax = total - total * 100/(100+taxRate);
            $this->{self::FLD_SALES_TAX} = is_null($this->{self::FLD_SALES_TAX_RATE}) ? null
                : $this->{self::FLD_GROSS_PRICE} - round(($this->{self::FLD_GROSS_PRICE} * 100) / (100 + (float)$this->{self::FLD_SALES_TAX_RATE}), 2);
            $this->{self::FLD_NET_PRICE} = $this->{self::FLD_GROSS_PRICE} - $this->{self::FLD_SALES_TAX};
        } else {
            $this->{self::FLD_NET_PRICE} = $total;
            $this->{self::FLD_SALES_TAX} = is_null($this->{self::FLD_SALES_TAX_RATE}) ? null
                : round(($this->{self::FLD_NET_PRICE} / 100) * (float)$this->{self::FLD_SALES_TAX_RATE}, 2);
            $this->{self::FLD_GROSS_PRICE} = is_null($this->{self::FLD_NET_PRICE}) ? null
                : $this->{self::FLD_NET_PRICE} + $this->{self::FLD_SALES_TAX};
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

        unset($_data[self::FLD_PRECURSOR_POSITION]);
        unset($_data[self::FLD_PRECURSOR_POSITION_MODEL]);
    }

    public function prepareForCopy(): void
    {
        $this->{self::FLD_IS_REVERSED} = false;
        $this->{self::FLD_REVERSAL} = false;
        $this->{self::FLD_PRECURSOR_POSITION_MODEL} = null;
        $this->{self::FLD_PRECURSOR_POSITION} = null;

        parent::prepareForCopy();
    }
}
