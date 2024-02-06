<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Tinebase_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE016_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE016_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE016_UPDATE005 = __CLASS__ . '::update005';
    const RELEASE016_UPDATE006 = __CLASS__ . '::update006';
    const RELEASE016_UPDATE007 = __CLASS__ . '::update007';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE => [
            self::RELEASE016_UPDATE001 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update001',
            ],
            self::RELEASE016_UPDATE002 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update002',
            ],
            self::RELEASE016_UPDATE004 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update004',
            ],
            self::RELEASE016_UPDATE005 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update005',
            ],
            self::RELEASE016_UPDATE006 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update006',
            ],
            self::RELEASE016_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE => [
            self::RELEASE016_UPDATE000 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update000',
            ],
            self::RELEASE016_UPDATE003 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update003',
            ]
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('customfield_config') < 7) {
            $this->_backend->dropForeignKey('customfield_config', 'config_customfields::application_id--applications::id');
            $this->_backend->dropIndex('customfield_config', 'application_id-name');
            $this->_backend->addIndex('customfield_config', new Setup_Backend_Schema_Index_Xml(
                '<index>
                        <name>application_id-name</name>
                        <unique>true</unique>
                        <field>
                            <name>application_id</name>
                        </field>
                        <field>
                            <name>model</name>
                        </field>
                        <field>
                            <name>name</name>
                        </field>
                    </index>'));
            $this->_backend->addForeignKey('customfield_config', new Setup_Backend_Schema_Index_Xml(
                '<index>
                        <name>config_customfields::application_id--applications::id</name>
                        <field>
                            <name>application_id</name>
                        </field>
                        <foreign>true</foreign>
                        <reference>
                            <table>applications</table>
                            <field>id</field>
                            <ondelete>CASCADE</ondelete>
                        </reference>
                    </index>'));

            $this->setTableVersion('customfield_config', 7);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.1', self::RELEASE016_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_BankAccount::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.2', self::RELEASE016_UPDATE002);
    }

    public function update003()
    {
        Tinebase_Setup_Initialize::addSchedulerTasks();
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.3', self::RELEASE016_UPDATE003);
    }

    /**
     * add sales schedule tasks
     */
    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_SchedulerTask::class
        ]);
        Tinebase_TransactionManager::getInstance()->rollBack();
        $scheduler = Tinebase_Core::getScheduler();
        if (Tinebase_Application::getInstance()->isInstalled('Sales')) {
            Sales_Scheduler_Task::addCreateAutoInvoicesDailyTask($scheduler);
            Sales_Scheduler_Task::addCreateAutoInvoicesMonthlyTask($scheduler);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.4', self::RELEASE016_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_AppPassword::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.5', self::RELEASE016_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Alarm::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.6', self::RELEASE016_UPDATE006);
    }

    public function update007()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_SchedulerTask::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.7', self::RELEASE016_UPDATE007);
    }
}
