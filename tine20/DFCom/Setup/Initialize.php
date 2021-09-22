<?php
/**
 * @package     DFCom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class for DFCom initialization
 * 
 * @package     Setup
 */
class DFCom_Setup_Initialize extends Setup_Initialize
{
    /**
     * init setup authKey
     */
    protected function _initializeSetupAuthKey()
    {
        DFCom_Config::getInstance()->set(DFCom_Config::SETUP_AUTH_KEY, Tinebase_Record_Abstract::generateUID(20));
    }

    protected function _initializeDefaultContainer()
    {
        Tinebase_Container::getInstance()->createSystemContainer(
            DFCom_Config::APP_NAME,
            DFCom_Model_Device::class,
            'Devices',
            DFCom_Config::DEFAULT_DEVICE_CONTAINER
        );
    }

    protected function _initializeDefaultDeviceLists()
    {
        DFCom_Config::getInstance()->set(DFCom_Config::DEFAULT_DEVICE_LISTS, [
            'DFCom_device_list_employee',
//            'DFCom_device_list_absenceReasons'
        ]);
    }

    protected function _initializeAnonymousRole()
    {
        try {
            $role = Tinebase_Acl_Roles::getInstance()->getRoleByName(DFCom_Config::PUBLIC_ROLE_NAME);
        } catch (Tinebase_Exception_NotFound $e) {
            $role = Tinebase_Acl_Roles::getInstance()->createRole(new Tinebase_Model_Role([
                'name' => DFCom_Config::PUBLIC_ROLE_NAME,
                'description' => 'This role will be used by the anonymous user during public api calls',
            ]));
        }
        $apps = Tinebase_Application::getInstance()->getApplications();
        Tinebase_Acl_Roles::getInstance()->setRoleRights($role->getId(), [
            ['application_id' => $apps->find('name', 'Addressbook')->getId(), 'right' => Tinebase_Acl_Rights::RUN],
            ['application_id' => $apps->find('name', 'HumanResources')->getId(), 'right' => Tinebase_Acl_Rights::RUN],
            ['application_id' => $apps->find('name', Timetracker_Config::APP_NAME)->getId(), 'right' => Tinebase_Acl_Rights::RUN],
        ]);
    }
}
