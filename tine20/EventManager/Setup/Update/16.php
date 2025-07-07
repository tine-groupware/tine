<?php

/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class EventManager_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        Setup_SchemaTool::updateSchema([
            EventManager_Model_Event::class,
            EventManager_Model_Option::class
            ]);
        $this->addApplicationUpdate('EventManager', '16.0', self::RELEASE016_UPDATE000);
    }
}
