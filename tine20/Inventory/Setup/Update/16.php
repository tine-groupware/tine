<?php

/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Inventory_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE016_UPDATE002 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE016_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Inventory', '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Setup_SchemaTool::updateSchema( [ Inventory_Model_InventoryItem::class ] );
        $this->addApplicationUpdate('Inventory', '16.1', self::RELEASE016_UPDATE001);
    }

    /**
     * delete obsolete export definition
     */
    public function update002()
    {
        $obsoleteNames = ['i_default_xls'];
        $filter =Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
            ['field' => 'name', 'operator' => 'in', 'value' => $obsoleteNames]
        ]);
        Tinebase_ImportExportDefinition::getInstance()->deleteByFilter($filter);
        $this->addApplicationUpdate('Inventory', '16.2', self::RELEASE016_UPDATE002);
    }
}
