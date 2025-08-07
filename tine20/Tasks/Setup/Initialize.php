<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage    Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * class for Tasks initialization
 * 
 * @package     Tasks
 * @subpackage    Setup
 */
class Tasks_Setup_Initialize extends Setup_Initialize
{
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
        );
        
        $closedStatus = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records->filter('is_open', 0);
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All tasks in my task lists", // _("All tasks in my task lists")
            'filters'           => array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My responsibility",                      // _("My responsibility")
            'description'       => "All tasks that I am responsible for",   // _("All tasks that I am responsible for")
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "To be done for me",                      // _("All tasks for me")
            'description'       => "All tasks to be done for me",   // _("All tasks that I am responsible for")
            'filters'           => array(
                array('field' => 'tasksDue',    'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "To be done for me this week",
            'description'       => "To be done for me this week", // _("To be done for me this week")
            'filters'           => array(
                array('field' => 'due',         'operator' => 'within', 'value' => 'weekThis'),
                array('field' => 'tasksDue',    'operator' => 'equals', 'value' => Addressbook_Model_Contact::CURRENTCONTACT),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me", // _("Last modified by me")
            'description'       => "All tasks that I have last modified", // _("All tasks that I have last modified")
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Tasks without responsible", // _("Tasks without responsible")
            'description'       => "Tasks without responsible",
            'filters'           => array(array(
                'field'     => 'organizer',
                'operator'  => 'equals',
                'value'     => '',
            )),
        ))));
    }

    public static function applicationInstalled(Tinebase_Model_Application $app): void
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }
        if (class_exists('Timetracker_Config') && Timetracker_Config::APP_NAME === $app->name) {
            Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config([
                'application_id' => $app->getId(),
                'model' => Timetracker_Model_Timesheet::class,
                'is_system' => true,
                'name' => 'TasksTimesheetCoupling',
                'definition' => [
                    Tinebase_Model_CustomField_Config::DEF_HOOK => [
                        [Tasks_Controller::class, 'timesheetMCHookFun'],
                    ],
                ]
            ]));
        }
    }
}
