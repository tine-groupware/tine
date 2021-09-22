<?php

/**
 * Tine 2.0
 *
 * @package     OnlyOfficeIntegrator_
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class OnlyOfficeIntegrator_Setup_Update_1 extends Setup_Update_Abstract
{
    const RELEASE001_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE001_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE001_UPDATE003 = __CLASS__ . '::update003';
    const RELEASE001_UPDATE004 = __CLASS__ . '::update004';
    const RELEASE001_UPDATE005 = __CLASS__ . '::update005';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE001_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE001_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE001_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
            self::RELEASE001_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE001_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
    ];

    public function update001()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_History::class]);

        Tinebase_FileSystem::getInstance()->createAclNode(OnlyOfficeIntegrator_Controller::getRevisionsChangesPath());
        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.1', self::RELEASE001_UPDATE001);
    }

    public function update002()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_AccessToken::class]);
        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.2', self::RELEASE001_UPDATE002);
    }

    public function update003()
    {
        OnlyOfficeIntegrator_Controller_History::getInstance()->getBackend()->delete(
            array_keys(OnlyOfficeIntegrator_Controller_History::getInstance()->getBackend()->search(null, null, [
                Tinebase_Backend_Sql_Abstract::IDCOL, OnlyOfficeIntegrator_Model_History::FLDS_NODE_ID
            ]))
        );
        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.3', self::RELEASE001_UPDATE003);
    }

    public function update004()
    {
        Setup_SchemaTool::updateSchema([OnlyOfficeIntegrator_Model_History::class]);

        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.4', self::RELEASE001_UPDATE004);
    }

    public function update005()
    {
        (new OnlyOfficeIntegrator_Setup_Initialize())->addMissingInitializeCF();

        $this->addApplicationUpdate(OnlyOfficeIntegrator_Config::APP_NAME, '1.5', self::RELEASE001_UPDATE005);
    }
}
