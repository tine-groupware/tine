<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 *
 * @package     Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_Initialize extends Setup_Initialize
{
    /**
     * array with user role rights, overwrite this in your app to add more rights to user role
     *
     * @var array
     */
    static protected $_userRoleRights = array(
        Tinebase_Acl_Rights::RUN,
        Tinebase_Acl_Rights::REPORT_BUGS,
        Tinebase_Acl_Rights::MANAGE_OWN_STATE,
        Tinebase_Acl_Rights::MANAGE_OWN_PROFILE,
    );

    /**
     * Override method: Tinebase needs additional initialisation
     *
     * @see tine20/Setup/Setup_Initialize#_initialize($_application)
     */
    public function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        if ($locale = Tinebase_Core::getLocale()) {
            Tinebase_Config::getInstance()->{Tinebase_Config::DEFAULT_LOCALE} = $locale->getLanguage();
        }
        $this->_initProcedures();

        $this->_setupConfigOptions($_options);
        $this->_setupGroups();

        $roleController = Tinebase_Acl_Roles::getInstance();
        $roleController->createInitialRoles();

        $oldNotesValue = $roleController->useNotes(false);
        $oldModLogValue = $roleController->modlogActive(false);
        parent::_initialize($_application, $_options);
        $roleController->useNotes($oldNotesValue);
        $roleController->modlogActive($oldModLogValue);
    }

    /**
     * Initializes database procedures if they exist
     */
    protected function _initProcedures()
    {
        $backend = Setup_Backend_Factory::factory();
        $dbCommand = Tinebase_Backend_Sql_Command::factory(Tinebase_Core::getDb());
        $dbCommand->initProcedures($backend);
    }

    /**
     * set config options (accounts/authentication/email/...)
     *
     * @param array $_options
     */
    protected function _setupConfigOptions($_options)
    {
        // ignore empty options
        foreach($_options as $key => $value) {
            if (empty($_options[$key])) unset($_options[$key]);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Saving config options (accounts/authentication/email/...)');

        // this is a dangerous TRACE as there might be passwords in here!
        //if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_options, TRUE));

        $defaults = empty($_options['authenticationData']) ? Setup_Controller::getInstance()->loadAuthenticationData() : $_options['authenticationData'];
        $defaultGroupNames = $this->_parseDefaultGroupNameOptions($_options);
        $defaults['accounts'][Tinebase_User::getConfiguredBackend()] = array_merge($defaults['accounts'][Tinebase_User::getConfiguredBackend()], $defaultGroupNames);

        if (!Tinebase_Config::getInstance()->{Tinebase_Config::CREDENTIAL_CACHE_SHARED_KEY}) {
            Tinebase_Auth_CredentialCache_Adapter_Shared::setRandomKeyInConfig();
        }
        
        $emailConfigKeys = Setup_Controller::getInstance()->getEmailConfigKeys();
        $configsToSet = array_merge($emailConfigKeys, array('authentication', 'accounts', 'redirectSettings', 'acceptedTermsVersion'));

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($configsToSet, TRUE));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($defaults, TRUE));

        $optionsToSave = array();
        foreach ($configsToSet as $group) {
            if (isset($_options[$group])) {
                $parsedOptions = (is_string($_options[$group])) ? Setup_Frontend_Cli::parseConfigValue($_options[$group]) : $_options[$group];

                switch ($group) {
                    case 'authentication':
                    case 'accounts':
                        $backend = (isset($parsedOptions['backend'])) ? ucfirst($parsedOptions['backend']) : Tinebase_User::SQL;
                        $optionsToSave[$group][$backend] = (isset($parsedOptions[$backend])) ? $parsedOptions[$backend] : $parsedOptions;
                        $optionsToSave[$group]['backend'] = $backend;
                        break;
                    default:
                        $optionsToSave[$group] = $parsedOptions;
                }
            } else if (isset($defaults[$group])) {
                $optionsToSave[$group] = $defaults[$group];
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($optionsToSave, TRUE));

        Setup_Controller::getInstance()->saveEmailConfig($optionsToSave);
        Setup_Controller::getInstance()->saveAuthentication($optionsToSave);
    }

    /**
    * Extract default group name settings from {@param $_options}
    *
    * @param array $_options
    * @return array
    */
    protected function _parseDefaultGroupNameOptions($_options)
    {
        $result = array(
            'defaultAdminGroupName' => (isset($_options['defaultAdminGroupName'])) ? $_options['defaultAdminGroupName'] : Tinebase_Group::DEFAULT_ADMIN_GROUP,
            'defaultUserGroupName'  => (isset($_options['defaultUserGroupName'])) ? $_options['defaultUserGroupName'] : Tinebase_Group::DEFAULT_USER_GROUP,
        );

        return $result;
    }

    /**
     * import groups(ldap)/create initial groups(sql)
     * 
     * @todo allow to configure if groups should be synced?
     */
    protected function _setupGroups()
    {
        $groupController = Tinebase_Group::getInstance();
        $oldValue = $groupController->modlogActive(false);

        if ($groupController instanceof Tinebase_Group_Interface_SyncAble && ! $groupController->isDisabledBackend()) {
            Tinebase_Group::syncGroups();
        } else {
            Tinebase_Group::createInitialGroups();
        }

        $groupController->modlogActive($oldValue);
    }

    /**
     * init scheduler tasks
     */
    protected function _initializeSchedulerTasks()
    {
        self::addSchedulerTasks();
    }

    /**
     * adds all tasks from scheduler (calling methods with this name from Tinebase_Scheduler_Task: addXYZTask)
     *
     * @return void
     */
    public static function addSchedulerTasks(): void
    {
        $scheduler = Tinebase_Core::getScheduler();
        $oldRightValue = $scheduler->doRightChecks(false);

        try {
            $reflection = new ReflectionClass(Tinebase_Scheduler_Task::class);
            foreach ($reflection->getMethods() as $method) {
                if (preg_match('/^add[a-z]+task$/i', $method->getName())) {
                    call_user_func_array([Tinebase_Scheduler_Task::class, $method->getName()], [$scheduler]);
                }
            }
        } finally {
            $scheduler->doRightChecks($oldRightValue);
        }
    }

    public function _initializePF()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter([
            'name' => "All Cost Centers", // _('All Cost Centers')
            'description' => "All cost center records", // _('All cost center records')
            'filters' => [],
            'account_id' => NULL,
            'model' => Tinebase_Model_CostCenter::class,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId(),
        ]));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter([
            'name' => "All Cost Units", // _('All Cost Units')
            'description' => "All cost unit records", // _('All cost unit records')
            'filters' => [],
            'account_id' => NULL,
            'model' => Tinebase_Model_CostUnit::class,
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId(),
        ]));
    }
}
