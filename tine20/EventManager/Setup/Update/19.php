<?php

/**
 * tine Groupware
 *
 * @package     EventManager
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class EventManager_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE019_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE019_UPDATE003 = __CLASS__ . '::update003';
    protected const RELEASE019_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE019_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE019_UPDATE006 = __CLASS__ . '::update006';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE019_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE019_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE019_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE019_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE019_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        // drop old event manager localization table if exists
        $locTableName = EventManager_Model_EventLocalization::getConfiguration()->getTableName();
        $this->dropTable($locTableName);

        Setup_SchemaTool::updateSchema([
            EventManager_Model_EventLocalization::class,
        ]);

        $eventTableName = SQL_TABLE_PREFIX . EventManager_Model_Event::TABLE_NAME;
        $db = $this->getDb();
        $schema = $db->describeTable($eventTableName);
        if (
            array_key_exists('name', $schema)
            && array_key_exists('description', $schema)
            && array_key_exists('subheading', $schema)
        ) {
            $fields = ['name', 'description', 'subheading'];
            foreach (EventManager_Config::getInstance()->{EventManager_Config::LANGUAGES_AVAILABLE}->records as $lang) {
                foreach (
                    $db->query('SELECT id, name, description, subheading FROM ' . $eventTableName)
                             ->fetchAll(Zend_Db::FETCH_NUM) as $row
                ) {
                    foreach ($fields as $idx => $fieldName) {
                        $localization = [
                            'id' => Tinebase_Record_Abstract::generateUID(),
                            Tinebase_Record_PropertyLocalization::FLD_RECORD_ID => $row[0],
                            Tinebase_Record_PropertyLocalization::FLD_TYPE => $fieldName,
                            Tinebase_Record_PropertyLocalization::FLD_TEXT => $row[$idx + 1],
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
        }

        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
    }

    public function update003()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Option::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.3', self::RELEASE019_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.4', self::RELEASE019_UPDATE004);
    }

    public function update005()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.5', self::RELEASE019_UPDATE005);
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.6', self::RELEASE019_UPDATE006);
    }
}
