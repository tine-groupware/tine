<?php declare(strict_types=1);
/**
 * tine Groupware
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */

use Tinebase_ModelConfiguration_Const as TMCC;

class Sales_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE019_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE019_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE019_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE019_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE019_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE019_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        Sales_Setup_Initialize::createDocumentInvoiceFavorites();

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    public function update002(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_PurchaseInvoice::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
    }

    public function update003(): void
    {
        Sales_Setup_Initialize::createDocumentOfferAndOrderFavorites();

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.3', self::RELEASE019_UPDATE003);
    }

    public function update004(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $fields = Sales_Model_Document_Address::getConfiguration()->getFields();
        $fields[Sales_Model_Document_Address::FLD_DOCUMENT_TYPE][TMCC::NULLABLE] = true;
        Sales_Model_Document_Address::getConfiguration()->setFields($fields);

        $fields = Sales_Model_Document_Customer::getConfiguration()->getFields();
        $fields[Sales_Model_Document_Customer::FLD_DOCUMENT_TYPE][TMCC::NULLABLE] = true;
        Sales_Model_Document_Customer::getConfiguration()->setFields($fields);

        $fields = Sales_Model_Document_Debitor::getConfiguration()->getFields();
        $fields[Sales_Model_Document_Debitor::FLD_DOCUMENT_TYPE][TMCC::NULLABLE] = true;
        Sales_Model_Document_Debitor::getConfiguration()->setFields($fields);

        $fields = Sales_Model_Document_Supplier::getConfiguration()->getFields();
        $fields[Sales_Model_Document_Supplier::FLD_DOCUMENT_TYPE][TMCC::NULLABLE] = true;
        Sales_Model_Document_Supplier::getConfiguration()->setFields($fields);

        $this->_backend->dropIndex(Sales_Model_Document_Address::TABLE_NAME, Sales_Model_Document_Address::FLD_DOCUMENT_ID);

        Setup_SchemaTool::updateSchema([
            Sales_Model_Address::class,
            Sales_Model_Debitor::class,
            Sales_Model_DivisionEvalDimensionItem::class,
            Sales_Model_Document_Address::class,
            Sales_Model_Document_Customer::class,
            Sales_Model_Document_Debitor::class,
            Sales_Model_Document_Supplier::class,
            Sales_Model_Document_SupplierAddress::class,
        ]);

        Sales_Model_Document_Address::resetConfiguration();
        Sales_Model_Document_Customer::resetConfiguration();
        Sales_Model_Document_Debitor::resetConfiguration();
        Sales_Model_Document_Supplier::resetConfiguration();

        $adrTbl = SQL_TABLE_PREFIX . Sales_Model_Document_Address::TABLE_NAME;
        $customerTbl = SQL_TABLE_PREFIX . Sales_Model_Document_Customer::TABLE_NAME;
        $debitorTbl = SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME;
        $supplierTbl = SQL_TABLE_PREFIX . Sales_Model_Document_Supplier::TABLE_NAME;

        foreach ([
                     Sales_Model_Document_Delivery::class => SQL_TABLE_PREFIX . Sales_Model_Document_Delivery::TABLE_NAME,
                     Sales_Model_Document_Invoice::class => SQL_TABLE_PREFIX . Sales_Model_Document_Invoice::TABLE_NAME,
                     Sales_Model_Document_Offer::class => SQL_TABLE_PREFIX . Sales_Model_Document_Offer::TABLE_NAME,
                     Sales_Model_Document_Order::class => SQL_TABLE_PREFIX . Sales_Model_Document_Order::TABLE_NAME,
                     Sales_Model_Document_PurchaseInvoice::class => SQL_TABLE_PREFIX . Sales_Model_Document_PurchaseInvoice::TABLE_NAME,
                 ] as $class => $docTbl) {
            $this->getDb()->query('UPDATE ' . $adrTbl . ' as a JOIN ' . $docTbl . ' as d ON a.document_id = d.id SET a.document_type = "' . $class . '"');
            $this->getDb()->query('UPDATE ' . $customerTbl . ' as a JOIN ' . $docTbl . ' as d ON a.document_id = d.id SET a.document_type = "' . $class . '"');
            $this->getDb()->query('UPDATE ' . $debitorTbl . ' as a JOIN ' . $docTbl . ' as d ON a.document_id = d.id SET a.document_type = "' . $class . '"');
            $this->getDb()->query('UPDATE ' . $supplierTbl . ' as a JOIN ' . $docTbl . ' as d ON a.document_id = d.id SET a.document_type = "' . $class . '"');
        }

        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Address::class,
            Sales_Model_Document_Customer::class,
            Sales_Model_Document_Debitor::class,
            Sales_Model_Document_Supplier::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.4', self::RELEASE019_UPDATE004);
    }
}
