<?php

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 * this is 2024.11 (ONLY!)
 */
class Addressbook_Setup_Update_17 extends Setup_Update_Abstract
{
    const RELEASE017_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE017_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE017_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE017_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.0', self::RELEASE017_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Addressbook_Model_Contact::class,
            Tinebase_Model_Container::class,
        ]);
        $this->addApplicationUpdate(Addressbook_Config::APP_NAME, '17.1', self::RELEASE017_UPDATE001);
    }
}
