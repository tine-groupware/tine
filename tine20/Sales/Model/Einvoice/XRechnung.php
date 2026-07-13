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

    public const FLD_OVERWRITES = 'overwrites';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'XRechnung', // ngettext('XRechnung', 'XRechnungen', n)
        self::RECORDS_NAME                  => 'XRechnungen', // gettext('GENDER_XRechnung')

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_OVERWRITES   => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        \Sales_Model_Einvoice_XRechnungOverwrite::FLD_XRECHNUNG_ELEMENT => [],
                    ],
                ],
            ],
        ],
        self::FIELDS                    => [
            self::FLD_OVERWRITES            => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Overwrites', // _('Overwrites')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Einvoice_XRechnungOverwrite::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
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