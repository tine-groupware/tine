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

use Tinebase_ModelConfiguration_Const as TMCC;
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
    const RELEASE017_UPDATE014 = __CLASS__ . '::update014';

    const RELEASE017_UPDATE015 = __CLASS__ . '::update015';
    const RELEASE017_UPDATE016 = __CLASS__ . '::update016';
    const RELEASE017_UPDATE017 = __CLASS__ . '::update017';
    const RELEASE017_UPDATE018 = __CLASS__ . '::update018';
    const RELEASE017_UPDATE019 = __CLASS__ . '::update019';
    const RELEASE017_UPDATE020 = __CLASS__ . '::update020';
    const RELEASE017_UPDATE021 = __CLASS__ . '::update021';
    const RELEASE017_UPDATE022 = __CLASS__ . '::update022';
    const RELEASE017_UPDATE023 = __CLASS__ . '::update023';
    const RELEASE017_UPDATE024 = __CLASS__ . '::update024';
    protected const RELEASE017_UPDATE025 = __CLASS__ . '::update025';
    protected const RELEASE017_UPDATE026 = __CLASS__ . '::update026';
    protected const RELEASE017_UPDATE027 = __CLASS__ . '::update027';
    protected const RELEASE017_UPDATE028 = __CLASS__ . '::update028';
    protected const RELEASE017_UPDATE029 = __CLASS__ . '::update029';
    protected const RELEASE017_UPDATE030 = __CLASS__ . '::update030';
    protected const RELEASE017_UPDATE031 = __CLASS__ . '::update031';
    protected const RELEASE017_UPDATE032 = __CLASS__ . '::update032';
    protected const RELEASE017_UPDATE033 = __CLASS__ . '::update033';
    protected const RELEASE017_UPDATE034 = __CLASS__ . '::update034';
    protected const RELEASE017_UPDATE035 = __CLASS__ . '::update035';
    protected const RELEASE017_UPDATE036 = __CLASS__ . '::update036';
    protected const RELEASE017_UPDATE037 = __CLASS__ . '::update037';
    protected const RELEASE017_UPDATE038 = __CLASS__ . '::update038';
    protected const RELEASE017_UPDATE039 = __CLASS__ . '::update039';
    protected const RELEASE017_UPDATE040 = __CLASS__ . '::update040';
    protected const RELEASE017_UPDATE041 = __CLASS__ . '::update041';
    protected const RELEASE017_UPDATE042 = __CLASS__ . '::update042';
    protected const RELEASE017_UPDATE043 = __CLASS__ . '::update043';
    protected const RELEASE017_UPDATE044 = __CLASS__ . '::update044';

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
        // ACHTUNG column rename!!!
        (self::PRIO_NORMAL_APP_STRUCTURE - 5) => [
            self::RELEASE017_UPDATE032 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update032',
            ],
        ],
        (self::PRIO_NORMAL_APP_STRUCTURE - 4) => [
            self::RELEASE017_UPDATE030 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update030',
            ],
            self::RELEASE017_UPDATE036 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update036',
            ],
        ],
        (self::PRIO_NORMAL_APP_STRUCTURE - 3) => [
            self::RELEASE017_UPDATE008 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update008',
            ],
        ],
        (self::PRIO_NORMAL_APP_STRUCTURE - 2) => [
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
            self::RELEASE017_UPDATE014 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update014',
            ],
            self::RELEASE017_UPDATE015 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update015',
            ],
            self::RELEASE017_UPDATE016 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update016',
            ],
            self::RELEASE017_UPDATE017 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update017',
            ],
            self::RELEASE017_UPDATE018 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update018',
            ],
            self::RELEASE017_UPDATE019 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update019',
            ],
            self::RELEASE017_UPDATE020 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update020',
            ],
            self::RELEASE017_UPDATE021 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update021',
            ],
            self::RELEASE017_UPDATE023 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update023',
            ],
            self::RELEASE017_UPDATE024 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update024',
            ],
            self::RELEASE017_UPDATE025 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update025',
            ],
            self::RELEASE017_UPDATE026 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update026',
            ],
            self::RELEASE017_UPDATE027 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update027',
            ],
            self::RELEASE017_UPDATE028 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update028',
            ],
            self::RELEASE017_UPDATE029 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update029',
            ],
            self::RELEASE017_UPDATE031 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update031',
            ],
            self::RELEASE017_UPDATE033 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update033',
            ],
            self::RELEASE017_UPDATE034 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update034',
            ],
            self::RELEASE017_UPDATE035 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update035',
            ],
            self::RELEASE017_UPDATE037 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update037',
            ],
            self::RELEASE017_UPDATE038 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update038',
            ],
            self::RELEASE017_UPDATE039 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update039',
            ],
            self::RELEASE017_UPDATE040 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update040',
            ],
            self::RELEASE017_UPDATE041 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update041',
            ],
            self::RELEASE017_UPDATE042 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update042',
            ],
            self::RELEASE017_UPDATE043 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update043',
            ],
            self::RELEASE017_UPDATE044 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update044',
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
            self::RELEASE017_UPDATE022 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update022',
            ],
            self::RELEASE017_UPDATE043 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update043',
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

        $this->divisionUpdate();

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
        $this->divisionUpdate();
        
        $division = Sales_Setup_Initialize::createDefaultDivision();
        $category = Sales_Setup_Initialize::createDefaultCategory($division);
        $debitorCtrl = Sales_Controller_Debitor::getInstance();
        $db = $this->getDb();

        Sales_Controller_Customer::getInstance()->doContainerACLChecks(false);
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
        Sales_Controller_Customer::getInstance()->doContainerACLChecks(true);

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
                . ' CHANGE costcenter eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('costbearer', Sales_Model_Product::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Product::TABLE_NAME)
                . ' CHANGE costbearer eval_dim_cost_bearer varchar(255) DEFAULT NULL');
        }

        if ($this->_backend->columnExists('cost_center_id', Sales_Model_Document_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Invoice::TABLE_NAME)
                . ' CHANGE cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('cost_bearer_id', Sales_Model_Document_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Invoice::TABLE_NAME)
                . ' CHANGE cost_bearer_id eval_dim_cost_bearer varchar(255) DEFAULT NULL');
        }

        if ($this->_backend->columnExists('cost_center_id', Sales_Model_Document_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Offer::TABLE_NAME)
                . ' CHANGE cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('cost_bearer_id', Sales_Model_Document_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Offer::TABLE_NAME)
                . ' CHANGE cost_bearer_id eval_dim_cost_bearer varchar(255) DEFAULT NULL');
        }

        if ($this->_backend->columnExists('cost_center_id', Sales_Model_Document_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Order::TABLE_NAME)
                . ' CHANGE cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('cost_bearer_id', Sales_Model_Document_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Document_Order::TABLE_NAME)
                . ' CHANGE cost_bearer_id eval_dim_cost_bearer varchar(255) DEFAULT NULL');
        }

        if ($this->_backend->columnExists('payment_cost_center_id', Sales_Model_DocumentPosition_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Invoice::TABLE_NAME)
                . ' CHANGE payment_cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('payment_cost_bearer_id', Sales_Model_DocumentPosition_Invoice::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Invoice::TABLE_NAME)
                . ' CHANGE payment_cost_bearer_id eval_dim_cost_bearer varchar(255) DEFAULT NULL');
        }

        if ($this->_backend->columnExists('payment_cost_center_id', Sales_Model_DocumentPosition_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Offer::TABLE_NAME)
                . ' CHANGE payment_cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('payment_cost_bearer_id', Sales_Model_DocumentPosition_Offer::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Offer::TABLE_NAME)
                . ' CHANGE payment_cost_bearer_id eval_dim_cost_bearer varchar(255) DEFAULT NULL');
        }

        if ($this->_backend->columnExists('payment_cost_center_id', Sales_Model_DocumentPosition_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Order::TABLE_NAME)
                . ' CHANGE payment_cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        if ($this->_backend->columnExists('payment_cost_bearer_id', Sales_Model_DocumentPosition_Order::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_DocumentPosition_Order::TABLE_NAME)
                . ' CHANGE payment_cost_bearer_id eval_dim_cost_bearer varchar(255) DEFAULT NULL');
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
                [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_MODEL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Customer::class],
                [TMFA::FIELD => Tinebase_Model_NumberableConfig::FLD_PROPERTY, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'number']
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

        /* this is a customer specific update I would say, remove from upstream
        $this->_db->query('UPDATE IGNORE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME . ' as d JOIN '
            . SQL_TABLE_PREFIX . Sales_Model_Address::TABLE_NAME . ' AS a ON a.' . Sales_Model_Address::FLD_DEBITOR_ID
            . ' = d.id SET d.number = a.' . Sales_Model_Address::FLD_CUSTOM1 . ' WHERE a.' . Sales_Model_Address::FLD_CUSTOM1 . ' IS NOT NULL AND a.' . Sales_Model_Address::FLD_CUSTOM1 . ' <> ""');

        $this->_db->query('UPDATE IGNORE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME . ' as d JOIN '
            . SQL_TABLE_PREFIX . Sales_Model_Customer::TABLE_NAME . ' AS c ON d.' . Sales_Model_Debitor::FLD_CUSTOMER_ID
            . ' = c.id JOIN ' . SQL_TABLE_PREFIX . Sales_Model_Address::TABLE_NAME . ' AS a ON a.' . Sales_Model_Address::FLD_CUSTOMER_ID
            . ' = c.id SET d.number = a.' . Sales_Model_Address::FLD_CUSTOM1 . ' WHERE a.' . Sales_Model_Address::FLD_CUSTOM1 . ' IS NOT NULL AND a.' . Sales_Model_Address::FLD_CUSTOM1 . ' <> ""');
        */

        $bucket = Sales_Model_Debitor::class . '#' . Sales_Model_Debitor::FLD_NUMBER . '#' . Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION};
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'numberable WHERE bucket = "' . $bucket . '"');
        $this->getDb()->query('INSERT INTO ' . SQL_TABLE_PREFIX . 'numberable (bucket, number) SELECT "' . $bucket . '", REPLACE(`' . Sales_Model_Debitor::FLD_NUMBER . '`, "DEB-", "") FROM ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.11', self::RELEASE017_UPDATE011);
    }

    public function update012()
    {
        if ($this->_backend->tableExists(Sales_Model_Invoice::TABLE_NAME) && $this->_backend->columnExists('costcenter_id', Sales_Model_Invoice::TABLE_NAME)) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Sales_Model_Invoice::TABLE_NAME)
                . ' CHANGE costcenter_id eval_dim_cost_center varchar(255) DEFAULT NULL');
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
            . SQL_TABLE_PREFIX . 'relations AS r ON c.id = r.own_id AND r.own_model = "' . Sales_Model_PurchaseInvoice::class
            . '" AND r.own_backend = "Sql" AND r.`type` = "COST_CENTER" AND related_model = "Tinebase_Model_CostCenter" SET c.eval_dim_cost_center = r.related_id');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND related_model = "'
            . Sales_Model_PurchaseInvoice::class . '" AND `type` = "COST_CENTER"');
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND own_model = "'
            . Sales_Model_PurchaseInvoice::class . '" AND `type` = "COST_CENTER"');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.13', self::RELEASE017_UPDATE013);
    }

    public function update014()
    {
        $this->setTableVersion('sales_numbers', 2);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.14', self::RELEASE017_UPDATE014);
    }

    public function update015()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.15', self::RELEASE017_UPDATE015);
    }

    public function update016()
    {
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.16', self::RELEASE017_UPDATE016);
    }

    public function update017()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ]);

        foreach ([
             Sales_Model_Document_Invoice::TABLE_NAME,
             Sales_Model_Document_Offer::TABLE_NAME,
             Sales_Model_Document_Order::TABLE_NAME,
         ] as $table) {
            $this->getDb()->update(SQL_TABLE_PREFIX . $table, [
                Sales_Model_Document_Abstract::FLD_POSITIONS_GROSS_SUM => new Zend_Db_Expr(Sales_Model_Document_Abstract::FLD_GROSS_SUM . ' + ' . Sales_Model_Document_Abstract::FLD_INVOICE_DISCOUNT_SUM)
            ], '1=1');
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.17', self::RELEASE017_UPDATE017);
    }

    public function update018()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Boilerplate::class,
            Sales_Model_Document_Boilerplate::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.18', self::RELEASE017_UPDATE018);
    }

    public function update019()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Invoice::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.19', self::RELEASE017_UPDATE019);
    }

    public function update020()
    {
        $this->divisionUpdate();
        
        Setup_SchemaTool::updateSchema([
            Sales_Model_Debitor::class,
            Sales_Model_Division::class,
            Sales_Model_DivisionBankAccount::class,
            Sales_Model_Document_Debitor::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.20', self::RELEASE017_UPDATE020);
    }

    protected function divisionUpdate()
    {
        if ($this->_backend->tableExists(Sales_Model_Division::TABLE_NAME)
                && $this->_backend->columnExists(Sales_Model_Division::FLD_NAME, Sales_Model_Division::TABLE_NAME)) {
            if (!$this->_backend->tableExists(Sales_Model_DivisionBankAccount::TABLE_NAME)) {
                Setup_SchemaTool::updateSchema([
                    Sales_Model_DivisionBankAccount::class,
                ]);
            }
            return;
        }

        $mc = Sales_Model_Division::getConfiguration();
        $fieldsProp = new ReflectionProperty(Tinebase_ModelConfiguration::class, '_fields');
        $fieldsProp->setAccessible(true);
        $fields = $fieldsProp->getValue($mc);
        $fields[Sales_Model_Division::FLD_NAME][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_ADDR_PREFIX1][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_ADDR_POSTAL][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_ADDR_LOCALITY][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_ADDR_COUNTRY][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_CONTACT_NAME][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_CONTACT_EMAIL][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_CONTACT_PHONE][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fields[Sales_Model_Division::FLD_VAT_NUMBER][Tinebase_ModelConfiguration_Const::DEFAULT_VAL] = '';
        $fieldsProp->setValue($mc, $fields);

        Setup_SchemaTool::updateSchema([
            Sales_Model_Division::class,
            Sales_Model_DivisionBankAccount::class,
        ]);

        Sales_Model_Division::resetConfiguration();
    }

    public function update021()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Debitor::class,
            Sales_Model_Division::class,
            Sales_Model_Document_Debitor::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_EDocument_EAS::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.21', self::RELEASE017_UPDATE021);
    }

    public function update022()
    {
        Sales_Setup_Initialize::initializeEDocumentEAS();

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.22', self::RELEASE017_UPDATE022);
    }

    public function update023()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_AttachedDocument::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_DispatchHistory::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.23', self::RELEASE017_UPDATE023);
    }

    public function update024()
    {
        if ($this->_backend->tableExists('document_attached_document')) {
            $this->renameTable('document_attached_document', Sales_Model_Document_AttachedDocument::TABLE_NAME);
        }
        if ($this->_backend->tableExists('document_dispatch_history')) {
            $this->renameTable('document_dispatch_history', Sales_Model_Document_DispatchHistory::TABLE_NAME);
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.24', self::RELEASE017_UPDATE024);
    }

    public function update025()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_DocumentPosition_Offer::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.25', self::RELEASE017_UPDATE025);
    }

    public function update026()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Offer::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Order::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_DocumentPosition_Invoice::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.26', self::RELEASE017_UPDATE026);
    }

    public function update027()
    {
        /** @var Sales_Model_Division $division */
        foreach (Sales_Controller_Division::getInstance()->getAll() as $division) {


            /**
             * @var Tinebase_Record_Interface $model
             * @var array<string> $properties
             */
            foreach([
                        Sales_Model_Document_Delivery::class => [Sales_Model_Document_Delivery::FLD_DOCUMENT_NUMBER, Sales_Model_Document_Delivery::FLD_DOCUMENT_PROFORMA_NUMBER],
                        Sales_Model_Document_Invoice::class => [Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER, Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER],
                        Sales_Model_Document_Offer::class => [Sales_Model_Document_Offer::FLD_DOCUMENT_NUMBER],
                        Sales_Model_Document_Order::class => [Sales_Model_Document_Order::FLD_DOCUMENT_NUMBER],
                    ] as $model => $properties) {
                $fields = $model::getConfiguration()->getFields();
                foreach ($properties as $property) {
                    $config = $fields[$property];
                    $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::NO_AUTOCREATE] = true;
                    $record = new $model([
                        Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY => new Sales_Model_Document_Category([
                            Sales_Model_Document_Category::FLD_DIVISION_ID => $division,
                        ], true),
                    ], true);

                    list($objectClass, $method) = explode('::', $config[TMCC::CONFIG][Tinebase_Numberable::CONFIG_OVERRIDE]);
                    $object = call_user_func($objectClass . '::getInstance');
                    $configOverride = call_user_func_array([$object, $method], [$record]);
                    $config[TMCC::CONFIG] = array_merge($config[TMCC::CONFIG], $configOverride);
                    $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY] = 'Division - ' . $division->getTitle();

                    if ($numCfg = Tinebase_Numberable::getCreateUpdateNumberableConfig($model, $property, $config)) {
                        $numCfg->{Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY} = 'Division - ' . $division->getId();
                        Tinebase_Controller_NumberableConfig::getInstance()->update($numCfg);
                    }
                }
            }


            $config = Sales_Model_Debitor::getConfiguration()->getFields()[Sales_Model_Debitor::FLD_NUMBER];
            $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::NO_AUTOCREATE] = true;
            $record = new Sales_Model_Debitor([
                Sales_Model_Debitor::FLD_DIVISION_ID => $division,
            ], true);
            $config[TMCC::CONFIG] = array_merge($config[TMCC::CONFIG], Sales_Controller_Debitor::getInstance()->numberConfigOverride($record));
            $config[TMCC::CONFIG][Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY] = 'Division - ' . $division->getTitle();

            if ($numCfg = Tinebase_Numberable::getCreateUpdateNumberableConfig(Sales_Model_Debitor::class, Sales_Model_Debitor::FLD_NUMBER, $config)) {
                $numCfg->{Tinebase_Model_NumberableConfig::FLD_ADDITIONAL_KEY} = 'Division - ' . $division->getId();
                Tinebase_Controller_NumberableConfig::getInstance()->update($numCfg);
            }
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.27', self::RELEASE017_UPDATE027);
    }

    public function update028(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Invoice::class,
            Sales_Model_Contract::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.28', self::RELEASE017_UPDATE028);
    }

    public function update029(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $mc = Sales_Model_Debitor::getConfiguration();
        $refProp = new ReflectionProperty($mc, '_fields');
        $refProp->setAccessible(true);
        $fields = $mc->getFields();
        $fields[Sales_Model_Debitor::FLD_PAYMENT_MEANS][TMCC::NULLABLE] = true;
        $refProp->setValue($mc, $fields);
        $mc = Sales_Model_Document_Debitor::getConfiguration();
        $fields = $mc->getFields();
        $fields[Sales_Model_Debitor::FLD_PAYMENT_MEANS][TMCC::NULLABLE] = true;
        $refProp->setValue($mc, $fields);

        Setup_SchemaTool::updateSchema([
            Sales_Model_Debitor::class,
            Sales_Model_Division::class,
            Sales_Model_Document_Debitor::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_EDocument_EAS::class,
            Sales_Model_EDocument_PaymentMeansCode::class,
            Sales_Model_Invoice::class,
        ]);

        if (Sales_Controller_EDocument_PaymentMeansCode::getInstance()->getAll()->count() === 0) {
            Sales_Setup_Initialize::initializeEDocumentPaymentMeansCode();
        }

        $debitor = new Sales_Model_Debitor([], true);
        $debitor->runConvertToData();

        $this->getDb()->update(SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME, [
            Sales_Model_Debitor::FLD_PAYMENT_MEANS => $debitor->{Sales_Model_Debitor::FLD_PAYMENT_MEANS},
        ]);
        $this->getDb()->update(SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME, [
            Sales_Model_Debitor::FLD_PAYMENT_MEANS => $debitor->{Sales_Model_Debitor::FLD_PAYMENT_MEANS},
        ]);

        Sales_Model_Debitor::resetConfiguration();
        Sales_Model_Document_Debitor::resetConfiguration();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Debitor::class,
            Sales_Model_Document_Debitor::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.29', self::RELEASE017_UPDATE029);
    }

    public function update030(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_EDocument_PaymentMeansCode::class,
        ]);

        if (Sales_Controller_EDocument_PaymentMeansCode::getInstance()->getAll()->count() === 0) {
            Sales_Setup_Initialize::initializeEDocumentPaymentMeansCode();
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.30', self::RELEASE017_UPDATE030);
    }

    public function update031(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Debitor::class,
            Sales_Model_Document_Debitor::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.31', self::RELEASE017_UPDATE031);
    }

    public function update032(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->_backend->tableExists(Sales_Model_Debitor::TABLE_NAME) &&
                ($this->_backend->columnExists('edocument_dispatch_type', Sales_Model_Debitor::TABLE_NAME)
                || $this->_backend->columnExists('edocument_transport', Sales_Model_Debitor::TABLE_NAME))) {
            if ($this->_backend->columnExists('edocument_transport', Sales_Model_Debitor::TABLE_NAME)) {
                $this->_db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME
                    . ' CHANGE COLUMN edocument_transport edocument_dispatch_type varchar(255) DEFAULT \'Sales_Model_EDocument_Dispatch_Email\'');
            }
            if (!$this->_backend->columnExists('edocument_dispatch_config', Sales_Model_Debitor::TABLE_NAME)) {
                $this->_db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME
                    . ' ADD COLUMN edocument_dispatch_config longtext NOT NULL');
            }
            if ($this->_backend->columnExists('edocument_transport', Sales_Model_Document_Debitor::TABLE_NAME)) {
                $this->_db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME
                    . ' CHANGE COLUMN edocument_transport edocument_dispatch_type varchar(255) DEFAULT \'Sales_Model_EDocument_Dispatch_Email\'');
            }
            if (!$this->_backend->columnExists('edocument_dispatch_config', Sales_Model_Document_Debitor::TABLE_NAME)) {
                $this->_db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME
                    . ' ADD COLUMN edocument_dispatch_config longtext NOT NULL');
            }
            
            foreach ([SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME, SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME] as $table) {
                $this->_db->query('UPDATE ' . $table . ' SET edocument_dispatch_type = "' . Sales_Model_EDocument_Dispatch_Manual::class . '" WHERE edocument_dispatch_type = "download"');
                $this->_db->query('UPDATE ' . $table . ' SET edocument_dispatch_type = "' . Sales_Model_EDocument_Dispatch_Email::class . '", edocument_dispatch_config = "{\\"document_types\\":[{\\"document_type\\":\\"paperslip\\"},{\\"document_type\\":\\"edocument\\"}]}" WHERE edocument_dispatch_type = "email"');
            }
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.32', self::RELEASE017_UPDATE032);
    }

    public function update033(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Debitor::class,
            Sales_Model_Division::class,
            Sales_Model_Document_AttachedDocument::class,
            Sales_Model_Document_Debitor::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_DispatchHistory::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ]);

        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Document_Invoice::TABLE_NAME . ' SET ' .
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS . ' = "DISPATCHED" WHERE ' .
            Sales_Model_Document_Invoice::FLD_INVOICE_STATUS . ' = "SHIPPED"');

        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . Sales_Model_Document_Offer::TABLE_NAME . ' SET ' .
            Sales_Model_Document_Offer::FLD_OFFER_STATUS . ' = "DISPATCHED" WHERE ' .
            Sales_Model_Document_Offer::FLD_OFFER_STATUS . ' = "RELEASED"');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.33', self::RELEASE017_UPDATE033);
    }

    public function update034(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_DispatchHistory::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.34', self::RELEASE017_UPDATE034);
    }

    public function update035(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Boilerplate::class,
            Sales_Model_Document_Boilerplate::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.35', self::RELEASE017_UPDATE035);
    }

    public function update036(): void
    {
        $this->divisionUpdate();
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.36', self::RELEASE017_UPDATE036);
    }

    public function update037(): void
    {
        foreach ([SQL_TABLE_PREFIX . Sales_Model_Debitor::TABLE_NAME, SQL_TABLE_PREFIX . Sales_Model_Document_Debitor::TABLE_NAME] as $table) {
            $this->_db->query('UPDATE ' . $table . ' SET edocument_dispatch_config = REPLACE(edocument_dispatch_config, \'"ubl\', \'"edocument\') WHERE edocument_dispatch_config LIKE \'%"document_type\\\\\\\\":\\\\\\\\"ubl\\\\\\\\"%\'');
            $this->_db->query('UPDATE ' . $table . ' SET edocument_dispatch_config = REPLACE(edocument_dispatch_config, \'"document_type":"ubl"\', \'"document_type":"edocument"\') WHERE edocument_dispatch_config LIKE \'%"document_type":"ubl"%\'');
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.37', self::RELEASE017_UPDATE037);
    }

    public function update038(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_DispatchHistory::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.38', self::RELEASE017_UPDATE038);
    }

    public function update039(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Sales_Scheduler_Task::addEMailDispatchResponseMinutelyTask(Tinebase_Core::getScheduler());

        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_DispatchHistory::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.39', self::RELEASE017_UPDATE039);
    }

    public function update040(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_EDocument_VATEX::class,
            Sales_Model_EDocument_VATEXLocalization::class,
        ]);

        Sales_Setup_Initialize::initializeEDocumentVATEX();

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.40', self::RELEASE017_UPDATE040);
    }

    public function update041(): void
    {
        /** @var Tinebase_Record_Interface $model */
        foreach ([
                    Sales_Model_Customer::class,
                    Sales_Model_Document_Invoice::class,
                    Sales_Model_Document_Offer::class,
                    Sales_Model_Document_Order::class,
                 ] as $model) {
            foreach ([
                        'taxable' => 'standard',
                        'nonTaxable' => 'outsideTaxScope',
                        'export' => 'freeExportItem',
                     ] as $old => $new) {
                $this->_db->update(
                    SQL_TABLE_PREFIX . $model::getConfiguration()->getTableName(),
                    [Sales_Model_Customer::FLD_VAT_PROCEDURE => $new],
                    Sales_Model_Customer::FLD_VAT_PROCEDURE . ' = "' . $old . '"'
                );
            }
        }

        $ae = Sales_Controller_EDocument_VATEX::getInstance()->getByCode('vatex-eu-ae');
        $g = Sales_Controller_EDocument_VATEX::getInstance()->getByCode('vatex-eu-g');
        $o = Sales_Controller_EDocument_VATEX::getInstance()->getByCode('vatex-eu-o');
        foreach ([
                     Sales_Model_Document_Invoice::class,
                     Sales_Model_Document_Offer::class,
                     Sales_Model_Document_Order::class,
                 ] as $model) {
            $this->_db->update(
                SQL_TABLE_PREFIX . $model::getConfiguration()->getTableName(),
                [Sales_Model_Document_Abstract::FLD_VATEX_ID => $ae->getId()],
                Sales_Model_Customer::FLD_VAT_PROCEDURE . ' = "' . Sales_Config::VAT_PROCEDURE_REVERSE_CHARGE . '"'
            );
            $this->_db->update(
                SQL_TABLE_PREFIX . $model::getConfiguration()->getTableName(),
                [Sales_Model_Document_Abstract::FLD_VATEX_ID => $g->getId()],
                Sales_Model_Customer::FLD_VAT_PROCEDURE . ' = "' . Sales_Config::VAT_PROCEDURE_FREE_EXPORT_ITEM . '"'
            );
            $this->_db->update(
                SQL_TABLE_PREFIX . $model::getConfiguration()->getTableName(),
                [Sales_Model_Document_Abstract::FLD_VATEX_ID => $o->getId()],
                Sales_Model_Customer::FLD_VAT_PROCEDURE . ' = "' . Sales_Config::VAT_PROCEDURE_OUTSIDE_TAX_SCOPE . '"'
            );
        }

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.41', self::RELEASE017_UPDATE041);
    }

    public function update042(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Customer::class,
            Sales_Model_Document_Customer::class,
        ]);

        /** @var Tinebase_Record_Interface $model */
        foreach ([
                     Sales_Model_Document_Customer::class,
                 ] as $model) {
            foreach ([
                         'taxable' => 'standard',
                         'nonTaxable' => 'outsideTaxScope',
                         'export' => 'freeExportItem',
                     ] as $old => $new) {
                $this->_db->update(
                    SQL_TABLE_PREFIX . $model::getConfiguration()->getTableName(),
                    [Sales_Model_Customer::FLD_VAT_PROCEDURE => $new],
                    Sales_Model_Customer::FLD_VAT_PROCEDURE . ' = "' . $old . '"'
                );
            }
        }


        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.42', self::RELEASE017_UPDATE042);
    }

    public function update043(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        foreach ([SQL_TABLE_PREFIX . 'sales_suppliers', SQL_TABLE_PREFIX . Sales_Model_Customer::TABLE_NAME] as $table) {
            $this->_db->query('UPDATE ' . $table . ' SET currency = "EUR" WHERE LOWER(currency) IN ("euro", "eur")');
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.43', self::RELEASE017_UPDATE043);
    }

    public function update044(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '17.44', self::RELEASE017_UPDATE044);
    }
}
