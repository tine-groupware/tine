<?php

/**
 * tine Groupware
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Inventory_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE018_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        // this needs! to run before the schema update. otherwise we would loose data in FLD_INVOICE
        // in case sales is installed, it actually already performs the schema update
        Inventory_Setup_Initialize::addInventoryItemInvoiceSysCF();

        // in case sales is not installed we need to do a schema update
        Setup_SchemaTool::updateSchema([
            Inventory_Model_InventoryItem::class,
        ]);

        $this->addApplicationUpdate(Inventory_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }
}
