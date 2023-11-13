<?php

/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class HumanResources_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        $freeTimeIds = HumanResources_Controller_FreeTime::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_FreeTime::class, [
                [TMFA::FIELD => 'employee_id', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'division_id', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                        [TMFA::FIELD => HumanResources_Model_Division::FLD_FREE_TIME_CAL, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => '/'],
                    ]],
                ]],
                [TMFA::FIELD => 'type', TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => 'allow_planning', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => true],
                ]],
            ]), null, false, true);

        $ctrl = HumanResources_Controller_FreeDay::getInstance();
        foreach ($ctrl->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_FreeDay::class, [
                    [TMFA::FIELD => 'freetime_id', TMFA::OPERATOR => 'in', TMFA::VALUE => $freeTimeIds],
                    [TMFA::FIELD => 'event', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => null],
                ])) as $freeDay) {
            $ctrl->inspectFreeDay($freeDay);
        }

        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            HumanResources_Model_Contract::class,
        ]);

        $this->getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_Contract::TABLE_NAME, [
            HumanResources_Model_Contract::FLD_VACATION_ENTITLEMENT_BASE => new Zend_Db_Expr($this->getDb()->quoteIdentifier('vacation_days'))
        ]);

        $contractCtrl = HumanResources_Controller_Contract::getInstance();
        $raii = $contractCtrl->assertPublicUsage();
        $contracts = $contractCtrl->getAll();
        (new Tinebase_Record_Expander(HumanResources_Model_Contract::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME,
            ],
        ]))->expand($contracts);
        foreach ($contracts as $contract) {
            $days = 0;
            foreach ($contract->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME}?->{HumanResources_Model_WorkingTimeScheme::FLDS_JSON}[0]['days'] ?? [] as $day) {
                if ($day > 0) ++$days;
            }
            if ($days > 0 && $days !== 5) {
                $contract->{HumanResources_Model_Contract::FLD_VACATION_ENTITLEMENT_DAYS} = $days;
                $this->getDb()->update(SQL_TABLE_PREFIX . HumanResources_Model_Contract::TABLE_NAME, [
                    HumanResources_Model_Contract::FLD_VACATION_ENTITLEMENT_DAYS => $days,
                ], $this->getDb()->quoteInto('WHERE id = ?', $contract->getId()));
            }
        }

        unset($raii);
        $this->addApplicationUpdate(HumanResources_Config::APP_NAME, '16.2', self::RELEASE016_UPDATE002);
    }
}
