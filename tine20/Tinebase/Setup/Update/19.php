<?php

/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class Tinebase_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE019_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE019_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE019_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE019_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE019_UPDATE006 = __CLASS__ . '::update006';
    protected const RELEASE019_UPDATE007 = __CLASS__ . '::update007';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT        => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_TINEBASE_STRUCTURE => [
            self::RELEASE019_UPDATE002 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update002',
            ],
            self::RELEASE019_UPDATE003 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update003',
            ],
            self::RELEASE019_UPDATE004 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update004',
            ],
            self::RELEASE019_UPDATE005 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update005',
            ],
            self::RELEASE019_UPDATE006 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update006',
            ],
            self::RELEASE019_UPDATE007 => [
                self::CLASS_CONST => self::class,
                self::FUNCTION_CONST => 'update007',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE019_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        if (!$this->_backend->columnExists('request_id', 'timemachine_modlog')) {
            $this->_backend->addCol('timemachine_modlog', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>request_id</name>
                    <type>text</type>
                    <length>6</length>
                    <notnull>false</notnull>
                </field>'));
            if ($this->getTableVersion('timemachine_modlog') < 6) {
                $this->setTableVersion('timemachine_modlog', 6);
            }
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    public function update002(): void
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Instance::class,
            Tinebase_Model_InstanceMailDomain::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
    }

    public function update003(): void
    {
        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Instance::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.3', self::RELEASE019_UPDATE003);
    }

    public function update004(): void
    {
        if (!$this->_backend->columnExists('show_result_count', 'filter')) {
            $this->_backend->addCol('filter', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>show_result_count</name>
                    <type>boolean</type>
                    <default>false</default>
                </field>'));
            if ($this->getTableVersion('filter') < 6) {
                $this->setTableVersion('filter', 6);
            }
        }
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.4', self::RELEASE019_UPDATE004);
    }

    public function update005(): void
    {
        $_purgeDateField = new Setup_Backend_Schema_Field_Xml(
            '<field>
                <name>purge_date</name>
                <type>date</type>
                <notnull>false</notnull>
            </field>');

        if (!$this->_backend->columnExists('purge_date', 'groups')) {
            $this->_backend->addCol('groups', $_purgeDateField);
            if ($this->getTableVersion('groups') < 11) {
                $this->setTableVersion('groups', 11);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'accounts')) {
            $this->_backend->addCol('accounts', $_purgeDateField);
            if ($this->getTableVersion('accounts') < 21) {
                $this->setTableVersion('accounts', 21);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'relations')) {
            $this->_backend->addCol('relations', $_purgeDateField);
            if ($this->getTableVersion('relations') < 10) {
                $this->setTableVersion('relations', 10);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'tags')) {
            $this->_backend->addCol('tags', $_purgeDateField);
            if ($this->getTableVersion('tags') < 11) {
                $this->setTableVersion('tags', 11);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'roles')) {
            $this->_backend->addCol('roles', $_purgeDateField);
            if ($this->getTableVersion('roles') < 6) {
                $this->setTableVersion('roles', 6);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'notes')) {
            $this->_backend->addCol('notes', $_purgeDateField);
            if ($this->getTableVersion('notes') < 7) {
                $this->setTableVersion('notes', 7);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'filter')) {
            $this->_backend->addCol('filter', $_purgeDateField);
            if ($this->getTableVersion('filter') < 7) {
                $this->setTableVersion('filter', 7);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'importexport_definition')) {
            $this->_backend->addCol('importexport_definition', $_purgeDateField);
            if ($this->getTableVersion('importexport_definition') < 17) {
                $this->setTableVersion('importexport_definition', 17);
            }
        }

        if (!$this->_backend->columnExists('purge_date', 'departments')) {
            $this->_backend->addCol('departments', $_purgeDateField);
            if ($this->getTableVersion('departments') < 3) {
                $this->setTableVersion('departments', 3);
            }
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.5', self::RELEASE019_UPDATE005);
    }

    public function update006(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
        
        Setup_SchemaTool::updateAllSchema();

        $task = Tinebase_Core::getScheduler()->getBackend()->getByProperty(Tinebase_Controller::class . '::removeObsoleteData');
        $task->config->setCron(Tinebase_Scheduler_Task::TASK_TYPE_DAILY);
        Tinebase_Core::getScheduler()->update($task);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.6', self::RELEASE019_UPDATE006);
    }

    public function update007(): void
    {
        try {
            $this->_backend->dropIndex('timemachine_modlog', 'seq');
        } catch (Zend_Db_Statement_Exception) {}

        if ($this->getTableVersion('timemachine_modlog') < 7) {
            try {
                $this->_backend->dropIndex('timemachine_modlog', 'unique-fields');
            } catch (Zend_Db_Statement_Exception) {}

            if ($this->_backend->columnExists('modified_attribute', 'timemachine_modlog')) {
                $this->_backend->dropCol('timemachine_modlog', 'modified_attribute');
            }
            if ($this->_backend->columnExists('old_value', 'timemachine_modlog')) {
                $this->_backend->dropCol('timemachine_modlog', 'old_value');
            }

            while ($ids = $this->_db->query('SELECT id FROM ' . SQL_TABLE_PREFIX . 'timemachine_modlog WHERE record_backend IS NULL LIMIT 10000')->fetchAll(Zend_Db::FETCH_COLUMN, 0)) {
                $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . 'timemachine_modlog SET record_backend = "Sql" WHERE record_backend IS NULL AND id IN (?)', $ids);
            }

            $sql = $this->_backend->addAlterCol('', 'timemachine_modlog', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>record_type</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>'));
            $sql = $this->_backend->addAlterCol($sql, 'timemachine_modlog', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>record_backend</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>'));
            $sql = $this->_backend->addAlterCol($sql, 'timemachine_modlog', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>record_id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>'));
            $this->getDb()->query($this->_backend->addAlterCol($sql, 'timemachine_modlog', new Setup_Backend_Schema_Field_Xml(
                '<field>
                    <name>seq</name>
                    <type>integer</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>')));

            $this->_backend->addIndex('timemachine_modlog', new Setup_Backend_Schema_Index_Xml(
                '<index>
                    <name>unique-fields</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <field>
                        <name>record_type</name>
                    </field>
                    <field>
                        <name>record_backend</name>
                    </field>
                    <field>
                        <name>record_id</name>
                    </field>
                    <field>
                        <name>seq</name>
                    </field>
                </index>'));

            $this->setTableVersion('timemachine_modlog', 7);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '19.7', self::RELEASE019_UPDATE007);
    }
}
