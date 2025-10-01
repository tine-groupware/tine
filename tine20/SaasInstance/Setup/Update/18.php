<?php

/**
 * Tine 2.0
 *
 * @package     SaasInstance
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class SaasInstance_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(SaasInstance_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }
}
