<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Sales config class
 * 
 * @package     Sales
 * @subpackage  Config
 * 
 */
class Sales_Config extends Tinebase_Config_Abstract
{
    /**
     * @var string
     */
    const APP_NAME = 'Sales';

    /**
     * sets the biggest interval, contracts will be billed
     * 
     * @var string
     */
    const AUTO_INVOICE_CONTRACT_INTERVAL = 'auto_invoice_contract_interval';
    
    /**
     * defines which billables should be ignored
     * 
     * @var string
     */
    const IGNORE_BILLABLES_BEFORE = 'ignoreBillablesBefore';
    
    /**
     * How should the contract number be created
     * @var string
     */
    const CONTRACT_NUMBER_GENERATION = 'contractNumberGeneration';
    
    /**
     * How should the contract number be validated
     * @var string
     */
    const CONTRACT_NUMBER_VALIDATION = 'contractNumberValidation';

    const DEFAULT_DEBITOR_EDOCUMENT_DISPATCH_TYPE = 'defaultDebitorEDocumentDispatchType';
    const DEFAULT_EDOCUMENT_DISPATCH_DOCUMENT_TYPES = 'defaultEDocumentDispatchDocumentTypes';

    const PAYMENT_REMINDER_LEVEL = 'paymentReminderLevel';

    /**
     * How should the contract number be created
     * @var string
     */
    public const PRODUCT_NUMBER_GENERATION = 'productNumberGeneration';
    public const PRODUCT_NUMBER_GENERATION_AUTO = 'auto';
    public const PRODUCT_NUMBER_GENERATION_MANUAL = 'manual';

    public const EDOCUMENT = 'edocument';
    public const EDOCUMENT_SVC_BASE_URL = 'svc_base_url';
    public const VALIDATION_SVC = 'validation_svc';
    public const VIEW_SVC = 'view_svc';
    const CUSTOMER_CONTACT_PERSON_FILTER = 'customerContactPersonFilter';
    public const PAYMENT_MEANS_ID_TMPL = 'paymentMeansIdTmpl';

    public const INVOICE_EDOCUMENT_NAME_TMPL = 'invoiceEDocumentNameTmpl';
    public const INVOICE_PAPERSLIP_NAME_TMPL = 'invoicePaperslipNameTmpl';
    public const INVOICE_EDOCUMENT_RENAME_TMPL = 'invoiceEDocumentRenameTmpl';
    public const INVOICE_PAPERSLIP_RENAME_TMPL = 'invoicePaperslipRenameTmpl';

    /**
     * How should the contract number be validated
     * 
     * @var string
     */
    const PRODUCT_NUMBER_VALIDATION = 'productNumberValidation';
    
    /**
     * Prefix of the product number
     * 
     * @var string
     */
    const PRODUCT_NUMBER_PREFIX = 'productNumberPrefix';
    
    /**
     * Fill product number with leading zero's if needed
     * 
     * @var string
     */
    const PRODUCT_NUMBER_ZEROFILL = 'productNumberZeroFill';

    /**
     * container xprop to update related customer contacts
     *
     * @const string XPROP_CUSTOMER_ADDRESSBOOK
     */
    public const XPROP_CUSTOMER_ADDRESSBOOK = 'customer_addressbook';

    /**
     * default payment terms in days
     *
     * @const string DEFAULT_PAYMENT_TERMS
     */
    public const DEFAULT_PAYMENT_TERMS = 'defaultPaymentTerms';

    /**
     * Invoice Type
     * 
     * @var string
     */
    const INVOICE_TYPE = 'invoiceType';

    const PRICE_TYPE = 'priceType';
    const PRICE_TYPE_NET = 'net';
    const PRICE_TYPE_GROSS = 'gross';

    const INVOICE_DISCOUNT_TYPE = 'invoiceDiscountType';
    const INVOICE_DISCOUNT_PERCENTAGE = 'PERCENTAGE';
    const INVOICE_DISCOUNT_SUM = 'SUM';
    
    const PAYMENT_METHODS = 'paymentMethods';
    const DEBITOR_DEFAULT_PAYMENT_MEANS = 'debitorDefaultPaymentMeans';

    /**
     * Product Category
     * 
     * @var string
     */
    const PRODUCT_CATEGORY = 'productCategory';

    /**
     * Document Category
     * @deprecated DO NOT USE, it is just required for migration purposes
     * @var string
     */
    const DOCUMENT_CATEGORY = 'documentCategory';

    const DEFAULT_DIVISION = 'defaultDivision';

    const DOCUMENT_CATEGORY_DEFAULT = 'documentCategoryDefault';

    const DOCUMENT_POSITION_TYPE = 'documentPositionType';

    const PRODUCT_UNFOLDTYPE = 'productUnfoldType';

    const PRODUCT_UNIT = 'productUnit';

    const LANGUAGES_AVAILABLE = 'languagesAvailable';

    const VARIABLE_POSITION_FLAG = 'subProductPositionFlag';

    const VAT_PROCEDURES = 'vatProcedures';
    const VAT_PROCEDURE_STANDARD = 'standard'; // was taxable
    const VAT_PROCEDURE_OUTSIDE_TAX_SCOPE = 'outsideTaxScope'; // was nonTaxable
    const VAT_PROCEDURE_REVERSE_CHARGE = 'reverseCharge';
    const VAT_PROCEDURE_FREE_EXPORT_ITEM = 'freeExportItem'; // was export
    const VAT_PROCEDURE_ZERO_RATED_GOODS = 'zeroRatedGoods';

    const REVERSE_CHANGE_TEMPLATE = 'reverseChargeTemplate';

    /**
     * followup status
     */
    public const DOCUMENT_FOLLOWUP_STATUS = 'followupStatus';
    public const DOCUMENT_FOLLOWUP_STATUS_NONE = 'none';
    public const DOCUMENT_FOLLOWUP_STATUS_PARTIALLY = 'partially';
    public const DOCUMENT_FOLLOWUP_STATUS_COMPLETED = 'completed';

    /**
     * reversal status
     */
    public const DOCUMENT_REVERSAL_STATUS = 'reversalStatus';
    public const DOCUMENT_REVERSAL_STATUS_NOT_REVERSED = 'notReversed';
    public const DOCUMENT_REVERSAL_STATUS_PARTIALLY_REVERSED = 'partiallyReversed';
    public const DOCUMENT_REVERSAL_STATUS_REVERSED = 'reversed';

    public const DOCUMENT_PURCHASE_INVOICE_STATUS = 'documentPurchaseInvoiceStatus';
    public const DOCUMENT_PURCHASE_INVOICE_STATUS_TRANSITIONS = 'documentPurchaseInvoiceStatusTransitions';

    /**
     * offer status
     * 
     * @var string 
     */
    public const DOCUMENT_OFFER_STATUS = 'documentOfferStatus';
    public const DOCUMENT_OFFER_STATUS_TRANSITIONS = 'documentOfferStatusTransitions';

