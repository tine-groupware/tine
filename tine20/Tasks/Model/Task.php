<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Task-Record Class
 * 
 * @package     Tasks
 * @subpackage    Model
 */
class Tasks_Model_Task extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Task';
    public const TABLE_NAME = 'tasks';
    public const CLASS_PUBLIC         = 'PUBLIC';
    public const CLASS_PRIVATE        = 'PRIVATE';

    public const FLD_ATTENDEES = 'attendees';
    public const FLD_DEPENDENS_ON = 'dependens_on';
    public const FLD_DEPENDENT_TASKS = 'dependent_taks';
    public const FLD_DUE = 'due';

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
    protected static $_modelConfiguration = array(
        self::VERSION       => 12,
        'recordName'        => 'Task',  // gettext('GENDER_Task')
        'recordsName'       => 'Tasks', // ngettext('Task', 'Tasks', n)
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        self::HAS_ALARMS    => true,
        'createModule'      => true,
        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        'containerProperty' => 'container_id',

        'containerName'     => 'Tasks',
        'containersName'    => 'Tasks',
        'containerUsesFilter' => true,

        'titleProperty'     => 'summary',//array('%s - %s', array('number', 'title')),
        'appName'           => 'Tasks',
        'modelName'         => 'Task',

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'description'       => [
                    self::COLUMNS       => ['description'],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
                'organizer'       => [
                    self::COLUMNS       => ['organizer'],
                ],
                'uid__id'       => [
                    self::COLUMNS       => ['uid', 'id'],
                ],
                'etag'       => [
                    self::COLUMNS       => ['etag'],
                ],
            ]
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'source' => [],
                self::FLD_ATTENDEES => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Tasks_Model_Attendee::FLD_USER_ID => [],
                        'alarms' => [],
                    ],
                ],
                self::FLD_DEPENDENS_ON => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Tasks_Model_TaskDependency::FLD_DEPENDS_ON => [],
                    ],
                ],
                self::FLD_DEPENDENT_TASKS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Tasks_Model_TaskDependency::FLD_TASK_ID => [],
                    ],
                ],
            ],
        ],
        
        'fields'            => array(
            'summary'           => array(
                'label'             => 'Summary', //_('Summary'),
                'type'              => 'string',
                self::LENGTH        => 255,
                'validators'        => array(Zend_Filter_Input::PRESENCE => 'required'),
                'queryFilter'       => true,
            ),
            'description'       => array(
                'label'             => 'Description', //_('Description')
                'type'              => 'fulltext',
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'queryFilter'       => true,
            ),
            self::FLD_DUE       => array(
                'label'             => 'Due', //_('Due')
                'type'              => 'datetime',
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'priority'          => array(
                'label'             => 'Priority', //_('Priority')
                self::TYPE          => self::TYPE_KEY_FIELD,
                self::NAME          => Tasks_Config::TASK_PRIORITY,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'default'           => Tasks_Model_Priority::NORMAL,
            ),
            'percent'           => array(
                'label'             => 'Percent', //_('Percent')
                'type'              => 'integer',
                'specialType'       => 'percent',
                'default'           => 0,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'status'            => array(
                'label'             => 'Status', //_('Status')
                self::TYPE          => self::TYPE_KEY_FIELD,
                self::NAME          => Tasks_Config::TASK_STATUS,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => false),
                self::DEFAULT_VAL   => 'NEEDS-ACTION',
            ),
            'organizer'         => array(
                'label'             => 'Organizer', //_('Organizer')
                'type'              => 'user',
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'      => array(Zend_Filter_Empty::class => null),
            ),
            'originator_tz'     => array(
                'label'             => null,
                'type'              => 'string',
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'class'             => array(
                self::DISABLED      => true,
                'label'             => 'Class', //_('Class')
                'type'              => 'string',
                self::DEFAULT_VAL   => self::CLASS_PUBLIC,
                'validators'        => array(
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    array('InArray', array(self::CLASS_PUBLIC, self::CLASS_PRIVATE)),
                ),
            ),
            'completed'         => array(
                'label'             => 'Completed', //_('Completed')
                'type'              => 'datetime',
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'geo'               => array(
                self::DISABLED      => true,
                'label'             => 'Geo', //_('Geo')
                'type'              => 'float',
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'location'          => array(
                self::DISABLED      => true,
                'label'             => 'Location', //_('Location')
                'type'              => 'string',
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'url'               => array(
                'label'             => null,
                'type'              => 'string',
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'uid'               => array(
                'label'             => null,
                'type'              => 'string',
                self::LENGTH        => 255,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'etag'              => array(
                'label'             => null,
                'type'              => 'string',
                self::LENGTH        => 60,
                self::NULLABLE      => true,
                'validators'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'dtstart'           => [
                self::LABEL         => null,
                self::TYPE          => self::TYPE_DATETIME,
                self::NULLABLE      => true,
            ],
            'source'    => [
                self::LABEL         => 'Source', // _('Source')
                self::TYPE          => self::TYPE_DYNAMIC_RECORD,
                self::LENGTH        => 40,
                self::NULLABLE      => true,
                self::CONFIG        => [
                    self::REF_MODEL_FIELD               => 'source_model',
                    self::PERSISTENT                    => Tinebase_Model_Converter_DynamicRecord::REFID,
                ],
                self::FILTER_DEFINITION => [
                    self::FILTER            => Tinebase_Model_Filter_Id::class,
                ]
            ],
            'source_model' => [
                self::TYPE          => self::TYPE_MODEL,
                self::LENGTH        => 100,
                self::NULLABLE      => true,
                self::CONFIG        => [
                    self::AVAILABLE_MODELS => [],
                ]
            ],
            self::FLD_DEPENDENS_ON => [
                self::LABEL         => 'Depends on', // _('Depends on')
                self::TYPE          => self::TYPE_RECORDS,
                self::CONFIG        => [
                    self::APP_NAME          => Tasks_Config::APP_NAME,
                    self::MODEL_NAME        => Tasks_Model_TaskDependency::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS => true,
                    self::REF_ID_FIELD      => Tasks_Model_TaskDependency::FLD_TASK_ID,
                ],
                self::UI_CONFIG                 => [
                    'xtype' => 'tasks.dependency'
                ],
            ],
            self::FLD_DEPENDENT_TASKS => [
                self::LABEL         => 'Dependent Tasks', // _('Dependent Tasks')
                self::TYPE          => self::TYPE_RECORDS,
                self::CONFIG        => [
                    self::APP_NAME          => Tasks_Config::APP_NAME,
                    self::MODEL_NAME        => Tasks_Model_TaskDependency::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS => true,
                    self::REF_ID_FIELD      => Tasks_Model_TaskDependency::FLD_DEPENDS_ON,
                ],
                self::UI_CONFIG                 => [
                    'xtype' => 'tasks.dependency'
                ],
            ],
            self::FLD_ATTENDEES         => [
                self::LABEL                 => 'Collaborators', // _('Collaborators')
                self::TYPE                  => self::TYPE_RECORDS,
                self::CONFIG        => [
                    self::APP_NAME          => Tasks_Config::APP_NAME,
                    self::MODEL_NAME        => Tasks_Model_Attendee::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS => true,
                    self::REF_ID_FIELD      => Tasks_Model_Attendee::FLD_TASK_ID,
                ],
            ],
        ),
    );

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User'     => array('created_by', 'last_modified_by', 'organizer'),
        'recursive'               => array('attachments' => 'Tinebase_Model_Tree_Node'),
    );
    
    /**
     * sets the record related properties from user generated input.
     *
     * TODO FIXME remove this whole function!!!!
     *
     * @param   array $_data
     * @return void
     */
    public function setFromArray(array &$_data)
    {
        if (empty($_data['geo'])) {
            $_data['geo'] = NULL;
        }
        
        if (empty($_data['class'])) {
            $_data['class'] = self::CLASS_PUBLIC;
        }
        
        if (isset($_data['organizer']) && is_array($_data['organizer'])
        ) {
            if (isset($_data['organizer']['account_id'])) {
                $_data['organizer'] = $_data['organizer']['account_id'];
            } else if (isset($_data['organizer']['accountId'])) {
                $_data['organizer'] = $_data['organizer']['accountId'];
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Account ID missing from organizer data: '
                    . print_r($_data['organizer'], true));
            }
        }
        
        parent::setFromArray($_data);
    }
    
    /**
     * create notification message for task alarm
     *
     * @return string
     * @throws Tinebase_Exception_NotFound
     * 
     * @todo should we get the locale pref for each single user here instead of the default?
     * @todo move lead stuff to Crm(_Model_Lead)?
     * @todo add getSummary to Addressbook_Model_Contact for linked contacts?
     * @todo what about priority translation here?
     */
    public function getNotificationMessage(): string
    {
        // get locale from prefs
        $localePref = Tinebase_Core::getPreference()->getValue(Tinebase_Preference::LOCALE);
        $locale = Tinebase_Translation::getLocale($localePref);
        
        $translate = Tinebase_Translation::getTranslation($this->_application, $locale);
        
        // get date strings
        $timezone = ($this->originator_tz) ? $this->originator_tz : Tinebase_Core::getUserTimezone();
        $dueDateString = Tinebase_Translation::dateToStringInTzAndLocaleFormat($this->due, $timezone, $locale);
        
        // resolve values
        Tinebase_User::getInstance()->resolveUsers($this, 'organizer', true);
        $status = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records->getById($this->status);
        $organizerName = ($this->organizer) ? $this->organizer->accountDisplayName : '';
        
        $text = $this->summary . "\n\n"
            . $translate->_('Due')          . ': ' . $dueDateString                  . "\n" 
            . $translate->_('Organizer')    . ': ' . $organizerName                  . "\n" 
            . $translate->_('Description')  . ': ' . $this->description              . "\n"
            . $translate->_('Priority')     . ': ' . $this->priority                 . "\n"
            . $translate->_('Status')       . ': ' . $translate->_($status['value']) . "\n"
            . $translate->_('Percent')      . ': ' . $this->percent                  . "%\n\n";
            
        // add relations (get with ignore acl)
        $relations = Tinebase_Relations::getInstance()->getRelations(
            get_class($this),
            'Sql',
            $this->getId(),
            null, array('TASK'),
            true);

        foreach ($relations as $relation) {
            /* @var Tinebase_Model_Relation $relation */
            if ($relation->related_model == 'Crm_Model_Lead') {
                $lead = $relation->related_record;
                if ($lead === null) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' Related lead not found for relation: '
                        . print_r($relation->toArray(), true));
                    continue;
                }
                $text .= $translate->_('Lead') . ': ' . $lead->lead_name . "\n";
                $leadRelations = Tinebase_Relations::getInstance()->getRelations(get_class($lead), 'Sql', $lead->getId());
                foreach ($leadRelations as $leadRelation) {
                    if ($leadRelation->related_model == 'Addressbook_Model_Contact') {
                        $contact = $leadRelation->related_record;
                        $text .= $leadRelation->type . ': ' . $contact->n_fn . ' (' . $contact->org_name . ')' . "\n"
                            . ((! empty($contact->tel_work)) ?  "\t" . $translate->_('Telephone')
                                . ': ' . $contact->tel_work   . "\n" : '')
                            . ((! empty($contact->email)) ?     "\t" . $translate->_('Email')
                                . ': ' . $contact->email      . "\n" : '');
                    }
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' ' . $text);
            
        return $text;
    }
    
    /**
     * sets and returns the addressbook entry of the organizer
     * 
     * @return Addressbook_Model_Contact
     */
    public function resolveOrganizer()
    {
        Tinebase_User::getInstance()->resolveUsers($this, 'organizer', true);
        
        if (! empty($this->organizer) && $this->organizer instanceof Tinebase_Model_User) {
            $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($this->organizer->contact_id, TRUE);
            if ($contacts) {
                return $contacts->getFirstRecord();
            }
        }
    }
}
