<?php
/**
 * @package     Tasks
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * task config class
 * 
 * @package     Tasks
 * @subpackage  Config
 */
class Tasks_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'Tasks';

    /**
     * Tasks Status Available
     * 
     * @var string
     */
    const TASK_STATUS = 'taskStatus';

    /**
     * Attendee Status Available
     *
     * @var string
     */
    const ATTENDEE_STATUS = 'attendeeStatus';

    /**
     * Tasks Priorities Available
     * 
     * @var string
     */
    const TASK_PRIORITY = 'taskPriority';

    /**
     * Tasks Templates Container
     *
     * @var string containerId
     */
    const TEMPLATE_CONTAINER = 'templateContainer';

    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::TASK_STATUS => array(
                                   //_('Available task statuses')
            'label'                 => 'Available task statuses',
                                   //_('Possible task status. Please note that additional attendee status might impact other Tasks systems on export or synchronization.')
            'description'           => 'Possible task status. Please note that additional attendee status might impact other Tasks systems on export or synchronization.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Tasks_Model_Status'),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'default'               => [
                'records' => [
                    ['id' => Tasks_Model_Task::TASK_STATUS_NEEDS_ACTION, 'value' => 'No response', 'is_open' => 1,
                        'icon' => 'images/icon-set/icon_invite.svg', 'system' => true], //_('No response')
                    ['id' => Tasks_Model_Task::TASK_STATUS_COMPLETED, 'value' => 'Completed', 'is_open' => 0,
                        'icon' => 'images/icon-set/icon_ok.svg', 'system' => true], //_('Completed')
                    ['id' => Tasks_Model_Task::TASK_STATUS_CANCELLED, 'value' => 'Cancelled', 'is_open' => 0,
                        'icon' => 'images/icon-set/icon_stop.svg', 'system' => true], //_('Cancelled')
                    ['id' => Tasks_Model_Task::TASK_STATUS_IN_PROCESS, 'value' => 'In process', 'is_open' => 1,
                        'icon' => 'images/icon-set/icon_reload.svg', 'system' => true], //_('In process')
                ],
                'default' => 'NEEDS-ACTION',
            ]
        ),
        self::TASK_PRIORITY => array(
                                   //_('Available task priorities')
            'label'                 => 'Available task priorities',
                                   //_('Possible task priorities. Please note that additional priorities might impact other task systems on export or synchronization.')
            'description'           => 'Possible task priorities. Please note that additional priorities might impact other Task systems on export or synchronization.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Tasks_Model_Priority'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => Tasks_Model_Priority::LOW,    'value' => 'low',      'icon' => 'images/icon-set/icon_prio_low.svg', 'system' => true), //_('low')
                    array('id' => Tasks_Model_Priority::NORMAL, 'value' => 'normal',   'icon' => 'images/icon-set/icon_prio_normal.svg', 'system' => true), //_('normal')
                    array('id' => Tasks_Model_Priority::HIGH,   'value' => 'high',     'icon' => 'images/icon-set/icon_prio_high.svg', 'system' => true), //_('high')
                    array('id' => Tasks_Model_Priority::URGENT, 'value' => 'urgent',   'icon' => 'images/icon-set/icon_prio_urgent.svg', 'system' => true), //_('urgent')
                ),
                'default' => Tasks_Model_Priority::NORMAL,
            )
        ),
        self::ATTENDEE_STATUS => array(
            //_('Available collaborator statuses')
            'label'                 => 'Available collaborator statuses',
            //_('Possible task collaborator statuses. Please note that additional collaborator statuses might impact other task systems on export or synchronization.')
            'description'           => 'Possible task collaborator statuses. Please note that additional collaborator statuses might impact other task systems on export or synchronization.',
            'type'                  => Tinebase_Config_Abstract::TYPE_KEYFIELD_CONFIG,
            'options'               => array('recordModel' => Tasks_Model_AttendeeStatus::class),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'is_open' => 1, 'icon' => 'images/icon-set/icon_invite.svg',                      'system' => true), //_('No response')
                    array('id' => 'ACCEPTED',     'value' => 'Accepted',    'is_open' => 1, 'icon' => 'images/icon-set/icon_calendar_attendee_accepted.svg',  'system' => true), //_('Accepted')
                    array('id' => 'DECLINED',     'value' => 'Declined',    'is_open' => 0, 'icon' => 'images/icon-set/icon_calendar_attendee_cancle.svg',    'system' => true), //_('Declined')
                    array('id' => 'TENTATIVE',    'value' => 'Tentative',   'is_open' => 1, 'icon' => 'images/icon-set/icon_calendar_attendee_tentative.svg', 'system' => true), //_('Tentative')
                    array('id' => 'DELEGATED',    'value' => 'Delegated',   'is_open' => 1, 'icon' => 'images/icon-set/icon_calendar_attendee_tentative.svg', 'system' => true), //_('Delegated')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/icon-set/icon_reload.svg',                      'system' => true), //_('In process')
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/icon-set/icon_ok.svg',                          'system' => true), //_('Completed')
                ),
                'default' => 'NEEDS-ACTION'
            )
        ),
        self::TEMPLATE_CONTAINER => [
            self::LABEL                 => 'Tasks Template Container', // _('Tasks Template Container')
            self::DESCRIPTION           => 'Container for task templates', // _('Container for task templates')
            self::TYPE                  => self::TYPE_STRING,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
        ],
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Tasks';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
