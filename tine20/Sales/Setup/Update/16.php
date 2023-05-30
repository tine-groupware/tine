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
}
