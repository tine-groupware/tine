<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of an attendee
 *
 * @package Calendar
 * @property Tinebase_DateTime $alarm_ack_time
 * @property Tinebase_DateTime $alarm_snooze_time
 * @property string $transp
 * @property string $user_id
 * @property string $status
 * @property string $status_authkey
 * @property string $user_type
 * @property string $displaycontainer_id
 * @property string $user_email
 */
class Calendar_Model_Attender extends Tinebase_Record_Abstract
{
    /**
     * supported user types
     */
    const USERTYPE_USER        = 'user';
    const USERTYPE_GROUP       = 'group';
    const USERTYPE_GROUPMEMBER = 'groupmember';
    const USERTYPE_RESOURCE    = 'resource';
    const USERTYPE_ANY         = 'any';
    const USERTYPE_EMAIL       = 'email';

    /**
     * supported roles
     */
    const ROLE_REQUIRED        = 'REQ';
    const ROLE_OPTIONAL        = 'OPT';
    
    /**
     * supported status
     */
    const STATUS_NEEDSACTION   = 'NEEDS-ACTION';
    const STATUS_ACCEPTED      = 'ACCEPTED';
    const STATUS_DECLINED      = 'DECLINED';
    const STATUS_TENTATIVE     = 'TENTATIVE';

    const XPROP_REPLY_DTSTAMP  = 'replyDtstamp';
    const XPROP_REPLY_SEQUENCE = 'replySequence';

    const TRANSP_TRANSP        = 'TRANSPARENT';
    const TRANSP_OPAQUE        = 'OPAQUE';

    const FLD_CAL_EVENT_ID = 'cal_event_id';
    const FLD_USER_ID = 'user_id';
    const FLD_USER_TYPE = 'user_type';
    const FLD_USER_EMAIL = 'user_email';
    const FLD_USER_DISPLAYNAME = 'user_displayname';
    const FLD_ROLE = 'role';
    const FLD_QUANTITY = 'quantity';
    const FLD_STATUS = 'status';
    const FLD_STATUS_AUTHKEY = 'status_authkey';
    const FLD_DISPLAYCONTAINER_ID = 'displaycontainer_id';
    const FLD_TRANSP = 'transp';

    const MODEL_NAME_PART = 'Attender';
    const TABLE_NAME = 'cal_attendee';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = Calendar_Config::APP_NAME;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION           => 10,
        self::RECORD_NAME       => 'Attendee',
        self::RECORDS_NAME       => 'Attendees', // ngettext('Attendee', 'Attendees', n)
        self::CONTAINER_PROPERTY => NULL,
        self::TITLE_PROPERTY     => self::FLD_USER_ID,
        self::HAS_RELATIONS      => FALSE,
        self::HAS_CUSTOM_FIELDS   => FALSE,
        self::HAS_SYSTEM_CUSTOM_FIELDS => TRUE,
        self::HAS_NOTES          => FALSE,
        self::HAS_TAGS          => FALSE,
        self::HAS_ATTACHMENTS   => FALSE,
        self::MODLOG_ACTIVE      => TRUE,
        self::HAS_XPROPS        => TRUE,

        self::CREATE_MODULE      => FALSE,

        self::EXPOSE_HTTP_API     => FALSE,
        self::EXPOSE_JSON_API     => FALSE,

        self::APP_NAME           => Calendar_Config::APP_NAME,
        self::MODEL_NAME         => self::MODEL_NAME_PART,

