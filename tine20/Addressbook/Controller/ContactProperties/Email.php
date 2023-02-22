<?php declare(strict_types=1);

/**
 * ContactProperties Email controller for Addressbook application
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


// TODO FIXME !!!!! we need to prevent the json api (or any other api for that matter) to reach this controller
// this is a dependent record and *MUST* only be seen/updated via the contact record (as the container grants check is done there!)
// even with the acl delegation in place, it actually depends on definition => only the contact knows what grants to apply!

/**
 * ContactProperties Email controller class for Addressbook application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Addressbook_Controller_ContactProperties_Email extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Addressbook_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Addressbook_Model_ContactProperties_Email::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Addressbook_Model_ContactProperties_Email::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Addressbook_Model_ContactProperties_Email::class;
        $this->_purgeRecords = false;
    }
}