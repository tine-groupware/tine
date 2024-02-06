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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
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
            . SQL_TABLE_PREFIX . 'relations AS r ON c.id = r.own_id AND r.own_model = "' . Sales_Model_Contract::class
            . '" AND r.own_backend = "Sql" AND r.`type` = "COST_CENTER" AND related_model = "Tinebase_Model_CostCenter" SET c.eval_dim_cost_center = r.related_id');

        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE own_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND related_model = "'
            . Timetracker_Model_Timeaccount::class . '" AND `type` = "COST_CENTER"');
        $this->getDb()->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'relations WHERE related_model = "Tinebase_Model_CostCenter" AND own_backend = "Sql" AND own_model = "'
            . Timetracker_Model_Timeaccount::class . '" AND `type` = "COST_CENTER"');

        $this->addApplicationUpdate(Timetracker_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }
}
