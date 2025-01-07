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

class Sales_Model_EDocument_PMC_NoConfig extends Sales_Model_EDocument_PMC_Abstract
{
    public const MODEL_NAME_PART = 'EDocument_PMC_NoConfig';


    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::RECORD_NAME] = 'No Configuration'; // gettext('GENDER_No Configuration')
        $_definition[self::RECORDS_NAME] = 'No Configurations'; // ngettext('No Configuration', 'No Configurations', n)
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}