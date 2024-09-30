<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * this class handles the rights for the Addressbook application
 * 
 * a right is always specific to an application and not to a record
 * examples for rights are: admin, run
 * 
 * to add a new right you have to do these 3 steps:
 * - add a constant for the right
 * - add the constant to the $addRights in getAllApplicationRights() function
 * . add getText identifier in getTranslatedRightDescriptions() function
 * 
 * @package     Addressbook
 * @subpackage  Acl
 */
class Filemanager_Acl_Rights extends Tinebase_Acl_Rights_Abstract
{
    /**
     * @static string
     */
    const MANAGE_DOWNLOADLINKS = 'manage_downloadlinks';

    /**
     * @static string
     */
    const MANAGE_SHARED_NODE_FAVORITES = 'manage_shared_node_favorites';
    
    /**
     * holds the instance of the singleton
     *
     * @var Filemanager_Acl_Rights
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
     * @return Addressbook_Acl_Rights
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Acl_Rights;
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
            self::MANAGE_DOWNLOADLINKS,
            self::MANAGE_SHARED_NODE_FAVORITES
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
        $translate = Tinebase_Translation::getTranslation('Filemanager');
        
        $rightDescriptions = array(
            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS => array(
                'text'          => $translate->_('Manage shared folders'),
                'description'   => $translate->_('Allow Creating and moving new subfolders under shared root folder, both deleting and renaming actions require admin grant on the node itself.'),
            ),
            self::MANAGE_DOWNLOADLINKS  => array(
                'text'          => $translate->_('Manage anonymous download links'),
                'description'   => $translate->_('Allow viewing, adding and deleting download links'),
            ),
            self::MANAGE_SHARED_NODE_FAVORITES => array(
                'text'          => $translate->_('Manage shared filemanager favorites'),
                'description'   => $translate->_('Create or update shared filemanager favorites'),
            ),
        );
        
        $rightDescriptions = array_merge($rightDescriptions, parent::getTranslatedRightDescriptions());
        return $rightDescriptions;
    }
}
