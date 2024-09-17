<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Delivery DocumentPosition Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_DocumentPosition_Delivery extends Sales_Model_DocumentPosition_Abstract
{
    public const MODEL_NAME_PART = 'DocumentPosition_Delivery';
    public const TABLE_NAME = 'sales_document_position_delivery';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 4;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::RECORD_NAME] = 'Delivery Position'; // ngettext('Delivery Position', 'Delivery Positions', n)
        $_definition[self::RECORDS_NAME] = 'Delivery Positions'; // gettext('GENDER_Delivery Position')

        $_definition[self::FIELDS][self::FLD_PARENT_ID][self::CONFIG][self::MODEL_NAME] = self::MODEL_NAME_PART;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Delivery::MODEL_NAME_PART;

        // remove all moneytary fields, this is a delivery document, no money here
        unset($_definition[self::FIELDS][self::FLD_UNIT_PRICE_TYPE]);
        unset($_definition[self::FIELDS][self::FLD_UNIT_PRICE]);
        unset($_definition[self::FIELDS][self::FLD_POSITION_PRICE]);
        unset($_definition[self::FIELDS][self::FLD_POSITION_DISCOUNT_TYPE]);
        unset($_definition[self::FIELDS][self::FLD_POSITION_DISCOUNT_PERCENTAGE]);
        unset($_definition[self::FIELDS][self::FLD_POSITION_DISCOUNT_SUM]);
        unset($_definition[self::FIELDS][self::FLD_NET_PRICE]);
        unset($_definition[self::FIELDS][self::FLD_SALES_TAX_RATE]);
        unset($_definition[self::FIELDS][self::FLD_SALES_TAX]);
        unset($_definition[self::FIELDS][self::FLD_GROSS_PRICE]);
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    // no moneytary fields, no calc to do
    public function computePrice()
    {
    }
}

