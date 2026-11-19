<?php

/**
 * tine Groupware
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     https://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2027.11 (ONLY!)
 */
class Sales_Setup_Update_20 extends Setup_Update_Abstract
{
    protected const RELEASE020_UPDATE000 = __CLASS__ . '::update000';
    protected const RELEASE020_UPDATE001 = __CLASS__ . '::update001';
    protected const RELEASE020_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE020_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE020_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
            self::RELEASE020_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update000(): void
    {
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '20.0', self::RELEASE020_UPDATE000);
    }

    /**
     * attached documents of type supporting document were stored as 'supporting_document', the dispatch config
     * uses the keyfield id 'supportingDoc' (Sales_Config::ATTACHED_DOCUMENT_TYPES_SUPPORTING_DOC)
     */
    public function update001(): void
    {
        $this->getDb()->update(
            SQL_TABLE_PREFIX . Sales_Model_Document_AttachedDocument::TABLE_NAME,
            [
                Sales_Model_Document_AttachedDocument::FLD_TYPE =>
                    Sales_Model_Document_AttachedDocument::TYPE_SUPPORTING_DOCUMENT,
            ],
            $this->getDb()->quoteInto(Sales_Model_Document_AttachedDocument::FLD_TYPE . ' = ?', 'supporting_document')
        );
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '20.1', self::RELEASE020_UPDATE001);
    }

    /**
     * documents have xprops
     */
    public function update002(): void
    {
        Setup_SchemaTool::updateSchema([
            Sales_Model_Document_Credit::class,
            Sales_Model_Document_Delivery::class,
            Sales_Model_Document_Invoice::class,
            Sales_Model_Document_Offer::class,
            Sales_Model_Document_Order::class,
            Sales_Model_Document_PurchaseInvoice::class,
        ]);
        $this->addApplicationUpdate(Sales_Config::APP_NAME, '20.2', self::RELEASE020_UPDATE002);
    }
}
