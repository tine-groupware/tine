<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Calendar Event Filter
 * 
 * @package Calendar
 */
class Calendar_Model_EventFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Calendar_Model_Event::class;

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('modelName' => 'Calendar_Model_Event')),
        'external_id'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'uid'                   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'external_uid'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'etag'                  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'container_id'          => array('filter' => 'Calendar_Model_CalendarFilter', 'options' => array('modelName' => Calendar_Model_Event::class)),
        'query'                 => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('summary', 'description', 'location'), 'modelName' => Calendar_Model_Event::class)),
        'period'                => array('filter' => 'Calendar_Model_PeriodFilter'),
        'attender'              => array('filter' => 'Calendar_Model_AttenderFilter'),
        'attender_status'       => array('filter' => 'Calendar_Model_AttenderStatusFilter'),
        'attender_role'         => array('filter' => 'Calendar_Model_AttenderRoleFilter'),
        'organizer'             => array('filter' => 'Addressbook_Model_ContactIdFilter', 'options' => array('modelName' => 'Addressbook_Model_Contact')),
        'class'                 => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'                => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'                   => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'cal_events.id',
            'applicationName' => 'Calendar',
        )),
        'grants'                => array('filter' => 'Calendar_Model_GrantFilter'),
        // NOTE using dtstart and dtend filters may not lead to the desired result.
        //      you need to use the period filter to filter for events in a given period
        'dtstart'               => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'dtend'                 => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'transp'                => array('filter' => 'Tinebase_Model_Filter_Text'),
        'rrule'                 => array('filter' => 'Calendar_Model_RruleFilter'),
        'recurid'               => array('filter' => 'Tinebase_Model_Filter_Text'),
        'base_event_id'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'rrule_until'           => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'rrule_constraints'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'poll_id'               => array('filter' => 'Tinebase_Model_Filter_Id'),
        'summary'               => array('filter' => 'Tinebase_Model_Filter_Text'),
        'location'              => array('filter' => 'Tinebase_Model_Filter_Text'),
        'location_record'       => array('filter' => 'Addressbook_Model_ContactIdFilter', 'options' => array('modelName' => 'Addressbook_Model_Contact')),
        'description'           => array('filter' => 'Tinebase_Model_Filter_FullText'),
        'is_deleted'            => array('filter' => 'Tinebase_Model_Filter_Bool'),
        'deleted_by'            => array('filter' => 'Tinebase_Model_Filter_User'),
        'deleted_time'          => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'creation_time'         => array('filter' => 'Tinebase_Model_Filter_Date'),
        'last_modified_by'      => array('filter' => 'Tinebase_Model_Filter_User'),
        'last_modified_time'    => array('filter' => 'Tinebase_Model_Filter_DateTime'),
        'created_by'            => array('filter' => 'Tinebase_Model_Filter_User'),
        'customfield'           => array('filter' => 'Tinebase_Model_Filter_CustomField', 'options' => array(
            'idProperty' => 'cal_events.id'
        )),
        'event_types' => ['filter' => Tinebase_Model_Filter_ForeignRecords::class, 'options' => [
            'controller' => Calendar_Controller_EventTypes::class,
            'recordClassName' => Calendar_Model_EventTypes::class,
            'refIdField' => 'record',
        ]],
    );
}
