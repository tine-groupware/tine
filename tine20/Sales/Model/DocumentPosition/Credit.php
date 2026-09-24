<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Credit DocumentPosition Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_DocumentPosition_Credit extends Sales_Model_DocumentPosition_Abstract
{
    public const MODEL_NAME_PART = 'DocumentPosition_Credit';
    public const TABLE_NAME = 'sales_document_position_credit';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::RECORD_NAME] = 'Credit Position'; // ngettext('Credit Position', 'Credit Positions', n)
        $_definition[self::RECORDS_NAME] = 'Credit Positions'; // gettext('GENDER_Credit Position')

        $_definition[self::FIELDS][self::FLD_PARENT_ID][self::CONFIG][self::MODEL_NAME] = self::MODEL_NAME_PART;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Credit::MODEL_NAME_PART;

        $_definition[self::UI_CONFIG] = [
            self::UI_CONFIG_LAYOUT_SMALL => [
                Sales_Model_Document_Abstract::FLD_CUSTOMER_ID,
                self::FLD_TITLE,
                self::FLD_QUANTITY,
                self::FLD_UNIT,
                self::FLD_NET_PRICE,
            ],
            self::UI_CONFIG_LAYOUT_MEDIUM => [
                Sales_Model_Document_Abstract::FLD_CUSTOMER_ID,
                self::FLD_DOCUMENT_ID,
                self::FLD_POS_NUMBER,
                self::FLD_TYPE,
                self::FLD_TITLE,
                self::FLD_QUANTITY,
                self::FLD_UNIT,
                self::FLD_UNIT_PRICE,
                self::FLD_POSITION_DISCOUNT_SUM,
                self::FLD_NET_PRICE,
            ],
            self::UI_CONFIG_LAYOUT_BIG => [
                Sales_Model_Document_Abstract::FLD_CUSTOMER_ID,
                self::FLD_DOCUMENT_ID,
                self::FLD_POS_NUMBER,
                self::FLD_TYPE,
                self::FLD_TITLE,
                self::FLD_QUANTITY,
                self::FLD_UNIT,
                self::FLD_UNIT_PRICE_TYPE,
                self::FLD_UNIT_PRICE,
                self::FLD_POSITION_DISCOUNT_SUM,
                self::FLD_NET_PRICE,
                self::FLD_SALES_TAX_RATE,
                self::FLD_GROSS_PRICE,
                self::FLD_EVAL_DIM_COST_CENTER,
                self::FLD_SERVICE_PERIOD_START,
                self::FLD_SERVICE_PERIOD_END,
            ],
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
