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
    protected const RELEASE018_UPDATE004 = __CLASS__ . '::update004';
    protected const RELEASE018_UPDATE005 = __CLASS__ . '::update005';
    protected const RELEASE018_UPDATE006 = __CLASS__ . '::update006';


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
            self::RELEASE018_UPDATE004          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update004',
            ],
            self::RELEASE018_UPDATE005          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update005',
            ],
            self::RELEASE018_UPDATE006          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update006',
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
            foreach ($this->_db->query('SELECT id, ' . Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE . ' FROM ' . SQL_TABLE_PREFIX . $model::TABLE_NAME . ' WHERE ' . Sales_Model_Document_Abstract::FLD_SALES_TAX_BY_RATE . ' is not null')->fetchAll(\PDO::FETCH_COLUMN) as $idJson) {
                $id = $idJson[0];
                $json = $idJson[1];
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

    public function update004(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ]);

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.4', self::RELEASE018_UPDATE004);
    }

    public function update005(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Product::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.5', self::RELEASE018_UPDATE005);
    }

    public function update006(): void
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_PaymentReminder::class,
            Sales_Model_Document_PurchaseInvoice::class,
            Sales_Model_Document_Supplier::class,
            Sales_Model_DocumentPosition_PurchaseInvoice::class,
            Sales_Model_Supplier::class,
        ]);

        $transaction = Tinebase_RAII::getTransactionManagerRAII();

        $piCtrl = Sales_Controller_Document_PurchaseInvoice::getInstance();
        $refProp = new ReflectionProperty(Sales_Controller_Document_PurchaseInvoice::class, '_skipSetModlog');
        $refProp->setAccessible(true);
        $refProp->setValue($piCtrl, true);
        $raii = new Tinebase_RAII(fn() => $refProp->setValue($piCtrl, false));
        $recAttachmentCtrl = Tinebase_FileSystem_RecordAttachments::getInstance();

        foreach (Sales_Controller_PurchaseInvoice::getInstance()->getAll()->sort(fn($a, $b) => $a->date && $b->date ? $a->date->compare($b->date) : -1) as $oldPI) {
            $oldPI->relations = Tinebase_Relations::getInstance()->getRelations(Sales_Model_PurchaseInvoice::class, 'Sql', $oldPI->getId());

            $newPI = $piCtrl->create(new Sales_Model_Document_PurchaseInvoice([
                Sales_Model_Document_PurchaseInvoice::FLD_EXTERNAL_INVOICE_NUMBER => $oldPI->number,
                Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS => $oldPI->payed_at ? Sales_Model_Document_PurchaseInvoice::STATUS_PAID :
                    Sales_Model_Document_PurchaseInvoice::STATUS_APPROVAL_REQUESTED,
                Sales_Model_Document_PurchaseInvoice::FLD_DESCRIPTION => $oldPI->description,
                Sales_Model_Document_PurchaseInvoice::FLD_DOCUMENT_DATE => $oldPI->date,
                Sales_Model_Document_PurchaseInvoice::FLD_DUE_AT => $oldPI->due_at,
                Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_TERMS => $oldPI->due_in,
                Sales_Model_Document_PurchaseInvoice::FLD_PAY_AT => $oldPI->pay_at,
                Sales_Model_Document_PurchaseInvoice::FLD_PAID_AT => $oldPI->payed_at,
                Sales_Model_Document_PurchaseInvoice::FLD_OVER_DUE_AT => $oldPI->overdue_at,
                Sales_Model_Document_PurchaseInvoice::FLD_DOCUMENT_CURRENCY => 'EUR',
                Sales_Model_Document_PurchaseInvoice::FLD_INVOICE_DISCOUNT_PERCENTAGE => 0,
                Sales_Model_Document_PurchaseInvoice::FLD_INVOICE_DISCOUNT_SUM => 0,
                Sales_Model_Document_PurchaseInvoice::FLD_NET_SUM => $oldPI->price_net + $oldPI->price_gross2,
                Sales_Model_Document_PurchaseInvoice::FLD_POSITIONS_NET_SUM => $oldPI->price_net + $oldPI->price_gross2,
                Sales_Model_Document_PurchaseInvoice::FLD_POSITIONS_GROSS_SUM => $oldPI->price_gross + $oldPI->price_gross2,
                Sales_Model_Document_PurchaseInvoice::FLD_POSITIONS_DISCOUNT_SUM => 0,
                Sales_Model_Document_PurchaseInvoice::FLD_SALES_TAX => $oldPI->price_tax,
                Sales_Model_Document_PurchaseInvoice::FLD_SALES_TAX_BY_RATE => array_merge([[
                    Sales_Model_Document_SalesTax::FLD_TAX_RATE => ($salesTax = ($oldPI->sales_tax ?? 0)),
                    Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => $oldPI->price_tax,
                    Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => $oldPI->price_net + ($salesTax > 0 ? 0 : $oldPI->price_gross2),
                    Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => $oldPI->price_gross + ($salesTax > 0 ? 0 : $oldPI->price_gross2),
                ]], $oldPI->price_gross2 && $salesTax > 0 ? [[
                    Sales_Model_Document_SalesTax::FLD_TAX_RATE => 0,
                    Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => 0,
                    Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => $oldPI->price_gross2,
                    Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => $oldPI->price_gross2,
                ]] : []),
                Sales_Model_Document_PurchaseInvoice::FLD_GROSS_SUM => $oldPI->price_total,
                Sales_Model_Document_PurchaseInvoice::FLD_PAID_AMOUNT => $oldPI->price_total,
                Sales_Model_Document_PurchaseInvoice::FLD_APPROVER => $oldPI->relations->find('type', 'APPROVER')?->related_record->account_id ?: null,
                Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID => $oldPI->relations->find('type', 'SUPPLIER')?->related_record,
                Sales_Model_Document_PurchaseInvoice::FLD_XPROPS => ['migration_src_id' => $oldPI->getId()],
                Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS_USED  => $oldPI->payment_method,
                Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_REMINDERS => new Tinebase_Record_RecordSet(Sales_Model_Document_PaymentReminder::class, $oldPI->dunned_at ? [
                    new Sales_Model_Document_PaymentReminder([
                        Sales_Model_Document_PaymentReminder::FLD_DATE => $oldPI->dunned_at,
                        Sales_Model_Document_PaymentReminder::FLD_FEE => 0.0,
                        Sales_Model_Document_PaymentReminder::FLD_OUTSTANDING_AMOUNT => $oldPI->price_total,
                    ], true),
                ] : []),
                'created_by' => $oldPI->getIdFromProperty('created_by'),
                'creation_time' => $oldPI->creation_time,
                'last_modified_by' => $oldPI->getIdFromProperty('last_modified_by'),
                'last_modified_time' => $oldPI->last_modified_time,
                'seq' => 1,
            ]));

            $oldPath = $recAttachmentCtrl->getRecordAttachmentPath($oldPI) . '/';
            $newPath = $recAttachmentCtrl->getRecordAttachmentPath($newPI, true) . '/';
            foreach ($recAttachmentCtrl->getRecordAttachments($oldPI) as $recAttachment) {
                Tinebase_FileSystem::getInstance()->copy($oldPath . $recAttachment->name, $newPath . $recAttachment->name);
            }

        }

        Sales_Setup_Initialize::createDefaultFavoritesDocPurchaseInvoice();

        $this->addApplicationUpdate(Sales_Config::APP_NAME, '18.6', self::RELEASE018_UPDATE006);

        $transaction->release();
        unset($raii);
    }
}
