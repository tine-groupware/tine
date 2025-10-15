<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        delete notes completely or just set the is_deleted flag?
 */

/**
 * Class for handling notes
 * 
 * @package     Tinebase
 * @subpackage  Notes 
 */
class Tinebase_Notes implements Tinebase_Backend_Sql_Interface 
{
    /**
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db;

    /**
     * @var Tinebase_Db_Table
     */
    protected $_notesTable;
    
    /**
     * default record backend
     */
    public const DEFAULT_RECORD_BACKEND = 'Sql';
    
    /**
     * number of notes per record for activities panel
     * (NOT the tab panel)
     */
    public const NUMBER_RECORD_NOTES = 8;

    /**
     * max length of note text
     * 
     * @var integer
     */
    public const MAX_NOTE_LENGTH = 10000;
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Notes
     */
    private static $_instance = null;

    /**
     * the singleton pattern
     *
     * @return Tinebase_Notes
     */
    public static function getInstance() 
    {
        if (self::$_instance === null) {
            self::$_instance = new Tinebase_Notes();
        }
        
        return self::$_instance;
    }

    /**
     * the private constructor
     *
     */
    private function __construct()
    {

        $this->_db = Tinebase_Core::getDb();
        
        $this->_notesTable = new Tinebase_Db_Table(array(
            'name' => SQL_TABLE_PREFIX . 'notes',
            'primary' => 'id'
        ));
    }
    
    /************************** sql backend interface ************************/
    
    /**
     * get table name
     *
     * @return string
     */
    public function getTableName()
    {
        return 'notes';
    }
    
    /**
     * get table prefix
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->_db->table_prefix;
    }
    
    /**
     * get db adapter
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        return $this->_db;
    }
    
    /**
     * returns the db schema
     * 
     * @return array
     */
    public function getSchema()
    {
        return Tinebase_Db_Table::getTableDescriptionFromCache(SQL_TABLE_PREFIX . 'notes', $this->_db);
    }
    
    /************************** get notes ************************/

    /**
     * search for notes
     *
     * @param Tinebase_Model_NoteFilter $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $ignoreACL
     * @return Tinebase_Record_RecordSet subtype Tinebase_Model_Note
     */
    public function searchNotes(
        Tinebase_Model_NoteFilter $_filter,
        ?\Tinebase_Model_Pagination $_pagination = null,
        $ignoreACL = true
    ) {
        $select = $this->_db->select()
            ->from(array('notes' => SQL_TABLE_PREFIX . 'notes'))
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');
        
        if (! $ignoreACL) {
            $this->_checkFilterACL($_filter);
        }
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($select, $_filter, $this);
        if ($_pagination !== null) {
            $_pagination->appendPaginationSql($select);
        }
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Note', $rows, true);

        return $result;
    }
    
