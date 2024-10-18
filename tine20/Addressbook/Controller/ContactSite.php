<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * ContactSite controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_ContactSite extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Addressbook_Config::APP_NAME;
        $this->_modelName = Addressbook_Model_ContactSite::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => Addressbook_Model_ContactSite::class,
            'tableName'     => Addressbook_Model_ContactSite::TABLE_NAME,
            'modlogActive'  => true
        ));

    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_ContactSite
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_ContactSite
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_ContactSite();
        }
        
        return self::$_instance;
    }
}
