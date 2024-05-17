<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Inventory_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
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
        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        (new Inventory_Setup_Initialize())->initializeCostCenter();

        $this->_db->query('UPDATE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Inventory_Model_InventoryItem::TABLE_NAME)
            . ' AS ii LEFT JOIN ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Tinebase_Model_EvaluationDimensionItem::TABLE_NAME)
            . ' AS edi ON ii.eval_dim_cost_center = edi.id SET ii.eval_dim_cost_center = NULL WHERE edi.id IS NULL');

        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->_backend->columnExists('costcenter', Inventory_Model_InventoryItem::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Inventory_Model_InventoryItem::TABLE_NAME)
                . ' CHANGE costcenter eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName(Inventory_Config::APP_NAME)->getId(),
            Inventory_Model_InventoryItem::FLD_SERIAL_NUMBER, Inventory_Model_InventoryItem::class);
        
        if (null !== $cfc) {
            $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . Inventory_Model_InventoryItem::TABLE_NAME . ' AS i JOIN '
                . SQL_TABLE_PREFIX . 'customfield' . ' AS c '
                . 'ON i.id' . ' = c.record_id'
                . ' SET i.' . Inventory_Model_InventoryItem::FLD_SERIAL_NUMBER . ' = c.value'
                . ' WHERE c.customfield_id = "' . $cfc->getId() . '"');

            
            Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
        }

        Setup_SchemaTool::updateSchema( [
            Inventory_Model_InventoryItem::class,
            Inventory_Model_Type::class
        ] );
        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }
}
