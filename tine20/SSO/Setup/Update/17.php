<?php

/**
 * Tine 2.0
 *
 * @package     SSO
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class SSO_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE017_UPDATE004 = __CLASS__ . '::update004';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE017_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
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
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_ExternalIdp::class,
        ]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_ExternalIdp::class,
        ]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        if ($this->_backend->tableExists(SSO_Model_ExternalIdp::TABLE_NAME) &&
                $this->_backend->columnExists('logo', SSO_Model_ExternalIdp::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . SSO_Model_ExternalIdp::TABLE_NAME
                    . ' CHANGE COLUMN logo logo_light longblob DEFAULT NULL');
        }
        if ($this->_backend->tableExists(SSO_Model_RelyingParty::TABLE_NAME) &&
                $this->_backend->columnExists('logo', SSO_Model_RelyingParty::TABLE_NAME)) {
            $this->_db->query('ALTER TABLE ' . SQL_TABLE_PREFIX . SSO_Model_RelyingParty::TABLE_NAME
                . ' CHANGE COLUMN logo logo_light longblob DEFAULT NULL');
        }
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([
            SSO_Model_ExternalIdp::class,
            SSO_Model_RelyingParty::class,
        ]);
        $this->addApplicationUpdate(SSO_Config::APP_NAME, '17.4', self::RELEASE017_UPDATE004);
    }
}
