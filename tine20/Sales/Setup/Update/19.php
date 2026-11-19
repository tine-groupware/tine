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
    protected const RELEASE019_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE019_UPDATE006 = __CLASS__ . '::update006';
    protected const RELEASE019_UPDATE007 = __CLASS__ . '::update007';
    protected const RELEASE019_UPDATE008 = __CLASS__ . '::update008';
    protected const RELEASE019_UPDATE009 = __CLASS__ . '::update009';
    protected const RELEASE019_UPDATE010 = __CLASS__ . '::update010';
    protected const RELEASE019_UPDATE011 = __CLASS__ . '::update011';

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
            self::RELEASE019_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE019_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE019_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE019_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
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
            self::RELEASE019_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE019_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE019_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
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

    public function update005(): void
    {
        $_purgeDateField = new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>purge_date</name>
                <type>date</type>
                <notnull>false</notnull>
            </field>');

        if (!$this->_backend->columnExists('purge_date', 'sales_product_agg')) {
            $this->_backend->addCol('sales_product_agg', $_purgeDateField);
            if ($this->getTableVersion('sales_product_agg') < 7) {
                $this->setTableVersion('sales_product_agg', 7);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'sales_orderconf')) {
            $this->_backend->addCol('sales_orderconf', $_purgeDateField);
            if ($this->getTableVersion('sales_orderconf') < 3) {
                $this->setTableVersion('sales_orderconf', 3);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'sales_offers')) {
            $this->_backend->addCol('sales_offers', $_purgeDateField);
            if ($this->getTableVersion('sales_offers') < 3) {
                $this->setTableVersion('sales_offers', 3);
            }
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.5', self::RELEASE019_UPDATE005);
    }

    public function update006(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            Sales_Model_Supplier::class,
        ]);
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_BEARER, [
            Sales_Model_Supplier::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.6', self::RELEASE019_UPDATE006);
    }

    public function update007(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Credit::class,
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_DocumentPosition_PurchaseInvoice::class,
            Sales_Model_EDocument_EAS::class,
            Sales_Model_EDocument_PaymentMeansCode::class,
            Sales_Model_EDocument_UnitOfMeasure::class,
            Sales_Model_EDocument_UnitOfMeasureLocalization::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.7', self::RELEASE019_UPDATE007);
    }

    public function update008(): void
    {
        Sales_Setup_Initialize::initializeEDocumentUnitOfMeasure();

        foreach (['58', '59'] as $code) {
            $paymentMeans = Sales_Controller_EDocument_PaymentMeansCode::getInstance()->getByCode($code);
            $paymentMeans->{Sales_Model_EDocument_PaymentMeansCode::FLD_ACTIVE} = true;
            Sales_Controller_EDocument_PaymentMeansCode::getInstance()->update($paymentMeans);
        }
        foreach (array_keys(Sales_Setup_Initialize::$eDocumentEASActiveCodes) as $code) {
            $eas = Sales_Controller_EDocument_EAS::getInstance()->getByCode((string)$code);
            $eas->{Sales_Model_EDocument_EAS::FLD_ACTIVE} = true;
            Sales_Controller_EDocument_EAS::getInstance()->update($eas);
        }

        $db = $this->getDb();
        $unitMapping = [
            'PIECE' => Sales_Controller_EDocument_UnitOfMeasure::getInstance()->getByCode('H87'),
        ];
        foreach ([
                    Sales_Model_DocumentPosition_Credit::TABLE_NAME,
                    Sales_Model_DocumentPosition_Delivery::TABLE_NAME,
                    Sales_Model_DocumentPosition_Invoice::TABLE_NAME,
                    Sales_Model_DocumentPosition_Offer::TABLE_NAME,
                    Sales_Model_DocumentPosition_Order::TABLE_NAME,
                    Sales_Model_DocumentPosition_PurchaseInvoice::TABLE_NAME,
                ] as $table) {
            $quotedTable = $db->quoteIdentifier(SQL_TABLE_PREFIX . $table);
            if ($db->query($db->quoteInto('SELECT count(*) FROM ' . $quotedTable . ' WHERE `unit` IS NOT NULL AND `unit` NOT IN (?)', array_keys($unitMapping)))->fetchColumn() > 0) {
                throw new Tinebase_Exception('found unmapped position unit, adjust $unitMapping and rerun update');
            }
            foreach ($unitMapping as $old => $new) {
                $db->update(SQL_TABLE_PREFIX . $table, ['unit' => $new->getId()], $db->quoteInto('unit = ?', $old));
            }
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.8', self::RELEASE019_UPDATE008);
    }

    /**
     * german names of the units of measure (own translation, the KoSIT code list has english names only)
     */
    public function update009(): void
    {
        Sales_Setup_Initialize::updateEDocumentUnitOfMeasureTranslations();
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.9', self::RELEASE019_UPDATE009);
    }

    /**
     * product unit: keyfield -> Sales_Model_EDocument_UnitOfMeasure (schema)
     */
    public function update010(): void
    {
        Setup_SchemaTool::updateSchema([Sales_Model_Product::class]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.10', self::RELEASE019_UPDATE010);
    }

    /**
     * product unit: keyfield -> Sales_Model_EDocument_UnitOfMeasure (data), removes the keyfield config productUnit
     */
    public function update011(): void
    {
        $db = $this->getDb();
        $uomCtrl = Sales_Controller_EDocument_UnitOfMeasure::getInstance();
        $productTable = SQL_TABLE_PREFIX . Sales_Model_Product::TABLE_NAME;
        $quotedProductTable = $db->quoteIdentifier($productTable);

        // values of the (possibly customized) keyfield config productUnit, id => display value
        $keyFieldValues = ['PIECE' => 'Piece'];
        $salesAppId = Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME)->getId();
        $configValue = $db->query($db->quoteInto('SELECT `value` FROM '
            . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'config')
            . ' WHERE `name` = \'productUnit\' AND `application_id` = ?', $salesAppId))->fetchColumn();
        if ($configValue && is_array($config = json_decode($configValue, true))) {
            foreach ($config['records'] ?? [] as $record) {
                if (isset($record['id'])) {
                    $keyFieldValues[$record['id']] = $record['value'] ?? $record['id'];
                }
            }
        }

        $unitMapping = [];
        $unmapped = [];
        $units = null;
        $oldUnits = $db->query('SELECT DISTINCT `unit` FROM ' . $quotedProductTable
            . ' WHERE `unit` IS NOT NULL AND `unit` <> \'\'')->fetchAll(Zend_Db::FETCH_COLUMN);
        foreach ($oldUnits as $old) {
            if ('PIECE' === $old) {
                $unit = $uomCtrl->getByCode('H87');
            } elseif (null === ($unit = $uomCtrl->getByCode($old))) {
                try {
                    // already migrated
                    $uomCtrl->get($old);
                    continue;
                } catch (Tinebase_Exception_NotFound) {
                    // custom keyfield value
                }
                // find unit by name (any language)
                if (null === $units) {
                    $units = $uomCtrl->getAll();
                    (new Tinebase_Record_Expander(Sales_Model_EDocument_UnitOfMeasure::class, [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            Sales_Model_EDocument_UnitOfMeasure::FLD_NAME => [],
                        ],
                    ]))->expand($units);
                }
                $needles = array_map('mb_strtolower', array_unique([$old, $keyFieldValues[$old] ?? $old]));
                $unit = $units->filter(function ($u) use ($needles) {
                    $names = $u->{Sales_Model_EDocument_UnitOfMeasure::FLD_NAME};
                    return (bool) $names->filter(function ($l) use ($needles) {
                        $text = (string) $l->{Sales_Model_EDocument_UnitOfMeasureLocalization::FLD_TEXT};
                        return in_array(mb_strtolower($text), $needles);
                    })->count();
                })->getFirstRecord();
            }
            if (null === $unit) {
                $unmapped[] = $old;
            } else {
                $unitMapping[$old] = $unit->getId();
            }
        }
        if ($unmapped) {
            throw new Tinebase_Exception('found unmapped product units "' . implode('", "', $unmapped)
                . '", adjust $unitMapping and rerun update');
        }

        $db->update($productTable, ['unit' => null], '`unit` = \'\'');
        foreach ($unitMapping as $old => $new) {
            $db->update($productTable, ['unit' => $new], $db->quoteInto('`unit` = ?', $old));
        }

        Sales_Config::getInstance()->delete('productUnit');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '19.11', self::RELEASE019_UPDATE011);
    }
}
