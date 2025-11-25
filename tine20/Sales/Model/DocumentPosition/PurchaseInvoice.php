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

/**
 * Purchase Invoice DocumentPosition Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_DocumentPosition_PurchaseInvoice extends Sales_Model_DocumentPosition_Abstract
{
    public const MODEL_NAME_PART = 'DocumentPosition_PurchaseInvoice';
    public const TABLE_NAME = 'sales_document_position_purchase_invoice';
    
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::RECORD_NAME] = 'Purchase Invoice Position'; // ngettext('Purchase Invoice Position', 'Purchase Invoice Positions', n)
        $_definition[self::RECORDS_NAME] = 'Purchase Invoice Positions'; // gettext('GENDER_Purchase Invoice Position')

        $_definition[self::FIELDS][self::FLD_PARENT_ID][self::CONFIG][self::MODEL_NAME] = self::MODEL_NAME_PART;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART;

        $_definition[self::FIELDS][self::FLD_POSITION_PRICE][self::UI_CONFIG][self::READ_ONLY] = false;
        $_definition[self::FIELDS][self::FLD_NET_PRICE][self::UI_CONFIG][self::READ_ONLY] = false;
        $_definition[self::FIELDS][self::FLD_SALES_TAX][self::UI_CONFIG][self::READ_ONLY] = false;
        $_definition[self::FIELDS][self::FLD_GROSS_PRICE][self::UI_CONFIG][self::READ_ONLY] = false;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function computePrice(bool $onlyProductType = true): void
    {
        // nothing to do here
    }
}

