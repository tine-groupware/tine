<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use UBL21\Invoice\Invoice;

class Sales_Model_EDocument_PMC_PurchaseDirectDebit extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_PMC_PurchaseDirectDebit';

    public const FLD_CREDITOR_IDENTIFIER = 'creditor_identifier';
    public const FLD_IBAN = 'iban';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME                   => 'Direct Debit', // gettext('GENDER_Direct Debit')
        self::RECORDS_NAME                  => 'Direct Debit', // ngettext('Direct Debit', 'Direct Debits', n)

        self::FIELDS                        => [
            self::FLD_CREDITOR_IDENTIFIER       => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_IBAN                      => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}