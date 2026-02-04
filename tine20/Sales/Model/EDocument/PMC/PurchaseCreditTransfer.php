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

class Sales_Model_EDocument_PMC_PurchaseCreditTransfer extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_PMC_PurchaseCreditTransfer';

    public const FLD_ACCOUNT_IDENTIFIER = 'account_identifier';
    public const FLD_ACCOUNT_NAME = 'account_name';
    public const FLD_SERVICE_PROVIDER_IDENTIFIER = 'service_provider_identifier';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME                   => 'Credit Transfer', // gettext('GENDER_Credit Transfer')
        self::RECORDS_NAME                  => 'Credit Transfer', // ngettext('Credit Transfer', 'Credit Transfers', n)

        self::FIELDS                        => [
            self::FLD_ACCOUNT_IDENTIFIER        => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_ACCOUNT_NAME              => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_SERVICE_PROVIDER_IDENTIFIER => [
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