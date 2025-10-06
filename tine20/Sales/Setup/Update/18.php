<?php declare(strict_types=1);

/**
 * tine Groupware
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2025.11 (ONLY!)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Setup_Update_18 extends Setup_Update_Abstract
{
    protected const RELEASE018_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE018_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE018_UPDATE002 = __CLASS__ . '::update002';
    protected const RELEASE018_UPDATE003 = __CLASS__ . '::update003';


    static protected $_allUpdates = [
        self::PRIO_TINEBASE_STRUCTURE       => [
            self::RELEASE018_UPDATE003          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update003',
            ],
        ],
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

    public function update003(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_SalesTax::class,
        ]);

        $taxCtrl = Sales_Controller_Document_SalesTax::getInstance();
        $models = [
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
        ];
        foreach ($models as $model) {
            /** @var Tinebase_Controller_Record_Abstract $ctrl */
            $ctrl = Tinebase_Core::getApplicationInstance($model);
            foreach ($ctrl->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
                        [TMFA::FIELD => Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE, TMFA::OPERATOR => 'isnull', TMFA::VALUE => true],
                    ]), _onlyIds: [Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE]) as $id => $json) {
                if (null !== ($taxRates = json_decode($json, true))) {
                    foreach ($taxRates as $row) {
                        $taxCtrl->create(new Sales_Model_Document_SalesTax([
                            Sales_Model_Document_SalesTax::FLD_DOCUMENT_TYPE => $model,
                            Sales_Model_Document_SalesTax::FLD_DOCUMENT_ID => $id,
                            Sales_Model_Document_SalesTax::FLD_TAX_RATE => $row[Sales_Model_Document_Abstract::TAX_RATE],
                            Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => $row[Sales_Model_Document_Abstract::TAX_SUM],
                            Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => $row[Sales_Model_Document_Abstract::NET_SUM],
                            Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => $row[Sales_Model_Document_Abstract::NET_SUM]
                                + $row[Sales_Model_Document_Abstract::TAX_SUM],
                        ]));
                    }
                }
            }
        }

        Setup_SchemaTool::updateSchema($models);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.3', self::RELEASE018_UPDATE003);
    }
}
