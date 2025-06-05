<?php

/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Timetracker_Setup_Update_17 extends Setup_Update_Abstract
{
    public const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    public const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    public const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    public const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    public const RELEASE017_UPDATE004 = __CLASS__ . '::update004';
    public const RELEASE017_UPDATE005 = __CLASS__ . '::update005';
    public const RELEASE017_UPDATE006 = __CLASS__ . '::update006';
    public const RELEASE017_UPDATE007 = __CLASS__ . '::update007';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE017_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE017_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE017_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE017_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }


    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if (!Tinebase_Core::isReplica()) {
            Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
                Timetracker_Model_Timeaccount::class,
            ]);
        }

        $this->getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . Timetracker_Model_Timeaccount::TABLE_NAME . ' AS c JOIN '
            . SQL_TABLE_PREFIX . 'relations AS r ON c.id = r.own_id AND r.own_model = "' . Timetracker_Model_Timeaccount::class
            . '" AND r.own_backend = "Sql" AND r.`type` = "COST_CENTER" AND related_model = "Tinebase_Model_CostCenter" SET c.eval_dim_cost_center = r.related_id');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND related_model = "'
            . Timetracker_Model_Timeaccount::class . '" AND `type` = "COST_CENTER"');
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND own_model = "'
            . Timetracker_Model_Timeaccount::class . '" AND `type` = "COST_CENTER"');

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }
    
    public function update002()
    {
        $stateRepo = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_State',
            'tableName' => 'state',
        ));

        $states = $stateRepo->search(new Tinebase_Model_StateFilter(array(
            array('field' => 'state_id', 'operator' => 'in', 'value' => [
                "Timetracker-Timesheet-GridPanel-Grid_large",
                "Timetracker-Timesheet-GridPanel-Grid_big",
            ]),
        )));

        if (count($states) > 0) {
            $stateRepo->delete($states->getId());
        }
        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);

        $initalize = new Timetracker_Setup_Initialize();
        $method = new ReflectionMethod(Timetracker_Setup_Initialize::class, '_initializeSystemCFs');
        $method->setAccessible(true);
        $method->invoke($initalize);

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timeaccount::class,
        ]);

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.5', self::RELEASE017_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            Timetracker_Model_Timesheet::class,
        ]);

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.6', self::RELEASE017_UPDATE006);
    }

    public function update007()
    {
        $tsBackend = Timetracker_Controller_Timesheet::getInstance()->getBackend();
        
        if (!empty($delegator = Timetracker_Config::getInstance()->{Timetracker_Config::TS_CLEARED_AMOUNT_DELEGATOR})) {
            $fn = fn($ts) => call_user_func($delegator, $ts, $ts);
        } else {
            $tas = Timetracker_Controller_Timeaccount::getInstance()->getBackend()->getAll();
            $fn = function($ts) use($tas) {
                if ($ta = $tas->getById($ts->timeaccount_id)) {
                    $ts->{Timetracker_Model_Timesheet::FLD_RECORDED_AMOUNT} = round(($ts->accounting_time / 60) * (float)$ta->price, 2);
                }
            };
        }

        /** @var Timetracker_Model_Timesheet $ts */
        foreach ($tsBackend->getAll() as $ts) {
            $fn($ts);
            if ($ts->isDirty()) {
                $tsBackend->update($ts);
            }
        }

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.7', self::RELEASE017_UPDATE007);
    }
}
