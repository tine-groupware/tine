<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Notes
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * defines the datatype for one note
 * 
 * @package     Tinebase
 * @subpackage  Notes
 *
 * @property    string      $id
 * @property    string      $note_type_id
 * @property    string      $restricted_to
 * @property    string      $note
 * @property    string      $record_id
 * @property    string      $record_model
 * @property    string      $record_backend
 */
class Tinebase_Model_Note extends Tinebase_Record_Abstract
{
    public const FLD_NOTE_TYPE_ID = 'note_type_id';
    public const FLD_RESTRICTED_TO = 'restricted_to';
    public const FLD_NOTE = 'note';
    public const FLD_RECORD_ID = 'record_id';
    public const FLD_RECORD_MODEL = 'record_model';
    public const FLD_RECORD_BACKEND = 'record_backend';

    /**
     * system note type: note
     *
     * @staticvar string
     */
    public const SYSTEM_NOTE_NAME_NOTE = 'note';
    
    /**
     * system note type: telephone
     *
     * @staticvar string
     */
    public const SYSTEM_NOTE_NAME_TELEPHONE = 'telephone';
    
    /**
     * system note type: email
     *
     * @staticvar string
     */
    public const SYSTEM_NOTE_NAME_EMAIL = 'email';
    
    /**
     * system note type: created
     * 
     * @staticvar string
     */
    public const SYSTEM_NOTE_NAME_CREATED = 'created';
    
    /**
     * system note type: changed
     * 
     * @staticvar string
     */
    public const SYSTEM_NOTE_NAME_CHANGED = 'changed';

    /**
     * system note type: avscan
     *
     * @staticvar string
     */
    public const SYSTEM_NOTE_AVSCAN = 'avscan';

    /**
     * system note type: revealPassword
     *
     * @staticvar string
     */
    public const SYSTEM_NOTE_REVEAL_PASSWORD = 'revealPassword';

    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'recordName'        => 'Note',
        'recordsName'       => 'Notes', // ngettext('Note', 'Notes', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => false,
        // this will add a notes property which we shouldn't have...
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        'appName'           => 'Tinebase',
        'modelName'         => 'Note',
        'idProperty'        => 'id',

        'filterModel'       => [],

        'fields' => [
            self::FLD_NOTE_TYPE_ID => [
                'type' => self::TYPE_KEY_FIELD,
                'name' => Tinebase_Config::NOTE_TYPE,
                'validators' => [
                    'presence' => 'required',
                    Zend_Filter_Input::ALLOW_EMPTY => false
                ]
            ],
            self::FLD_RESTRICTED_TO => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Restricted to', // _('Restricted to')
                self::DEFAULT_VAL => null,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DESCRIPTION => 'If active, it is only visible for the creator of this note', //_('If active, it is only visible for the creator of this note')
                self::INPUT_FILTERS => [
                    Zend_Filter_Empty::class => null
                ],
            ],
            self::FLD_NOTE => [
                'type' => 'string',
                'validators' => [
                    'presence' => 'required',
                    Zend_Filter_Input::ALLOW_EMPTY => false
                ],
                'inputFilters' => [Zend_Filter_StringTrim::class => null],
            ],
            self::FLD_RECORD_ID => [
                'type' => 'string',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_RECORD_MODEL => [
                'type' => 'string',
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_RECORD_BACKEND => [
                'type' => 'string',
                'validators' => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => 'Sql'
                ],
            ],
        ],
    ];
    
    /**
     * returns array with record related properties
     * resolves the creator display name and calls Tinebase_Record_Abstract::toArray() 
     *
     * @param boolean $_recursive
     * @param boolean $_resolveCreator
     * @return array
     */    
    public function toArray($_recursive = TRUE, $_resolveCreator = TRUE)
    {
        $result = parent::toArray($_recursive);
        
        // get creator
        if ($this->created_by && $_resolveCreator) {
            //resolve creator; return default NonExistentUser-Object if creator cannot be resolved =>
            //@todo perhaps we should add a "getNonExistentUserIfNotExists" parameter to Tinebase_User::getUserById 
            try {
                $creator = Tinebase_User::getInstance()->getUserById($this->created_by);
            }
            catch (Tinebase_Exception_NotFound) {
                $creator = Tinebase_User::getInstance()->getNonExistentUser();
            }
             
            $result['created_by'] = $creator->accountDisplayName;
        }
        
        return $result;
    }
}
