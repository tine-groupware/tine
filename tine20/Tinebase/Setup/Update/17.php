<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Tinebase_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE017_UPDATE002 = __CLASS__ . '::update002';
    const RELEASE017_UPDATE003 = __CLASS__ . '::update003';

    static protected $_allUpdates = [
       self::PRIO_TINEBASE_BEFORE_STRUCT   => [
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
           self::RELEASE017_UPDATE002          => [
               self::CLASS_CONST                   => self::class,
               self::FUNCTION_CONST                => 'update002',
           ],
        ],
        self::PRIO_TINEBASE_STRUCTURE   => [
            self::RELEASE017_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
        self::PRIO_TINEBASE_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_FileObject::class,
            Tinebase_Model_Tree_FlySystem::class,
        ]);
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }

    public function update002()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Tinebase_Model_Tree_FlySystem::class,
        ]);

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.2', self::RELEASE017_UPDATE002);
    }

    public function update003()
    {
        $this->getDb()->update(SQL_TABLE_PREFIX . 'numberable', ['bucket' => ''], 'bucket IS NULL');

        $this->_backend->alterCol('numberable', new Setup_Backend_Schema_Field_Xml('<field>
                    <name>bucket</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>true</notnull>
                    <default/>
                </field>'));

        if ($this->getTableVersion('numberable') < 2) {
            $this->setTableVersion('numberable', 2);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '17.3', self::RELEASE017_UPDATE003);
    }
}
