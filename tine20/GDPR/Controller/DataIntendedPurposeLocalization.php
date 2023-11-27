<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     GDPR
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * DataIntendedPurpose Localization controller class for GDPR application
 *
 * @package     GDPR
 * @subpackage  Controller
 */
class GDPR_Controller_DataIntendedPurposeLocalization extends Tinebase_Controller_Record_PropertyLocalization
{
    /**
     * holds the instance of the singleton
     *
     * @var ActiveSync_Controller
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return GDPR_Controller_DataIntendedPurposeLocalization
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new GDPR_Controller_DataIntendedPurposeLocalization;
        }

        return self::$_instance;
    }
}
