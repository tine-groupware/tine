<?php
/**
 * Tine 2.0
  * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Crm initialization
 * 
 * @package     Crm
 */
class Crm_Setup_Initialize extends Setup_Initialize
{
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
            'model'             => 'Crm_Model_LeadFilter',
        );

        $closedStatus = Crm_Config::getInstance()->get(Crm_Config::LEAD_STATES)->records->filter('endslead', true)->id;

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Crm_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All leads I have read access to", // _("All leads I have read access to")
            'filters'           => array(
                array('field' => 'leadstate_id',    'operator' => 'notin',  'value' => $closedStatus),
            ),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me", // _("Last modified by me")
            'description'       => "All leads I last modified", // _("All leads I last modified")
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My leads",                              // _("My leads")
            'description'       => "All leads I am responsible for",   // _("All leads I am responsible for")
            'filters'           => array(array(
                'field'     => 'contact',
                'operator'  => 'AND',
                'value'     => array(array(
                    'field'     => 'id',
                    'operator'  => 'equals',
                    'value'     => Addressbook_Model_Contact::CURRENTCONTACT,
                ))
            )),
        ))));
    }

    protected function _initializeTasksCoupling()
    {
        if (class_exists('Tasks_Config') && Tinebase_Application::getInstance()->isInstalled(Tasks_Config::APP_NAME)) {
            static::applicationInstalled(Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME));
        }
    }

    public static function applicationInstalled(Tinebase_Model_Application $app): void
    {
        if (class_exists('Tasks_Config') && Tasks_Config::APP_NAME === $app->name) {
            if (!Tinebase_Core::isReplica()) {
                Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
                    'application_id' => $app->getId(),
                    'model' => Tasks_Model_Task::class,
                    'is_system' => true,
                    'name' => 'CrmTasksCoupling',
                    'definition' => [
                        Tinebase_Model_CustomField_Config::DEF_HOOK => [
                            [Crm_Controller::class, 'tasksMCHookFun'],
                        ],
                    ]
                ]));
            }

            $pfe = Tinebase_PersistentFilter::getInstance();
            $crmAppId = Tinebase_Application::getInstance()->getApplicationByName(Crm_Config::APP_NAME)->getId();
            if (!$pfe->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_PersistentFilterFilter::class, [
                        ['field' => 'account_id', 'operator' => 'isnull', 'value' =>  true],
                        ['field' => 'application_id', 'operator' => 'equals', 'value' =>  $crmAppId],
                        ['field' => 'model', 'operator' => 'equals', 'value' =>  Crm_Model_LeadFilter::class],
                        ['field' => 'name', 'operator' => 'equals', 'value' =>  'Leads with overdue tasks'],
                    ]))->getFirstRecord()) {
                // mainly for testing, uninstalling / installing in the same php process
                Crm_Model_Lead::resetConfiguration();

                $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter([
                    'account_id' => NULL,
                    'application_id' => $crmAppId,
                    'model' => Crm_Model_LeadFilter::class,
                    'name' => "Leads with overdue tasks", // _("Leads with overdue tasks")
                    'description' => "Leads with overdue tasks",
                    'filters' => array(array(
                        'field' => 'tasks',
                        'operator' => 'definedBy',
                        'value' => array(array(
                            'field' => 'due',
                            'operator' => 'before',
                            'value' => 'dayThis',
                        ))
                    )),
                ]));
            }
        }
    }
}
