<?php

/**
 * tine Groupware
 *
 * @package     EventManager
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class EventManager_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE018_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE018_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE018_UPDATE006 = __CLASS__ . '::update006';
    protected const RELEASE018_UPDATE007 = __CLASS__ . '::update007';
    protected const RELEASE018_UPDATE008 = __CLASS__ . '::update008';
    protected const RELEASE018_UPDATE009 = __CLASS__ . '::update009';
    protected const RELEASE018_UPDATE010 = __CLASS__ . '::update010';
    protected const RELEASE018_UPDATE011 = __CLASS__ . '::update011';
    protected const RELEASE018_UPDATE012 = __CLASS__ . '::update012';
    protected const RELEASE018_UPDATE013 = __CLASS__ . '::update013';
    protected const RELEASE018_UPDATE014 = __CLASS__ . '::update014';
    protected const RELEASE018_UPDATE015 = __CLASS__ . '::update015';
    protected const RELEASE018_UPDATE016 = __CLASS__ . '::update016';
    protected const RELEASE018_UPDATE017 = __CLASS__ . '::update017';
    protected const RELEASE018_UPDATE018 = __CLASS__ . '::update018';


    protected static $_allUpdates = [
        self::PRIO_TINEBASE_UPDATE        => [
            self::RELEASE018_UPDATE018          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update018',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE018_UPDATE011          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update011',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE018_UPDATE017          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update017',
            ],
            self::RELEASE018_UPDATE016          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update016',
            ],
            self::RELEASE018_UPDATE015          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update015',
            ],
            self::RELEASE018_UPDATE014          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update014',
            ],
            self::RELEASE018_UPDATE013          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update013',
            ],
            self::RELEASE018_UPDATE012          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update012',
            ],
            self::RELEASE018_UPDATE010          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update010',
            ],
            self::RELEASE018_UPDATE009          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update009',
            ],
            self::RELEASE018_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
            self::RELEASE018_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Appointment::class,
            EventManager_Model_Event::class,
            EventManager_Model_Option::class,
            EventManager_Model_Registration::class,
            EventManager_Model_Selection::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002()
    {
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_Registration::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004()
    {
        $this->_backend->dropTable('eventmanager_options_rule', EventManager_Config::APP_NAME);
        EventManager_Setup_Initialize::createEventFolder();
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Option::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_Registration::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.6', self::RELEASE018_UPDATE006);
    }

    public function update007()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_Registration::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.7', self::RELEASE018_UPDATE007);
    }

    public function update008()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Registration::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.8', self::RELEASE018_UPDATE008);
    }

    public function update009()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Registration::class,
            EventManager_Model_Register_Contact::class,
            EventManager_Model_Register_ContactPropertiesAddress::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.9', self::RELEASE018_UPDATE009);
    }

    public function update010()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_Registration::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.10', self::RELEASE018_UPDATE010);
    }

    public function update011()
    {
        if (!Tinebase_Core::isReplica()) {
            $container_id = EventManager_Config::getInstance()
                ->get(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER);
            if ($container_id) {
                Tinebase_Container::getInstance()->deleteContainer($container_id);
            }
        }
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.11', self::RELEASE018_UPDATE011);
    }

    public function update012()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Register_Contact::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.12', self::RELEASE018_UPDATE012);
    }

    public function update013()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.13', self::RELEASE018_UPDATE013);
    }

    public function update014()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_ImageMetadata::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.14', self::RELEASE018_UPDATE014);
    }

    public function update015()
    {
        EventManager_Setup_Initialize::initializeCostCenterCostBearer();
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.15', self::RELEASE018_UPDATE015);
    }

    public function update016()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Appointment::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.16', self::RELEASE018_UPDATE016);
    }

    public function update017()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.17', self::RELEASE018_UPDATE017);
    }

    public function update018()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        // drop old event manager localization table if exists
        $tableName = 'eventmanager_event_localization';
        $this->dropTable($tableName);

        Setup_SchemaTool::updateSchema([
            EventManager_Model_EventLocalization::class,
        ]);

        $eventTableName = SQL_TABLE_PREFIX . EventManager_Model_Event::TABLE_NAME;
        $db = $this->getDb();
        $schema = $db->describeTable($eventTableName);

        $sourceFields = [];
        foreach (['name', 'description', 'subheading'] as $fieldName) {
            if (array_key_exists($fieldName, $schema)) {
                $sourceFields[] = $fieldName;
            }
        }

        if (in_array('name', $sourceFields, true) || in_array('description', $sourceFields, true)) {
            $locTableName = SQL_TABLE_PREFIX . EventManager_Model_EventLocalization::getConfiguration()->getTableName();
            $selectCols = array_merge(['id'], $sourceFields);
            $rows = $db->query(
                'SELECT ' . implode(', ', $selectCols) . ' FROM ' . $eventTableName . ' WHERE is_deleted=0'
            )->fetchAll(Zend_Db::FETCH_ASSOC);

            foreach (EventManager_Config::getInstance()->{EventManager_Config::LANGUAGES_AVAILABLE}->records as $lang) {
                foreach ($rows as $row) {
                    foreach ($sourceFields as $fieldName) {
                        $value = $row[$fieldName];
                        if ($value === null || $value === '') {
                            continue;
                        }
                        $localization = [
                            'id' => Tinebase_Record_Abstract::generateUID(),
                            Tinebase_Record_PropertyLocalization::FLD_RECORD_ID => $row['id'],
                            Tinebase_Record_PropertyLocalization::FLD_TYPE => $fieldName,
                            Tinebase_Record_PropertyLocalization::FLD_TEXT => $value,
                            Tinebase_Record_PropertyLocalization::FLD_LANGUAGE => $lang->id,
                        ];
                        try {
                            $db->insert($locTableName, $localization);
                        } catch (Zend_Db_Statement_Exception $zdse) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                                Tinebase_Core::getLogger()
                                    ->debug(__METHOD__ . '::' . __LINE__ . ' lang text: ' . print_r($lang, true));
                            }
                            Tinebase_Exception::log($zdse);
                        }
                    }
                }
            }

            // explicitly drop the now obsolete sourceFields, to avoid pairing a
            // dropped column with a newly added column of a similar type and rewriting it
            foreach ($sourceFields as $fieldName) {
                try {
                    $db->query('ALTER TABLE ' . $eventTableName . ' DROP COLUMN ' . $fieldName);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    Tinebase_Exception::log($zdse);
                }
            }
        } elseif (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
            Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__
                . ' neither name nor description column found on ' . $eventTableName
                . ' - skipping data migration, nothing to preserve.'
            );
        }

        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '18.18', self::RELEASE018_UPDATE018);
    }
}