    /**
     * order status
     *
     * @var string
     */
    public const DOCUMENT_ORDER_STATUS = 'documentOrderStatus';
    public const DOCUMENT_ORDER_STATUS_TRANSITIONS = 'documentOrderStatusTransitions';

    /**
     * delivery status
     *
     * @var string
     */
    public const DOCUMENT_DELIVERY_STATUS = 'documentDeliveryStatus';
    public const DOCUMENT_DELIVERY_STATUS_TRANSITIONS = 'documentDeliveryStatusTransitions';

    /**
     * invoice status
     *
     * @var string
     */
    public const DOCUMENT_INVOICE_STATUS = 'documentInvoiceStatus';
    public const DOCUMENT_INVOICE_STATUS_TRANSITIONS = 'documentInvoiceStatusTransitions';

    /**
     * sender and recipient emails for datev
     *
     * @var string
     */
    public const DATEV_SENDER_EMAIL_PURCHASE_INVOICE = 'datevSenderEmailPurchaseInvoice';
    public const DATEV_SENDER_EMAIL_INVOICE = 'datevSenderEmailInvoice';

    public const DATEV_RECIPIENT_EMAILS_PURCHASE_INVOICE = 'datevRecipientEmailsPurchaseInvoice';
    public const DATEV_RECIPIENT_EMAILS_INVOICE = 'datevRecipientEmailsInvoice';


    /**
     * Invoice Type
     *
     * @var string
     */
    const INVOICE_CLEARED = 'invoiceCleared';

    
    /**
     * invoices module feature
     *
     * @var string
     */
    const FEATURE_INVOICES_MODULE = 'invoicesModule';
    
    /**
     * suppliers module feature
     *
     * @var string
     */
    const FEATURE_SUPPLIERS_MODULE = 'suppliersModule';
    
    /**
     * purchase invoices module feature
     *
     * @var string
     */
    const FEATURE_PURCHASE_INVOICES_MODULE = 'purchaseInvoicesModule';
    
    /**
     * offers module feature
     *
     * @var string
     */
    const FEATURE_OFFERS_MODULE = 'offersModule';

    /**
     * legacy offers feature
     *
     * @var string
     */
    const FEATURE_LEGACY_OFFERS = 'legacyOffers';

    /**
     * order confirmations module feature
     *
     * @var string
     */
    const FEATURE_ORDERCONFIRMATIONS_MODULE = 'orderConfirmationsModule';

    const DISPATCH_HISTORY_TYPES = 'dispatchHistoryType';

