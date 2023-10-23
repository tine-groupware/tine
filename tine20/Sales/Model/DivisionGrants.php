<?php
/**
 * class to handle grants
 * 
 * @package     Sales
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Division grants
 * 
 * @package     Sales
 * @subpackage  Record
 *  */
class Sales_Model_DivisionGrants extends Tinebase_Model_Grants
{
    public const MODEL_NAME_PART    = 'DivisionGrants';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = Sales_Config::APP_NAME;
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return [
            self::GRANT_ADMIN,
        ];
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function getAllGrantsMC(): array
    {
        return [
            self::GRANT_ADMIN => [
                self::LABEL         => 'Admin', // _('Admin')
                self::DESCRIPTION   => 'The grant to administrate this division (implies all other grants and the grant to set grants as well).', // _('The grant to administrate this division (implies all other grants and the grant to set grants as well).')
            ],
        ];
    }
}
