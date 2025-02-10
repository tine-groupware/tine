<?php

/**
 * tine Groupware
 *
 * @package     GDPR
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class GDPR_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(GDPR_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->getDb()->update(
            SQL_TABLE_PREFIX . GDPR_Model_DataIntendedPurpose::TABLE_NAME,
            [GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_REGISTRATION => 0],
            '`is_self_registration` is null'
        );
        $this->getDb()->update(
            SQL_TABLE_PREFIX . GDPR_Model_DataIntendedPurpose::TABLE_NAME,
            [GDPR_Model_DataIntendedPurpose::FLD_IS_SELF_SERVICE => 0],
            '`is_self_service` is null'
        );
        $this->addApplicationUpdate(GDPR_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }
}
