<?php

/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jan Evers <j.evers@metaways.de>
 */
class CrewScheduling_Setup_Update_1 extends Setup_Update_Abstract
{
    const RELEASE001_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE001_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE001_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE001_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $roles = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll();
        foreach ($roles as $role) {
            CrewScheduling_Controller_SchedulingRole::getInstance()->update($role);
        }

        $this->addApplicationUpdate('CrewScheduling', '1.14', self::RELEASE001_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema(
            [
                CrewScheduling_Model_SchedulingRole::class,
                CrewScheduling_Model_RequiredGroups::class,
                CrewScheduling_Model_AttendeeRole::class
            ]);

        $this->addApplicationUpdate('CrewScheduling', '1.15', self::RELEASE001_UPDATE002);
    }
}
