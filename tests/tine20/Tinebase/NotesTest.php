<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Test class for Tinebase_Group
 */
class Tinebase_NotesTest extends TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Notes
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $_objects = array();
    
    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tinebase_NotesTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * set up tests
     *
     */
    protected function setUp(): void
{
        parent::setUp();
        
        $this->_instance = Tinebase_Notes::getInstance();
        
        $this->_objects['contact'] = new Addressbook_Model_Contact(array(
            'id'        => Tinebase_Record_Abstract::generateUID(),
            'n_family'  => 'phpunit notes contact'
        ));
        
        $this->_objects['record'] = array(
            'id'        => $this->_objects['contact']->getId(),
            'model'     => 'Addressbook_Model_Contact',
            'backend'    => 'Sql',
        );
    }
    
    /**
     * try to add a note
     * 
     * @return Tinebase_Model_Note
     */
    public function testAddNote()
    {
        $note = new Tinebase_Model_Note(array(
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note',
            'record_model'      => $this->_objects['record']['model'],
            'record_backend'    => $this->_objects['record']['backend'],
            'record_id'         => $this->_objects['record']['id']
        ));
        
        // generates id
        $this->_instance->addNote($note);
        
        $testNote = $this->_instance->getNote($note->getId());
        
        $this->assertEquals($note->note, $testNote->note);
        
        return $testNote;
    }

    /**
     * try to add a system note
     *
     */
    public function testAddSystemNote()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        $translatedNoteString = $translate->_('created') . ' ' . $translate->_('by');
        
        $this->_instance->addSystemNote(
            $this->_objects['contact'], 
            Zend_Registry::get('currentAccount')->getId(), 
            Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED
        );
        
        $filter = new Tinebase_Model_NoteFilter(array(array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => $translatedNoteString
        )));
        $notes = $this->_instance->searchNotes($filter, new Tinebase_Model_Pagination());
        
        $this->assertGreaterThan(0, count($notes));
        $found = FALSE;
        foreach ($notes as $note) {
            if ($translatedNoteString . ' ' . Zend_Registry::get('currentAccount')->accountDisplayName == $note->note) {
                $found = TRUE;
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    /**
     * test search notes
     *
     */
    public function testSearchNotes()
    {
        $note = $this->testAddNote();
        
        $filter = new Tinebase_Model_NoteFilter(array(array(
            'field' => 'query',
            'operator' => 'contains',
            'value' => 'phpunit'
        )));
        
        $notes = $this->_instance->searchNotes($filter, new Tinebase_Model_Pagination());
        $notesCount = $this->_instance->searchNotesCount($filter);
        
        $this->assertGreaterThan(0, $notesCount);
        foreach ($notes as $note) {
            if ($note->note === $note['note']) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'note not found in notes: ' . print_r($notes, true));
    }
    
    /**
     * test to array and resolution of account display name
     *
     */
    public function testToArray()
    {
        $note = $this->_instance->getNote($this->testAddNote()->getId());
        
        $noteArray = $note->toArray();
        //print_r($noteArray);
        
        $this->assertEquals(Zend_Registry::get('currentAccount')->accountDisplayName, $noteArray['created_by']);
    }
    
    /**
     * try to delete a note
     *
     */
    public function testDeleteNote()
    {
        $note = $this->testAddNote();
        
        $this->_instance->deleteNotesOfRecord(
            $this->_objects['record']['model'],
            $this->_objects['record']['backend'],
            $this->_objects['record']['id']
        );
        
        $this->expectException('Tinebase_Exception_NotFound');
        
        $note = $this->_instance->getNote($note->getId());
    }
    
    /**
     * try to search for deleted notes
     */
    public function testDoNotGetDeletedNotes()
    {
        $filter = new Tinebase_Model_NoteFilter(array(array(
            'field'    => 'query',
            'operator' => 'contains',
            'value'    => 'phpunit'
        )));

        $this->_instance->deleteNotes($this->_instance->searchNotes($filter));

        $notes      = $this->_instance->searchNotes($filter, new Tinebase_Model_Pagination());
        $notesCount = $this->_instance->searchNotesCount($filter);
        
        $this->assertEquals(0, $notes->count());
        $this->assertEquals(0, $notesCount);
    }
    
    /**
     * try to get notes of multiple records (adding 'changed' note first)
     * 
     * @return void
     */
    public function testGetMultipleNotes()
    {
        $personasContactIds = array();
        foreach ($this->_personas as $persona) {
            $personasContactIds[] = $persona->contact_id;
        }
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($personasContactIds);
        
        // add note to contacts
        foreach ($contacts as $contact) {
            $this->_instance->addNote(new Tinebase_Model_Note(array(
                'note'          => 'very important note!',
                'note_type_id'  => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
                'record_id'     => $contact->getId(),
                'record_model'  => 'Addressbook_Model_Contact',
            )));
        }
        
        $this->_instance->getMultipleNotesOfRecords($contacts);
        foreach ($contacts as $contact) {
            $this->assertGreaterThan(0, count($contact->notes), 'No notes found for contact ' . $contact->n_fn);
        }
    }
}
