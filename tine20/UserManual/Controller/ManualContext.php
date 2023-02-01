<?php
/**
 * ManualContexts controller for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ManualContexts controller class for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Controller
 */
class UserManual_Controller_ManualContext extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'UserManual';

        $this->_modelName = 'UserManual_Model_ManualContext';
        $this->_purgeRecords = true;
        $this->_doContainerACLChecks = false;

        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'usermanual_manualcontext',
            'modlogActive' => true,
        ));
    }

    /**
     * holds the instance of the singleton
     *
     * @var UserManual_Controller_ManualContext
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return UserManual_Controller_ManualContext
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }

    /**
     * import ManualContext context from xml file
     *
     * @param string  $filenameOrUrl
     * @return boolean success
     */
    public function import($filenameOrUrl)
    {
        $filename = Tinebase_Helper::getFilename($filenameOrUrl);

        $importer = new UserManual_Import_ManualContext();

        return $importer->import($filename);
    }

    /**
     * @param string $path
     * @return NULL|Tinebase_Record_Abstract
     */
    public function searchForContextByPath($path)
    {
        $searchContext = $path;
        $contextRecord = null;

        $filter = new Tinebase_Model_Filter_FilterGroup();
        $filter->setConfiguredModel('UserManual_Model_ManualContext');
        $loops = 0;
        $maxLoops = 10;

        while (! empty($searchContext) && $contextRecord === null && $loops < $maxLoops) {
            $filter->setFromArray(array(
                array('field' => 'context', 'operator' => 'startswith', 'value' => $searchContext)
            ));
            $result = $this->search($filter);
            $contextRecord = $result->getFirstRecord();
            if (! $contextRecord) {
                // trim last part of context path
                $searchContext = preg_replace('/\/[a-z0-9 ]*$/i', '', $searchContext);
                $loops++;
            }
        }

        return $contextRecord;
    }
}
