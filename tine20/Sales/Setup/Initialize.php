<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for Tinebase initialization
 * 
 * @package     Sales
 */
class Sales_Setup_Initialize extends Setup_Initialize
{
    /**
    * init favorites
    */
    protected function _initializeFavorites() {
        // Products
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
            'model'             => 'Sales_Model_ProductFilter',
        );
        
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "My Products", // _('My Products')
                'description'       => "Products created by me", // _('Products created by me')
                'filters'           => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            ))
        ));
        
        // Contracts
        $commonValues['model'] = 'Sales_Model_ContractFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "My Contracts", // _('My Contracts')
                'description'       => "Contracts created by me", // _('Contracts created by myself')
                'filters'           => array(
                    array(
                        'field'     => 'created_by',
                        'operator'  => 'equals',
                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
                    )
                ),
            ))
        ));
        
        // Customers
        $commonValues['model'] = 'Sales_Model_CustomerFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Customers", // _('All Customers')
                'description' => "All customer records", // _('All customer records')
                'filters'     => array(
                ),
            ))
        ));
        
        // Offers
        $commonValues['model'] = 'Sales_Model_OfferFilter';
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Offers", // _('All Offers')
                'description' => "All offer records", // _('All offer records')
                'filters'     => array(
                ),
            ))
        ));
        
        static::createDefaultFavoritesForSub20();

        static::createDefaultFavoritesForSub22();

        static::createDefaultFavoritesForSub24();

        static::createDefaultFavoritesForContracts();

        static::createDefaultFavoritesDocPurchaseInvoice();
    }

    public static function createDefaultFavoritesDocPurchaseInvoice(): void
    {
        $commonValues = [
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME)->getId(),
            'model'             => Sales_Model_Document_PurchaseInvoice::class,
        ];

        $pfe = Tinebase_PersistentFilter::getInstance();

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'All Purchase Invoices', // _('All Purchase Invoices')
                'description'       => 'All Purchase Invoices', // _('All Purchase Invoices')
                'filters'           => [],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Unpaid Purchase Invoices', // _('Unpaid Purchase Invoices')
                'description'       => 'All Purchase Invoices that have not yet been paid', // _('All Purchase Invoices that have not yet been paid')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OPERATOR_NOT, TMFA::VALUE => Sales_Model_Document_PurchaseInvoice::STATUS_PAID],
                ],
            ])
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, [
                'name'              => 'Unapproved Purchase Invoices', // _('Unapproved Purchase Invoices')
                'description'       => 'All Purchase Invoices that have not yet been approved', // _('All Purchase Invoices that have not yet been approved')
                'filters'           => [
                    [TMFA::FIELD => Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED],
                ],
            ])
        ));
    }

    protected function _initializeEDocumentEAS(): void
    {
        self::initializeEDocumentEAS();
        self::initializeEDocumentPaymentMeansCode();
        self::initializeEDocumentVATEX();
    }

    public static function initializeEDocumentEAS(): void
    {
        $easData = json_decode(file_get_contents(__DIR__ . '/EAS_5.json'), true);
        foreach ($easData['daten'] as $eas) {
            Sales_Controller_EDocument_EAS::getInstance()->create(new Sales_Model_EDocument_EAS([
                Sales_Model_EDocument_EAS::FLD_NAME => $eas[1],
                Sales_Model_EDocument_EAS::FLD_CODE => $eas[0],
                Sales_Model_EDocument_EAS::FLD_REMARK => $eas[2],
            ]));
        }
    }

    public static function initializeEDocumentVATEX(): void
    {
        static::_importVatExData(json_decode(file_get_contents(__DIR__ . '/VATEX_1.json'), true), 'en');
        static::_importVatExData(json_decode(file_get_contents(__DIR__ . '/VATEX_1_DE.json'), true), 'de');
    }

    protected static function _importVatExData(array $vatexData, string $lang): void
    {
        $vatexCtrl = Sales_Controller_EDocument_VATEX::getInstance();
        foreach ($vatexData['daten'] as $row) {
            if (null === ($vatex = $vatexCtrl->getByCode($row[0]))) {
                $vatexCtrl->create(new Sales_Model_EDocument_VATEX([
                    Sales_Model_EDocument_VATEX::FLD_CODE => $row[0],
                    Sales_Model_EDocument_VATEX::FLD_NAME => [[
                        Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                        Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[1],
                    ]],
                    Sales_Model_EDocument_VATEX::FLD_DESCRIPTION => [[
                        Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                        Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[2],
                    ]],
                    Sales_Model_EDocument_VATEX::FLD_REMARK => array_merge([], $row[3] ? [[
                        Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                        Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[3],
                    ]] : []),
                ]));
            } else {
                Tinebase_Record_Expander::expandRecord($vatex);
                foreach ([
                             Sales_Model_EDocument_VATEX::FLD_NAME => 1,
                             Sales_Model_EDocument_VATEX::FLD_DESCRIPTION => 2,
                             Sales_Model_EDocument_VATEX::FLD_REMARK => 3,
                         ] as $property => $offset) {
                    if (null === ($text = $vatex->{$property}->find(Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE, $lang))) {
                        $vatex->{$property}->addRecord(new Sales_Model_EDocument_VATEXLocalization([
                            Sales_Model_EDocument_VATEXLocalization::FLD_LANGUAGE => $lang,
                            Sales_Model_EDocument_VATEXLocalization::FLD_TEXT => $row[$offset],
                        ], true));
                    } else {
                        $text->{Sales_Model_EDocument_VATEXLocalization::FLD_TEXT} = $row[$offset];
                    }
                }
                $vatexCtrl->update($vatex);
            }
        }
    }

    public static function initializeEDocumentPaymentMeansCode(): void
    {
        $paymentMeansData = json_decode(file_get_contents(__DIR__ . '/UNTDID_4461_3.json'), true);
        foreach ($paymentMeansData['daten'] as $row) {
            $paymentMeans = new Sales_Model_EDocument_PaymentMeansCode([
                Sales_Model_EDocument_PaymentMeansCode::FLD_CODE => $row[0],
                Sales_Model_EDocument_PaymentMeansCode::FLD_NAME => $row[1],
                Sales_Model_EDocument_PaymentMeansCode::FLD_DESCRIPTION => $row[2],
            ]);
            switch ($row[0]) {
                case '58': // SEPA credit transfer
                    $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS} = Sales_Model_EDocument_PMC_PayeeFinancialAccount::class;
                    break;
                case '59': // SEPA direct debit
                    $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS} = Sales_Model_EDocument_PMC_PaymentMandate::class;
                    break;
                default:
                    $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS} = Sales_Model_EDocument_PMC_NoConfig::class;
                    break;
            }
            $paymentMeans = Sales_Controller_EDocument_PaymentMeansCode::getInstance()->create($paymentMeans);
            if ('58' === $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_CODE}) { // SEPA credit transfer
                Sales_Config::getInstance()->{Sales_Config::DEBITOR_DEFAULT_PAYMENT_MEANS} = $paymentMeans->getId();
            }
        }
    }

    public static function initializeBoilerPlates()
    {
        $importer = Tinebase_Import_Csv_Generic::createFromDefinition(
            Tinebase_ImportExportDefinition::getInstance()->getFromFile(dirname(__DIR__) . '/Import/definitions/sales_import_boilerplate_csv.xml', Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME))
        );
        $importer->importFile(__DIR__ . '/files/boilerplate.csv');
    }

    protected function _initializeBoilerPlates()
    {
        static::initializeBoilerPlates();
    }

    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        $scheduler = Tinebase_Core::getScheduler();
        Sales_Scheduler_Task::addUpdateProductLifespanTask($scheduler);
        Sales_Scheduler_Task::addCreateAutoInvoicesDailyTask($scheduler);
        Sales_Scheduler_Task::addCreateAutoInvoicesMonthlyTask($scheduler);
        Sales_Scheduler_Task::addEMailDispatchResponseMinutelyTask($scheduler);
    }

    protected function _initializeTbSystemCFEvaluationDimension(): void
    {
        self::createTbSystemCFEvaluationDimension();
    }

    public static function createTbSystemCFEvaluationDimension(): void
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $appId = Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId();

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => 'divisions',
            'application_id' => $appId,
            'model' => Tinebase_Model_EvaluationDimensionItem::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_FIELD => [
                    TMCC::LABEL             => 'Limit to Sales Divisions', // _('Limit to Sales Divisions')
                    TMCC::TYPE              => TMCC::TYPE_RECORDS,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => Sales_Config::APP_NAME,
                        TMCC::MODEL_NAME        => Sales_Model_DivisionEvalDimensionItem::MODEL_NAME_PART,
                        TMCC::REF_ID_FIELD      => Sales_Model_DivisionEvalDimensionItem::FLD_EVAL_DIMENSION_ITEM_ID,
                        TMCC::DEPENDENT_RECORDS => true,
                    ],
                    TMCC::UI_CONFIG         => [
                        'searchComboConfig'     => [
                            'useEditPlugin'         => false,
                        ],
                    ]
                ],
            ]
        ], true));

        Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
            'name' => 'divisions',
            'application_id' => $appId,
            'model' => Tinebase_Model_EvaluationDimension::class,
            'is_system' => true,
            'definition' => [
                Tinebase_Model_CustomField_Config::DEF_HOOK => [
                    [Sales_Controller_Division::class, 'evalDimModelConfigHook'],
                ],
            ],
        ], true));
    }

    protected function _initializeCostCenterCostBearer()
    {
        self::initializeCostCenterCostBearer();
    }

    public static function initializeCostCenterCostBearer()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            Sales_Model_Invoice::class,
            Sales_Model_Product::class,
            Sales_Model_Contract::class,
            Sales_Model_PurchaseInvoice::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_BEARER, [
            Sales_Model_Product::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
    }

    /**
     * add more contract favorites
     */
    public static function createDefaultFavoritesForContracts()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Inactive Contracts", // _('Inactive Contracts')
                'description' => "Contracts that have already been terminated", // _('Contracts that have already been terminated')
                'filters' => [
                    ['field' => 'end_date', 'operator' => 'before', 'value' => Tinebase_Model_Filter_Date::DAY_THIS]
                ],
            ))
        ));
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "Active Contracts", // _('Active Contracts')
                'description' => "Contracts that are still running", // _('Contracts that are still running')
                'filters' => [
                        ['field' => 'end_date', 'operator' => 'after', 'value' => Tinebase_Model_Filter_Date::DAY_LAST],
                ],
            ))
        ));
    }

    /**
     * creates default favorited for version 8.22 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub22()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Purchase Invoices
        $commonValues['model'] = 'Sales_Model_SupplierFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Suppliers",        // _('All Suppliers')
                'description' => "All supplier records", // _('All supplier records')
                'filters' => array(),
            ))
        ));
    }

    /**
     * creates default favorited for version 8.24 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub24()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Purchase Invoices
        $commonValues['model'] = 'Sales_Model_PurchaseInvoiceFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Purchase Invoices", // _('All Purchase Invoices')
                'description' => "All purchase invoices", // _('All purchase invoices')
                'filters' => array(),
            ))
        ));
    }

    /**
     * creates default favorited for version 8.20 (gets called in initialization of this app)
     */
    public static function createDefaultFavoritesForSub20()
    {
        $commonValues = array(
            'account_id' => NULL,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
        );

        $pfe = Tinebase_PersistentFilter::getInstance();

        // Products
        $commonValues['model'] = 'Sales_Model_ProductFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Products", // _('All Products')
                'description' => "All product records", // _('All product records')
                'filters' => array(),
            ))
        ));

        // Contracts
        $commonValues['model'] = 'Sales_Model_ContractFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Contracts", // _('All Contracts')
                'description' => "All contract records", // _('All contract records')
                'filters' => array(),
            ))
        ));

        // Invoices
        $commonValues['model'] = 'Sales_Model_InvoiceFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Invoices", // _('All Invoices')
                'description' => "All invoice records", // _('All invoice records')
                'filters' => array(),
            ))
        ));

        // OrderConfirmations
        $commonValues['model'] = 'Sales_Model_OrderConfirmationFilter';

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name' => "All Order Confirmations", // _('All Order Confirmations')
                'description' => "All order confirmation records", // _('All order confirmation records')
                'filters' => array(),
            ))
        ));
    }

    protected function _initializeDefaultDivision(): void
    {
        $division = static::createDefaultDivision();
        static::createDefaultCategory($division);
    }

    public static function createDefaultDivision(): Sales_Model_Division
    {
        $t = Tinebase_Translation::getTranslation('Sales');
        $division = Sales_Controller_Division::getInstance()->create(new Sales_Model_Division([
            Sales_Model_Division::FLD_TITLE => $t->_('Default Division'),
            Sales_Model_Division::FLD_NAME => $t->_('Fill with name'),
            Sales_Model_Division::FLD_ADDR_PREFIX1 => $t->_('Fill with address'),
            Sales_Model_Division::FLD_ADDR_POSTAL => $t->_('Fill with postal'),
            Sales_Model_Division::FLD_ADDR_LOCALITY => $t->_('Fill with locality'),
            Sales_Model_Division::FLD_ADDR_COUNTRY => $t->_('Fill with country'),
            Sales_Model_Division::FLD_CONTACT_NAME => $t->_('Fill with contact name'),
            Sales_Model_Division::FLD_CONTACT_EMAIL => $t->_('Fill with contact email'),
            Sales_Model_Division::FLD_CONTACT_PHONE => $t->_('Fill with contact phone'),
            Sales_Model_Division::FLD_VAT_NUMBER => $t->_('Fill with vat number'),
        ]));
        Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION} = $division->getId();
        Tinebase_Container::getInstance()->setGrants($division->container_id,
            new Tinebase_Record_RecordSet(Sales_Model_DivisionGrants::class, [
                new Sales_Model_DivisionGrants([
                    'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    Sales_Model_DivisionGrants::GRANT_ADMIN => true,
                ])
            ]));

        return $division;
    }

    public static function createDefaultCategory(Sales_Model_Division $division): Sales_Model_Document_Category
    {
        $t = Tinebase_Translation::getTranslation('Sales');
        $category = Sales_Controller_Document_Category::getInstance()->create(new Sales_Model_Document_Category([
            Sales_Model_Document_Category::FLD_NAME => $t->_('Standard'),
            Sales_Model_Document_Category::FLD_DIVISION_ID => $division->getId(),
        ]));
        Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY_DEFAULT} = $category->getId();

        return $category;

    }
}
