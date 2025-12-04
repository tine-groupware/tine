<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 * @todo        refactor controller tests
 * @todo        resolve test dependencies - make them _stand-alone_
 */

/**
 * Test class for Tinebase_Group
 */
class Crm_ControllerTest extends Crm_AbstractTest
{
    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * test container
     *
     * @var Tinebase_Model_Container
     */
    protected $_testContainer;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();

        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            Crm_Model_Lead::class,
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        if ($personalContainer->count() === 0) {
            $this->_testContainer = Tinebase_Container::getInstance()->addPersonalContainer(Zend_Registry::get('currentAccount')->accountId, 'Crm', 'PHPUNIT');
        } else {
            $this->_testContainer = $personalContainer[0];
        }
        
        $this->_objects['initialLead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'     => $this->_testContainer->id,
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description',
            'end'           => Tinebase_DateTime::now(),
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => Tinebase_DateTime::now(),
        ));
        
        $this->_objects['updatedLead'] = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUnit',
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'container_id'     => $this->_testContainer->id,
            'start'         => Tinebase_DateTime::now(),
            'description'   => 'Description updated',
            'end'           => NULL,
            'turnover'      => '200000',
            'probability'   => 70,
            'end_scheduled' => NULL,
        ));

        $addressbookPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            Addressbook_Model_Contact::class,
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $addressbookContainer = $addressbookPersonalContainer[0];
        
        $this->_objects['user'] = new Addressbook_Model_Contact(array(
            'adr_one_countryname'   => 'DE',
            'adr_one_locality'      => 'Hamburg',
            'adr_one_postalcode'    => '24xxx',
            'adr_one_region'        => 'Hamburg',
            'adr_one_street'        => 'Pickhuben 4',
            'adr_one_street2'       => 'no second street',
            'adr_two_countryname'   => 'DE',
            'adr_two_locality'      => 'Hamburg',
            'adr_two_postalcode'    => '24xxx',
            'adr_two_region'        => 'Hamburg',
            'adr_two_street'        => 'Pickhuben 4',
            'adr_two_street2'       => 'no second street2',
            'assistent'             => 'Cornelius Weiß',
            'bday'                  => '1975-01-02 03:04:05', // new Tinebase_DateTime???
            'email'                 => 'unittests@tine20.org',
            'email_home'            => 'unittests@tine20.org',
            'note'                  => 'Bla Bla Bla',
            'container_id'                 => $addressbookContainer->id,
            'role'                  => 'Role',
            'title'                 => 'Title',
            'url'                   => 'http://www.tine20.org',
            'url_home'              => 'http://www.tine20.com',
            'n_family'              => 'Kneschke',
            'n_fileas'              => 'Kneschke, Lars',
            'n_given'               => 'Lars',
            'n_middle'              => 'no middle name',
            'n_prefix'              => 'no prefix',
            'n_suffix'              => 'no suffix',
            'org_name'              => 'Metaways Infosystems GmbH',
            'org_unit'              => 'Tine 2.0',
            'tel_assistent'         => '+49TELASSISTENT',
            'tel_car'               => '+49TELCAR',
            'tel_cell'              => '+49TELCELL',
            'tel_cell_private'      => '+49TELCELLPRIVATE',
            'tel_fax'               => '+49TELFAX',
            'tel_fax_home'          => '+49TELFAXHOME',
            'tel_home'              => '+49TELHOME',
            'tel_pager'             => '+49TELPAGER',
            'tel_work'              => '+49TELWORK',
        ));

        $tasksPersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            Zend_Registry::get('currentAccount'), 
            Tasks_Model_Task::class,
            Zend_Registry::get('currentAccount'), 
            Tinebase_Model_Grants::GRANT_EDIT
        );
        
        $tasksContainer = $tasksPersonalContainer[0];
        
        // create test task
        $this->_objects['task'] = new Tasks_Model_Task(array(
            // tine record fields
            'container_id'         => $tasksContainer->id,
            'created_by'           => Zend_Registry::get('currentAccount')->getId(),
            'creation_time'        => Tinebase_DateTime::now(),
            'percent'              => 70,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            'summary'              => 'phpunit: crm test task',        
        ));
        
        $this->_objects['note'] = new Tinebase_Model_Note(array(
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note',    
        ));
    }

    /**
     * try to add a lead
     *
     */
    public function testAddLead()
    {
        $translate = Tinebase_Translation::getTranslation();
        
        $lead = $this->_objects['initialLead'];
        $lead->notes = new Tinebase_Record_RecordSet('Tinebase_Model_Note', array($this->_objects['note']));
        $lead = Crm_Controller_Lead::getInstance()->create($lead);

        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
        
        $notes = Tinebase_Notes::getInstance()->getNotesOfRecord('Crm_Model_Lead', $lead->getId());
        
        foreach ($notes as $note) {
            if ($note->note_type_id === Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED) {
                $translatedMessage = $translate->_('created') . ' ' . $translate->_('by') . ' ';
                $this->assertEquals($translatedMessage.Zend_Registry::get('currentAccount')->accountDisplayName, $note->note);
            } else {
                $this->assertEquals($this->_objects['note']->note, $note->note);
            }
        }

        return $lead;
    }
    
    /**
     * try to get a lead
     *
     */
    public function testGetLead()
    {
        $lead = $this->testAddLead();
        $lead = Crm_Controller_Lead::getInstance()->get($lead->getId());
        
        $this->assertEquals($this->_objects['initialLead']->description, $lead->description);
    }
    
    
    /**
     * try to update a lead
     *
     */
    public function testUpdateLead()
    {
        $lead = $this->testAddLead();
        $this->_objects['updatedLead']->id = $lead->getId();
        $lead = Crm_Controller_Lead::getInstance()->update($this->_objects['updatedLead']);
        
        $this->assertEquals($this->_objects['updatedLead']->description, $lead->description);
    }

    /**
     * try to get all leads and compare counts
     *
     */
    public function testGetAllLeads()
    {
        $this->testAddLead();
        $filter = $this->_getFilter();
        
        $leads = Crm_Controller_Lead::getInstance()->search($filter);
        $count = Crm_Controller_Lead::getInstance()->searchCount($filter);
        
        $this->assertEquals(1, count($leads), 'count mismatch');
        $this->assertEquals($count['count'], count($leads), 'wrong totalcount');
        $this->assertTrue($leads instanceof Tinebase_Record_RecordSet, 'wrong type');
    }
    
    /**
     * try to get all shared leads
     *
     */
    public function testGetSharedLeads()
    {
        $filter = $this->_getFilter('shared');
        $leads = Crm_Controller_Lead::getInstance()->search($filter);
        
        $this->assertEquals(0, count($leads));
        $this->assertTrue($leads instanceof Tinebase_Record_RecordSet, 'wrong type');
    }
    
    /**
     * try to set / get linked tasks
     *
     */
    public function testLinkedTasks()
    {
        $task = Tasks_Controller_Task::getInstance()->create($this->_objects['task']);
        
        // link task
        $lead = $this->testAddLead();
        $lead->relations = array(array(
            'own_model'              => 'Crm_Model_Lead',
            'own_backend'            => 'Sql',
            'own_id'                 => $lead->getId(),
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Tasks_Model_Task',
            'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'related_id'             => $task->getId(),
            'type'                   => 'TASK'
        ));
        $lead = Crm_Controller_Lead::getInstance()->update($lead);
        
        // check linked tasks
        $updatedLead = Crm_Controller_Lead::getInstance()->get($lead->getId());
        
        $this->assertGreaterThan(0, count($updatedLead->relations));
        $this->assertEquals($task->getId(), $updatedLead->relations[0]->related_id);
        
    }

    /**
     * try to update a lead with the read only state
     *
     */
    public function testUpdateReadonlyLead()
    {
        $lead = Crm_Controller_Lead::getInstance()->create($this->_getLead(true, true, false, Tinebase_Record_Abstract::generateUID(10)));
        // save read-only status
        $lead->leadstate_id = 7; //read-only
        $updatedLead = Crm_Controller_Lead::getInstance()->update($lead);
        // try to save the Lead again
        $translation = Tinebase_Translation::getTranslation('Crm');
        $this->expectException('Tinebase_Exception_SystemGeneric');
        $this->expectExceptionMessage($translation->_('This lead state is set to read-only. Therefore, updating this lead is not possible.'));
        Crm_Controller_Lead::getInstance()->update($updatedLead);
    }

    /**
     * try to delete a lead
     *
     */
    public function testDeleteLead()
    {
        $lead = $this->testAddLead();
        Crm_Controller_Lead::getInstance()->delete($lead->getId());

        // purge all relations
        $backend = new Tinebase_Relation_Backend_Sql();
        $backend->purgeAllRelations('Crm_Model_Lead', 'Sql', $lead->getId());

        // delete contact
        Addressbook_Controller_Contact::getInstance()->delete($this->_objects['user']->getId());
        
        $this->expectException('Tinebase_Exception_NotFound');
        Crm_Controller_Lead::getInstance()->get($lead->getId());
    }
    
    /**
     * get lead filter
     *
     * @return Crm_Model_LeadFilter
     */
    protected function _getFilter($container = 'single')
    {
        $filterData = array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'PHPUnit'
            ),
        );
        $filterData[] = ($container == 'single') 
            ? array(
                'field' => 'container_id', 
                'operator' => 'equals', 
                'value' => $this->_testContainer->id
            ) 
            : array(
                'field' => 'container_id', 
                'operator' => 'specialNode', 
                'value' => $container
            );
        
        $filter = new Crm_Model_LeadFilter($filterData);
        
        $filter->createFilter('showClosed', 'equals', TRUE);

        return $filter;
    }
}
