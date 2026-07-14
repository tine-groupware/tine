<?php

/**
 * tine Groupware
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2026.11 (ONLY!)
 */
class Inventory_Setup_Update_19 extends Setup_Update_Abstract
{
    protected const RELEASE019_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE019_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE019_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE019_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '19.0', self::RELEASE019_UPDATE000);
    }

    public function update001(): void
    {
        $config = Inventory_Config::getInstance()->get(Inventory_Config::INVENTORY_STATUS);

        $hasUpdates = false;

        foreach ($config->records as $record) {
            if (! isset($record->is_open)) {
                $record->is_open = in_array($record->id, [
                    Inventory_Model_Status::ORDERED,
                    Inventory_Model_Status::AVAILABLE,
                    Inventory_Model_Status::IN_USE,
                    Inventory_Model_Status::DEFECT,
                    Inventory_Model_Status::UNKNOWN,
                ]) ? 1 : 0;
                $hasUpdates = true;
            }
        }

        if ($hasUpdates) {
            Inventory_Config::getInstance()->set(
                Inventory_Config::INVENTORY_STATUS,
                $config
            );
        }

        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '19.1', self::RELEASE019_UPDATE001);
    }
}