    /**
     * checks acl of filter
     * 
     * @param Tinebase_Model_NoteFilter $noteFilter
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkFilterACL(Tinebase_Model_NoteFilter $noteFilter)
    {
        $recordModelFilter = $noteFilter->getFilter('record_model');
        if (empty($recordModelFilter)) {
            throw new Tinebase_Exception_AccessDenied('record model filter required');
        }
        
        $recordIdFilter = $noteFilter->getFilter('record_id');
        if (empty($recordIdFilter) || $recordIdFilter->getOperator() !== 'equals') {
            throw new Tinebase_Exception_AccessDenied('record id filter required or wrong operator');
        }
        
        $recordModel = $recordModelFilter->getValue();
        if (! is_string($recordModel)) {
            throw new Tinebase_Exception_AccessDenied('no explicit record model set in filter');
        }

        $recordId = $recordIdFilter->getValue();
        if (empty($recordId)) {
            $recordIdFilter->setValue('');
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' record ID is empty');
        } else {
            try {
                $controller = Tinebase_Core::getApplicationInstance($recordModel);
                if ($controller instanceof Tinebase_Controller_Record_Abstract) {
                    $record = $controller->get($recordId);
                    if ($record instanceof Addressbook_Model_Contact && !Tinebase_Core::getUser()
                            ->hasGrant($record->container_id, Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Do not fetch record notes because user has no private data grant for adb container');
                        $recordIdFilter->setValue('');
                    }
                }
            } catch (Tinebase_Exception_AccessDenied) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Do not fetch record notes because user has no read grant for container');
                $recordIdFilter->setValue('');
            }
        }
    }
    
    /**
     * count notes
     *
     * @param Tinebase_Model_NoteFilter $_filter
     * @param boolean $ignoreACL
     * @return int notes count
     */
    public function searchNotesCount(Tinebase_Model_NoteFilter $_filter, $ignoreACL = true)
    {
        $select = $this->_db->select()
            ->from(array('notes' => SQL_TABLE_PREFIX . 'notes'), array('count' => 'COUNT(' . $this->_db->quoteIdentifier('id') . ')'))
            ->where($this->_db->quoteIdentifier('is_deleted') . ' = 0');
        
        if (! $ignoreACL) {
            $this->_checkFilterACL($_filter);
        }
        
        Tinebase_Backend_Sql_Filter_FilterGroup::appendFilters($select, $_filter, $this);
        
        $result = $this->_db->fetchOne($select);
        return $result;
    }
    
    /**
     * get a single note
     *
     * @param   string $_noteId
     * @return  Tinebase_Model_Note
     * @throws  Tinebase_Exception_NotFound
     */
    public function getNote($_noteId)
    {
        $row = $this->_notesTable->fetchRow($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ? AND '
            . $this->_db->quoteIdentifier('is_deleted') . ' = 0', (string) $_noteId));
        
        if (!$row) {
            throw new Tinebase_Exception_NotFound('Note not found.');
        }
        
