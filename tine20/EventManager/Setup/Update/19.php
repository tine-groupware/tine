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
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
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
        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        // location has been moved to location_record -> we remove the value
        $db = $this->getDb();
        $db->query('UPDATE ' . SQL_TABLE_PREFIX . EventManager_Model_Event::TABLE_NAME . ' set location = ""');

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

        $container = $this->_getOrCreateSharedEventContainer();

        $db = $this->getDb();
        $eventTableName = SQL_TABLE_PREFIX . EventManager_Model_Event::TABLE_NAME;
        $db->update(
            $eventTableName,
            ['container_id' => $container->getId()],
            $db->quoteInto('container_id IS NULL OR container_id = ?', '')
        );

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.5', self::RELEASE019_UPDATE005);
    }

    protected function _getOrCreateSharedEventContainer()
    {
        $containerName = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_SHARED_CONTAINER_NAME);

        try {
            $container = Tinebase_Container::getInstance()->getContainerByName(
                EventManager_Model_Event::class,
                $containerName,
                Tinebase_Model_Container::TYPE_SHARED
            );
        } catch (Tinebase_Exception_NotFound $e) {
            $container = new Tinebase_Model_Container([
                'name'           => $containerName,
                'type'           => Tinebase_Model_Container::TYPE_SHARED,
                'backend'        => 'Sql',
                'owner_id'       => Tinebase_Core::getUser(),
                'application_id' => Tinebase_Application::getInstance()
                    ->getApplicationByName(EventManager_Config::APP_NAME)->getId(),
                'model'          => EventManager_Model_Event::class,
            ]);
            $container = Tinebase_Container::getInstance()->addContainer($container);

            $grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [[
                'account_id'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ   => true,
                Tinebase_Model_Grants::GRANT_ADD    => true,
                Tinebase_Model_Grants::GRANT_EDIT   => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
            ], [
                'account_id'   => Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ   => true,
                Tinebase_Model_Grants::GRANT_ADD    => true,
                Tinebase_Model_Grants::GRANT_EDIT   => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ]]);
            Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, true, false);
        }

        return $container;
    }

    public function update006()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
        ]);

        $this->addApplicationUpdate(EventManager_Config::APP_NAME, '19.6', self::RELEASE019_UPDATE006);
    }
}
