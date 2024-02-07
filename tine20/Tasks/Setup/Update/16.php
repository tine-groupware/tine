<?php
/**
 * Tasks_Setup_Update_16
 *
 * Tine groupware
 *
 * @category  Setup
 * @package   Tasks
 * @author    Philipp Schüle <p.schuele@metaways.de>
 * @copyright 2022-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license   http://www.gnu.org/licenses/agpl.html AGPL3
 * @link      https://www.tine-groupware.de/
 */

/**
 * Tasks_Setup_Update_16
 *
 * This is 2023.11 (ONLY!)
 *
 * @category  Setup
 * @package   Tasks
 * @author    Philipp Schüle <p.schuele@metaways.de>
 * @copyright 2022-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license   http://www.gnu.org/licenses/agpl.html AGPL3
 * @link      https://www.tine-groupware.de/
 */
class Tasks_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE016_UPDATE003 = __CLASS__ . '::update003';

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
            self::RELEASE016_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    /**
     * Not much
     *
     * @return void
     */
    public function update000()
    {
        $this->addApplicationUpdate('Tasks', '16.0', self::RELEASE016_UPDATE000);
    }

    /**
     * Not much
     *
     * @return void
     */
    public function update001()
    {
        $this->addApplicationUpdate('Tasks', '16.1', self::RELEASE016_UPDATE001);
    }

    /**
     * Update models
     *
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->getDb()->update(
            SQL_TABLE_PREFIX . Tasks_Model_Task::TABLE_NAME, ['percent' => 0],
            '`percent` is null'
        );

        Setup_SchemaTool::updateSchema(
            [
            Tasks_Model_Attendee::class,
            Tasks_Model_Task::class,
            Tasks_Model_TaskDependency::class,
            ]
        );
        $this->addApplicationUpdate('Tasks', '16.2', self::RELEASE016_UPDATE002);
    }

    /**
     * Remove obsolete favorites
     *
     * @return void
     * @throws Exception
     */
    public function update003(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Tinebase_PersistentFilter::getInstance()->deleteByFilter(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Tinebase_Model_PersistentFilterFilter::class, [
                ['field' => 'model', 'operator' => 'equals', 'value' => 'Tasks_Model_TaskFilter'],
                ['field' => 'application_id', 'operator' => 'equals', 'value' =>
                    Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId()],
                ['field' => 'name', 'operator' => 'startswith', 'value' => 'To be done for me'],
                ], _options: ['ignoreAcl' => true]
            )
        );

        $this->addApplicationUpdate('Tasks', '16.3', self::RELEASE016_UPDATE003);
    }
}
