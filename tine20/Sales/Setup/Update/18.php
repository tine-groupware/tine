<?php

/**
 * tine Groupware
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */
class Sales_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';


    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE018_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE018_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
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
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.0', self::RELEASE018_UPDATE000);
    }

    public function update001(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Supplier::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.1', self::RELEASE018_UPDATE001);
    }

    public function update002(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Supplier::class,
        ]);

        /** @var Tinebase_Record_Interface $model */
        foreach ([
                     Sales_Model_Supplier::class,
                 ] as $model) {
            foreach ([
                         'taxable' => 'standard',
                         'nonTaxable' => 'outsideTaxScope',
                         'export' => 'freeExportItem',
                     ] as $old => $new) {
                $this->_db->update(
                    SQL_TABLE_PREFIX . $model::getConfiguration()->getTableName(),
                    [Sales_Model_Customer::FLD_VAT_PROCEDURE => $new],
                    Sales_Model_Customer::FLD_VAT_PROCEDURE . ' = "' . $old . '"'
                );
            }
        }


        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.2', self::RELEASE018_UPDATE002);
    }
}