        return new Tinebase_Model_Note($row->toArray());
    }
    
    /**
     * get all notes of a given record (calls searchNotes)
     * 
     * @param  string $_model     model of record
     * @param  string $_id        id of record
     * @param  string $_backend   backend of record
     * @param  boolean $_onlyNonSystemNotes get only non-system notes per default
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Note
     */

    /**
     * get all notes of a given record (calls searchNotes)
     *
     * @param  string $_model     model of record
     * @param  string $_id        id of record
     * @param  string $_backend   backend of record
     * @param  boolean $_onlyNonSystemNotes get only non-system notes per default
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Note
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    public function getNotesOfRecord(string $_model,
                                     string $_id,
                                     string $_backend = self::DEFAULT_RECORD_BACKEND,
                                     bool $_onlyNonSystemNotes = true)
    {
        $backend = ucfirst(strtolower($_backend));

        $filter = $this->_getNotesFilter($_id, $_model, $backend, $_onlyNonSystemNotes);
        
        $pagination = new Tinebase_Model_Pagination(array(
            'limit' => Tinebase_Notes::NUMBER_RECORD_NOTES,
            'sort'  => 'creation_time',
            'dir'   => 'DESC'
        ));
        
        return $this->searchNotes($filter, $pagination);
    }
    
    /**
     * get all notes of all given records (calls searchNotes)
     * 
     * @param  Tinebase_Record_RecordSet  $_records       the recordSet
     * @param  string                     $_notesProperty  the property in the record where the notes are in (defaults: 'notes')
     * @param  string                     $_backend   backend of record
     * @return Tinebase_Record_RecordSet|null
     */
    public function getMultipleNotesOfRecords($_records, $_notesProperty = 'notes', $_backend = 'Sql', $_onlyNonSystemNotes = TRUE)
    {
        if (count($_records) == 0) {
            return null;
        }
        
        $modelName = $_records->getRecordClassName();
        $filter = $this->_getNotesFilter($_records->getArrayOfIds(), $modelName, $_backend, $_onlyNonSystemNotes);
        
        // search and add index
        $notesOfRecords = $this->searchNotes($filter);
        $notesOfRecords->addIndices(array('record_id'));
        
        // add notes to records
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && count($notesOfRecords) > 0) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Getting ' . count($notesOfRecords)
                . ' notes for ' . count($_records) . ' records.');
        }
        foreach($_records as $record) {
            //$record->notes = Tinebase_Notes::getInstance()->getNotesOfRecord($modelName, $record->getId(), $_backend);
            $record->{$_notesProperty} = $notesOfRecords->filter('record_id', $record->getId());
        }

        return $notesOfRecords;
    }
    
    /************************** set / add / delete notes ************************/
    
    /**
     * sets notes of a record
     * 
     * @param Tinebase_Record_Interface  $_record            the record object
     * @param string                    $_backend           backend (default: 'Sql')
     * @param string                    $_notesProperty     the property in the record where the tags are in (default: 'notes')
     * 
     * @todo add update notes ?
     */
    public function setNotesOfRecord($_record, $_backend = 'Sql', $_notesProperty = 'notes')
    {
        $model = $_record::class;
        $backend = ucfirst(strtolower($_backend));
        
        $currentNotes = $this->getNotesOfRecord($model, $_record->getId(), $backend);
        $notes = $_record->$_notesProperty;
        
        if ($notes instanceOf Tinebase_Record_RecordSet) {
            $notesToSet = $notes;
        } else {
            if (count($notes) > 0 && $notes[0] instanceOf Tinebase_Record_Interface) {
                // array of notes records given
                $notesToSet = new Tinebase_Record_RecordSet('Tinebase_Model_Note', $notes);
            } else {
                // array of arrays given
                $notesToSet = new Tinebase_Record_RecordSet('Tinebase_Model_Note');
                foreach($notes as $noteData) {
                    if (!empty($noteData)) {
                        $noteArray = (!is_array($noteData)) ? array('note' => $noteData) : $noteData;
                        if (!isset($noteArray['note_type_id'])) {
                            // get default note type
                            $noteArray['note_type_id'] = Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE;
                        }
                        try {
                            $note = new Tinebase_Model_Note($noteArray);
                            $notesToSet->addRecord($note);
                            
                        } catch (Tinebase_Exception_Record_Validation $terv) {
                            // discard invalid notes here
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                                . ' Note is invalid! '
                                . $terv->getMessage()
                            );
                        }
                    }
                }
            }
            $_record->$_notesProperty = $notesToSet;
        }
        
        $toDetach = array_diff($currentNotes->getArrayOfIds(), $notesToSet->getArrayOfIds());
        $toDelete = new Tinebase_Record_RecordSet('Tinebase_Model_Note');
        foreach ($toDetach as $detachee) {
            $toDelete->addRecord($currentNotes->getById($detachee));
        }

        // delete detached/deleted notes
        $this->deleteNotes($toDelete);

        if (count($notesToSet) > 0) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Adding ' . count($notesToSet) . ' new note(s) to record.');
            foreach ($notesToSet as $note) {
                if (!$note->getId()) {
                    $note->record_model = $model;
                    $note->record_backend = $backend;
                    $note->record_id = $_record->getId();
                    $this->addNote($note);
                }
            }
        }
    }
    
    /**
     * add new note
     *
     * @param Tinebase_Model_Note $_note
     * @param boolean $skipModlog
     * @return Tinebase_Model_Note
     */
    public function addNote(Tinebase_Model_Note $_note, $skipModlog = false)
    {
        if (!$_note->getId()) {
            $id = $_note->generateUID();
            $_note->setId($id);
        }

        if (! $skipModlog) {
            $seq = (int)$_note->seq;
            Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($_note, 'create');
            $_note->seq = $seq;
        }
        
        $data = $_note->toArray(FALSE, FALSE);

        if (mb_strlen((string)$data['note']) > 65535) {
            $data['note'] = mb_substr((string) $data['note'], 0, 65535);
        }
        
        $this->_notesTable->insert($data);
        return $_note;
    }

    /**
     * add new system note
     *
     * @param Tinebase_Record_Interface|string $_record
     * @param string|Tinebase_Model_User $_userId
     * @param string $_type (created|changed)
     * @param Tinebase_Record_RecordSet|string $_mods (Tinebase_Model_ModificationLog)
     * @param string $_backend backend of record
     * @return Tinebase_Model_Note|boolean
     *
     * @throws Tinebase_Exception_NotFound
     * @todo attach modlog record (id) to note instead of saving an ugly string
     * @todo get field translations from application?
     */
    public function addSystemNote(
        $_record,
        $_userId = null,
        string $_type = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED,
        $_mods = null,
        $_backend = 'Sql',
        $_modelName = null
    ) {
        if (
            (empty($_mods) || $_mods instanceof Tinebase_Record_RecordSet && count($_mods) === 0)
            && $_type === Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Nothing changed -> do not add "changed" note.');
            }
            return false;
        }
        
        $id = $_record instanceof Tinebase_Record_Interface ? $_record->getId() : $_record;
        $seq = $_record instanceof Tinebase_Record_Interface && $_record->has('seq') ? $_record->seq : 0;
        $modelName = $_modelName ?? (($_record instanceof Tinebase_Record_Interface)
            ? $_record::class : 'unknown');
        if (($_userId === null)) {
            $_userId = Tinebase_Core::getUser();
        }
        $user = ($_userId instanceof Tinebase_Model_User) ? $_userId : Tinebase_User::getInstance()->getUserById($_userId);
        
        $translate = Tinebase_Translation::getTranslation();
        $noteText = $translate->_($_type) . ' ' . $translate->_('by') . ' ' . $user->accountDisplayName;

        if ($_mods !== null) {
            if ($_mods instanceof Tinebase_Record_RecordSet && count($_mods) > 0) {
                $noteText .= ' | ' .$translate->_('Changed fields:');
                foreach ($_mods as $mod) {
                    $modifiedAttribute = $mod->modified_attribute;
                    if (empty($modifiedAttribute)) {
                        $noteText.= ' ' . $this->_getSystemNoteChangeText($mod, $translate);
                    } else {
                        $noteText .= ' ' . $translate->_($mod->modified_attribute) . ' (' . $this->_getSystemNoteChangeText($mod) . ')';
                    }
                }
            } else if (is_string($_mods)) {
                $noteText = $_mods;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Adding "' . $_type . '" system note note to record (id ' . $id . ')');
        }

        $note = new Tinebase_Model_Note(array(
            'note_type_id'              => $_type,
            'restricted_to'             => null,
            'note'                      => mb_substr($noteText, 0, self::MAX_NOTE_LENGTH),
            'record_model'              => $modelName,
            'record_backend'            => ucfirst(strtolower($_backend)),
            'record_id'                 => $id,
            'seq'                       => $seq,
        ));
        
        return $this->addNote($note);
    }
    
    /**
     * get system note change text
     * 
     * @param Tinebase_Model_ModificationLog $modification
     * @param Zend_Translate $translate
     * @return string
     */
    protected function _getSystemNoteChangeText(Tinebase_Model_ModificationLog $modification, ?\Zend_Translate $translate = null)
    {
        $recordProperties = [];
        /** @var Tinebase_Record_Interface $model */
        if (($model = $modification->record_type) && ($mc = $model::getConfiguration())) {
            $recordProperties = $mc->recordFields;
        }
        $modifiedAttribute = $modification->modified_attribute;

        // new ModificationLog implementation
        if (empty($modifiedAttribute)) {
            $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
            $return = '';
            foreach ($diff->diff as $attribute => $value) {

                if (is_array($value) && isset($value['model']) && isset($value['added'])) {
                    $tmpDiff = new Tinebase_Record_RecordSetDiff($value);

                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' fetching translated text for diff: ' . print_r($tmpDiff->toArray(), true));

                    $return .= ' ' . $translate->_($attribute) . ' (' . $tmpDiff->getTranslatedDiffText() . ')';
                } else {
                    $oldData = $diff->oldData ? $diff->oldData[$attribute] : null;

                    if (isset($recordProperties[$attribute]) && ($oldData || $value) &&
                            isset($recordProperties[$attribute]['config']['controllerClassName']) && ($controller =
                            $recordProperties[$attribute]['config']['controllerClassName']::getInstance()) &&
                            method_exists($controller, 'get')) {
                        if ($oldData) {
                            if (is_array($oldData)) $oldData = $oldData['id'] ?? '';
                            try {
                                $oldDataString = $controller->get($oldData, null, false, true)->getTitle();
                            } catch (Tinebase_Exception_NotFound|Tinebase_Exception_AccessDenied) {
                                $oldDataString = $oldData;
                            }
                        } else {
                            $oldDataString = '';
                        }
                        if ($value) {
                            if (is_array($value)) $value = $value['id'] ?? '';
                            try {
                                $valueString = $controller->get($value, null, false, true)->getTitle();
                            } catch(Tinebase_Exception_NotFound|Tinebase_Exception_AccessDenied) {
                                $valueString = $value;
                            }
                        } else {
                            $valueString = '';
                        }
                    } else {
                        if (is_array($oldData)) {
                            $oldDataString = '';
                            foreach ($oldData as $key => $val) {
                                if (is_object($val)) {
                                    $val = $val->toArray();
                                }
                                $oldDataString .= ' ' . $key . ': ' . (is_array($val) ? ($val['id'] ?? print_r($val,
                                        true)) : $val);
                            }
                        } else {
                            $oldDataString = $oldData;
                        }
                        if (is_array($value)) {
                            $valueString = '';
                            foreach ($value as $key => $val) {
                                if (is_object($val)) {
                                    $val = $val->toArray();
                                }
                                $valueString .= ' ' . $key . ': ' . (is_array($val) ? ($val['id'] ?? print_r($val,
                                        true)) : $val);
                            }
                        } else {
                            $valueString = $value;
                        }
                    }

                    if (null !== $oldDataString || (null !== $valueString && '' !== $valueString)) {
                        $return .= ' ' . $translate->_($attribute) . ' (' . $oldDataString . ' -> ' . $valueString . ')';
                    }
                }
            }

            return $return;

        // old ModificationLog implementation
        } else {
            // check if $modification->new_value is json string and record set diff
            // @see 0008546: When edit event, history show "code" ...
            if (Tinebase_Helper::is_json($modification->new_value)) {
                $newValueArray = Zend_Json::decode($modification->new_value);
                if ((isset($newValueArray['model']) || array_key_exists('model', $newValueArray)) && (isset($newValueArray['added']) || array_key_exists('added', $newValueArray))) {
                    $diff = new Tinebase_Record_RecordSetDiff($newValueArray);

                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' fetching translated text for diff: ' . print_r($diff->toArray(), true));

                    return $diff->getTranslatedDiffText();
                }
            }

            return $modification->old_value . ' -> ' . $modification->new_value;
        }
    }
    
    /**
     * add multiple modification system nodes
     * 
     * @param Tinebase_Record_RecordSet $_mods
     * @param string $_userId
     * @param string $modelName
     */
    public function addMultipleModificationSystemNotes($_mods, $_userId, $modelName = null)
    {
        $_mods->addIndices(array('record_id'));
        foreach ($_mods->record_id as $recordId) {
            $modsOfRecord = $_mods->filter('record_id', $recordId);
            $this->addSystemNote($recordId, $_userId, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $modsOfRecord, 'Sql', $modelName);
        }
    }

    /**
     * delete notes
     *
     * @param Tinebase_Record_RecordSet $notes
     */
    public function deleteNotes(Tinebase_Record_RecordSet $notes)
    {
        $sqlBackend = new Tinebase_Backend_Sql(
            array(
                'tableName' => $this->getTableName(),
                'modelName' => 'Tinebase_Model_Note'
            ),
            $this->getAdapter());

        foreach($notes as $note) {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($note, 'delete', $note);
            $sqlBackend->update($note);
        }
    }

    /**
     * undelete notes
     *
     * @param array $ids
     */
    public function unDeleteNotes(array $ids)
    {
        $sqlBackend = new Tinebase_Backend_Sql(
            array(
                'tableName' => $this->getTableName(),
                'modelName' => 'Tinebase_Model_Note'
            ),
            $this->getAdapter());

        $notes = $sqlBackend->getMultiple($ids);
        foreach($notes as $note) {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($note, 'undelete', $note);
            $sqlBackend->update($note);
        }
    }

    /**
     * delete notes
     *
     * @param  string $_model     model of record
     * @param  string $_backend   backend of record
     * @param  string $_id        id of record
     */
    public function deleteNotesOfRecord($_model, $_backend, $_id)
    {
        $backend = ucfirst(strtolower($_backend));
        
        $notes = $this->getNotesOfRecord($_model, $_id, $backend);

        $this->deleteNotes($notes);
    }
    
    /**
     * get note filter
     * 
     * @param string|array $_id
     * @param string $_model
     * @param string $_backend
     * @param boolean $_onlyNonSystemNotes
     * @return Tinebase_Model_NoteFilter
     */
    protected function _getNotesFilter($_id, $_model, $_backend, $_onlyNonSystemNotes = true): Tinebase_Model_NoteFilter
    {
        $backend = ucfirst(strtolower($_backend));
        $noteTypes = Tinebase_Config::getInstance()->get(Tinebase_Config::NOTE_TYPE)->records;
        $currentUser = Tinebase_Core::getUser();
        if ($currentUser && !is_string($currentUser)) {
            $currentUser = $currentUser->getId();
        }

        if ($_onlyNonSystemNotes) {
            $noteTypes = $noteTypes->filter('is_user_type', 1);
        }

        $filterData = [
            [
                'field' => 'record_model',
                'operator' => 'equals',
                'value' => $_model
            ], [
                'field' => 'record_backend',
                'operator' => 'equals',
                'value' => $backend
            ], [
                'field' => 'record_id',
                'operator' => 'in',
                'value' => (array) $_id
            ], [
                'field' => 'note_type_id',
                'operator' => 'in',
                'value' => $noteTypes->getId()
            ],
        ];
        if ($currentUser) {
            $filterData[] = ['condition' => 'OR',
                'filters' => [
                    [
                        'field' => 'restricted_to',
                        'operator' => 'equals',
                        'value' => $currentUser
                    ], [
                        'field' => 'restricted_to',
                        'operator' => 'equals',
                        'value' => null,
                    ]
                ]
            ];
        }
        return new Tinebase_Model_NoteFilter($filterData);
    }
    
    /************************** note types *******************/

    /**
     * Search for records matching given filter
     *
     *
     * @param  Tinebase_Model_Filter_FilterGroup $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @param  array|string|boolean $_cols columns to get, * per default / use self::IDCOL or TRUE to get only ids
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function search(
        ?\Tinebase_Model_Filter_FilterGroup $_filter = null,
        ?\Tinebase_Model_Pagination $_pagination = null,
        $_cols = '*'
    ): never {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Return a single record
     *
     * @param string $_id
     * @param boolean $_getDeleted get deleted records
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function get($_id, $_getDeleted = FALSE): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getMultiple(string|array $_ids, ?array $_containerIds = null, bool $_getDeleted = false): Tinebase_Record_RecordSet
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC'): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Create a new persistent contact
     *
     * @param  Tinebase_Record_Interface $_record
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function create(Tinebase_Record_Interface $_record): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Upates an existing persistent record
     *
     * @param  Tinebase_Record_Interface $_record
     * @return Tinebase_Record_Interface|null
     * @throws Tinebase_Exception_NotImplemented
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $data = $_record->toArray(false, false);

        if (!isset($data['id'])) throw new Tinebase_Exception_Backend('id not set');
        if (mb_strlen((string)$data['note']) > 65535) {
            $data['note'] = mb_substr((string) $data['note'], 0, 65535);
        }

        $this->_notesTable->update($data, $this->_db->quoteInto('id = ?', $data['id']));

        return $_record;
    }

    /**
     * update multiple records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_data
     * @param Tinebase_Model_Pagination $_pagination
     * @throws Tinebase_Exception_NotImplemented
     */
    public function updateMultiple($_filter, $_data, $_pagination = null): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * Deletes one or more existing persistent record(s)
     *
     * @param string|array $_identifier
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function delete($_identifier): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * get backend type
     *
     * @return never
     * @throws Tinebase_Exception_NotImplemented
     */
    public function getType(): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * sets modlog active flag
     *
     * @param $_bool
     * @return never
     */
    public function setModlogActive($_bool): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * checks if modlog is active or not
     *
     * @return never
     */
    public function getModlogActive(): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * fetch a single property for all records defined in array of $ids
     *
     * @param array|string $ids
     * @param string $property
     * @return never
     */
    public function getPropertyByIds($ids, $property): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * get all Notes, including deleted ones, no ACL check
     *
     * @ param boolean $ignoreACL
     * @ param boolean $getDeleted
     * @return Tinebase_Record_RecordSet subtype Tinebase_Model_Note
     */
    public function getAllNotes($orderBy = null, $limit = null, $offset = null)
    {
        $select = $this->_db->select()
            ->from(array('notes' => SQL_TABLE_PREFIX . 'notes'));
        if (null !== $orderBy) {
            $select->order($orderBy);
        }
        if (null !== $limit) {
            $select->limit($limit, $offset);
        }

        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);

        return new Tinebase_Record_RecordSet('Tinebase_Model_Note', $rows, true);
    }

    /**
     * permanently delete notes by id
     *
     * @param array $_ids
     * @return int
     */
    public function purgeNotes(array $_ids)
    {
        return $this->_db->delete(SQL_TABLE_PREFIX . 'notes', $this->_db->quoteInto('id IN (?)', $_ids));
    }

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return never
     */
    public function has(array $_ids, $_getDeleted = false): never
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' is not implemented');
    }

    /**
     * @param bool $purge
     * @param int $offset
     * @param ?bool $dryrun
     * @param ?Tinebase_DateTime $beforeDate
     * @return int
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function removeObsoleteData(
        bool $purge = false,
        int $offset = 0,
        ?bool $dryrun = null,
        ?Tinebase_DateTime $beforeDate = null
    ): int
    {
        $limit = 10000;
        $controllers = [];
        $models = [];
        $deleteIds = [];
        $deletedCount = 0;

        $oneMonthBefore = Tinebase_DateTime::now()->subMonth(1);

        $purgeCountCreated = 0;
        $purgeCountEmptyUpdate = 0;

        do {
            $notes = $this->getAllNotes('creation_time ASC', $limit, $offset);
            $offset += $limit;

            /** @var Tinebase_Model_Note $note */
            foreach ($notes as $note) {
                if ($beforeDate && $note->creation_time->isLater($beforeDate)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Retention time reached - we keep the rest');
                    }
                    return $deletedCount;
                }

                if ($note->note_type_id === Tinebase_Model_Note::SYSTEM_NOTE_AVSCAN) {
                    // we only keep the avscan notes of last month
                    if ($note->creation_time->isEarlier($oneMonthBefore)) {
                        $deleteIds[] = $note->getId();
                        continue;
                    }
                }

                if (!isset($controllers[$note->record_model])) {
                    if (str_starts_with($note->record_model, 'Tinebase')) {
                        continue;
                    }
                    try {
                        /* @var Tinebase_Controller_Record_Abstract $recordController */
                        $recordController = Tinebase_Core::getApplicationInstance($note->record_model);
                        if (! $recordController instanceof Tinebase_Controller_Record_Abstract) {
                            // make phpstan happy
                            continue;
                        }
                        $controllers[$note->record_model] = $recordController;
                    } catch (Tinebase_Exception_AccessDenied) {
                        // TODO log
                        continue;
                    } catch (Tinebase_Exception_NotFound) {
                        $deleteIds[] = $note->getId();
                        continue;
                    }
                    $oldACLCheckValue = $recordController->doContainerACLChecks(false);
                    $oldRightCheckValue = $recordController->doRightChecks(false);
                    $models[$note->record_model] = array(
                        0 => new $note->record_model(),
                        1 => ($note->record_model !== 'Filemanager_Model_Node' && class_exists($note->record_model . 'Filter')),
                        2 => $note->record_model . 'Filter',
                        3 => $oldACLCheckValue,
                        4 => $oldRightCheckValue,
                    );
                }
                $recordController = $controllers[$note->record_model];
                $model = $models[$note->record_model];

                if ($model[1]) {
                    $filter = new $model[2](array(
                        array(
                            'field' => $model[0]->getIdProperty(),
                            'operator' => 'equals',
                            'value' => $note->record_id
                        )
                    ));
                    if ($model[0]->has('is_deleted')) {
                        $filter->addFilter(new Tinebase_Model_Filter_Int(array(
                            'field' => 'is_deleted',
                            'operator' => 'notnull',
                            'value' => null
                        )));
                    }
                    $result = $recordController->searchCount($filter);

                    if (is_bool($result) || (is_string($result) && $result === ((string)intval($result)))) {
                        $result = (int)$result;
                    }

                    if (!is_int($result)) {
                        if (is_array($result) && isset($result['totalcount'])) {
                            $result = (int)$result['totalcount'];
                        } elseif (is_array($result) && isset($result['count'])) {
                            $result = (int)$result['count'];
                        } else {
                            // todo log
                            // dummy line, remove!
                            $result = 1;
                        }
                    }

                    if ($result === 0) {
                        $deleteIds[] = $note->getId();
                    } else if ($purge) {
                        if ($note->note_type_id === Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED) {
                            $deleteIds[] = $note->getId();
                            $purgeCountCreated++;
                        } else if ($note->note_type_id === Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED
                            && !str_contains($note->note, '|'))
                        {
                            $deleteIds[] = $note->getId();
                            $purgeCountEmptyUpdate++;
                        }
                    }
                } else {
                    try {

                        $recordController->get($note->record_id, null, false, true);
                    } catch (Tinebase_Exception_NotFound) {
                        $deleteIds[] = $note->getId();
                    }
                }
            }
            if (count($deleteIds) > 0) {
                $deletedCount += count($deleteIds);
                if ($dryrun) {
                    $offset -= count($deleteIds);
                } else {
                    $offset -= $this->purgeNotes($deleteIds);
                }
                if ($offset < 0) {
                    $offset = 0;
                }
                $deleteIds = [];
            }
        } while ($notes->count() === $limit);

        foreach ($controllers as $model => $controller) {
            $controller->doContainerACLChecks($models[$model][3]);
            $controller->doRightChecks($models[$model][4]);
        }

        if ($purge && Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Purged ' . $purgeCountEmptyUpdate . ' system notes with empty updates');
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Purged ' . $purgeCountCreated . ' create system notes');
        }

        return $deletedCount;
    }

    public function addSelectHook(Tinebase_Backend_Sql_SelectHook $hook): void
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__);
    }

    public function removeSelectHook(Tinebase_Backend_Sql_SelectHook $hook): void
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__);
    }

    public function getByProperty(mixed $value, string $property = 'name', bool $getDeleted = false): Tinebase_Record_Interface
    {
        throw new Tinebase_Exception_NotImplemented();
    }
}
