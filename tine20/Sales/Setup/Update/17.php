<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE017_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE017_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE017_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE017_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE017_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE017_UPDATE010 = __CLASS__ . '::update010';
    const RELEASE017_UPDATE011 = __CLASS__ . '::update011';
    const RELEASE017_UPDATE012 = __CLASS__ . '::update012';
    const RELEASE017_UPDATE013 = __CLASS__ . '::update013';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT => [
            self::RELEASE017_UPDATE006 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update006',
            ],
            self::RELEASE017_UPDATE012 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update012',
            ],
        ],
        (self::PRIO_NORMAL_APP_STRUCTURE - 2) => [
            self::RELEASE017_UPDATE008 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update008',
            ],
        ],
        (self::PRIO_NORMAL_APP_STRUCTURE - 1) => [
            self::RELEASE017_UPDATE001 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update001',
            ],
            self::RELEASE017_UPDATE002 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update002',
            ],
            self::RELEASE017_UPDATE003 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update003',
            ],
            self::RELEASE017_UPDATE005 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update005',
            ],
            self::RELEASE017_UPDATE011 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update011',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE => [
            self::RELEASE017_UPDATE009 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update009',
            ],
            self::RELEASE017_UPDATE013 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update013',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE => [
            self::RELEASE017_UPDATE000 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update000',
            ],
            self::RELEASE017_UPDATE004 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update004',
            ],
            self::RELEASE017_UPDATE007 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update007',
            ],
            self::RELEASE017_UPDATE010 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update010',
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

        Sales_Setup_Initialize::initializeCostCenterCostBearer();

        foreach ($this->_backend->getOwnForeignKeys(Sales_Model_Product::TABLE_NAME) as $fKey) {
            $this->_backend->dropForeignKey(Sales_Model_Product::TABLE_NAME, $fKey['constraint_name']);
        }

        $this->_db->delete(SQL_TABLE_PREFIX . Sales_Model_Document_Address::TABLE_NAME, Sales_Model_Document_Address::FLD_DOCUMENT_ID . ' IS NULL');

        Setup_SchemaTool::updateSchema([
            Sales_Model_Address::class,
            Sales_Model_Customer::class,
            Sales_Model_Division::class,
            Sales_Model_DivisionEvalDimensionItem::class,
            Sales_Model_Debitor::class,
            Sales_Model_Document_Address::class,
            Sales_Model_Document_Category::class,
            Sales_Model_Document_Customer::class,
            Sales_Model_Document_Debitor::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Tinebase_Model_EvaluationDimensionItem::class,
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
            Sales_Model_Customer::class), null, false, true) as $customerId) {
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

        $categories = Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY};

        $updates = [];
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
            foreach ([
                         Sales_Model_Document_Boilerplate::TABLE_NAME,
                         Sales_Model_Boilerplate::TABLE_NAME,
                     ] as $table) {
                $db->update(SQL_TABLE_PREFIX . $table, [
                    Sales_Model_Boilerplate::FLD_DOCUMENT_CATEGORY => $newId,
                ], Sales_Model_Boilerplate::FLD_DOCUMENT_CATEGORY . $db->quoteInto(' = ?', $oldId));
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

    public function update004()
    {
        $divisionId = Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION};
        foreach ([
                     Sales_Model_Document_Delivery::class => [Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER, Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER],
                     Sales_Model_Document_Invoice::class => [Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER, Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER],
                     Sales_Model_Document_Offer::class => [Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER],
                     Sales_Model_Document_Order::class => [Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER],
                 ] as $model => $props) {
            foreach ($props as $property) {
                $this->getDb()->update(SQL_TABLE_PREFIX . 'numberable', ['bucket' => new Zend_Db_Expr('CONCAT(bucket, "#' . $divisionId . '")')], 'bucket = "' . $model . '#' . $property . '"');
            }
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }

    public function update005()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        foreach ($this->_backend->getOwnForeignKeys(Sales_Model_Product::TABLE_NAME) as $key) {
            $this->_backend->dropForeignKey(Sales_Model_Product::TABLE_NAME, $key['constraint_name']);
        }

        Sales_Setup_Initialize::createTbSystemCFEvaluationDimension();
        Sales_Setup_Initialize::initializeCostCenterCostBearer();

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.5', self::RELEASE017_UPDATE005);
    }

    public function update006()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->_backend->columnExists('costcenter', Sales_Model_Product::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Product::TABLE_NAME)
                . ' RENAME COLUMN costcenter TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('costbearer', Sales_Model_Product::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Product::TABLE_NAME)
                . ' RENAME COLUMN costbearer TO eval_dim_cost_bearer');
        }

        if ($this->_backend->columnExists('cost_center_id', Sales_Model_Document_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Invoice::TABLE_NAME)
                . ' RENAME COLUMN cost_center_id TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('cost_bearer_id', Sales_Model_Document_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Invoice::TABLE_NAME)
                . ' RENAME COLUMN cost_bearer_id TO eval_dim_cost_bearer');
        }

        if ($this->_backend->columnExists('cost_center_id', Sales_Model_Document_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Offer::TABLE_NAME)
                . ' RENAME COLUMN cost_center_id TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('cost_bearer_id', Sales_Model_Document_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Offer::TABLE_NAME)
                . ' RENAME COLUMN cost_bearer_id TO eval_dim_cost_bearer');
        }

        if ($this->_backend->columnExists('cost_center_id', Sales_Model_Document_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Order::TABLE_NAME)
                . ' RENAME COLUMN cost_center_id TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('cost_bearer_id', Sales_Model_Document_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Order::TABLE_NAME)
                . ' RENAME COLUMN cost_bearer_id TO eval_dim_cost_bearer');
        }

        if ($this->_backend->columnExists('payment_cost_center_id', Sales_Model_DocumentPosition_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Invoice::TABLE_NAME)
                . ' RENAME COLUMN payment_cost_center_id TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('payment_cost_bearer_id', Sales_Model_DocumentPosition_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Invoice::TABLE_NAME)
                . ' RENAME COLUMN payment_cost_bearer_id TO eval_dim_cost_bearer');
        }

        if ($this->_backend->columnExists('payment_cost_center_id', Sales_Model_DocumentPosition_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Offer::TABLE_NAME)
                . ' RENAME COLUMN payment_cost_center_id TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('payment_cost_bearer_id', Sales_Model_DocumentPosition_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Offer::TABLE_NAME)
                . ' RENAME COLUMN payment_cost_bearer_id TO eval_dim_cost_bearer');
        }

        if ($this->_backend->columnExists('payment_cost_center_id', Sales_Model_DocumentPosition_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Order::TABLE_NAME)
                . ' RENAME COLUMN payment_cost_center_id TO eval_dim_cost_center');
        }
        if ($this->_backend->columnExists('payment_cost_bearer_id', Sales_Model_DocumentPosition_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Order::TABLE_NAME)
                . ' RENAME COLUMN payment_cost_bearer_id TO eval_dim_cost_bearer');
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.6', self::RELEASE017_UPDATE006);
    }

    public function update007()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $allCat = Sales_Controller_Document_Category::getInstance()->getAll();
        $allCustomer = Sales_Controller_Customer::getInstance()->getAll();
        (new Tinebase_Record_Expander(Sales_Model_Customer::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Sales_Model_Customer::FLD_DEBITORS => [],
            ],
        ]))->expand($allCustomer);

        $did2c = [];
        foreach ($this->_db->select()->from(SQL_TABLE_PREFIX . Sales_Model_Document_Customer::TABLE_NAME, [
            Sales_Model_Document_Customer::FLD_DOCUMENT_ID,
            Sales_Model_Document_Customer::FLD_ORIGINAL_ID,
        ])->query()->fetchAll(Zend_Db::FETCH_NUM) as $row) {
            $did2c[$row[0]] = $row[1];
        }

        $stdCat = Sales_Config::getInstance()->{Sales_Config::DOCUMENT_CATEGORY_DEFAULT};
        $flds = Sales_Model_Document_Debitor::getConfiguration()->fields;
        $ctrl = Sales_Controller_Document_Debitor::getInstance();
        $ctrl->doContainerACLChecks(false);
        foreach ([
                     Sales_Model_Document_Delivery::TABLE_NAME,
                     Sales_Model_Document_Invoice::TABLE_NAME,
                     Sales_Model_Document_Offer::TABLE_NAME,
                     Sales_Model_Document_Order::TABLE_NAME,
                 ] as $table) {
            foreach ($this->_db->select()->from(['a' => SQL_TABLE_PREFIX . $table], [
                Sales_Model_Document_Abstract::ID,
                Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY
            ])->joinLeft(['b' => SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME], 'a.id = b.' . Sales_Model_Document_Debitor::FLD_DOCUMENT_ID, [])
                         ->where('b.id IS NULL')->query()->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                if (!($cat = $allCat->getById($row[1]))) {
                    $this->_db->update(SQL_TABLE_PREFIX . $table, [Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY => $stdCat], 'id = "' . $row[0] . '"');
                    $cat = $allCat->getById($stdCat);
                }
                $customer = $allCustomer->getById($did2c[$row[0]]);
                if (!($debitor = $customer->{Sales_Model_Customer::FLD_DEBITORS}
                    ->find(Sales_Model_Debitor::FLD_DIVISION_ID, $cat->{Sales_Model_Document_Category::FLD_DIVISION_ID}))) {
                    $customer->{Sales_Model_Customer::FLD_DEBITORS}->addRecord($debitor = Sales_Controller_Debitor::getInstance()->create(
                        new Sales_Model_Debitor([
                            Sales_Model_Debitor::FLD_DIVISION_ID => $cat->{Sales_Model_Document_Category::FLD_DIVISION_ID},
                            Sales_Model_Debitor::FLD_CUSTOMER_ID => $customer->getId(),
                            Sales_Model_Debitor::FLD_NAME => '-',
                        ], true)
                    ));
                }
                $data = array_intersect_key($debitor->toArray(), $flds);
                $data[Sales_Model_Document_Debitor::FLD_ORIGINAL_ID] = $data['id'];
                unset($data['id']);
                $data[Sales_Model_Document_Debitor::FLD_DOCUMENT_ID] = $row[0];
                $ctrl->create(new Sales_Model_Document_Debitor($data));
            }
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.7', self::RELEASE017_UPDATE007);
    }

    public function update008()
    {
        $this->_db->query('INSERT INTO ' . SQL_TABLE_PREFIX . 'numberable (bucket, `number`) SELECT "' . Sales_Model_Customer::class . '#number", `number` FROM ' . SQL_TABLE_PREFIX . Sales_Model_Customer::TABLE_NAME);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.8', self::RELEASE017_UPDATE008);
    }

    public function update009()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Customer::class,
            Sales_Model_Document_Customer::class,
        ]);

        $numConf = Tinebase_Controller_NumberableConfig::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_NumberableConfig::class, [
                [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_BUCKET_KEY, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Customer::class . '#number']
            ]))->getFirstRecord();

        $numConf->{Tinebase_Model_NumberableConfig::FLD_ZEROFILL} = 0;
        Tinebase_Controller_NumberableConfig::getInstance()->update($numConf);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.9', self::RELEASE017_UPDATE009);
    }

    public function update010()
    {
        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Document_Address::TABLE_NAME . ' AS a JOIN '
            . SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME . ' AS d '
            . 'ON a.' . Sales_Model_Document_Address::FLD_DOCUMENT_ID . ' = d.' . Sales_Model_Document_Debitor::FLD_DOCUMENT_ID
            . ' SET a.' . Sales_Model_Document_Address::FLD_DEBITOR_ID . ' = d.id');

        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Boilerplate::TABLE_NAME . ' SET '
            . Sales_Model_Boilerplate::FLD_DOCUMENT_CATEGORY . ' = NULL');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.10', self::RELEASE017_UPDATE010);
    }

    public function update011()
    {
        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME . ' as a JOIN '
            . SQL_TABLE_PREFIX . Sales_Model_Customer::TABLE_NAME . ' AS b ON a.' . Sales_Model_Debitor::FLD_CUSTOMER_ID . ' = b.id SET a.number = b.number');

        $this->_db->query('UPDATE IGNORE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME . ' as d JOIN '
            . SQL_TABLE_PREFIX . Sales_Model_Address::TABLE_NAME . ' AS a ON a.' . Sales_Model_Address::FLD_DEBITOR_ID
            . ' = d.id SET d.number = a.' . Sales_Model_Address::FLD_CUSTOM1 . ' WHERE a.' . Sales_Model_Address::FLD_CUSTOM1 . ' IS NOT NULL AND a.' . Sales_Model_Address::FLD_CUSTOM1 . ' <> ""');

        $this->_db->query('UPDATE IGNORE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME . ' as d JOIN '
            . SQL_TABLE_PREFIX . Sales_Model_Customer::TABLE_NAME . ' AS c ON d.' . Sales_Model_Debitor::FLD_CUSTOMER_ID
            . ' = c.id JOIN ' . SQL_TABLE_PREFIX . Sales_Model_Address::TABLE_NAME . ' AS a ON a.' . Sales_Model_Address::FLD_CUSTOMER_ID
            . ' = c.id SET d.number = a.' . Sales_Model_Address::FLD_CUSTOM1 . ' WHERE a.' . Sales_Model_Address::FLD_CUSTOM1 . ' IS NOT NULL AND a.' . Sales_Model_Address::FLD_CUSTOM1 . ' <> ""');

        $bucket = Sales_Model_Debitor::class . '#' . Sales_Model_Debitor::FLD_NUMBER . '#' . Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION};
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'numberable WHERE bucket = "' . $bucket . '"');
        $this->getDb()->query('INSERT INTO ' . SQL_TABLE_PREFIX . 'numberable (bucket, number) SELECT "' . $bucket . '", REPLACE(' . Sales_Model_Debitor::FLD_NUMBER . ', "DEB-", "") FROM ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.11', self::RELEASE017_UPDATE011);
    }

    public function update012()
    {
        if ($this->_backend->tableExists(Sales_Model_Invoice::TABLE_NAME) && $this->_backend->columnExists('costcenter_id', Sales_Model_Invoice::TABLE_NAME)) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Invoice::TABLE_NAME)
                . ' RENAME COLUMN costcenter_id TO eval_dim_cost_center');
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.12', self::RELEASE017_UPDATE012);
    }

    public function update013()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if (!Tinebase_Core::isReplica()) {
            Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
                    Sales_Model_Contract::class,
                    Sales_Model_Invoice::class,
                    Sales_Model_PurchaseInvoice::class,
                ]);
        }

        foreach ($this->_backend->getOwnForeignKeys(Sales_Model_Contract::TABLE_NAME) as $fKey) {
            $this->_backend->dropForeignKey(Sales_Model_Contract::TABLE_NAME, $fKey['constraint_name']);
        }

        Setup_SchemaTool::updateSchema([
            Sales_Model_Contract::class,
            Sales_Model_Invoice::class,
            Tinebase_Model_EvaluationDimensionItem::class,
            Tinebase_Model_Container::class,
        ]);


        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Contract::TABLE_NAME . ' AS c JOIN '
            . SQL_TABLE_PREFIX . 'relations AS r ON c.id = r.own_id AND r.own_model = "' . Sales_Model_Contract::class
            . '" AND r.own_backend = "Sql" AND r.`type` = "LEAD_COST_CENTER" AND related_model = "Tinebase_Model_CostCenter" SET c.eval_dim_cost_center = r.related_id');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND related_model = "'
            . Sales_Model_Contract::class . '" AND `type` = "LEAD_COST_CENTER"');
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND own_model = "'
            . Sales_Model_Contract::class . '" AND `type` = "LEAD_COST_CENTER"');

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_PurchaseInvoice::TABLE_NAME . ' AS c JOIN '
            . SQL_TABLE_PREFIX . 'relations AS r ON c.id = r.own_id AND r.own_model = "' . Sales_Model_Contract::class
            . '" AND r.own_backend = "Sql" AND r.`type` = "COST_CENTER" AND related_model = "Tinebase_Model_CostCenter" SET c.eval_dim_cost_center = r.related_id');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND related_model = "'
            . Sales_Model_PurchaseInvoice::class . '" AND `type` = "COST_CENTER"');
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND own_model = "'
            . Sales_Model_PurchaseInvoice::class . '" AND `type` = "COST_CENTER"');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.13', self::RELEASE017_UPDATE013);
    }
}
