<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

class HumanResources_Setup_Update_Release12 extends Setup_Update_Abstract
{
    public function update_0()
    {
        $this->updateSchema('HumanResources', array('HumanResources_Model_Account', 'HumanResources_Model_Contract',
            'HumanResources_Model_CostCenter', 'HumanResources_Model_Employee', 'HumanResources_Model_ExtraFreeTime',
            'HumanResources_Model_FreeDay', 'HumanResources_Model_FreeTime',
            'HumanResources_Model_WorkingTimeScheme'
        ));
        $this->setApplicationVersion('HumanResources', '12.1');
    }

    public function update_1()
    {
        $this->updateSchema('HumanResources', array('HumanResources_Model_Employee'));
        $this->setApplicationVersion('HumanResources', '12.2');
    }

    public function update_2()
    {
        $this->updateSchema('HumanResources', [HumanResources_Model_WorkingTimeScheme::class]);
        $this->setApplicationVersion('HumanResources', '12.3');
    }

    public function update_3()
    {
        $this->updateSchema('HumanResources', array(HumanResources_Model_DailyWTReport::class));
        $this->setApplicationVersion('HumanResources', '12.4');
    }

    public function update_4()
    {
        $this->updateSchema('HumanResources', array(HumanResources_Model_DailyWTReport::class));
        $this->setApplicationVersion('HumanResources', '12.5');
    }
}
