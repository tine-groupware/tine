<?php

/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Tasks_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
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
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Tasks', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        /* we do this in update 002 now
        if (!$this->_backend->columnExists('source', 'tasks')) {
            $sql = $this->_backend->addAddCol('', 'tasks', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>source</name>
                    <type>text</type>
                    <length>40</length>
                    <default>null</default>
                </field>'));

            $this->getDb()->query($this->_backend->addAddCol($sql, 'tasks', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>source_model</name>
                    <type>text</type>
                    <length>100</length>
                    <default>null</default>
                </field>')));
        }

        if ($this->getTableVersion('tasks') < 12) {
            $this->setTableVersion('tasks', 12);
        }*/

        $this->addApplicationUpdate('Tasks', '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Tasks_Model_Attendee::class,
            Tasks_Model_Task::class,
            Tasks_Model_TaskDependency::class,
        ]);
        $this->addApplicationUpdate('Tasks', '16.2', self::RELEASE016_UPDATE002);
    }
}
