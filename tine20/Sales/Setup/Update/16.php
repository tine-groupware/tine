<?php

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Sales_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE016_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE016_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE016_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE016_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE016_UPDATE007 = __CLASS__ . '::update007';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE016_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE016_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE016_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE016_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE016_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ]
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Sales', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        // moved to \Sales_Frontend_Cli::addEmailToSalesAddress
        $this->addApplicationUpdate('Sales', '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '16.2', self::RELEASE016_UPDATE002);
    }
    
    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('sales_purchase_invoices') < 7) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
               <field>
                    <name>last_datev_send_date</name>
                    <type>datetime</type>
                </field>
        ');
            $this->_backend->addCol('sales_purchase_invoices', $declaration, 7);
            $this->setTableVersion('sales_purchase_invoices', 7);
        }
        $this->addApplicationUpdate('Sales', '16.3', self::RELEASE016_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_DocumentPosition_Invoice::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '16.4', self::RELEASE016_UPDATE004);
    }

    public function update005()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if (!$this->_backend->columnExists('vat_procedure', 'sales_customers')) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>vat_procedure</name>
                    <type>text</type>
                    <length>40</length>
                    <default>taxable</default>
                </field>
            ');
            $this->_backend->addCol('sales_customers', $declaration, 10);
            if ($this->getTableVersion('sales_customers') < 5) {
                $this->setTableVersion('sales_customers', 5);
            }
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '16.5', self::RELEASE016_UPDATE005);
    }

    public function update006()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        $filter = new Tinebase_Model_PersistentFilterFilter(array(
            array(
                'field' => 'name',
                'operator' => 'equals',
                'value' => 'Active Contracts'
            ),
            array(
                'field' => 'application_id',
                'operator' => 'equals',
                'value' => Tinebase_Application::getInstance()->getApplicationById('Sales')->getId()
            ),
        ));
        $result = Tinebase_PersistentFilter::getInstance()->search($filter)->getFirstRecord();
        if($result){
            $pfe->delete([$result->getId()]);
        }
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter([
            'account_id' => NULL,
            'model' => 'Sales_Model_ContractFilter',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
            'name' => "Active Contracts", // _('Active Contracts')
            'description' => "Contracts that are still running", // _('Contracts that are still running')
            'filters' => [
                ['field' => 'end_date', 'operator' => 'after', 'value' => Tinebase_Model_Filter_Date::DAY_LAST],
            ],
        ]));
        $this->addApplicationUpdate('Sales', '16.6', self::RELEASE016_UPDATE006);
    }

    public function update007()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Invoice::class,
        ]);
        $this->addApplicationUpdate('Sales', '16.7', self::RELEASE016_UPDATE007);
    }
}
