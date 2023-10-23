<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Sales_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        Setup_SchemaTool::updateSchema([
            Sales_Model_Customer::class,
            Sales_Model_Division::class,
            Sales_Model_Debitor::class,
            Sales_Model_Invoice::class,
            Sales_Model_Document_Address::class,
            Sales_Model_Document_Customer::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        $division = Sales_Setup_Initialize::createDefaultDivision();
        $category = Sales_Setup_Initialize::createDefaultCategory($division);
        $debitorCtrl = Sales_Controller_Debitor::getInstance();
        $db = $this->getDb();

        foreach (Sales_Controller_Customer::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Sales_Model_Customer::class), null, true) as $customerId) {
            $debitor = $debitorCtrl->create(new Sales_Model_Debitor([
                Sales_Model_Debitor::FLD_NAME => '-',
                Sales_Model_Debitor::FLD_CUSTOMER_ID => $customerId,
                Sales_Model_Debitor::FLD_DIVISION_ID => $division->getId(),
            ]));

            $db->update(SQL_TABLE_PREFIX . Sales_Model_Address::TABLE_NAME, [
                Sales_Model_Address::FLD_CUSTOMER_ID => null,
                Sales_Model_Address::FLD_DEBITOR_ID => $debitor->getId(),
            ], Sales_Model_Address::FLD_CUSTOMER_ID . $db->quoteInto(' = ? AND ', $customerId)
                . Sales_Model_Address::FLD_TYPE . ' != "' . Sales_Model_Address::TYPE_POSTAL . '"');
        }

        //
        $categories = Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY};

        $updates = [];
        // @TODO migrate category keyfield to records & update category_id in all documents & set DOCUMENT_CATEGORY_DEFAULT
        /** @var Tinebase_Config_KeyFieldRecord $record */
        foreach ($categories->records as $record) {
            if ('STANDARD' === $record->getId()) {
                $updates[$record->getId()] = $category->getId();
            } else {
                $cat = Sales_Controller_Document_Category::getInstance()->create(new Sales_Model_Document_Category([
                    Sales_Model_Document_Category::FLD_NAME => $record->value,
                    Sales_Model_Document_Category::FLD_DIVISION_ID => $division->getId(),
                ]));
                $updates[$record->getId()] = $cat->getId();
            }
        }
        foreach ($updates as $oldId => $newId) {
            foreach ([
                        Sales_Model_Document_Delivery::TABLE_NAME,
                        Sales_Model_Document_Invoice::TABLE_NAME,
                        Sales_Model_Document_Offer::TABLE_NAME,
                        Sales_Model_Document_Order::TABLE_NAME,
                     ] as $table) {
                $db->update(SQL_TABLE_PREFIX . $table, [
                    Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY => $newId,
                ], Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY . $db->quoteInto(' = ?', $oldId));
            }
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        if (!$this->_backend->columnExists('debitor_id', 'sales_sales_invoices')) {
            $this->_backend->addCol('sales_sales_invoices', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>debitor_id</name>
                    <type>text</type>
                    <length>40</length>
                </field>'));
        }

        if ($this->getTableVersion('sales_sales_invoices') < 10) {
            $this->setTableVersion('sales_sales_invoices', 10);
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }
}
