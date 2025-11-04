<?php

/**
 * tine Groupware
 *
 * @package     SSO
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class SSO_Setup_Update_18 extends Setup_Update_Abstract
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

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
            ],
            self::RELEASE018_UPDATE007          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update007',
            ],
            self::RELEASE018_UPDATE008          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update008',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_OAuthDeviceCode::class,
        ]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        foreach ($this->_backend->getOwnForeignKeys(SSO_Model_OAuthDeviceCode::TABLE_NAME) as $fKey) {
            $this->_backend->dropForeignKey(SSO_Model_OAuthDeviceCode::TABLE_NAME, $fKey['constraint_name']);
        }

        Setup_SchemaTool::updateSchema([
            SSO_Model_OAuthDeviceCode::class,
        ]);
        
        $this->_backend->dropTable('sso_oauth_device', SSO_Config::APP_NAME);

        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }

    public function update003()
    {
        foreach (SSO_Controller_RelyingParty::getInstance()->getAll() as $rp) {
            $rp->{SSO_Model_RelyingParty::FLD_CONFIG}->isValid(); // set new default values re oauth grants
            SSO_Controller_RelyingParty::getInstance()->update($rp);
        }

        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_RelyingParty::class,
        ]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005()
    {

        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . SSO_Model_Token::TABLE_NAME . ' SET `ttl` = "' . Tinebase_DateTime::now()->addDay(7)->toString() . '" WHERE `ttl` IS NULL');

        SSO_Scheduler_Task::addDeleteExpiredTokensTask(Tinebase_Core::getScheduler());

        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }


    public function update006(): void
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_ExternalIdp::class,
        ]);

        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.6', self::RELEASE018_UPDATE006);
    }

    public function update007(): void
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_ExternalIdp::class,
        ]);

        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.7', self::RELEASE018_UPDATE007);
    }

    public function update008(): void
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_ExternalIdp::class,
        ]);

        $this->addApplicationUpdate(SSO_Config::APP_NAME, '18.8', self::RELEASE018_UPDATE008);
    }
}
