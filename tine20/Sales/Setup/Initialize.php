<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

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
                    TMCC::LABEL             => 'Sales Divisions', // _('Sales Divisions')
                    TMCC::TYPE              => TMCC::TYPE_RECORDS,
                    TMCC::CONFIG            => [
                        TMCC::APP_NAME          => Sales_Config::APP_NAME,
                        TMCC::MODEL_NAME        => Sales_Model_DivisionEvalDimensionItem::MODEL_NAME_PART,
                        TMCC::REF_ID_FIELD      => Sales_Model_DivisionEvalDimensionItem::FLD_EVAL_DIMENSION_ITEM_ID,
                        TMCC::DEPENDENT_RECORDS => true,
                    ],
                ],
            ]
        ], true));
    }

    protected function _initializeCostCenterCostBearer()
    {
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_BEARER, [
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
                'name' => "All Purchase Imvoices", // _('All Purchase Imvoices')
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
        ]));
        Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION} = $division->getId();

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