    const ATTACHED_DOCUMENT_TYPES = 'attachedDocumentTypes';
    const ATTACHED_DOCUMENT_TYPES_PAPERSLIP = 'paperslip';
    const ATTACHED_DOCUMENT_TYPES_EDOCUMENT = 'edocument';
    const ATTACHED_DOCUMENT_TYPES_SUPPORTING_DOC = 'supportingDoc';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::AUTO_INVOICE_CONTRACT_INTERVAL => array(
            //_('Auto Invoice Contract Interval')
            'label'                 => 'Auto Invoice Contract Interval',
            //_('Sets the maximum interval at which contracts will be billed.')
            'description'           => 'Sets the maximum interval at which contracts will be billed.',
            'type'                  => 'integer',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 12
        ),
        self::ATTACHED_DOCUMENT_TYPES => [
            self::LABEL              => 'Attached Document Types', //_('Attached Document Types')
            self::DESCRIPTION        => 'Attached Document Types',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => self::ATTACHED_DOCUMENT_TYPES_PAPERSLIP,
                        'value' => 'Paperslip', //_('Paperslip')
                        'icon' => null,
                        'system' => true,
                    ], [
                        'id' => self::ATTACHED_DOCUMENT_TYPES_EDOCUMENT,
                        'value' => 'eDocument', //_('eDocument')
                        'icon' => null,
                        'system' => true,
                    ], [
                        'id' => self::ATTACHED_DOCUMENT_TYPES_SUPPORTING_DOC,
                        'value' => 'Supporting Document', //_('Supporting Document')
                        'icon' => null,
                        'system' => true,
                    ],
                ],
            ],
        ],
        self::DISPATCH_HISTORY_TYPES => [
            self::LABEL              => 'Attached Document Types', //_('Attached Document Types')
            self::DESCRIPTION        => 'Attached Document Types',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => Sales_Model_Document_DispatchHistory::DH_TYPE_START,
                        'value' => 'Start', //_('Start')
                        'icon' => 'images/icon-set/icon_arrow_right.svg',
                        'system' => true,
                    ], [
                        'id' => Sales_Model_Document_DispatchHistory::DH_TYPE_WAIT_FOR_FEEDBACK,
                        'value' => 'Waiting for feedback', //_('Waiting for feedback')
                        'icon' => 'images/icon-set/icon_timetracking.svg',
                        'system' => true,
                    ], [
                        'id' => Sales_Model_Document_DispatchHistory::DH_TYPE_SUCCESS,
                        'value' => 'Success', //_('Success')
                        'icon' => 'images/icon-set/icon_hook_green.svg',
                        'system' => true,
                    ], [
                        'id' => Sales_Model_Document_DispatchHistory::DH_TYPE_FAIL,
                        'value' => 'Failure', //_('Failure')
                        'icon' => 'images/icon-set/icon_cross_red.svg',
                        'system' => true,
                    ],
                ],
            ],
        ],
        self::DOCUMENT_FOLLOWUP_STATUS => [
            //_('Followup Status')
            self::LABEL              => 'Followup Status',
            //_('Possible Followup Status')
            self::DESCRIPTION        => 'Possible Followup Status',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => self::DOCUMENT_FOLLOWUP_STATUS_NONE,
                        //_('None')
                        'value' => 'None',
                        'icon' => null,
                        'system' => true,
                    ], [
                        'id' => self::DOCUMENT_FOLLOWUP_STATUS_PARTIALLY,
                        //_('Partially')
                        'value' => 'Partially',
                        'icon' => null,
                        'system' => true,
                    ], [
                        'id' => self::DOCUMENT_FOLLOWUP_STATUS_COMPLETED,
                        //_('Completed')
                        'value' => 'Completed',
                        'icon' => null,
                        'system' => true,
                    ],
                ],
                self::DEFAULT_STR => self::DOCUMENT_FOLLOWUP_STATUS_NONE,
            ],
        ],
        self::DOCUMENT_REVERSAL_STATUS => [
            //_('Reversal Status')
            self::LABEL              => 'Reversal Status',
            //_('Possible Reversal Status')
            self::DESCRIPTION        => 'Possible Reversal Status',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => self::DOCUMENT_REVERSAL_STATUS_NOT_REVERSED,
                        //_('Not reversed')
                        'value' => 'Not reversed',
                        'icon' => null,
                        'system' => true,
                    ], [
                        'id' => self::DOCUMENT_REVERSAL_STATUS_PARTIALLY_REVERSED,
                        //_('Partially reversed')
                        'value' => 'Partially reversed',
                        'icon' => null,
                        'system' => true,
                    ], [
                        'id' => self::DOCUMENT_REVERSAL_STATUS_REVERSED,
                        //_('Reversed')
                        'value' => 'Reversed',
                        'icon' => null,
                        'system' => true,
                    ],
                ],
                self::DEFAULT_STR => self::DOCUMENT_REVERSAL_STATUS_NOT_REVERSED,
            ],
        ],
        self::DEFAULT_DEBITOR_EDOCUMENT_DISPATCH_TYPE => [
            self::TYPE                  => self::TYPE_MIXED,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => Sales_Model_EDocument_Dispatch_Email::class,
        ],
        self::DEFAULT_EDOCUMENT_DISPATCH_DOCUMENT_TYPES => [
            self::TYPE                  => self::TYPE_MIXED,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [[Tinebase_Core::class, 'createInstance'], Tinebase_Record_RecordSet::class, Sales_Model_EDocument_Dispatch_DocumentType::class, [
                [Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_PAPERSLIP],
                [Sales_Model_EDocument_Dispatch_DocumentType::FLD_DOCUMENT_TYPE => Sales_Config::ATTACHED_DOCUMENT_TYPES_EDOCUMENT],
            ]],
        ],
        self::DOCUMENT_PURCHASE_INVOICE_STATUS => [
            //_('Purchase Invoice Stati')
            self::LABEL              => 'Purchase Invoice Stati',
            //_('Possible Purchase Invoice Stati')
            self::DESCRIPTION        => 'Possible Purchase Invoice Stati',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS            => [
                self::RECORD_MODEL => Sales_Model_Document_Status::class,
                self::OPTION_TRANSITIONS_CONFIG => self::DOCUMENT_PURCHASE_INVOICE_STATUS_TRANSITIONS,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED,
                        //_('Approval Requested (unbooked, open)')
                        'value' => 'Approval Requested (unbooked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => false,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_PurchaseInvoice::STATUS_APPROVED,
                        //_('Approved (booked, open)')
                        'value' => 'Approved (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_PurchaseInvoice::STATUS_PAID,
                        //_('Paid (booked, closed)')
                        'value' => 'Paid (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ],
                ],
                self::DEFAULT_STR => Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED,
            ],
        ],
        self::DOCUMENT_PURCHASE_INVOICE_STATUS_TRANSITIONS => [
            //_('Purchase Invoice Status Transitions')
            self::LABEL              => 'Purchase Invoice Status Transitions',
            //_('Purchase Invoice Status Transitions')
            self::DESCRIPTION        => 'Purchase Invoice Status Transitions',
            self::TYPE               => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                '' => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED,
                        Sales_Model_Document_PurchaseInvoice::STATUS_APPROVED,
                        Sales_Model_Document_PurchaseInvoice::STATUS_PAID,
                    ]
                ],
                Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_PurchaseInvoice::STATUS_APPROVED,
                        Sales_Model_Document_PurchaseInvoice::STATUS_PAID,
                    ]
                ],
                Sales_Model_Document_PurchaseInvoice::STATUS_APPROVED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED, // till we have a better (storno) solution
                        Sales_Model_Document_PurchaseInvoice::STATUS_PAID,
                    ]
                ],
            ]
        ],
        self::DOCUMENT_OFFER_STATUS => [
            //_('Offer Status')
            self::LABEL              => 'Offer Status',
            //_('Possible Offer Status')
            self::DESCRIPTION        => 'Possible Offer Status',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS            => [
                self::RECORD_MODEL => Sales_Model_Document_Status::class,
                self::OPTION_TRANSITIONS_CONFIG => self::DOCUMENT_OFFER_STATUS_TRANSITIONS,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                // OFFER_STATUS // keyfield: In Bearbeitung(ungebucht, offen), Zugestellt(gebucht, offen), Beauftragt(gebucht, geschlossen), Abgelehnt(gebucht, geschlossen)
                self::RECORDS => [
                    [
                        'id' => Sales_Model_Document_Offer::STATUS_DRAFT,
                        //_('Draft (unbooked, open)')
                        'value' => 'Draft (unbooked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => false,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Offer::STATUS_MANUAL_DISPATCH,
                        //_('Manual dispatch required (booked, open)')
                        'value' => 'Manual dispatch required (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        // 'id' => Sales_Model_Document_Offer::STATUS_RELEASED, => RELEASED
                        'id' => Sales_Model_Document_Offer::STATUS_DISPATCHED,
                        //_('Dispatched (booked, open)')
                        'value' => 'Dispatched (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Offer::STATUS_ORDERED,
                        //_('Ordered (booked, closed)')
                        'value' => 'Ordered (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Offer::STATUS_REJECTED,
                        //_('Rejected (booked, closed)')
                        'value' => 'Rejected (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ],
                ],
                self::DEFAULT_STR => Sales_Model_Document_Offer::STATUS_DRAFT,
            ],
        ],
        self::DOCUMENT_OFFER_STATUS_TRANSITIONS => [
            //_('Offer Status Transitions')
            self::LABEL              => 'Offer Status Transitions',
            //_('Possible Offer Status Transitions')
            self::DESCRIPTION        => 'Possible Offer Status Transitions',
            self::TYPE               => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                '' => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Offer::STATUS_DRAFT,
                        Sales_Model_Document_Offer::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Offer::STATUS_DISPATCHED,
                        Sales_Model_Document_Offer::STATUS_ORDERED,
                        Sales_Model_Document_Offer::STATUS_REJECTED,
                    ]
                ],
                Sales_Model_Document_Offer::STATUS_DRAFT => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Offer::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Offer::STATUS_DISPATCHED,
                        Sales_Model_Document_Offer::STATUS_ORDERED,
                        Sales_Model_Document_Offer::STATUS_REJECTED,
                    ]
                ],
                Sales_Model_Document_Offer::STATUS_DISPATCHED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Offer::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Offer::STATUS_ORDERED,
                        Sales_Model_Document_Offer::STATUS_REJECTED,
                    ]
                ],
                Sales_Model_Document_Offer::STATUS_MANUAL_DISPATCH => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Offer::STATUS_DISPATCHED,
                        Sales_Model_Document_Offer::STATUS_ORDERED,
                        Sales_Model_Document_Offer::STATUS_REJECTED,
                    ]
                ],
                Sales_Model_Document_Offer::STATUS_ORDERED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Offer::STATUS_REJECTED,
                    ],
                ],
                Sales_Model_Document_Offer::STATUS_REJECTED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Offer::STATUS_ORDERED,
                    ]
                ],
            ]
        ],
        self::DOCUMENT_ORDER_STATUS => [
            //_('Order Status')
            self::LABEL              => 'Order Status',
            //_('Possible Order Status')
            self::DESCRIPTION        => 'Possible Order Status',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS            => [
                self::RECORD_MODEL => Sales_Model_Document_Status::class,
                self::OPTION_TRANSITIONS_CONFIG => self::DOCUMENT_ORDER_STATUS_TRANSITIONS,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                // ORDER_STATUS // keyfield: eingegangen (order änderbar, nicht erledigt), angenommen (nicht mehr änderbar (AB ist raus), nicht erledigt), abgeschlossen(nicht mehr änderbar, erledigt) -> feld berechnet sich automatisch! (ggf. lassen wir das abschließen doch zu aber mit confirm)
                self::RECORDS => [
                    [
                        'id' => Sales_Model_Document_Order::STATUS_RECEIVED,
                        //_('Received (unbooked, open)')
                        'value' => 'Received (unbooked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => false,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Order::STATUS_ACCEPTED,
                        //_('Accepted (booked, open)')
                        'value' => 'Accepted (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH,
                        //_('Manual dispatch required (booked, open)')
                        'value' => 'Manual dispatch required (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Order::STATUS_DISPATCHED,
                        //_('Dispatched (booked, open)')
                        'value' => 'Dispatched (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Order::STATUS_DONE,
                        //_('Done (booked, closed)')
                        'value' => 'Done (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ],
                    [
                        'id' => Sales_Model_Document_Abstract::STATUS_REVERSAL,
                        //_('Reversal (booked, closed)')
                        'value' => 'Reversal (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => true,
                        Sales_Model_Document_Status::FLD_IS_USER_TYPE => false,
                        'system' => true
                    ],
                ],
                self::DEFAULT_STR => Sales_Model_Document_Order::STATUS_RECEIVED,
            ],
        ],
        self::DOCUMENT_ORDER_STATUS_TRANSITIONS => [
            //_('Order Status Transitions')
            self::LABEL              => 'Order Status Transitions',
            //_('Possible Order Status Transitions')
            self::DESCRIPTION        => 'Possible Order Status Transitions',
            self::TYPE               => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                '' => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Order::STATUS_RECEIVED,
                        Sales_Model_Document_Order::STATUS_ACCEPTED,
                        Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Order::STATUS_DISPATCHED,
                        Sales_Model_Document_Order::STATUS_DONE,
                        Sales_Model_Document_Abstract::STATUS_REVERSAL,
                    ]
                ],
                Sales_Model_Document_Order::STATUS_RECEIVED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Order::STATUS_ACCEPTED,
                        Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Order::STATUS_DISPATCHED,
                        Sales_Model_Document_Order::STATUS_DONE,
                    ]
                ],
                Sales_Model_Document_Order::STATUS_ACCEPTED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Order::STATUS_DISPATCHED,
                        Sales_Model_Document_Order::STATUS_DONE,
                    ]
                ],
                Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Order::STATUS_DISPATCHED,
                        Sales_Model_Document_Order::STATUS_DONE,
                    ]
                ],
                Sales_Model_Document_Order::STATUS_DISPATCHED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Order::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Order::STATUS_DONE,
                    ]
                ],
                Sales_Model_Document_Order::STATUS_DONE => [
                    self::TRANSITION_TARGET_STATUS => [],
                ],
                Sales_Model_Document_Abstract::STATUS_REVERSAL => [
                    self::TRANSITION_TARGET_STATUS => [],
                ],
            ]
        ],

        self::DOCUMENT_DELIVERY_STATUS => [
            //_('Delivery Status')
            self::LABEL              => 'Delivery Status',
            //_('Possible Delivery Status')
            self::DESCRIPTION        => 'Possible Delivery Status',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS            => [
                self::RECORD_MODEL => Sales_Model_Document_Status::class,
                self::OPTION_TRANSITIONS_CONFIG => self::DOCUMENT_DELIVERY_STATUS_TRANSITIONS,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                // - DELIVERY_STATUS // keyfield erstellt(Ungebucht, offen), geliefert(gebucht, abgeschlossen)
                //    NOTE: man könnte einen ungebuchten Status als Packliste einführen z.B. Packliste(ungebucht, offen)
                self::RECORDS => [
                    [
                        'id' => Sales_Model_Document_Delivery::STATUS_CREATED,
                        //_('Created (unbooked, open)')
                        'value' => 'Created (unbooked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => false,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Delivery::STATUS_MANUAL_DISPATCH,
                        //_('Manual dispatch required (booked, open)')
                        'value' => 'Manual dispatch required (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Delivery::STATUS_DISPATCHED,
                        //_('Dispatched (booked, open)')
                        'value' => 'Dispatched (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Delivery::STATUS_DELIVERED,
                        //_('Done (booked, closed)')
                        'value' => 'Done (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Abstract::STATUS_REVERSAL,
                        //_('Reversal (booked, closed)')
                        'value' => 'Reversal (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => true,
                        Sales_Model_Document_Status::FLD_IS_USER_TYPE => false,
                        'system' => true
                    ],
                ],
                self::DEFAULT_STR => Sales_Model_Document_Delivery::STATUS_CREATED,
            ],
        ],
        self::EDOCUMENT    => [
            //_('eDocument')
            self::LABEL                 => 'eDocument',
            //_('eDocument')
            self::DESCRIPTION           => 'eDocument',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
            self::CONTENT               => [
                self::EDOCUMENT_SVC_BASE_URL          => [
                    self::LABEL                 => 'EDocument Service Base URL',
                    //_('EDocument Service Base')
                    self::DESCRIPTION           => 'EDocument Service Base URL',
                    //_('EDocument Service Base')
                    self::TYPE                  => self::TYPE_STRING,
                    self::DEFAULT_STR           => '',
                ],
                self::VALIDATION_SVC => [
                    self::LABEL                 => 'Validation Service URL',
                    //_('Validation Service URL')
                    self::DESCRIPTION           => 'Validation Service URL',
                    //_('Validation Service URL')
                    self::TYPE                  => self::TYPE_STRING,
                    self::DEFAULT_STR           => '',
                ],
                self::VIEW_SVC => [
                    self::LABEL                 => 'View Service URL',
                    //_('Validation Service URL')
                    self::DESCRIPTION           => 'View Service URL',
                    //_('Validation Service URL')
                    self::TYPE                  => self::TYPE_STRING,
                    self::DEFAULT_STR           => '',
                ],
            ],
            self::DEFAULT_STR           => [],
        ],
        self::DOCUMENT_DELIVERY_STATUS_TRANSITIONS => [
            //_('Delivery Status Transitions')
            self::LABEL              => 'Delivery Status Transitions',
            //_('Possible Delivery Status Transitions')
            self::DESCRIPTION        => 'Possible Delivery Status Transitions',
            self::TYPE               => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                '' => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Delivery::STATUS_CREATED,
                        Sales_Model_Document_Delivery::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Delivery::STATUS_DISPATCHED,
                        Sales_Model_Document_Delivery::STATUS_DELIVERED,
                    ]
                ],
                Sales_Model_Document_Delivery::STATUS_CREATED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Delivery::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Delivery::STATUS_DISPATCHED,
                        Sales_Model_Document_Delivery::STATUS_DELIVERED,
                    ]
                ],
                Sales_Model_Document_Delivery::STATUS_MANUAL_DISPATCH => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Delivery::STATUS_DISPATCHED,
                        Sales_Model_Document_Delivery::STATUS_DELIVERED,
                    ]
                ],
                Sales_Model_Document_Delivery::STATUS_DISPATCHED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Delivery::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Delivery::STATUS_DELIVERED,
                    ]
                ],
                Sales_Model_Document_Delivery::STATUS_DELIVERED => [
                    self::TRANSITION_TARGET_STATUS => [],
                ],
                Sales_Model_Document_Abstract::STATUS_REVERSAL => [
                    self::TRANSITION_TARGET_STATUS => [],
                ],
            ]
        ],

        self::DOCUMENT_INVOICE_STATUS => [
            //_('Invoice Status')
            self::LABEL              => 'Invoice Status',
            //_('Possible Invoice Status')
            self::DESCRIPTION        => 'Possible Invoice Status',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS            => [
                self::RECORD_MODEL => Sales_Model_Document_Status::class,
                self::OPTION_TRANSITIONS_CONFIG => self::DOCUMENT_INVOICE_STATUS_TRANSITIONS,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                // INVOICE_STATUS proforma(Ungebucht, offen), gebucht(gebucht, offen),  Verschickt(gebucht, offen), Bezahlt(gebucht, geschlossen)
                self::RECORDS => [
                    [
                        'id' => Sales_Model_Document_Invoice::STATUS_PROFORMA,
                        //_('Proforma (unbooked, open)')
                        'value' => 'Proforma (unbooked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => false,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Invoice::STATUS_BOOKED,
                        //_('Booked (booked, open)')
                        'value' => 'Booked (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH,
                        //_('Manual dispatch required (booked, open)')
                        'value' => 'Manual dispatch required (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        //'id' => Sales_Model_Document_Invoice::STATUS_SHIPPED, => SHIPPED
                        'id' => Sales_Model_Document_Invoice::STATUS_DISPATCHED,
                        //_('Dispatched (booked, open)')
                        'value' => 'Dispatched (booked, open)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => false,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Invoice::STATUS_PAID,
                        //_('Paid (booked, closed)')
                        'value' => 'Paid (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => false,
                        'system' => true
                    ], [
                        'id' => Sales_Model_Document_Abstract::STATUS_REVERSAL,
                        //_('Reversal (booked, closed)')
                        'value' => 'Reversal (booked, closed)',
                        'icon' => null,
                        Sales_Model_Document_Status::FLD_BOOKED => true,
                        Sales_Model_Document_Status::FLD_CLOSED => true,
                        Sales_Model_Document_Status::FLD_REVERSAL => true,
                        Sales_Model_Document_Status::FLD_IS_USER_TYPE => false,
                        'system' => true
                    ],
                ],
                self::DEFAULT_STR => Sales_Model_Document_Invoice::STATUS_PROFORMA,
            ],
        ],
        self::DOCUMENT_INVOICE_STATUS_TRANSITIONS => [
            //_('Invoice Status Transitions')
            self::LABEL              => 'Invoice Status Transitions',
            //_('Possible Invoice Status Transitions')
            self::DESCRIPTION        => 'Possible Invoice Status Transitions',
            self::TYPE               => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                '' => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Invoice::STATUS_PROFORMA,
                        Sales_Model_Document_Invoice::STATUS_BOOKED,
                        Sales_Model_Document_Invoice::STATUS_DISPATCHED,
                        Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Abstract::STATUS_REVERSAL,
                    ]
                ],
                Sales_Model_Document_Invoice::STATUS_PROFORMA => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Invoice::STATUS_BOOKED,
                        Sales_Model_Document_Invoice::STATUS_DISPATCHED,
                        Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH,
                    ]
                ],
                Sales_Model_Document_Invoice::STATUS_BOOKED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Invoice::STATUS_DISPATCHED,
                        Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH,
                    ]
                ],
                Sales_Model_Document_Invoice::STATUS_DISPATCHED => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH,
                        Sales_Model_Document_Invoice::STATUS_PAID,
                    ],
                ],
                Sales_Model_Document_Invoice::STATUS_MANUAL_DISPATCH => [
                    self::TRANSITION_TARGET_STATUS => [
                        Sales_Model_Document_Invoice::STATUS_DISPATCHED,
                        Sales_Model_Document_Invoice::STATUS_PAID,
                    ],
                ],
                Sales_Model_Document_Invoice::STATUS_PAID => [
                    self::TRANSITION_TARGET_STATUS => [],
                ],
                Sales_Model_Document_Abstract::STATUS_REVERSAL => [
                    self::TRANSITION_TARGET_STATUS => [],
                ],
            ]
        ],

        self::IGNORE_BILLABLES_BEFORE => array(
            //_('Ignore Billables Before Date')
            'label'                 => 'Ignore Billables Before Date',
            //_('Sets the date billables will be ignored before.')
            'description'           => 'Sets the date billables will be ignored before.',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => '2000-01-01 22:00:00'
        ),
        self::CONTRACT_NUMBER_GENERATION => array(
                                   //_('Contract Number Creation')
            'label'                 => 'Contract Number Creation',
                                   //_('Should the contract number be set manually or be auto-created?')
            'description'           => 'Should the contract number be set manually or be auto-created?',
            'type'                  => 'string',
                                    // _('automatically')
                                    // _('manually')
            'options'               => array(array('auto', 'automatically'), array('manual', 'manually')),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'auto'
        ),
        self::CONTRACT_NUMBER_VALIDATION => array(
                                   //_('Contract Number Validation')
            'label'                 => 'Contract Number Validation',
                                   //_('The Number can be validated as text or number.')
            'description'           => 'The Number can be validated as text or number.',
            'type'                  => 'string',
                                    // _('Number')
                                    // _('Text')
            'options'               => array(array('integer', 'Number'), array('string', 'Text')),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'string'
        ),
        self::LANGUAGES_AVAILABLE => [
            self::LABEL                 => 'Languages Available', //_('Languages Available')
            self::DESCRIPTION           => 'List of languages in which multilingual texts are available.', //_('List of languages in which multilingual texts are available.')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            'localeTranslationList'     => 'Language',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 'de', 'value' => 'German'],
                    ['id' => 'en', 'value' => 'English'],
                ],
                self::DEFAULT_STR           => 'en',
            ],
        ],
        self::INVOICE_EDOCUMENT_NAME_TMPL => [
            //_('Invoice eDocument Attachment Name Template')
            self::LABEL                 => 'Invoice eDocument Attachment Name Template',
            self::DESCRIPTION           => 'Invoice eDocument Attachment Name Template',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '{{ sanitizeFileName(document.document_number|replace({"/": "-"})) }}-xrechnung.xml',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::INVOICE_EDOCUMENT_RENAME_TMPL => [
            //_('Invoice eDocument Attachment Rename Template')
            self::LABEL                 => 'Invoice eDocument Attachment Rename Template',
            self::DESCRIPTION           => 'Invoice eDocument Attachment Rename Template',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::INVOICE_PAPERSLIP_NAME_TMPL => [
            //_('Invoice Paperslip Attachment Name Template')
            self::LABEL                 => 'Invoice EDocument Paperslip Name Template',
            self::DESCRIPTION           => 'Invoice EDocument Paperslip Name Template',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '{{ sanitizeFileName( (document.document_date|default(date)) ~ "_" '
                . '~ (document.isBooked() ? (document.document_number|replace({"/": "-"})) : "Proforma-" '
                . '~ ((document.has("document_proforma_number") ? document.document_proforma_number : document.document_number )|replace({"/": "-"}))) '
                . '~ (document.document_title ? ("-" ~ document.document_title) : "") ) }}.pdf',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::INVOICE_PAPERSLIP_RENAME_TMPL => [
            //_('Invoice Paperslip Attachment Rename Template')
            self::LABEL                 => 'Invoice Paperslip Attachment Rename Template',
            self::DESCRIPTION           => 'Invoice Paperslip Attachment Rename Template',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => '',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
        self::INVOICE_TYPE => array(
                                   //_('Invoice Type')
            'label'                 => 'Invoice Type',
                                   //_('Possible Invoice Types.')
            'description'           => 'Possible Invoice Types.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_InvoiceType'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'INVOICE',  'value' => 'Invoice',  'system' => true), // _('Invoice')
                    array('id' => 'REVERSAL', 'value' => 'Reversal', 'system' => true), // _('Reversal')
                    array('id' => 'CREDIT',   'value' => 'Credit',   'system' => true)  // _('Credit')
                ),
                'default' => 'INVOICE'
            )
        ),
        self::PRICE_TYPE => [
            self::LABEL                 => 'Price Type', //_('Price Type')
            self::DESCRIPTION           => 'Price is net or gross. Calculation is based on net or gross.', //_('Price is net or gross. Calculation is based on net or gross.')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => self::PRICE_TYPE_NET,   'value' => 'Net',   'system' => true], // _('Net')
                    ['id' => self::PRICE_TYPE_GROSS, 'value' => 'Gross', 'system' => true], // _('Gross')
                ],
                self::DEFAULT_STR => self::PRICE_TYPE_NET,
            ],
        ],
        self::INVOICE_DISCOUNT_TYPE => [
            self::LABEL                 => 'Invoice Discount Type', //_('Invoice Discount Type')
            self::DESCRIPTION           => 'Invoice Discount Type', //_('Invoice Discount Type')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            /*self::OPTIONS               => [
                self::RECORD_MODEL          => ....
            ],*/
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => self::INVOICE_DISCOUNT_PERCENTAGE, 'value' => 'Percentage', 'system' => true], // _('Percentage')
                    ['id' => self::INVOICE_DISCOUNT_SUM, 'value' => 'Sum', 'system' => true], // _('Sum')
                ],
            ],
        ],
        self::DOCUMENT_POSITION_TYPE => [
            self::LABEL                 => 'Document Position Type', //_('Document Position Type')
            self::DESCRIPTION           => 'Document Position Type', //_('Document Position Type')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            /*self::OPTIONS               => [
                self::RECORD_MODEL          => ....
            ],*/
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => Sales_Model_DocumentPosition_Abstract::POS_TYPE_PRODUCT, 'value' => 'Product', 'system' => true], // _('Product')
                    ['id' => Sales_Model_DocumentPosition_Abstract::POS_TYPE_TEXT, 'value' => 'Text', 'system' => true], // _('Text')
                    ['id' => Sales_Model_DocumentPosition_Abstract::POS_TYPE_HEADING, 'value' => 'Heading', 'system' => true], // _('Heading')
                    ['id' => Sales_Model_DocumentPosition_Abstract::POS_TYPE_PAGEBREAK, 'value' => 'Page Break', 'system' => true], // _('Page Break')
                    ['id' => Sales_Model_DocumentPosition_Abstract::POS_TYPE_ALTERNATIVE, 'value' => 'Alternative', 'system' => true], // _('Alternative')
                    ['id' => Sales_Model_DocumentPosition_Abstract::POS_TYPE_OPTIONAL, 'value' => 'Optional', 'system' => true], // _('Optional')
                ],
                self::DEFAULT_STR           => Sales_Model_DocumentPosition_Abstract::POS_TYPE_PRODUCT,
            ],
        ],
        self::DOCUMENT_CATEGORY_DEFAULT => [
            //_('Default Category')
            self::LABEL                 => 'Default Category',
            //_('Default Category')
            self::DESCRIPTION           => 'Default Category',
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => Sales_Config::APP_NAME,
                self::MODEL_NAME            => Sales_Model_Document_Category::MODEL_NAME_PART,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
        ],

        // DEPRECATED!!!! DO NOT USE only for migration purposes required
        self::DOCUMENT_CATEGORY => [
            self::LABEL                 => 'Document Category',
            self::DESCRIPTION           => 'Document Category',
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => [
                self::RECORD_MODEL          => Tinebase_Config_KeyFieldRecord::class,
            ],
            self::SETBYADMINMODULE      => false,
            self::CLIENTREGISTRYINCLUDE => false,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 'STANDARD', 'value' => 'Standard', 'system' => false],
                ],
                self::DEFAULT_STR           => 'STANDARD',
            ],
        ],
        self::PRODUCT_CATEGORY => array(
                                   //_('Product Category')
            'label'                 => 'Product Category',
                                   //_('Possible Product Categories.')
            'description'           => 'Possible Product Categories.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_ProductCategory'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'DEFAULT', 'value' => 'Default', 'system' => true) // _('Default')
                ),
                'default' => 'DEFAULT'
            )
        ),
        self::PRODUCT_UNFOLDTYPE => [
            self::LABEL                 => 'Product Unfold Type', //_('Product Unfold Type')
            self::DESCRIPTION           => 'Product Unfold Type', //_('Product Unfold Type')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            /*self::OPTIONS               => [
                self::RECORD_MODEL          => ....
            ],*/
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => Sales_Model_Product::UNFOLD_TYPE_BUNDLE, 'value' => 'Bundle', 'system' => true], // _('Shared')
                    ['id' => Sales_Model_Product::UNFOLD_TYPE_SET, 'value' => 'Set', 'system' => true], // _('Own')
                ],
            ],
        ],
        self::PRODUCT_UNIT => [
            self::LABEL                 => 'Product Unit', //_('Product Unit')
            self::DESCRIPTION           => 'Product Unit', //_('Product Unit')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            /*self::OPTIONS               => [
                self::RECORD_MODEL          => ....
            ],*/
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => Sales_Model_Product::UNIT_PIECE, 'value' => 'Piece', 'system' => true], // _('Piece')
                ],
            ],
        ],
        self::VARIABLE_POSITION_FLAG => [
            self::LABEL                 => 'Sub-Product Variable Position Flag', //_('Sub-Product Variable Position Flag')
            self::DESCRIPTION           => 'The accounting method for sub-products varies, allowing identical sub-products to be recorded either individually or grouped together in a single position on a document.', //_('The accounting method for sub-products varies, allowing identical sub-products to be recorded either individually or grouped together in a single position on a document.')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            /*self::OPTIONS               => [
                self::RECORD_MODEL          => ....
            ],*/
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 'NONE', 'value' => 'None', 'system' => true], // _('None')
                    ['id' => 'SHARED', 'value' => 'Shared', 'system' => true], // _('Shared')
                    ['id' => 'OWN', 'value' => 'Own', 'system' => true], // _('Own')
                ],
                self::DEFAULT_STR => 'NONE',
            ],
        ],
        self::PAYMENT_REMINDER_LEVEL => [
            self::LABEL                 => 'Payment Reminder Levels', //_('Payment Reminder Levels')
            self::DESCRIPTION           => 'Payment Reminder Levels',
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::DEFAULT_STR           => [
                self::RECORDS               => [
                    ['id' => 'FIRST', 'value' => 'First Reminder', 'system' => true], // _('First Reminder')
                    ['id' => 'SECOND', 'value' => 'Second Reminder', 'system' => true], // _('Second Reminder')
                    ['id' => 'THIRD', 'value' => 'Third Reminder', 'system' => true], // _('Third Reminder')
                ],
                self::DEFAULT_STR => 'FIRST',
            ],
        ],
        self::PRODUCT_NUMBER_GENERATION => array(
                                   //_('Product Number Creation')
            'label'                 => 'Product Number Creation',
                                   //_('Should the product number be set manually or be auto-created?')
            'description'           => 'Should the product number be set manually or be auto-created?',
            'type'                  => 'string',
            'options'               => [
                [self::PRODUCT_NUMBER_GENERATION_AUTO, 'automatically'], // _('automatically')
                [self::PRODUCT_NUMBER_GENERATION_MANUAL, 'manually'] // _('manually')
            ],
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => self::PRODUCT_NUMBER_GENERATION_AUTO
        ),
        self::PRODUCT_NUMBER_VALIDATION => array(
                                   //_('Product Number Validation')
            'label'                 => 'Product Number Validation',
                                   //_('The Number can be validated as text or number.')
            'description'           => 'The Number can be validated as text or number.',
            'type'                  => 'string',
                                    // _('Number')
                                    // _('Text')
            'options'               => array(array('integer', 'Number'), array('string', 'Text')),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'string'
        ),
        self::PRODUCT_NUMBER_PREFIX => array(
                                   //_('Product Number Prefix')
            'label'                 => 'Product Number Prefix',
                                   //_('The prefix of the product number.')
            'description'           => 'The prefix of the product number',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'P-'
        ),
        self::PRODUCT_NUMBER_ZEROFILL => array(
                                   //_('Product Number Zero Fill')
            'label'                 => 'Product Number Zero Fill',
                                   //_('Fill the number with leading zero's if needed.')
            'description'           => 'Fill the number with leading zero\'s if needed.',
            'type'                  => 'number',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => '5'
        ),
        self::DEBITOR_DEFAULT_PAYMENT_MEANS => [
            self::LABEL                 => 'Debitor Default Payment Means', //_('Debitor Default Payment Means')
            self::DESCRIPTION           => 'Debitor Default Payment Means', //_('Debitor Default Payment Means')
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
        ],
        self::PAYMENT_METHODS => array(
                                   //_('Payment Method')
            'label'                 => 'Payment Method',
                                   //_('Possible Payment Methods.')
            'description'           => 'Possible Payment Methods.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_PaymentMethod'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'BANK TRANSFER', 'value' => 'Bank transfer', 'system' => true), // _('Bank transfer')
                    array('id' => 'DIRECT DEBIT',  'value' => 'Direct debit',  'system' => true),  // _('Direct debit')
                    array('id' => 'CANCELLATION',  'value' => 'Cancellation',  'system' => true),  // _('Cancellation')
                    array('id' => 'CREDIT',  'value' => 'Credit',  'system' => true),  // _('Credit')
                    array('id' => 'CREDIT CARD',  'value' => 'Credit card',  'system' => true),  // _('Credit card')
                    array('id' => 'EC CARD',  'value' => 'EC card',  'system' => true),  // _('EC card')
                    array('id' => 'PAYPAL',  'value' => 'Paypal',  'system' => true),  // _('Paypal')
                    array('id' => 'ASSETS', 'value' => 'Assets', 'system' => true), // _('Assets')
                ),
                'default' => 'BANK TRANSFER'
            )
        ),
        self::INVOICE_CLEARED => array(
                                   //_('Invoice Cleared')
            'label'                 => 'Invoice Cleared',
                                   //_('Possible Invoice Cleared States.')
            'description'           => 'Possible Invoice Cleared States.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_InvoiceCleared'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'TO_CLEAR', 'value' => 'to clear', 'system' => true), // _('to clear')
                    array('id' => 'CLEARED',  'value' => 'cleared',  'system' => true), // _('cleared')
                ),
                'default' => 'TO_CLEAR'
            )
        ),
        self::DATEV_SENDER_EMAIL_PURCHASE_INVOICE                 => [
            self::LABEL                     => 'Datev sender email purchase invoice', //_('Datev sender email purchase invoice')
            self::DESCRIPTION               => 'Datev sender email for purchase invoice' , //_('Datev sender email purchase invoice')
            self::TYPE                      => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => '',
        ],
        self::DATEV_SENDER_EMAIL_INVOICE                 => [
            self::LABEL                     => 'Datev sender email invoice', //_('Datev sender email for invoice')
            self::DESCRIPTION               => 'Datev sender email for invoice', //_('Datev sender email for invoice')
            self::TYPE                      => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => '',
        ],
        self::DATEV_RECIPIENT_EMAILS_PURCHASE_INVOICE    => [
            self::LABEL                     => 'Datev recipient emails purchase invoice', //_('Datev recipient emails purchase invoice')
            self::DESCRIPTION               => 'Datev recipient emails for purchase invoice', //_('Datev recipient emails for purchase invoice')
            self::TYPE                      => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => [],
        ],
        self::DATEV_RECIPIENT_EMAILS_INVOICE    => [
            self::LABEL                     => 'Datev recipient emails invoice', //_('Datev recipient emails')
            self::DESCRIPTION               => 'Datev recipient emails for invoice', //_('Datev recipient emails for invoice')
            self::TYPE                      => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => [],
        ],
        self::PAYMENT_MEANS_ID_TMPL         => [
            self::LABEL                     => 'Payment Means Identifier Template', //_('Payment Means Identifier Template')
            self::DESCRIPTION               => 'Payment Means Identifier Template',
            self::TYPE                      => self::TYPE_STRING,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => '{{ invoice.document_number }} {{ invoice.debitor_id.number }}',
        ],
        self::VAT_PROCEDURES => [
            //_('VAT Procedures')
            self::LABEL              => 'VAT Procedures',
            //_('Possible VAT Procedures')
            self::DESCRIPTION        => 'Possible VAT Procedures',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS            => [self::RECORD_MODEL => Sales_Model_EDocument_VATProcedure::class],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    [
                        'id' => self::VAT_PROCEDURE_STANDARD,
                        //_('Standard rate')
                        'value' => 'Standard rate',
                        'icon' => null,
                        'system' => true,
                        Sales_Model_EDocument_VATProcedure::FLD_UNTDID_5305 => 'S',
                    ], [
                        'id' => self::VAT_PROCEDURE_OUTSIDE_TAX_SCOPE,
                        //_('Services outside scope of tax')
                        'value' => 'Services outside scope of tax',
                        'icon' => null,
                        'system' => true,
                        Sales_Model_EDocument_VATProcedure::FLD_UNTDID_5305 => 'O',
                        Sales_Model_EDocument_VATProcedure::FLD_VATEX => ['vatex-eu-o'],
                    ], [
                        'id' => self::VAT_PROCEDURE_REVERSE_CHARGE,
                        //_('Reverse charge')
                        'value' => 'Reverse charge',
                        'icon' => null,
                        'system' => true,
                        Sales_Model_EDocument_VATProcedure::FLD_UNTDID_5305 => 'AE',
                        Sales_Model_EDocument_VATProcedure::FLD_VATEX => ['vatex-eu-ae'],
                    ], [
                        'id' => self::VAT_PROCEDURE_FREE_EXPORT_ITEM,
                        //_('Free export item, tax not charged')
                        'value' => 'Free export item, tax not charged',
                        'icon' => null,
                        'system' => true,
                        Sales_Model_EDocument_VATProcedure::FLD_UNTDID_5305 => 'G',
                        Sales_Model_EDocument_VATProcedure::FLD_VATEX => ['vatex-eu-g'],
                    ], [
                        'id' => self::VAT_PROCEDURE_ZERO_RATED_GOODS,
                        //_('Zero rated goods')
                        'value' => 'Zero rated goods',
                        'icon' => null,
                        'system' => true,
                        Sales_Model_EDocument_VATProcedure::FLD_UNTDID_5305 => 'Z',
                    ]
                ],
                self::DEFAULT_STR => self::VAT_PROCEDURE_STANDARD,
            ],
        ],
        self::REVERSE_CHANGE_TEMPLATE => [
            //_('Reverse Charge Template')
            self::LABEL                 => 'Reverse Charge Template',
            //_('Enabled Features in Sales Application.')
            self::DESCRIPTION           => 'Reverse Charge Templates in multiple languages.',
            self::TYPE                  => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE => false,
            self::SETBYADMINMODULE      => false,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => [
                'de' => 'Steuerschuldnerschaft des Leistungsempfangers gemaß §13b UStG. Reverse-Charge-Verfahren.
USt.-ID des Leistungsempfangers: { vatid }',
                'en' => 'Tax liability of the recipient of the service according to §13b UStG. Reverse charge procedure.
VAT ID of the service recipient: { vatid }',
            ],
        ],
        self::DEFAULT_DIVISION => [
            //_('Default Division')
            self::LABEL                 => 'Default Division',
            //_('Default Division')
            self::DESCRIPTION           => 'Default Division',
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => Sales_Config::APP_NAME,
                self::MODEL_NAME            => Sales_Model_Division::MODEL_NAME_PART,
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
        ],

        self::DEFAULT_PAYMENT_TERMS => [
            //_('Default Payment Terms')
            self::LABEL                 => 'Default Payment Terms',
            //_('Default payment terms in days')
            self::DESCRIPTION           => 'Default payment terms in days',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 10, // 0 means immediately
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
        ],

        /**
         * enabled Sales features
         * 
         * to overwrite the defaults, you can add a Sales/config.inc.php like this:
         * 
         * <?php
            return array (
                // this switches some modules off
                'features' => array(
                    'invoicesModule'             => false,
                    'offersModule'               => false,
                    'orderConfirmationsModule'   => false,
                )
            );
         */
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Sales Application.')
            self::DESCRIPTION           => 'Enabled Features in Sales Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [

                self::FEATURE_INVOICES_MODULE           => [
                    self::LABEL                             => 'Invoices Module',
                    //_('Invoices Module')
                    self::DESCRIPTION                       => 'Invoices Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_OFFERS_MODULE             => [
                    self::LABEL                             => 'Offers Module',
                    //_('Offers Module')
                    self::DESCRIPTION                       => 'Offers Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                // TODO remove that after migration (maybe in 2023.11)
                self::FEATURE_LEGACY_OFFERS             => [
                    self::LABEL                             => 'Legacy Offers',
                    //_('Legacy Offers')
                    self::DESCRIPTION                       => 'Legacy (non-document) Offers',
                    //_('Legacy (non-document) Offers')
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => false,
                ],
                self::FEATURE_ORDERCONFIRMATIONS_MODULE => [
                    self::LABEL                             => 'Order Confirmations Module',
                    //_('Order Confirmations Module')
                    self::DESCRIPTION                       => 'Order Confirmations Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_SUPPLIERS_MODULE          => [
                    self::LABEL                             => 'Suppliers Module',
                    //_('Suppliers Module')
                    self::DESCRIPTION                       => 'Suppliers Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_PURCHASE_INVOICES_MODULE  => [
                    self::LABEL                             => 'Purchase Invoice Module',
                    //_('Purchase Invoice Module')
                    self::DESCRIPTION                       => 'Purchase Invoice Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::CUSTOMER_CONTACT_PERSON_FILTER => [
            //_('Contact person filter')
            self::LABEL                 => 'Contact person filter',
            //_('Configure filter for customer contacts via container path filter')
            self::DESCRIPTION           => 'Contact person filter',
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR               => '/all',
        ],
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Sales';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
