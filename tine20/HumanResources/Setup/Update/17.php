<?php

/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class HumanResources_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE017_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE017_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        (self::PRIO_NORMAL_APP_STRUCTURE - 1) => [
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE017_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE017_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE017_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->_backend->columnExists('cost_center_id', HumanResources_Model_CostCenter::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . HumanResources_Model_CostCenter::TABLE_NAME)
                . ' CHANGE cost_center_id eval_dim_cost_center varchar(255) DEFAULT NULL');
        }
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        HumanResources_Setup_Initialize::initializeCostCenterCostBearer();

        $this->_db->query('DELETE cc FROM ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . HumanResources_Model_CostCenter::TABLE_NAME)
            . ' AS cc LEFT JOIN ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . Tinebase_Model_EvaluationDimensionItem::TABLE_NAME)
            . ' AS edi ON cc.eval_dim_cost_center = edi.id WHERE edi.id IS NULL');

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        $this->_fixDivisionOfEmployees();
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    protected function _fixDivisionOfEmployees()
    {
        $division = HumanResources_Controller_Division::getInstance()->getAll()->getFirstRecord();
        if(!$division) {
            $division = HumanResources_Controller_Division::getInstance()->create(new HumanResources_Model_Division([
                'title' => 'auto generated',
            ]));
        }

        $this->_db->update(
            SQL_TABLE_PREFIX . HumanResources_Model_Employee::TABLE_NAME,
            ['division_id' => $division->getId()],
            'division_id is NULL');
    }

    public function update004()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        $this->_fixDivisionOfEmployees();
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Employee::class,
        ]);

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Contract::class,
        ]);
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.5', self::RELEASE017_UPDATE005);
    }

    public function update006(): void
    {
        $this->getDb()->update(
            SQL_TABLE_PREFIX . HumanResources_Model_FreeTime::TABLE_NAME,
            [
                HumanResources_Model_FreeTime::FLD_TYPE_STATUS => new Zend_Db_Expr('NULL'),
            ],
            "type = 'vacation' AND type_status IS NOT NULL"
        );

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '17.6', self::RELEASE017_UPDATE006);
    }
}
