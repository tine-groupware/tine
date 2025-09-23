<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AbstractTest class for CrewScheduling_Export_*
 */
class CrewScheduling_Export_AbstractTest extends TestCase
{
    /**
     * @var Tinebase_Record_RecordSet|null
     */
    protected $_schedulingRoles = null;

    /**
     * @var Tinebase_Model_CustomField_Config|null
     */
    protected $_schedulingRoleCfCfg = null;

    public function setUp(): void
    {
        // FIXME: groups created in _createSchedulingGroups need to be removed in tearDown or only be created once
        $this->_skipIfLDAPBackend();

        parent::setUp();

        $this->_schedulingRoles = CrewScheduling_Controller_SchedulingRole::getInstance()->getAll('order');

        $this->_schedulingRoleCfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Addressbook'), 'schedulingRole',
            Addressbook_Model_List::class);
    }

    protected function _createSchedulingGroups()
    {
        $groupController = Admin_Controller_Group::getInstance();
        $listController = Addressbook_Controller_List::getInstance();

        /** @var CrewScheduling_Model_SchedulingRole $role */
        foreach ($this->_schedulingRoles as $role) {
            $newGroup = new Tinebase_Model_Group(array(
                'name' => $role->name,
            ));
            $group = $groupController->create($newGroup);
            $list = $listController->get($group->list_id);
            $list->customfields = [$this->_schedulingRoleCfCfg->name => [$role->getIdProperty() => $role->getId()]];
            $listController->update($list);
            $groupController->addGroupMember($group->getId(), $this->_personas['sclever']->getId());
            $groupController->addGroupMember($group->getId(), $this->_personas['rwright']->getId());
            $groupController->addGroupMember($group->getId(), $this->_personas['pwulf']->getId());
            // we do NOT add jmcblack but instead jsmith => get 5 mails at the end of the test
            $groupController->addGroupMember($group->getId(), $this->_personas['jsmith']->getId());
        }
    }

    /**
     * @return array
     * @todo remove dependency on ChurchEdition (or move test to ChurchEdition)
     */
    protected function _createSchedulingEvent()
    {
        $churchEventTypeData = new ChurchEdition_Model_ChurchEventType([
            'name' => 'My Test Type',
            'liturgie' => true
        ]);
        $churchEventType = ChurchEdition_Controller_ChurchEventType::getInstance()->create($churchEventTypeData);
        $calJson = new Calendar_Frontend_Json();

        $event = $calJson->saveEvent([
            'summary'       => 'event with type',
            'customfields'  => ['church_event_type' => $churchEventType->id],
            'dtstart'       => '2015-02-22 22:00:00',
            'dtend'         => '2015-02-22 23:00:00',
            'attendee'      => [
                [
                    'user_id'        => $this->_personas['sclever']->contact_id,
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class,[[
                        CrewScheduling_Model_AttendeeRole::FLD_ROLE => $this->_schedulingRoles->getFirstRecord(),
                    ]], _bypassFilters: true),
                ], [
                    'user_id'        => $this->_personas['rwright']->contact_id,
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class,[[
                        CrewScheduling_Model_AttendeeRole::FLD_ROLE => $this->_schedulingRoles->getByIndex(4),
                    ]], _bypassFilters: true),
                ], [
                    'user_id'        => $this->_personas['pwulf']->contact_id,
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class,[[
                        CrewScheduling_Model_AttendeeRole::FLD_ROLE => $this->_schedulingRoles->getByIndex(5),
                    ]], _bypassFilters: true),
                ], [
                    'user_id'        => $this->_personas['jmcblack']->contact_id,
                    'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                    CrewScheduling_Config::CREWSHEDULING_ROLES => new Tinebase_Record_RecordSet(CrewScheduling_Model_AttendeeRole::class,[[
                        CrewScheduling_Model_AttendeeRole::FLD_ROLE => $this->_schedulingRoles->getByIndex(6),
                    ]], _bypassFilters: true),
                ]
            ]
        ]);

        static::assertTrue(isset($event['customfields']['church_event_type']));
        static::assertTrue(isset($event['attendee'][0]['crewscheduling_roles']) &&
            count($event['attendee'][0]['crewscheduling_roles']) === 1);

        return $event;
    }
}