        self::TABLE            => [
            self::NAME    => self::TABLE_NAME,
            self::INDEXES       => [
                'user_id'             => [
                    self::COLUMNS               => ['user_id'],
                ],
                'user_type'                      => [
                    self::COLUMNS               => ['user_type'],
                ],
                'status'               => [
                    self::COLUMNS               => ['status'],
                ],
                'status_authkey'                     => [
                    self::COLUMNS               => ['status_authkey'],
                ],
            ],
        ],

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'cal_attendee::cal_event_id-cal_events::id' => [
                    'targetEntity' => Calendar_Model_Event::class,
                    'fieldName' => self::FLD_CAL_EVENT_ID,
                    'joinColumns' => [[
                        'name' => self::FLD_CAL_EVENT_ID,
                        'referencedColumnName'  => 'id',
                        self::ON_DELETE                 => 'CASCADE',
                    ]],
                ],
                'cal_attendee::displaycontainer_id--container::id' => [
                    'targetEntity' => Tinebase_Model_Container::class,
                    'fieldName' => self::FLD_DISPLAYCONTAINER_ID,
                    'joinColumns' => [[
                        'name' => self::FLD_DISPLAYCONTAINER_ID,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],


        self::FIELDS          => [
            self::FLD_USER_ID => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 40,
                self::NULLABLE   => true,
                self::LABEL      => 'User', // _('User')
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_CAL_EVENT_ID => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 40,
                self::NULLABLE   => false,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL      => 'Event Id', // _('Event Id')
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_USER_TYPE     => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 32,
                self::DEFAULT_VAL   => self::USERTYPE_USER,
                self::NULLABLE      => false,
                self::VALIDATORS    => [
                    ['InArray', [self::USERTYPE_ANY, self::USERTYPE_USER, self::USERTYPE_GROUP, self::USERTYPE_GROUPMEMBER, self::USERTYPE_RESOURCE, self::USERTYPE_EMAIL]],
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::USERTYPE_USER
                ],
            ],
            self::FLD_USER_EMAIL => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 255,
                self::NULLABLE   => true,
            ],
            self::FLD_USER_DISPLAYNAME => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 255,
                self::NULLABLE   => true,
            ],
            self::FLD_ROLE     => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 32,
                self::DEFAULT_VAL   => self::ROLE_REQUIRED,
                self::NULLABLE      => false,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::ROLE_REQUIRED
                ],
            ],
            self::FLD_TRANSP     => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 40,
                self::DEFAULT_VAL   => self::TRANSP_OPAQUE,
                self::NULLABLE      => true,
                self::VALIDATORS    => [
                    ['InArray', [self::TRANSP_OPAQUE, self::TRANSP_TRANSP]],
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::TRANSP_OPAQUE
                ],
            ],
            self::FLD_DISPLAYCONTAINER_ID => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 40,
                self::NULLABLE   => true,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_STATUS_AUTHKEY => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 40,
                self::NULLABLE   => false,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            self::FLD_STATUS => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 40,
                self::NULLABLE   => false,
                self::DEFAULT_VAL   => self::STATUS_NEEDSACTION,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::STATUS_NEEDSACTION
                ],
            ],
            self::FLD_QUANTITY => [
                self::TYPE       => self::TYPE_INTEGER,
                self::NULLABLE   => false,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL   => 1,
            ],
        ]
    ];

    /**
     * cache for already resolved attendee
     * 
     * @var array type => array of id => object
     */
    protected static $_resolvedAttendeesCache = array(
        self::USERTYPE_USER        => array(),
        self::USERTYPE_GROUPMEMBER => array(),
        self::USERTYPE_GROUP       => array(),
        self::USERTYPE_RESOURCE    => array(),
        Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF => array()
    );

    protected static $_resolveAttendeeCustomfield;

    /**
     * returns accountId of this attender if present
     * 
     * @return string|null
     */
    public function getUserAccountId()
    {
        if (! in_array($this->user_type, array(self::USERTYPE_USER, self::USERTYPE_GROUPMEMBER))) {
            return null;
        }

        $adbController = Addressbook_Controller_Contact::getInstance();
        $adbAcl = $adbController->doContainerACLChecks(false);
        try {
            $contact = $adbController->get($this->user_id, null, false);
            return $contact->account_id ? $contact->account_id : null;
        } catch (Tinebase_Exception_NotFound $e) {
            return null;
        } finally {
            $adbController->doContainerACLChecks($adbAcl);
        }
    }
    
    /**
     * get email of attender if exists
     * 
     * @return string
     */
    public function getEmail($event=null)
    {
        if (self::USERTYPE_EMAIL === $this->user_type) {
            return $this->user_email;
        }

        $resolvedUser = $this->getResolvedUser($event);
        if (! $resolvedUser instanceof Tinebase_Record_Interface) {
            return '';
        }
        
        switch ($this->user_type) {
            case self::USERTYPE_USER:
            case self::USERTYPE_GROUPMEMBER:
                return $resolvedUser->getPreferredEmailAddress();
                break;
            case self::USERTYPE_GROUP:
                $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
                $domain = isset($smtpConfig['primarydomain']) ? '@' . $smtpConfig['primarydomain'] : '';
                return $resolvedUser->getId() . $domain;
                break;
            case self::USERTYPE_RESOURCE:
                return $resolvedUser->email;
                break;
            default:
                throw new Exception("type " . $this->user_type . " not yet supported");
                break;
        }
    }

    /**
     * get email addresses this attendee had in the past
     *
     * @return array
     */
    public function getEmailsFromHistory()
    {
        $emails = array();

        $typeMap = array(
            self::USERTYPE_USER        => 'Addressbook_Model_Contact',
            self::USERTYPE_GROUPMEMBER => 'Addressbook_Model_Contact',
            self::USERTYPE_RESOURCE    => 'Calendar_Model_Resource',
        );

        if (isset ($typeMap[$this->user_type])) {
            $type = $typeMap[$this->user_type];
            $id = $this->user_id instanceof Tinebase_Record_Interface ? $this->user_id->getId() : $this->user_id;

            $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications(
                Tinebase_Helper::array_value(0, explode('_', $type)),
                $this->user_id instanceof Tinebase_Record_Interface ? $this->user_id->getId() : $this->user_id,
                $type,
                'Sql',
                $this->creation_time
            )->filter('change_type', Tinebase_Timemachine_ModificationLog::UPDATED);

            /** @var Tinebase_Model_ModificationLog $modification */
            foreach($modifications as $modification) {
                $modified_attribute = $modification->modified_attribute;

                // legacy code
                if (!empty($modified_attribute)) {
                    if (in_array($modification->modified_attribute, array_keys(Addressbook_Model_Contact::getEmailFields()))) {
                        if ($modification->old_value) {
                            $emails[] = $modification->old_value;
                        }
                    }

                // new code modificationLog implementation
                } else {
                    /** @var Tinebase_Record_Diff $diff */
                    $diff = new Tinebase_Record_Diff(json_decode($modification->new_value, true));
                    if (isset($diff->oldData['email'])) {
                        $emails[] = $diff->oldData['email'];
                    }
                    if (isset($diff->oldData['email_home'])) {
                        $emails[] = $diff->oldData['email_home'];
                    }
                }
            }
        }

        return $emails;
    }

    /**
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getTitle()
    {
        try {
            return $this->getName();
        } catch (Exception $e) {
            return parent::getTitle();
        }
    }

    /**
     * get name of attender
     * 
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getName()
    {
        if (self::USERTYPE_EMAIL === $this->user_type) {
            return $this->user_displayname;
        }
        $resolvedUser = $this->getResolvedUser(null, false);
        if (! $resolvedUser instanceof Tinebase_Record_Interface) {
            Tinebase_Translation::getTranslation('Calendar');
            return Tinebase_Translation::getTranslation('Calendar')->_('unknown');
        }
        
        switch ($this->user_type) {
            case self::USERTYPE_USER:
            case self::USERTYPE_GROUPMEMBER:
                return $resolvedUser->n_fileas;
                break;
            case self::USERTYPE_GROUP:
            case self::USERTYPE_RESOURCE:
                $translation = Tinebase_Translation::getTranslation('Calendar');
                $name = $resolvedUser->name ?: $resolvedUser->n_fileas;
                if ($this->user_type == self::USERTYPE_GROUP) {
                    $name = $name . ' (' . $translation->_('Group') . ')';
                }
                return $name;
            default:
                throw new Tinebase_Exception_InvalidArgument("type " . $this->user_type . " not yet supported");
        }
    }

    /**
     * get translated type of attender
     *
     * @return string
     */
    public function getType($locale = null)
    {
        $translation = Tinebase_Translation::getTranslation('Calendar', $locale);
        switch ($this->user_type) {
            case self::USERTYPE_USER:
                return $translation->translate('User');
            case self::USERTYPE_GROUPMEMBER:
                return $translation->translate('Member of group');
            case self::USERTYPE_GROUP:
                return $translation->translate('Group');
            case self::USERTYPE_RESOURCE:
                return $translation->translate('Resource');
            case self::USERTYPE_EMAIL:
                return $translation->translate('Email');
            default:
                return '';
        }

    }

    /**
     * returns the resolved user_id
     * 
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function getResolvedUser($event = null, $resolveDisplayContainer = true)
    {
        if ($this->user_type === self::USERTYPE_EMAIL) {
            $result = new Addressbook_Model_Contact(array(
                'n_fileas' => $this->{self::FLD_USER_DISPLAYNAME},
                'email' => $this->{self::FLD_USER_EMAIL},
            ));
        } elseif ($this->user_type === self::USERTYPE_RESOURCE) {
            $resource = $this->user_id;
            if (! $resource instanceof Calendar_Model_Resource) {
                $resource = Calendar_Controller_Resource::getInstance()->get($resource, _aclProtect: false);
            }
            // return pseudo contact with resource data
            $result = new Addressbook_Model_Contact(array(
                'n_family' => $resource->name,
                'email' => $resource->email,
                'id' => $resource->getId(),
            ));
        } else {
            $clone = clone $this;
            $resolvable = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array($clone));
            self::resolveAttendee($resolvable, $resolveDisplayContainer, $event);
            $result = $clone->user_id;
            if ($this->{self::FLD_USER_DISPLAYNAME}) {
                $result->n_fileas = $this->{self::FLD_USER_DISPLAYNAME};
                $result->n_fn = $this->{self::FLD_USER_DISPLAYNAME};
            }
            if ($this->{self::FLD_USER_EMAIL}) {
                $result->email = $this->{self::FLD_USER_EMAIL};
            }
        }
        
        return $result;
    }
    
    public function getStatusString()
    {
        $statusConfig = Calendar_Config::getInstance()->attendeeStatus;
        $statusRecord = $statusConfig && $statusConfig->records instanceof Tinebase_Record_RecordSet ? $statusConfig->records->getById($this->status) : false;
        
        return $statusRecord ? $statusRecord->value : $this->status;
    }
    
    public function getRoleString()
    {
        $rolesConfig = Calendar_Config::getInstance()->attendeeRoles;
        $rolesRecord = $rolesConfig && $rolesConfig->records instanceof Tinebase_Record_RecordSet ? $rolesConfig->records->getById($this->role) : false;
        
        return $rolesRecord? $rolesRecord->value : $this->role;
    }

    /**
     * returns if given attendee is the same as this attendee
     *
     * @param  Calendar_Model_Attender $compareTo
     * @return bool
     */
    public function isSame($compareTo)
    {
        $compareToSet = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $compareTo instanceof Calendar_Model_Attender ? [$compareTo] : []);
        return !!self::getAttendee($compareToSet, $this);
    }

    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array &$_data)
    {
        if (isset($_data['displaycontainer_id']) && is_array($_data['displaycontainer_id'])) {
            $_data['displaycontainer_id'] = $_data['displaycontainer_id']['id'];
        }
        
        if (isset($_data['user_id']) && is_array($_data['user_id'])) {
            if (isset($_data['user_id']['accountId'])) {
                // NOTE: we need to support accounts, cause the client might not have the contact, e.g. when the attender is generated from a container owner
                $_data['user_id'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_data['user_id']['accountId'], true)->getId();
            } elseif (isset($_data['user_id']['id'])) {
                $_data['user_id'] = $_data['user_id']['id'];
            }
        }
        
        if (empty($_data['quantity'])) {
            $_data['quantity'] = 1;
        }
        
        parent::setFromArray($_data);
    }
    
    /**
     * converts an array of emails to a recordSet of attendee for given record
     * 
     * @param  Calendar_Model_Event $_event
     * @param  array                $_emails
     * @param  bool                 $_implicitAddMissingContacts
     */
    public static function emailsToAttendee(Calendar_Model_Event $_event, $_emails, $_implicitAddMissingContacts = TRUE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " list of new attendees " . print_r($_emails, true));
        
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
                
        // resolve current attendee
        self::resolveAttendee($_event->attendee);
        
        // build currentMailMap
        // NOTE: non resolvable attendee will be discarded in the map
        //       this is _important_ for the calculation of migration as it
        //       saves us from deleting attendee out of current users scope
        $emailsOfCurrentAttendees = array();
        /** @var Calendar_Model_Attender $currentAttendee */
        foreach ($_event->attendee as $currentAttendee) {
            if ($currentAttendeeEmailAddress = $currentAttendee->getEmail()) {
                $emailsOfCurrentAttendees[$currentAttendeeEmailAddress] = $currentAttendee;
            }
        }
        
        // collect emails of new attendees (skipping if no email present)
        $emailsOfNewAttendees = array();
        foreach ($_emails as $newAttendee) {
            if ($newAttendee['email']) {
                $emailsOfNewAttendees[$newAttendee['email']] = $newAttendee;
            }
        }
        
        // attendees to remove
        $attendeesToDelete = array_diff_key($emailsOfCurrentAttendees, $emailsOfNewAttendees);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " attendees to delete " . print_r(array_keys($attendeesToDelete), true));
        
        // delete attendees no longer attending from recordset
        /** @var Calendar_Model_Attender $attendeeToDelete */
        foreach ($attendeesToDelete as $attendeeToDelete) {
            // NOTE: email of attendee might have changed in the meantime
            //       => get old email adresses from modlog and try to match
            foreach($attendeeToDelete->getEmailsFromHistory() as $oldEmail) {
                if (isset($emailsOfNewAttendees[$oldEmail])) {
                    unset($emailsOfNewAttendees[$oldEmail]);
                    continue 2;
                }
            }
            
            $_event->attendee->removeRecord($attendeeToDelete);
        }

        /* that's what I had in mind, but churchedition comes with custom system roles ...
        $systemRoles = Calendar_Config::getInstance()->{Calendar_Config::ATTENDEE_ROLES}->records
            ->filter('system', true)->getArrayOfIds(); */
        $systemRoles = ['REQ', 'OPT'];

        // attendees to keep and update
        $attendeesToKeep   = array_diff_key($emailsOfCurrentAttendees, $attendeesToDelete);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " attendees to keep " . print_r(array_keys($attendeesToKeep), true));
        //var_dump($attendeesToKeep);
        foreach($attendeesToKeep as $emailAddress => $attendeeToKeep) {
            $newSettings = $emailsOfNewAttendees[$emailAddress];

            // update object by reference
            $attendeeToKeep->status = isset($newSettings['partStat']) ? $newSettings['partStat'] : $attendeeToKeep->status;
            $attendeeToKeep->role   = isset($newSettings['role']) && in_array($newSettings['role'], $systemRoles) &&
                in_array($attendeeToKeep->role, $systemRoles) ? $newSettings['role'] : $attendeeToKeep->role;
        }

        // new attendess to add to event
        $attendeesToAdd    = array_diff_key($emailsOfNewAttendees,     $emailsOfCurrentAttendees);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " attendees to add " . print_r(array_keys($attendeesToAdd), true));
        
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        
        // add attendee identified by their emailAdress
        foreach ($attendeesToAdd as $newAttendee) {
            $attendeeId = NULL;
            
            if ($newAttendee['userType'] == Calendar_Model_Attender::USERTYPE_USER) {
                // list from groupmember expand
                if ( ! $attendeeId &&
                    preg_match('#^urn:uuid:principals/intelligroups/([a-z0-9]+)#', $newAttendee['email'], $matches)
                ) {
                    $newAttendee['userType'] = Calendar_Model_Attender::USERTYPE_GROUP;
                    $attendeeId = $matches[1];

                    try {
                        $list = Addressbook_Controller_List::getInstance()->get($attendeeId);
                        if ($list && $list->type === Addressbook_Model_List::LISTTYPE_GROUP) {
                            $attendeeId = $list->group_id;
                        }
                    } catch (Exception $e) {
                        // do nothing
                    }
                }

                // does a contact with this email address exist?
                if (! $attendeeId && $contact = self::resolveEmailToContact($newAttendee, false)) {
                    $attendeeId = $contact->getId();
                    
                }
                
                // does a resouce with this email address exist?
                if (! $attendeeId) {
                    $resources = Calendar_Controller_Resource::getInstance()->search(new Calendar_Model_ResourceFilter(array(
                        array('field' => 'email', 'operator' => 'equals', 'value' => $newAttendee['email']),
                    )));
                    
                    if(count($resources) > 0) {
                        $newAttendee['userType'] = Calendar_Model_Attender::USERTYPE_RESOURCE;
                        $attendeeId = $resources->getFirstRecord()->getId();
                    }
                }
                // does a list with this name exist?
                if ( ! $attendeeId &&
                    isset($smtpConfig['primarydomain']) && 
                    preg_match('/(?P<localName>.*)@' . preg_quote($smtpConfig['primarydomain'], '/') . '$/', $newAttendee['email'], $matches)
                ) {
                    $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(array(
                        array('field' => 'name',       'operator' => 'equals', 'value' => $matches['localName']),
                        array('field' => 'type',       'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP),
                        array('field' => 'showHidden', 'operator' => 'equals', 'value' => TRUE),
                    )));
                    
                    if(count($lists) > 0) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of lists " . count($lists));
                    
                        $newAttendee['userType'] = Calendar_Model_Attender::USERTYPE_GROUP;
                        $attendeeId = $lists->getFirstRecord()->group_id;
                    }
                }

                // does a list with this id exist?
                if (! $attendeeId) {
                    try {
                        $listId = explode('@', $newAttendee['email'])[0];
                        $list = Addressbook_Controller_List::getInstance()->get($listId);
                        if ($list && $list->type === Addressbook_Model_List::LISTTYPE_GROUP) {
                            $newAttendee['userType'] = Calendar_Model_Attender::USERTYPE_GROUP;
                            $attendeeId = $list->group_id;
                        }
                    } catch (Exception $e) {
                        // do nothing
                    }
                }

                if (! $attendeeId) {
                    // autocreate a contact if allowed
                    $contact = self::resolveEmailToContact($newAttendee, $_implicitAddMissingContacts);
                    if ($contact) {
                        $attendeeId = $contact->getId();
                    }
                }

            } elseif($newAttendee['userType'] == Calendar_Model_Attender::USERTYPE_GROUP) {
                $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(array(
                    array('field' => 'name',       'operator' => 'equals', 'value' => $newAttendee['displayName']),
                    array('field' => 'type',       'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP),
                    array('field' => 'showHidden', 'operator' => 'equals', 'value' => TRUE),
                )));
                
                if(count($lists) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of lists " . count($lists));
                
                    $attendeeId = $lists->getFirstRecord()->group_id;
                }
            } elseif (self::USERTYPE_EMAIL === $newAttendee['userType']) {
                $_event->attendee->addRecord(new Calendar_Model_Attender(array(
                    'user_email'=> $newAttendee['email'],
                    'user_displayName'=> $newAttendee['displayName'],
                    'user_type' => $newAttendee['userType'],
                    'status'    => isset($newAttendee['partStat']) ? $newAttendee['partStat'] : self::STATUS_NEEDSACTION,
                    'role'      => $newAttendee['role']
                )));
            }

            if ($attendeeId !== NULL) {
                // finally add to attendee
                $_event->attendee->addRecord(new Calendar_Model_Attender(array(
                    'user_id'   => $attendeeId,
                    'user_type' => $newAttendee['userType'],
                    'status'    => isset($newAttendee['partStat']) ? $newAttendee['partStat'] : self::STATUS_NEEDSACTION,
                    'role'      => $newAttendee['role']
                )));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " updated attendees list " . print_r($_event->attendee->toArray(), true));
    }
    
    /**
     * get attendee with user_id = email address and create contacts for them on the fly if they do not exist
     * 
     * @param Calendar_Model_Event $_event
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function resolveEmailOnlyAttendee(Calendar_Model_Event $_event)
    {
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        
        foreach ($_event->attendee as $currentAttendee) {
            if (is_string($currentAttendee->user_id) && preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $currentAttendee->user_id)) {
                if ($currentAttendee->user_type !== Calendar_Model_Attender::USERTYPE_USER) {
                    throw new Tinebase_Exception_InvalidArgument('it is only allowed to set contacts as email only attender');
                }
                $contact = self::resolveEmailToContact(array(
                    'email'     => $currentAttendee->user_id,
                ));
                $currentAttendee->user_id = $contact->getId();
            }
        }
    }

    public static function enforceListIdForGroups(Tinebase_Record_RecordSet $_attendees)
    {
        $groups = [];
        $lists = [];
        foreach ($_attendees->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUP) as $groupAttendee) {
            if ($groupAttendee->user_id instanceof Tinebase_Record_Interface) {
                if ($groupAttendee->user_id instanceof Tinebase_Model_Group && !empty($groupAttendee->user_id->list_id)) {
                    $groupAttendee->user_id = Addressbook_Controller_List::getInstance()->get($groupAttendee->user_id->list_id);
                }
            } else {
                if (!isset($groups[$groupAttendee->user_id]) && !isset($lists[$groupAttendee->user_id])) {
                    try {
                        $lists[$groupAttendee->user_id] = Addressbook_Controller_List::getInstance()
                            ->get($groupAttendee->user_id);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $groups[$groupAttendee->user_id] = Tinebase_Group::getInstance()
                            ->getGroupById($groupAttendee->user_id);
                    }
                }
                if (isset($groups[$groupAttendee->user_id])) {
                    $groupAttendee->user_id = $groups[$groupAttendee->user_id]->list_id;
                }
            }
        }
    }
    
   /**
    * check if contact with given email exists in addressbook and creates it if not
    *
    * @param  array $_attenderData array with email, firstname and lastname (if available)
    * @param  boolean $_implicitAddMissingContacts
    * @return Addressbook_Model_Contact
    * @throws Tinebase_Exception_InvalidArgument
    *
    * @todo filter by fn if multiple matches
    */
    public static function resolveEmailToContact($_attenderData, $_implicitAddMissingContacts = TRUE, $defaultData = [])
    {
        if (! isset($_attenderData['email']) || empty($_attenderData['email'])) {
            throw new Tinebase_Exception_InvalidArgument('email address is needed to resolve contact');
        }
        
        $email = $_attenderData['email'];

        $adbController = Addressbook_Controller_Contact::getInstance();
        $oldAdbAcl = $adbController->doContainerACLChecks(false);
        try {
            $contact = $adbController->getContactByEmail($email);
        } finally {
            $adbController->doContainerACLChecks($oldAdbAcl);
        }
        
        if ($contact) {
            $result = $contact;
            $result->resolveAttenderCleanUp();
        
        } else if ($_implicitAddMissingContacts === TRUE) {
            $translation = Tinebase_Translation::getTranslation('Calendar');
            $i18nNote = $translation->_('This contact has been automatically added by the system as an event attender');
            if ($email !== $_attenderData['email']) {
                $i18nNote .= "\n";
                $i18nNote .= $translation->_('The email address has been shortened:') . ' ' . $_attenderData['email'] . ' -> ' . $email;
            }
            $contactData = array_merge([
                'note'        => $i18nNote,
                'email'       => $email,
                'n_family'    => (isset($_attenderData['lastName']) && ! empty($_attenderData['lastName'])) ? $_attenderData['lastName'] : $email,
                'n_given'     => (isset($_attenderData['firstName'])) ? $_attenderData['firstName'] : '',
            ], $defaultData);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Ádd new contact " . print_r($contactData, true));
            $contact = new Addressbook_Model_Contact($contactData);
            $result = Addressbook_Controller_Contact::getInstance()->create($contact, false);
        } else {
            $result = NULL;
        }
        
        return $result;
    }
    
    /**
     * resolves group members and adds/removes them if necessary
     *
     * NOTE: If a user is listed as user and as group member, we suppress the group member
     *
     * NOTE: The role to assign to a new group member is not always clear, as multiple groups
     *       might be the 'source' of the group member. To deal with this, we take the role of
     *       the first group when we add new group members
     *
     * @param Tinebase_Record_RecordSet $_attendee
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Record_Validation
     */
    public static function resolveGroupMembers($_attendee): void
    {
        if (! $_attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }

        // flatten user_ids (not groups for group/list handling bellow)
        foreach($_attendee as $attendee) {
            if ($attendee->user_type != Calendar_Model_Attender::USERTYPE_GROUP && $attendee->user_id instanceof Tinebase_Record_Interface) {
                $attendee->user_id = $attendee->user_id->getId();
            }
        }
        
        $groupAttendee = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUP);
        
        $allCurrGroupMembers = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        $allCurrGroupMembersContactIds = $allCurrGroupMembers->user_id;
        
        $allGroupMembersContactIds = array();
        foreach ($groupAttendee as $groupAttender) {
            $listId = null;
        
            if ($groupAttender->user_id instanceof Addressbook_Model_List) {
                $groupAttender->user_id = $listId = $groupAttender->user_id->getId();
            } elseif ($groupAttender->user_id instanceof Tinebase_Model_Group &&
                    !empty($groupAttender->user_id->list_id)) {
                $groupAttender->user_id = $listId = $groupAttender->user_id->list_id;
            } elseif ($groupAttender->user_id !== NULL) {
                try {
                    $list = Addressbook_Controller_List::getInstance()->get($groupAttender->user_id);
                    $listId = $list->getId();
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                    // let's try group
                    try {
                        $group = Tinebase_Group::getInstance()->getGroupById($groupAttender->user_id);
                        if (!empty($group->list_id)) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                . ' FIXME: deprecated use of group id');
                            $groupAttender->user_id = $listId = $group->list_id;
                        }
                    } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__
                            . ' ' . $ternd->getMessage());
                    }
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Group attender ID missing');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' ' . print_r($groupAttender->toArray(), TRUE));
            }
            
            if ($listId !== null) {
                $groupAttenderContactIds = Addressbook_Controller_List::getInstance()->get($listId)->members;
                $allGroupMembersContactIds = array_merge($allGroupMembersContactIds, $groupAttenderContactIds);
                
                $toAdd = array_diff($groupAttenderContactIds, $allCurrGroupMembersContactIds);
                
                foreach ($toAdd as $userId) {
                    $_attendee->addRecord(new Calendar_Model_Attender(array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_GROUPMEMBER,
                        'user_id'   => $userId,
                        'role'      => $groupAttender->role
                    )));
                }
            }
        }
        
        $toDel = array_diff($allCurrGroupMembersContactIds, $allGroupMembersContactIds);
        foreach ($toDel as $contactId) {
            $attender = $allCurrGroupMembers->find('user_id', $contactId);
            $_attendee->removeRecord($attender);
        }
        
        // calculate double members (groupmember + user)
        $groupmembers = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        $users        = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_USER);
        $duplicates = array_intersect($users->user_id, $groupmembers->user_id);
        foreach ($duplicates as $user_id) {
            $attender = $groupmembers->find('user_id', $user_id);
            $_attendee->removeRecord($attender);
        }
    }

    /**
     * get own attender
     * 
     * @param Tinebase_Record_RecordSet $_attendee
     * @return Calendar_Model_Attender|NULL
     */
    public static function getOwnAttender($_attendee)
    {
        return self::getAttendee($_attendee, new Calendar_Model_Attender(array(
            'user_id'   => Tinebase_Core::getUser()->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER
        )));
    }
    
    /**
     * get a single attendee from set of attendee
     * 
     * @param Tinebase_Record_RecordSet $_attendeesSet
     * @param Calendar_Model_Attender   $_attendee
     * @return Calendar_Model_Attender|NULL
     */
    public static function getAttendee($_attendeesSet, Calendar_Model_Attender $_attendee)
    {
        if (!$_attendeesSet instanceof Tinebase_Record_RecordSet) {
            return null;
        }
        
        $attendeeUserId = $_attendee->user_id instanceof Tinebase_Record_Interface
            ? $_attendee->user_id->getId()
            : $_attendee->user_id;
        
        foreach ($_attendeesSet as $attendeeFromSet) {
            $attendeeFromSetUserId = $attendeeFromSet->user_id instanceof Tinebase_Record_Interface
                ? $attendeeFromSet->user_id->getId()
                : $attendeeFromSet->user_id;
            
            if ($attendeeFromSetUserId === $attendeeUserId) {
                if ($attendeeFromSet->user_type === $_attendee->user_type) {
                    // can stop here
                    return $attendeeFromSet;
                }
                
                if (   $_attendee->user_type       === Calendar_Model_Attender::USERTYPE_USER
                    && $attendeeFromSet->user_type === Calendar_Model_Attender::USERTYPE_GROUPMEMBER
                ) {
                    $foundGroupMember = $attendeeFromSet;
                    // continue searching for $_attendee->user_type
                    // @todo maybe we can also return in this case immediately
                }
            }
        }
        
        return isset($foundGroupMember) ? $foundGroupMember : null;
    }
    
    /**
     * returns migration of two attendee sets
     * 
     * @param  Tinebase_Record_RecordSet $_current
     * @param  Tinebase_Record_RecordSet $_update
     * @return array migrationKey => Tinebase_Record_RecordSet
     */
    public static function getMigration($_current, $_update)
    {
        $result = array(
            'toDelete' => new Tinebase_Record_RecordSet('Calendar_Model_Attender'),
            'toCreate' => $_update->getClone(true), // shallow copy! as we set the id below, we do NOT want to actuall recrods to be cloned!
            'toUpdate' => new Tinebase_Record_RecordSet('Calendar_Model_Attender'),
        );
        
        foreach($_current as $currAttendee) {
            $updateAttendee = self::getAttendee($result['toCreate'], $currAttendee);
            if ($updateAttendee) {
                $updateAttendee->setId($currAttendee->getId());
                $result['toUpdate']->addRecord($updateAttendee);
                $result['toCreate']->removeRecord($updateAttendee);
            } else {
                $result['toDelete']->addRecord($currAttendee);
            }
        }
        
        return $result;
    }
    
    /**
     * fill resolved attendees class cache
     * 
     * @param  Tinebase_Record_RecordSet|array  $eventAttendees
     * @throws Calendar_Exception
     */
    public static function fillResolvedAttendeesCache($eventAttendees)
    {
        if (empty($eventAttendees)) {
            return;
        }
        
        $eventAttendees = $eventAttendees instanceof Tinebase_Record_RecordSet
            ? array($eventAttendees)
            : $eventAttendees;
        
        $typeMap = array(
            self::USERTYPE_USER        => array(),
            self::USERTYPE_GROUPMEMBER => array(),
            self::USERTYPE_GROUP       => array(),
            self::USERTYPE_RESOURCE    => array(),
            Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF => array()
        );
        
        // build type map 
        foreach ($eventAttendees as $eventAttendee) {
            foreach ($eventAttendee as $attendee) {
                $user     = $attendee->user_id;
                $userType = $attendee->user_type;
                $userId   = $user instanceof Tinebase_Record_Interface
                    ? $user->getId()
                    : $user;
                
                if (isset(self::$_resolvedAttendeesCache[$userType][$userId])) {
                    // already in cache
                    continue;
                }
                
                if ($user instanceof Tinebase_Record_Interface) {
                    // can fill cache with model from $attendee
                    self::$_resolvedAttendeesCache[$userType][$userId] = $user;
                    
                    continue;
                }
                
                // must be resolved
                $typeMap[$userType][] = $userId;
            }
        }

        $adbController = Addressbook_Controller_Contact::getInstance();
        // get all missing user_id entries
        foreach ($typeMap as $type => $ids) {
            $ids = array_unique($ids);
            
            if (empty($ids)) {
                continue;
            }
            
            switch ($type) {
                case self::USERTYPE_USER:
                case self::USERTYPE_GROUPMEMBER:
                    $resolveCf = $adbController->resolveCustomfields(false);
                    try {
                        $contacts = $adbController->getMultiple($ids, true);
                    } finally {
                        $adbController->resolveCustomfields($resolveCf);
                    }
                    
                    foreach ($contacts as $contact) {
                        $contact->resolveAttenderCleanUp();
                        self::$_resolvedAttendeesCache[$type][$contact->getId()] = $contact;
                    }
                    break;
                    
                case self::USERTYPE_GROUP:
                case Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF:
                    // first fetch the groups, then the lists identified by list_id
                    $groups = Tinebase_Group::getInstance()->getMultiple($ids);
                    $lists  = Addressbook_Controller_List::getInstance()->getMultiple($groups->list_id, true);
                    
                    foreach ($groups as $group) {
                        $list = $lists->getById($group->list_id);
                        if ($list) {
                            self::$_resolvedAttendeesCache[$type][$group->getId()] = $list;
                        }
                    }
                    break;
                    
                case self::USERTYPE_RESOURCE:
                    $resources = Calendar_Controller_Resource::getInstance()->getMultiple($ids, true);
                    // NOTE: resource detail resolving is for export only - we might switch this to async in future for better performance
                    Tinebase_Container::getInstance()->getGrantsOfRecords($resources, Tinebase_Core::getUser());
                    $resources->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations('Calendar_Model_Resource', 'Sql', $resources->getId()));
                    if (Tinebase_Core::isFilesystemAvailable()) {
                        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($resources);
                    }

                    foreach ($resources as $resource) {
                        self::$_resolvedAttendeesCache[$type][$resource->getId()] = $resource;
                    }
                    break;

                case self::USERTYPE_EMAIL:
                    break;

                default:
                    throw new Tinebase_Exception_InvalidArgument("type $type not supported");
            }
        }
    }
    
    /**
     * return list of resolved attendee for given record(set)
     * 
     * @param Tinebase_Record_RecordSet|array   $eventAttendees 
     * @param bool                              $resolveDisplayContainers
     * @return Tinebase_Record_RecordSet
     */
    public static function getResolvedAttendees($eventAttendees, $resolveDisplayContainers = TRUE)
    {
        if (empty($eventAttendees)) {
            return null;
        }
        
        self::fillResolvedAttendeesCache($eventAttendees);
        
        $eventAttendees = $eventAttendees instanceof Tinebase_Record_RecordSet
            ? array($eventAttendees)
            : $eventAttendees;
        
        $foundDisplayContainers = false;
        
        // set containing all attendee
        $allAttendees = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        
        // build type map 
        foreach ($eventAttendees as $eventAttendee) {
            foreach ($eventAttendee as $attendee) {
                if (   $resolveDisplayContainers
                    && ! $foundDisplayContainers
                    && (is_int($attendee->displaycontainer_id) || is_string($attendee->displaycontainer_id))
                ) {
                        $foundDisplayContainers = true;
                }
                
                if ($attendee->user_id instanceof Tinebase_Record_Interface) {
                    // already resolved
                    $allAttendees->addRecord($attendee);
                    
                    continue;
                }
                
                if (isset(self::$_resolvedAttendeesCache[$attendee->user_type][$attendee->user_id])) {
                    $clonedAttendee = clone $attendee;
                    
                    // resolveable from cache
                    $clonedAttendee->user_id = self::$_resolvedAttendeesCache[$attendee->user_type][$attendee->user_id];
                    
                    $allAttendees->addRecord($clonedAttendee);
                    
                    continue;
                }
                
                // not resolved => problem!!!
            }
        }
        
        // resolve display containers
        if ($resolveDisplayContainers && $foundDisplayContainers) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($allAttendees, Tinebase_Core::getUser(), 'displaycontainer_id');
        }
        
        return $allAttendees;
    }
    
    /**
     * resolves given attendee for json representation
     * 
     * @todo move status_authkey cleanup elsewhere
     * @todo use self::getResolvedAttendees to avoid code duplication
     *
     * @param Tinebase_Record_RecordSet|array   $eventAttendees 
     * @param bool                              $resolveDisplayContainers
     * @param Calendar_Model_Event|array        $_events
     * @param bool                              $_sort
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function resolveAttendee($eventAttendees, $resolveDisplayContainers = true, $_events = null, $_sort = false)
    {
        if (empty($eventAttendees)) {
            return;
        }

        $eventAttendee = $eventAttendees instanceof Tinebase_Record_RecordSet ? array($eventAttendees) : $eventAttendees;

        $events = !$_events ? array() : $_events;
        $events = $_events instanceof Tinebase_Record_Interface ? array($events) : $events;
        $events = is_array($events) ? new Tinebase_Record_RecordSet('Calendar_Model_Event', $events) : $events;

        // set containing all attendee
        $allAttendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        $typeMap = array(self::USERTYPE_USER => array(), self::USERTYPE_GROUPMEMBER => array());
        
        // build type map 
        foreach ($eventAttendee as $attendee) {
            /** @var Calendar_Model_Attender $attender */
            foreach ($attendee as $attender) {
                if (self::USERTYPE_EMAIL === $attendee->user_type) {
                    continue;
                }
                if (empty($attender->user_id)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . ' user_id missing from attender: ' . print_r($attender->toArray(), true));
                    continue;
                }
                $allAttendee->addRecord($attender);
                
                if ($attender->user_id instanceof Tinebase_Record_Interface) {
                    // already resolved
                    continue;
                } elseif (isset(self::$_resolvedAttendeesCache[$attender->user_type])
                    && is_scalar($attender->user_id)
                    && isset(self::$_resolvedAttendeesCache[$attender->user_type][$attender->user_id])
                ) {
                    // already in cache
                    $attender->user_id = self::$_resolvedAttendeesCache[$attender->user_type][$attender->user_id];
                } else {
                    if (!isset($typeMap[$attender->user_type])) {
                        $typeMap[$attender->user_type] = [];
                    }
                    $typeMap[$attender->user_type][] = $attender->user_id;
                }
            }
        }
        
        // resolve display containers
        if ($resolveDisplayContainers) {
            $displaycontainerIds = array_diff($allAttendee->displaycontainer_id, array(''));
            if (! empty($displaycontainerIds)) {
                $allResources = $allAttendee->filter('user_type', self::USERTYPE_RESOURCE);
                $tmpRS = $allAttendee->getClone(true);
                $tmpRS->removeRecords($allResources);
                Tinebase_Container::getInstance()->getGrantsOfRecords($tmpRS, Tinebase_Core::getUser(),
                    'displaycontainer_id');
                Tinebase_Container::getInstance()->getGrantsOfRecords($allResources, Tinebase_Core::getUser(),
                    'displaycontainer_id', Calendar_Model_ResourceGrants::class);
            }
        }

        $organizerIds = array();
        foreach ($events as $event) {
            $organizerId = $event->organizer;
            if (! $organizerId instanceof Addressbook_Model_Contact) {
                $organizerIds[] = $organizerId;
            }
        }

        $contactIds = array_merge($typeMap[self::USERTYPE_USER], $typeMap[self::USERTYPE_GROUPMEMBER], $organizerIds);
        $resolveCf = Addressbook_Controller_Contact::getInstance()->resolveCustomfields(static::$_resolveAttendeeCustomfield);
        try {
            $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($contactIds), true);
            $contacts->resolveAttenderCleanUp();
        } finally {
            Addressbook_Controller_Contact::getInstance()->resolveCustomfields($resolveCf);
        }

        // get all user_id entries
        foreach ($typeMap as $type => $ids) {
            switch ($type) {
                case self::USERTYPE_USER:
                case self::USERTYPE_GROUPMEMBER:
                    $typeMap[$type] = $contacts;
                    break;
                case self::USERTYPE_GROUP:
                case Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF:
                    // first fetch the groups, then the lists identified by list_id
                    $typeMap[$type] = Tinebase_Group::getInstance()->getMultiple(array_unique($ids));
                    $listIds = array_unique(array_merge($typeMap[$type]->list_id, $ids));
                    $typeMap[$type] = Addressbook_Controller_List::getInstance()->getMultiple($listIds, true);
                    break;
                case self::USERTYPE_RESOURCE:
                    $typeMap[$type] = Calendar_Controller_Resource::getInstance()->getMultiple(array_unique($ids), true);
                    // NOTE: resource detail resolving is for export only - we might switch this to async in future for better performance
                    Tinebase_Container::getInstance()->getGrantsOfRecords($typeMap[$type], Tinebase_Core::getUser());
                    $typeMap[$type]->setByIndices('relations', Tinebase_Relations::getInstance()->getMultipleRelations('Calendar_Model_Resource', 'Sql', $typeMap[$type]->getId()));
                    if (Tinebase_Core::isFilesystemAvailable()) {
                        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($typeMap[$type]);
                    }
                    break;
                default:
                    throw new Tinebase_Exception_InvalidArgument("type $type not supported");
            }
        }
        
        // sort entries in
        foreach ($events as $event) {
            if ($event->organizer && ! $event->organizer instanceof Addressbook_Model_Contact) {
                $event->organizer = $contacts->getById($event->organizer);
            }
        }

        foreach ($eventAttendee as $attendee) {
            /** @var Calendar_Model_Attender $attender */
            foreach ($attendee as $attender) {
                if ($attender->user_id instanceof Tinebase_Record_Interface) {
                    // already resolved from cache
                    continue;
                }
                if (empty($attender->user_id) || ! is_scalar($attender->user_id)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                            . ' user_id missing from attender or not scalar: '
                            . print_r($attender->toArray(), true));
                    continue;
                }

                $attendeeTypeSet = $typeMap[$attender->user_type];
                $idx = $attendeeTypeSet->getIndexById($attender->user_id);
                if (!$idx && self::USERTYPE_GROUP === $attender->user_type) {
                    if (null !== ($list = $attendeeTypeSet->find('group_id', $attender->user_id))) {
                        $idx = $attendeeTypeSet->getIndexById($list->getId());
                    }
                }

                if ($idx !== false) {
                    $user = $attendeeTypeSet[$idx];
                    // copy to cache
                    self::$_resolvedAttendeesCache[$attender->user_type][$attender->user_id] = $user;
                    
                    $attender->user_id = $user;
                }
            }
        }
        
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                $event = $events->getById($attender->cal_event_id);

                // keep authkey if user has editGrant to displaycontainer
                if (isset($attender['displaycontainer_id'])
                    && !is_scalar($attender['displaycontainer_id'])
                    && isset($attender['displaycontainer_id']['account_grants'][Tinebase_Model_Grants::GRANT_EDIT])
                    && $attender['displaycontainer_id']['account_grants'][Tinebase_Model_Grants::GRANT_EDIT]
                ) {
                    continue;
                }

                    // keep authkey if attender is a contact (no account) and user has editGrant for event
                if ((($attender->user_type === self::USERTYPE_USER
                    && $attender->user_id instanceof Tinebase_Record_Interface
                    && (!$attender->user_id->has('account_id') || !$attender->user_id->account_id))
                        || $attender->user_type == self::USERTYPE_EMAIL)
                    && (!$event || $event->{Tinebase_Model_Grants::GRANT_EDIT})
                    && (!$event || !$event->hasExternalOrganizer())
                ) {
                    continue;
                }

                // keep authkey if attender is a resource and user has manage_resources
                if ($attender->user_type === self::USERTYPE_RESOURCE &&
                        isset($attender['displaycontainer_id']) && !is_scalar($attender['displaycontainer_id'])
                        && (isset($attender['displaycontainer_id']['account_grants'][Calendar_Model_ResourceGrants::RESOURCE_STATUS])
                            && $attender['displaycontainer_id']['account_grants'][Calendar_Model_ResourceGrants::RESOURCE_STATUS])) {
                    continue;
                }
                
                $attender->status_authkey = NULL;
            }
        }

        if ($eventAttendees instanceof Tinebase_Record_RecordSet && $_sort) {
            $eventAttendees->sort(function(Calendar_Model_Attender $a1, Calendar_Model_Attender $a2) {
                try {
                    return strcmp($a1->getName(), $a2->getName());

                    // ok time for some fun, enjoy:
                    // if attender has no name, we sort him bigger (end of list)
                } catch (Tinebase_Exception_InvalidArgument $teia) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . ' ' . $teia->getMessage());
                    try {
                        $a1->getName();
                    } catch (Tinebase_Exception_InvalidArgument $teia) {
                        try {
                            $a2->getName();
                        } catch (Tinebase_Exception_InvalidArgument $teia) {
                            return 0;
                        }
                        return 1;
                    }
                    return -1;
                }
            });
        }
    }
    
    public static function setContactCustomfieldResolve($_resolve) {
        static::$_resolveAttendeeCustomfield = $_resolve;
    }
    
    /**
     * checks if given alarm should be send to given attendee
     * 
     * @param  Calendar_Model_Attender $_attendee
     * @param  Tinebase_Model_Alarm    $_alarm
     * @return bool
     */
    public static function isAlarmForAttendee($_attendee, $_alarm, $_event=NULL)
    {
        // attendee: array with one user_type/id if alarm is for one attendee only
        $attendeeOption = $_alarm->getOption('attendee');
        
        // skip: array of array of user_type/id with attendees this alarm is to skip for
        $skipOption = $_alarm->getOption('skip');
        
        if ($attendeeOption) {
            $isAttendeeCondition = (bool) self::getAttendee(new Tinebase_Record_RecordSet('Calendar_Model_Attender', array($_attendee)), new Calendar_Model_Attender($attendeeOption));
            return ($isAttendeeCondition) && $_attendee->status != Calendar_Model_Attender::STATUS_DECLINED;
        }
        
        if (is_array($skipOption)) {
            $skipAttendees = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $skipOption);
            if(self::getAttendee($skipAttendees, $_attendee)) {
                return false;
            }
        }
        
        $isOrganizerCondition = $_event ? $_event->isOrganizer($_attendee) : TRUE;
        $isAttendeeCondition = $_event && $_event->attendee instanceof Tinebase_Record_RecordSet ? self::getAttendee($_event->attendee, $_attendee) : TRUE;
        return ($isAttendeeCondition || $isOrganizerCondition) && $_attendee->status != Calendar_Model_Attender::STATUS_DECLINED;
    }
    
    public function getUserId()
    {
        return $this->user_id instanceof Tinebase_Record_Interface ? $this->user_id->getId() : $this->user_id;
    }

    public function getKey()
    {
        return $this->user_type . '-' . $this->getUserId();
    }

    public static function fromKey($key)
    {
        return new Calendar_Model_Attender([
            'user_type' => preg_replace('/-.*$/', '', $key),
            'user_id' => preg_replace('/^[a-z]+-/', '', $key),
        ]);
    }

    /**
     * clear in class cache
     */
    public static function clearCache()
    {
        foreach(self::$_resolvedAttendeesCache as $name => $entries) {
            self::$_resolvedAttendeesCache[$name] = [];
        }
    }

    public function runConvertToData()
    {
        if (isset($this->_properties['xprops']) && is_array($this->_properties['xprops'])) {
            $this->_properties['xprops'] = json_encode($this->_properties['xprops']);
        }
    }
}
