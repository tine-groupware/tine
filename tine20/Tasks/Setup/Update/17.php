<?php

/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Tasks_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_UPDATE     => [
            // needs to run before \Timetracker_Setup_Update_17::update003
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Tasks_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->_db->update(SQL_TABLE_PREFIX . 'tasks', ['percent' => 0],
            $this->_db->quoteIdentifier('percent') . ' IS NULL');

        Setup_SchemaTool::updateSchema([
            Tasks_Model_Task::class,
        ]);

        $this->addApplicationUpdate(Tasks_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues = array(
            'account_id'        => null,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME)->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My responsibility",                      // _("My responsibility")
            'description'       => "All tasks that I am responsible for",   // _("All tasks that I am responsible for")
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "To be done for me",                      // _("All tasks for me")
            'description'       => "All tasks to be done for me",   // _("All tasks that I am responsible for")
            'filters'           => array(
                array('field' => 'tasksDue',    'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "To be done for me this week",
            'description'       => "To be done for me this week", // _("To be done for me this week")
            'filters'           => array(
                array('field' => 'due',         'operator' => 'within', 'value' => 'weekThis'),
                array('field' => 'tasksDue',    'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT),
            )
        ))));

        if ($pf = Tinebase_PersistentFilter::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(
                array_merge(['name' => 'My open tasks'], $commonValues)))->getFirstRecord()) {
            $pfe->getBackend()->delete($pf->getId());
        }
        if ($pf = Tinebase_PersistentFilter::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(
                array_merge(['name' => 'My open tasks this week'], $commonValues)))->getFirstRecord()) {
            $pfe->getBackend()->delete($pf->getId());
        }
        if ($pf = Tinebase_PersistentFilter::getInstance()->search(new Tinebase_Model_PersistentFilterFilter(
                array_merge(['name' => 'All tasks for me'], $commonValues)))->getFirstRecord()) {
            $pfe->getBackend()->delete($pf->getId());
        }

        $this->addApplicationUpdate(Tasks_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    /**
     * remove obsolete import definition tasks_import_demo_csv
     *
     * @return void
     * @throws Exception
     */
    public function update003(): void
    {
        try {
            $def = Tinebase_ImportExportDefinition::getInstance()->getByName('tasks_import_demo_csv');
            if (! $def->skip_upstream_updates) {
                Tinebase_ImportExportDefinition::getInstance()->delete($def->getId());
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }

        $this->addApplicationUpdate(Tasks_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    public function update004()
    {
        if (class_exists('Timetracker_Config') && Tinebase_Application::getInstance()->isInstalled(Timetracker_Config::APP_NAME)) {
            Tasks_Setup_Initialize::applicationInstalled(
                Tinebase_Application::getInstance()->getApplicationByName(Timetracker_Config::APP_NAME)
            );
        }
        $this->addApplicationUpdate(Tasks_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }
}
