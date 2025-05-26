<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Einvoice XRechnung config
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Einvoice_XRechnung extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Einvoice_XRechnung';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'XRechnung', // ngettext('XRechnung', 'XRechnungen', n)
        self::RECORDS_NAME                  => 'XRechnungen', // gettext('GENDER_XRechnung')

        self::FIELDS                    => [],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}