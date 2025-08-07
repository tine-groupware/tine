<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * this class handles the rights for the Tasks application
 * 
 * @package     Tasks
 * @subpackage  Acl
 */
class Tasks_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * the right to manage shared task favorites
     * 
     * @staticvar string
     */
    const MANAGE_SHARED_TASK_FAVORITES = 'manage_shared_task_favorites';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tasks_Acl_Rights
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
     */
    private function __construct()
    {
        
    }    
    
    /**
     * the singleton pattern
     *
     * @return Tasks_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Acl_Rights;
        }
        
        return self::$_instance;
    }
    
    /**
     * get all possible application rights
     *
     * @return  array   all application rights
     */
    public function getAllApplicationRights()
    {
        
        $allRights = parent::getAllApplicationRights();
        
        $addRights = array(
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS,
            Tinebase_Acl_Rights::USE_PERSONAL_TAGS,
            self::MANAGE_SHARED_TASK_FAVORITES,
        );
        $allRights = array_merge($allRights, $addRights);
        
        return $allRights;
    }

    /**
     * get translated right descriptions
     * 
     * @return  array with translated descriptions for this applications rights
     */
    public static function getTranslatedRightDescriptions()
    {
        $translate = Tinebase_Translation::getTranslation('Tasks');
        
        $rightDescriptions = array(
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS => array(
                'text'          => $translate->_('manage shared task lists'),
                'description'   => $translate->_('Create new shared task lists'),
            ),
            self::MANAGE_SHARED_TASK_FAVORITES => array(
                'text'          => $translate->_('manage shared task favorites'),
                'description'   => $translate->_('Create or update shared task favorites'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }

}
