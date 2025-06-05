<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        move some functionality to Tinebase_Acl_Roles
 * @todo        use the defined ACCOUNT_TYPE consts anywhere
 */

/**
 * this class handles the rights for a given application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * NOTE: This is a hibrite class. On the one hand it serves as the general
 *       Rights class to retreave rights for all apss for.
 *       On the other hand it also handles the Tinebase specific rights.
 * @package     Tinebase
 * @subpackage  Acl
 */
class Tinebase_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to send bugreports
     * @staticvar string
     */
    public const REPORT_BUGS = 'report_bugs';
    
    /**
     * the right to check for new versions
     * @staticvar string
     */
    public const CHECK_VERSION = 'check_version';

    public const MANAGE_NUMBERABLES = 'manage_numberables';

    /**
     * the right to manage the own profile
     * @staticvar string
     */
    public const MANAGE_OWN_PROFILE = 'manage_own_profile';

    public const MANAGE_BANK_ACCOUNTS = 'manage_bank_accounts';
    
    /**
     * the right to manage the own (client) state
     * @staticvar string
     */
    public const MANAGE_OWN_STATE = 'manage_own_state';

    public const MANAGE_EVALUATION_DIMENSIONS = 'manage_evaluation_dimensions';

    /**
     * the right to use the installation in maintenance mode
     * @staticvar string
     */
    public const MAINTENANCE = 'maintenance';

    /**
     * the right to access the replication data of all applications
     * @staticvar string
     */
    public const REPLICATION = 'replication';

    /**
     * account type anyone
     * @staticvar string
     */
    public const ACCOUNT_TYPE_ANYONE   = 'anyone';
    
    /**
     * account type user
     * @staticvar string
     */
    public const ACCOUNT_TYPE_USER     = 'user';

    /**
     * account type group
     * @staticvar string
     */
    public const ACCOUNT_TYPE_GROUP    = 'group';

    /**
     * account type role
     * @staticvar string
     */
    public const ACCOUNT_TYPE_ROLE     = 'role';

    /**
     * the right to be able to see license information (expiry, ...) in the frontend
     * @staticvar string
     */
    public const SHOW_LICENSE_INFO = 'show_license_info';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Acl_Rights
     */
    private static $_instance = NULL;
    
    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone() 
    {
    }
    
    /**
     * the constructor
     *
     * disabled. use the singleton
     * temporarly the constructor also creates the needed tables on demand and fills them with some initial values
     */
    private function __construct() {
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Acl_Rights;
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @param   string  $_application application name
     * @return  array   all application rights
     */
    public function getAllApplicationRights($_application = NULL)
    {
        $allRights = parent::getAllApplicationRights();
                
        if ( $_application === NULL || $_application === 'Tinebase' ) {
            $addRights = array(
                self::REPORT_BUGS,
                self::CHECK_VERSION,
                self::MANAGE_BANK_ACCOUNTS,
                self::MANAGE_NUMBERABLES,
                self::MANAGE_OWN_PROFILE,
                self::MANAGE_OWN_STATE,
                self::MANAGE_EVALUATION_DIMENSIONS,
                self::MAINTENANCE,
                self::SHOW_LICENSE_INFO,
                self::REPLICATION,
            );
            $removeRights = [
                self::MAINSCREEN,
                self::USE_PERSONAL_TAGS
            ];
        } else {
            $addRights = array();
            $removeRights = [];
        }
        
        $allRights = array_merge($allRights, $addRights);
        $allRights = array_values(array_diff($allRights, $removeRights));

        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        /** @var Zend_Translate_Adapter $translate */
        $translate = Tinebase_Translation::getTranslation('Tinebase');

        $rightDescriptions = array(
            self::REPORT_BUGS        => array(
                'text'                  => $translate->_('Report bugs'),
                'description'           => $translate->_('Report bugs to the software vendor directly when they occur.'),
            ),
            self::CHECK_VERSION      => array(
                'text'                  => $translate->_('Check version'),
                'description'           => $translate->_('Check for new versions of this software.'),
            ),
            self::MANAGE_BANK_ACCOUNTS => array(
                'text'                  => $translate->_('Manage bank accounts'),
                'description'           => $translate->_('The right to manage bank accounts.'),
            ),
            self::MANAGE_OWN_PROFILE => array(
                'text'                  => $translate->_('Manage own profile'),
                'description'           => $translate->_('The right to manage the own profile (selected contact data).'),
            ),
            self::MANAGE_NUMBERABLES => [
                'text'                  => $translate->_('Manage Numberables'),
                'description'           => $translate->_('The right to manage numberables.'),
            ],
            self::MANAGE_OWN_STATE   => array(
                'text'                  => $translate->_('Manage own client state'),
                'description'           => $translate->_('The right to manage the own client state.'),
            ),
            self::MANAGE_EVALUATION_DIMENSIONS   => [
                'text'                  => $translate->_('Manage evaluation dimensions'),
                'description'           => $translate->_('The right to manage evaluation dimensions.'),
            ],
            self::MAINTENANCE        => array(
                'text'                  => $translate->_('Maintenance'),
                'description'           => $translate->_('The right to use the installation in maintenance mode.'),
            ),
            self::SHOW_LICENSE_INFO => array(
                'text'          => $translate->_('Show License Info'),
                'description'   => $translate->_('The right to be able to see license information (expiry, ...).'),
            ),
            self::REPLICATION        => array(
                'text'                  => $translate->_('Replication'),
                'description'           => $translate->_('The right to access the replication data of all applications.'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        $rightDescriptions = array_intersect_key($rightDescriptions, array_flip(Tinebase_Acl_Rights::getInstance()->getAllApplicationRights()));

        return $rightDescriptions;
    }
    
    /**
     * only return admin / run rights
     * 
     * @return  array with translated descriptions for admin and run rights
     * 
     * @todo this should be called in getTranslatedRightDescriptions / parent::getTranslatedRightDescriptions() renamed
     */
    public static function getTranslatedBasicRightDescriptions()
    {
        return parent::getTranslatedRightDescriptions();
    }
}
