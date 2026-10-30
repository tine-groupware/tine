<?php

/**
 * tine Groupware
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class Calendar_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE019_UPDATE002 = __CLASS__ . '::update002';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE019_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Calendar_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema([
            Calendar_Model_Event::class,
            Calendar_Model_Attender::class,
        ]);

        $this->addApplicationUpdate(Calendar_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }

    /**
     * Add missing Exdates that might be missing because of a Bug in the individual recurrence
     *
     * @return void
     */
    public function update002()
    {
        $this->getDb()->query('INSERT INTO ' . SQL_TABLE_PREFIX . 'cal_exdate' .
            ' (id, cal_event_id, exdate) SELECT SHA1(CONCAT(src.cal_event_id, src.exdate)) AS id, src.cal_event_id, src.exdate FROM (SELECT ev.base_event_id AS cal_event_id, SUBSTR(ev.recurid, -19) AS exdate FROM ' .
            SQL_TABLE_PREFIX . Calendar_Model_Event::TABLE_NAME . ' AS ev WHERE ev.recurid IS NOT NULL AND ev.base_event_id IS NOT NULL AND ev.base_event_id != "" AND is_deleted = 0) AS src  LEFT JOIN ' .
            SQL_TABLE_PREFIX . 'cal_exdate' . ' AS existing ON existing.cal_event_id = src.cal_event_id  AND existing.exdate = src.exdate WHERE existing.id IS NULL');

        $this->addApplicationUpdate(Calendar_Config::APP_NAME, '19.2', self::RELEASE019_UPDATE002);
    }

}
