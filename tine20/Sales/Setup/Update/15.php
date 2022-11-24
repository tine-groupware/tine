<?php

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Sales_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE015_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE015_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE015_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE015_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE015_UPDATE007 = __CLASS__ . '::update007';
    const RELEASE015_UPDATE008 = __CLASS__ . '::update008';
    const RELEASE015_UPDATE009 = __CLASS__ . '::update009';
    const RELEASE015_UPDATE010 = __CLASS__ . '::update010';
    const RELEASE015_UPDATE011 = __CLASS__ . '::update011';
    const RELEASE015_UPDATE012 = __CLASS__ . '::update012';
    const RELEASE015_UPDATE013 = __CLASS__ . '::update013';
    const RELEASE015_UPDATE014 = __CLASS__ . '::update014';
    const RELEASE015_UPDATE015 = __CLASS__ . '::update015';
    const RELEASE015_UPDATE016 = __CLASS__ . '::update016';
    const RELEASE015_UPDATE017 = __CLASS__ . '::update017';
    const RELEASE015_UPDATE018 = __CLASS__ . '::update018';

    static protected $_allUpdates = [
        // this needs to be executed before TB struct update! cause we move the table from sales to tb
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE015_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
        ],
        // this needs to be executed before HR update, so we make it TB prio
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE015_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            // this one also maybe rather earlier than later, so prio TB
            self::RELEASE015_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE015_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE015_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE015_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE015_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE015_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
            self::RELEASE015_UPDATE013          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update013',
            ],
            self::RELEASE015_UPDATE014          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update014',
            ],
            self::RELEASE015_UPDATE015          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update015',
            ],
            self::RELEASE015_UPDATE016          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update016',
            ],
            self::RELEASE015_UPDATE017          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update017',
            ],
            self::RELEASE015_UPDATE018          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update018',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE015_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE015_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([Sales_Model_Product::class, Sales_Model_SubProductMapping::class,
            Sales_Model_Document_Offer::class, Sales_Model_Document_Boilerplate::class,
            Sales_Model_Document_Customer::class, Sales_Model_Document_Address::class,
            Sales_Model_Document_Order::class, Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('sales_customers') < 4) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
               <field>
                    <name>language</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>false</notnull>
                </field>
        ');
            $this->_backend->addCol('sales_customers', $declaration, 6);
            $this->setTableVersion('sales_customers', 4);
        }
        $this->addApplicationUpdate('Sales', '15.2', self::RELEASE015_UPDATE002);
    }

    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if (class_exists('HumanResources_Config') &&
            Tinebase_Application::getInstance()->isInstalled(HumanResources_Config::APP_NAME)) {
            $this->_backend->renameTable('sales_divisions', 'humanresources_division');
            Tinebase_Application::getInstance()->removeApplicationTable(Sales_Config::APP_NAME, 'sales_divisions');
            Tinebase_Application::getInstance()->removeApplicationTable(HumanResources_Config::APP_NAME,
                'humanresources_division');
        } else {
            $this->_backend->dropTable('sales_divisions', Sales_Config::APP_NAME);
        }
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.3', self::RELEASE015_UPDATE003);
    }

    public function update004()
    {
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX .
            'filter where `model` = "Sales_Model_Division" or `model` = "Sales_Model_DivisionFilter"');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.4', self::RELEASE015_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Delivery::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.5', self::RELEASE015_UPDATE005);
    }

    public function update006()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_ProductLocalization::class,
        ]);

        $lang = Sales_Config::getInstance()->{Sales_Config::LANGUAGES_AVAILABLE}->default;
        $db = $this->getDb();
        $tableName = SQL_TABLE_PREFIX . Sales_Model_ProductLocalization::getConfiguration()->getTableName();
        $schema = $db->describeTable($tableName);
        if (array_key_exists('name', $schema)) {
            foreach ($db->query('SELECT id, name, description, is_deleted FROM ' . SQL_TABLE_PREFIX .
                Sales_Model_Product::TABLE_NAME)->fetchAll(Zend_Db::FETCH_NUM) as $row) {
                $db->insert($tableName, [
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    Tinebase_Record_PropertyLocalization::FLD_RECORD_ID => $row[0],
                    Tinebase_Record_PropertyLocalization::FLD_TYPE => 'name',
                    Tinebase_Record_PropertyLocalization::FLD_TEXT => $row[1],
                    Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => $lang,
                    'is_deleted' => $row[3],
                ]);
                $db->insert($tableName, [
                    'id' => Tinebase_Record_Abstract::generateUID(),
                    Tinebase_Record_PropertyLocalization::FLD_RECORD_ID => $row[0],
                    Tinebase_Record_PropertyLocalization::FLD_TYPE => 'description',
                    Tinebase_Record_PropertyLocalization::FLD_TEXT => $row[2],
                    Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => $lang,
                    'is_deleted' => $row[3],
                ]);
            }
        }

        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.6', self::RELEASE015_UPDATE006);
    }

    public function update007()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.7', self::RELEASE015_UPDATE007);
    }

    public function update008()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.8', self::RELEASE015_UPDATE008);
    }

    public function update009()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Address::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.9', self::RELEASE015_UPDATE009);
    }

    public function update010()
    {
        // better safe than sorry, we do schema + content updates -> no transaction desired here
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'relations SET `own_model` = "'
            . Tinebase_Model_CostCenter::class . '" where `own_model` = "Sales_Model_CostCenter"');

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'relations SET `related_model` = "'
            . Tinebase_Model_CostCenter::class . '" where `related_model` = "Sales_Model_CostCenter"');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX .
            'filter where `model` = "Sales_Model_CostCenter" or `model` = "Sales_Model_CostCenterFilter"');

        if ($this->_backend->tableExists('sales_cost_centers')) {
            if ($this->_backend->columnExists('remark', 'sales_cost_centers')) {
                $this->_backend->alterCol('sales_cost_centers', new Setup_Backend_Schema_Field_Xml('<field>
                        <name>name</name>
                        <type>text</type>
                        <length>255</length>
                        <notnull>false</notnull>
                    </field>'), 'remark');
            }
            $this->getDb()->update(SQL_TABLE_PREFIX . 'sales_cost_centers', ['deleted_time' => '1970-01-01 00:00:00'], 'deleted_time IS NULL');
            $this->_backend->renameTable('sales_cost_centers', Tinebase_Model_CostCenter::TABLE_NAME);
        }
        Tinebase_Application::getInstance()->removeApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName(Sales_Config::APP_NAME), 'sales_cost_centers');

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.10', self::RELEASE015_UPDATE010);
    }

    public function update011()
    {
        try {
            $def = Tinebase_ImportExportDefinition::getInstance()->getByName('tinebase_import_costcenter_csv');
            Tinebase_ImportExportDefinition::getInstance()->delete([$def->getId()]);
        } catch (Tinebase_Exception_NotFound $tenf) {}
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.11', self::RELEASE015_UPDATE011);
    }

    public function update012()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Address::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.12', self::RELEASE015_UPDATE012);
    }

    public function update013()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Address::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.13', self::RELEASE015_UPDATE013);
    }

    public function update014()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
            Tinebase_Model_CostCenter::class,
            Sales_Model_ProductLocalization::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.14', self::RELEASE015_UPDATE014);
    }

    public function update015()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.15', self::RELEASE015_UPDATE015);
    }

    public function update016()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.16', self::RELEASE015_UPDATE016);
    }

    public function update017()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.17', self::RELEASE015_UPDATE017);
    }

    public function update018()
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
            Tinebase_Model_CostUnit::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '15.18', self::RELEASE015_UPDATE018);
    }
}
