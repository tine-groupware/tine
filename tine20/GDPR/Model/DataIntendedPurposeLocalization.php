<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  DataIntendedPurpose
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold localized DataIntendedPurpose data
 *
 * @package     GDPR
 * @subpackage  DataIntendedPurpose
 */
class GDPR_Model_DataIntendedPurposeLocalization extends Tinebase_Record_PropertyLocalization
{
    public const MODEL_NAME_PART = 'DataIntendedPurposeLocalization';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
