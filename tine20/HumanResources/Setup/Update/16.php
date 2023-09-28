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

    static protected $_allUpdates = [
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
}
