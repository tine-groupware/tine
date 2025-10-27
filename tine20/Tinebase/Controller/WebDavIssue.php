<?php declare(strict_types=1);
/**
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Controller_WebDavIssue extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Tinebase_Model_WebDavIssue::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Tinebase_Model_WebDavIssue::TABLE_NAME,
            // no modlog
        ]);
        $this->_modelName = Tinebase_Model_WebDavIssue::class;
        $this->_doContainerACLChecks = false;
        // we do purge on delete
    }
}
