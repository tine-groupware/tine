<?php

/**
 * ManualPages controller for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ManualPages controller class for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Controller
 */
class UserManual_Controller_ManualPage extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'UserManual';

        $this->_modelName = 'UserManual_Model_ManualPage';
        $this->_doContainerACLChecks = false;

        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'usermanual_manualpage',
            'modlogActive' => false,
        ));
    }

    /**
     * holds the instance of the singleton
     *
     * @var UserManual_Controller_ManualPage
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return UserManual_Controller_ManualPage
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    /**
     * import ManualPages from zip file
     *
     * @param string  $filenameOrUrl
     * @param boolean $clearTable
     * @return boolean success
     */
    public function import($filenameOrUrl, $clearTable = false)
    {
        $filename = Tinebase_Helper::getFilename($filenameOrUrl);

        if ($clearTable) {
            $this->_backend->clearTable();
        }

        $importer = new UserManual_Import_ManualPage();

        return $importer->import($filename);
    }

    /**
     * @param string $file
     * @return NULL|Tinebase_Record_Abstract
     */
    public function getPageByFilename($file)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Fetching file ' . $file
        );

        $filter = new Tinebase_Model_Filter_FilterGroup();
        $filter->setConfiguredModel('UserManual_Model_ManualPage');
        $filter->setFromArray(array(
            array('field' => 'file', 'operator' => 'equals', 'value' => $file)
        ));
        $pages = UserManual_Controller_ManualPage::getInstance()->search($filter);
        return $pages->getFirstRecord();
    }

    /**
     * @param UserManual_Model_ManualContext $context
     * @return null|Tinebase_Record_Abstract
     */
    public function getPageByContext($context)
    {
        if ($context) {
            return $this->getPageByFilename($context->file);
        }

        return null;
    }
}
