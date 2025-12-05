<?php

/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Tinebase_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = self::class . '::update000';
    protected const RELEASE018_UPDATE001 = self::class . '::update001';
    protected const RELEASE018_UPDATE002 = self::class . '::update002';
    protected const RELEASE018_UPDATE003 = self::class . '::update003';
    protected const RELEASE018_UPDATE004 = self::class . '::update004';
    protected const RELEASE018_UPDATE005 = self::class . '::update005';
    protected const RELEASE018_UPDATE006 = self::class . '::update006';
    protected const RELEASE018_UPDATE007 = self::class . '::update007';
    protected const RELEASE018_UPDATE008 = self::class . '::update008';
    protected const RELEASE018_UPDATE009 = self::class . '::update009';
    protected const RELEASE018_UPDATE010 = self::class . '::update010';
    protected const RELEASE018_UPDATE011 = self::class . '::update011';
    protected const RELEASE018_UPDATE012 = self::class . '::update012';
    protected const RELEASE018_UPDATE013 = self::class . '::update013';
    protected const RELEASE018_UPDATE014 = self::class . '::update014';
    protected const RELEASE018_UPDATE015 = self::class . '::update015';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_EVERYTHING => [
            self::RELEASE018_UPDATE003 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update003',
            ],
            self::RELEASE018_UPDATE004 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update004',
            ],
        ],
        self::PRIO_TINEBASE_STRUCTURE => [
            self::RELEASE018_UPDATE001 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update001',
            ],
            self::RELEASE018_UPDATE002 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update002',
            ],
            self::RELEASE018_UPDATE005 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update005',
            ],
            self::RELEASE018_UPDATE007 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update007',
            ],
            self::RELEASE018_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE018_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE018_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
            self::RELEASE018_UPDATE013          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update013',
            ],
            self::RELEASE018_UPDATE014          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update014',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE          => [
            self::RELEASE018_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
            self::RELEASE018_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE018_UPDATE015          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update015',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        if ($this->getTableVersion('accounts') < 20) {
            $declaration = new Setup_Backend_Schema_Field_Xml('
                <field>
                    <name>login_failures</name>
                    <type>text</type>
                    <length>4000</length>
                </field>
            ');
            $this->_backend->alterCol('accounts', $declaration);
            $this->setTableVersion('accounts', 20);
        }

        Tinebase_Core::getDb()->query('UPDATE ' . SQL_TABLE_PREFIX . 'accounts SET login_failures = ' .
            'JSON_OBJECT("JSON-RPC", CAST(login_failures AS INTEGER)) WHERE login_failures IS NOT NULL');
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_SchedulerTask::class,
        ]);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003()
    {
        /*if ($this->getTableVersion('notes') < 5) {
            $this->_backend->addCol('notes', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>note_visibility</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                </field>'));
            $this->setTableVersion('notes', 5);
        }*/

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004()
    {
        if ($this->_backend->columnExists('note_visibility', 'notes')) {
            $this->_backend->dropCol('notes', 'note_visibility');
        }

        if ($this->getTableVersion('notes') < 6) {
            $this->_backend->addCol('notes', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>restricted_to</name>
                    <type>text</type>
                    <length>64</length>
                    <default>NULL</default>
                </field>'));
            $this->setTableVersion('notes', 6);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_LogEntry::class,
            Tinebase_Model_SchedulerTask::class,
            Tinebase_Model_Tree_FlySystem::class,
        ]);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }

    public function update006()
    {
        Tinebase_Scheduler_Task::addCleanUpRelationTask(Tinebase_Core::getScheduler());

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.6', self::RELEASE018_UPDATE006);
    }

    public function update007(): void
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_SchedulerTask::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.7', self::RELEASE018_UPDATE007);
    }

    public function update008(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $db = Tinebase_Core::getDb();
        $select = $db->select()
            ->from(SQL_TABLE_PREFIX . 'scheduler_task', ['id', 'config']);
        $tasks = $db->fetchAll($select);
        $tasksByAppId = [];
        foreach ($tasks as $task) {
            try {
                $config = json_decode($task['config'], true);
                $applicationId = null;
                foreach ($config['callables'] as $callable) {
                    $class = $callable['controller'] ?? $callable['class'] ?? null;
                    if ($class) {
                        $parts = explode('_', $class);
                        $applicationName = $parts[0];

                        try {
                            $application = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
                            $applicationId = $application ? $application->getId() : Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId();
                            break;
                        } catch (Exception $e) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $e);
                        }
                    }
                }
                if ($applicationId) {
                    if (!isset($tasksByAppId[$applicationId])) {
                        $tasksByAppId[$applicationId] = [];
                    }
                    $tasksByAppId[$applicationId][] = $task['id'];
                }
            } catch (Exception $e) {
                continue;
            }
        }

        foreach ($tasksByAppId as $applicationId => $taskIds) {
            $taskIdsList = implode('","', $taskIds);
            $db->query('UPDATE ' . $db->quoteIdentifier(SQL_TABLE_PREFIX . 'scheduler_task') . ' SET ' .
                'application_id = "' . $applicationId . '" WHERE id IN ("' . $taskIdsList . '")');
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.8', self::RELEASE018_UPDATE008);
    }

    public function update009(): void
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_AppPassword::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.9', self::RELEASE018_UPDATE009);
    }

    public function update010(): void
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_TwigTemplate::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.10', self::RELEASE018_UPDATE010);
    }

    public function update011(): void
    {
        $select = $this->getDb()->select()
            ->from(['rr' => SQL_TABLE_PREFIX . 'role_rights'], ['rr.role_id', 'rr.application_id'])
            ->joinLeft(
                ['rr2' => SQL_TABLE_PREFIX . 'role_rights'],
                'rr.role_id = rr2.role_id AND rr.application_id = rr2.application_id AND rr2.right = "twig"',
                []
            )
            ->where('rr.right = "admin" AND rr2.id IS NULL');

        foreach ($select->query(Zend_Db::FETCH_NUM)->fetchAll() as $row) {
            $this->getDb()->insert(SQL_TABLE_PREFIX . 'role_rights', [
                'id' => Tinebase_Record_Abstract::generateUID(),
                'role_id' => $row[0],
                'application_id' => $row[1],
                'right' => 'twig',
            ]);
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.11', self::RELEASE018_UPDATE011);
    }

    public function update012(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_WebDavIssue::class,
        ]);

        Tinebase_Scheduler_Task::addReportWebDavIssuesTask(Tinebase_Scheduler::getInstance());

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.12', self::RELEASE018_UPDATE012);
    }

    public function update013(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_BatchJob::class,
            Tinebase_Model_BatchJobHistory::class,
            Tinebase_Model_BatchJobStep::class,
        ]);

        Tinebase_Scheduler_Task::addBatchJobMaintenanceTask(Tinebase_Core::getScheduler());

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.13', self::RELEASE018_UPDATE013);
    }

    public function update014(): void
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_CloudAccount::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.14', self::RELEASE018_UPDATE014);
    }

    public function update015(): void
    {
        if (!Tinebase_Core::isReplica()) {
            $cfc = new Tinebase_CustomField_Config();
            $cfc->setAllCFs();
            $result = $cfc->search(new Tinebase_Model_CustomField_ConfigFilter([
                ['field' => 'name', 'operator' => 'startswith', 'value' => 'eval_dim_'],
            ], '', ['ignoreAcl' => true]));

            foreach ($result as $cFConfig) {
                $definition = $cFConfig->definition;
                $definition[Tinebase_Model_CustomField_Config::DEF_FIELD][Tinebase_ModelConfiguration_Const::OWNING_APP] = Tinebase_Config::APP_NAME;
                $cFConfig->definition = $definition;
                Tinebase_CustomField::getInstance()->updateCustomField($cFConfig);
            }
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '18.15', self::RELEASE018_UPDATE015);
    }
}
