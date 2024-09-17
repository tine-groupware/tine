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
 * Order DocumentPosition Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_DocumentPosition_Offer extends Sales_Model_DocumentPosition_Abstract
{
    public const MODEL_NAME_PART = 'DocumentPosition_Offer';
    public const TABLE_NAME = 'sales_document_position_offer';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 4;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::RECORD_NAME] = 'Offer Position'; // ngettext('Offer Position', 'Offer Positions', n)
        $_definition[self::RECORDS_NAME] = 'Offer Positions'; // gettext('GENDER_Offer Position')

        $_definition[self::FIELDS][self::FLD_PARENT_ID][self::CONFIG][self::MODEL_NAME] = self::MODEL_NAME_PART;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Offer::MODEL_NAME_PART;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
