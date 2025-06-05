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
    public const RELEASE016_UPDATE000 = self::class . '::update000';
    public const RELEASE016_UPDATE001 = self::class . '::update001';
    public const RELEASE016_UPDATE002 = self::class . '::update002';
    public const RELEASE016_UPDATE003 = self::class . '::update003';
    public const RELEASE016_UPDATE004 = self::class . '::update004';
    public const RELEASE016_UPDATE005 = self::class . '::update005';
    public const RELEASE016_UPDATE006 = self::class . '::update006';
    public const RELEASE016_UPDATE007 = self::class . '::update007';
    public const RELEASE016_UPDATE008 = self::class . '::update008';
    public const RELEASE016_UPDATE009 = self::class . '::update009';

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
            self::RELEASE016_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
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
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ]
        ]
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        if ($this->getTableVersion('customfield_config') < 7) {
            try {
                $this->_backend->dropForeignKey('customfield_config', 'config_customfields::application_id--applications::id');
            } catch (Exception $e) {
                // might not exist
                Tinebase_Exception::log($e);
            }
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

    public function update008()
    {
        if ($this->getTableVersion('customfield_config') < 8) {
            $this->_backend->alterCol('customfield_config', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>definition</name>
                    <length>2147483647</length>
                    <type>text</type>
                </field>'));
            $this->setTableVersion('customfield_config', 8);
        }

        $this->addApplicationUpdate('Tinebase', '16.8', self::RELEASE016_UPDATE008);
    }
    public function update009()
    {
        if (Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            $pageNumber = 0;
            $pageCount = 10;
            $counter = 0;
            $models = [
                ['model' => 'Felamimail_Model_Account', 'application' => 'Felamimail']
            ];
            foreach ($models as $model) {
                do {
                    $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'timemachine_modlog')
                        ->limitPage(++$pageNumber, $pageCount)
                        ->where('new_value like "%\"password\":%"')
                        ->where($this->_db->quoteIdentifier('application_id') . ' = ?',
                            Tinebase_Application::getInstance()->getApplicationByName($model['application'])->getId())
                        ->where($this->_db->quoteIdentifier('record_type') . ' = ?', $model['model']);
                    $stmt = $select->query();
                    $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

                    foreach ($rows as $row) {
                        if (!str_contains((string) $row['new_value'], '"password":null')) {
                            Tinebase_Core::getDB()->update(SQL_TABLE_PREFIX . 'timemachine_modlog', [
                                'new_value' => preg_replace('/"password":"[^"]+"/', '"password":"******"', $row['new_value'])
                            ], 'id = ' . Tinebase_Core::getDb()->quote($row['id']));
                            $counter++;
                        }
                    }
                } while (count($rows) >= $pageCount);

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' Updated ' . $counter . ' modlog records from record_type'
                    . $model['model']);
            }
        }

        $this->addApplicationUpdate('Tinebase', '16.9', self::RELEASE016_UPDATE009);
    }
}
