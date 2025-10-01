<?php
/**
 * Tine 2.0
 *
 * @package     SaasInstance
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2021.11 (ONLY!)
 */
class SaasInstance_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $this->_backend->renameTable('saasinstance_actionLog', Tinebase_Model_ActionLog::TABLE_NAME, Tinebase_Config::APP_NAME);
        $this->addApplicationUpdate(SaasInstance_Config::APP_NAME, '14.1', self::RELEASE014_UPDATE001);
    }
}
