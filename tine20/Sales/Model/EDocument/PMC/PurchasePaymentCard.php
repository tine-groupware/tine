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

class Sales_Model_EDocument_PMC_PurchasePaymentCard extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_PMC_PurchasePaymentCard';

    public const FLD_PRIMARY_ACCOUNT_NUMBER = 'primary_account_number';
    public const FLD_CARD_HOLDER_NAME = 'card_holder_name';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME                   => 'Payment Card', // gettext('GENDER_Payment Card')
        self::RECORDS_NAME                  => 'Payment Card', // ngettext('Payment Card', 'Payment Cards', n)

        self::FIELDS                        => [
            self::FLD_PRIMARY_ACCOUNT_NUMBER   => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_CARD_HOLDER_NAME         => [
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