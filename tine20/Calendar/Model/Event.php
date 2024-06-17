<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Model of an event
 * 
 * Recuring Notes: 
 *  - deleted recurring exceptions are stored in exdate (array of datetimes)
 *  - modified recurring exceptions have their own event with recurid set the uid-dtstart
 *    of the originators event (@see RFC2445)
 *  - as id is unique, each modified recurring event has its own id
 *  - rrule is stored in RCF2445 format
 *  - the rrule_until is redundant to the rrule until property for fast queries
 *  - we don't use rrule count, they are converted to an until
 *  - like always in tine, we save all dates in UTC, but to correctly compute
 *    recurring events, we also save the timezone of the organizer
 *  - despite RFC2445 we have an expicit isAllDayEvent property
 * 
 * @package Calendar
 * @property Tinebase_Record_RecordSet      $alarms
 * @property Tinebase_DateTime              $creation_time
 * @property string                         $is_all_day_event
 * @property string                         $originator_tz
 * @property string                         $seq
 * @property string                         $uid
 * @property string                         $etag
 * @property string                         $class
 * @property int                            $container_id
 * @property string                         $organizer
 * @property Tinebase_Record_RecordSet      $attendee
 * @property Tinebase_DateTime              $dtstart
 * @property Tinebase_DateTime              $dtend
 * @property Calendar_Model_Rrule           $rrule
 * @property Tinebase_DateTime              $rrule_until
 * @property string                         $transp
 * @property string                         $status
 * @property string                         $summary
 * @property string                         $recurid
 * @property string                         $poll_id
 * @property string                         $description
 * @property string                         $external_seq
 * @property string                         $base_event_id
 * @property Tinebase_Record_RecordSet      $exdate
 * @property string                         $location
 */
class Calendar_Model_Event extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART      = 'Event';
    const TABLE_NAME           = 'cal_events';

    const TRANSP_TRANSP        = 'TRANSPARENT';
    const TRANSP_OPAQUE        = 'OPAQUE';
    
    const CLASS_PUBLIC         = 'PUBLIC';
    const CLASS_PRIVATE        = 'PRIVATE';
    //const CLASS_CONFIDENTIAL   = 'CONFIDENTIAL';
    
    const STATUS_CONFIRMED     = 'CONFIRMED';
    const STATUS_TENTATIVE     = 'TENTATIVE';
    const STATUS_CANCELED      = 'CANCELLED';
    
    const RANGE_ALL           = 'ALL';
    const RANGE_THIS          = 'THIS';
    const RANGE_THISANDFUTURE = 'THISANDFUTURE';
    const XPROPS_IMIP_PROPERTIES = 'imipProperties';
    const XPROPS_REPLICATABLE = 'calendarReplicatable';

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
        self::VERSION       => 19,
        'containerName'     => 'Calendar',
        'containersName'    => 'Calendars', // ngettext('Calendar', 'Calendars', n)
        'recordName'        => self::MODEL_NAME_PART, // gettext('GENDER_Event')
        'recordsName'       => 'Events', // ngettext('Event', 'Events', n)
        'titleProperty'     => 'summary',
        'hasRelations'      => true,
        'copyRelations'     => false,
        'hasCustomFields'   => true,
        'hasSystemCustomFields' => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        self::HAS_XPROPS    => true,

        'containerProperty' => 'container_id',

        'appName'           => 'Calendar',
        'modelName'         => self::MODEL_NAME_PART,
        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'description'               => [
                    self::COLUMNS               => ['description'],
                    self::FLAGS                 => ['fulltext'],
                ],
                'dtstart'                   => [
                    self::COLUMNS               => ['dtstart'],
                ],
                'dtend'                     => [
                    self::COLUMNS               => ['dtend'],
                ],
                'organizer'                 => [
                    self::COLUMNS               => ['organizer'],
                ],
                'location_record'           => [
                    self::COLUMNS               => ['location_record'],
                ],
                'uid'                       => [
                    self::COLUMNS               => ['uid'],
                ],
                'base_event_id'             => [
                    self::COLUMNS               => ['base_event_id'],
                ],
                'etag'                      => [
                    self::COLUMNS               => ['etag'],
                ],
                'rrule_until'               => [
                    self::COLUMNS               => ['rrule_until'],
                ],
                'rrule'                     => [
                    self::COLUMNS               => ['rrule'],
                ],
                'class'                     => [
                    self::COLUMNS               => ['class'],
                ],
                'poll_id'                   => [
                    self::COLUMNS               => ['poll_id'],
                ],
            ],
        ],

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'cal_events::container_id--container::id' => [
                    'targetEntity' => Tinebase_Model_Container::class,
                    'fieldName' => 'container_id',
                    'joinColumns' => [[
                        'name' => 'container_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'event_types'      => [],
            ],
        ],

        self::FIELDS        => [
            'external_seq'      => [
                self::TYPE          => self::TYPE_INTEGER,
                self::UNSIGNED      => true,
                self::DEFAULT_VAL   => 0,
                self::INPUT_FILTERS => [
                    Zend_Filter_Int::class,
                ],
                self::OMIT_MOD_LOG  => true,
            ],
            'dtend'      => [
                self::TYPE          => self::TYPE_DATETIME,
            ],
            'transp'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 40,
                self::DEFAULT_VAL   => self::TRANSP_OPAQUE,
                self::NULLABLE      => true,
                self::VALIDATORS    => [
                    [Zend_Validate_InArray::class, [self::TRANSP_OPAQUE, self::TRANSP_TRANSP]],
                ],
            ],
            'class'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 40,
                self::DEFAULT_VAL   => 'PUBLIC',
                self::VALIDATORS    => [
                    [Zend_Validate_InArray::class, [self::CLASS_PUBLIC, self::CLASS_PRIVATE, /*self::CLASS_CONFIDENTIAL,*/]],
                ],
            ],
            'description'      => [
                self::TYPE          => self::TYPE_TEXT,
                self::INPUT_FILTERS => [], // we need this to overwrite default text filter!
                self::NULLABLE      => true,
            ],
            'geo'      => [
                self::TYPE          => self::TYPE_FLOAT,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'adr_lon'      => [
                self::TYPE          => self::TYPE_FLOAT,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'adr_lat'      => [
                self::TYPE          => self::TYPE_FLOAT,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'location'      => [
                self::TYPE          => self::TYPE_TEXT,
                self::INPUT_FILTERS => [], // we need this to overwrite default text filter!
                self::LENGTH        => 1024,
                self::NULLABLE      => true,
            ],
            'location_record'      => [
                self::TYPE          => self::TYPE_STRING, // type record?
                self::LENGTH        => 40,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'organizer'      => [
                self::TYPE          => self::TYPE_STRING, // type record?
                self::LENGTH        => 40,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                ],
            ],
            'priority'      => [
                self::TYPE          => self::TYPE_INTEGER,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'status'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
                self::VALIDATORS    => [
                    [Zend_Validate_InArray::class, [self::STATUS_CONFIRMED, self::STATUS_TENTATIVE, self::STATUS_CANCELED,]],
                ]
            ],
            'summary'      => [
                self::TYPE          => self::TYPE_TEXT,
                self::INPUT_FILTERS => [], // we need this to overwrite default text filter!
                self::LENGTH        => 1024,
                self::NULLABLE      => true,
            ],
            'url'      => [
                self::TYPE          => self::TYPE_TEXT,
                self::INPUT_FILTERS => [], // we need this to overwrite default text filter!
                self::LENGTH        => 65535,
                self::NULLABLE      => true,
            ],
            'uid'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
            ],
            'etag'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 60,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'attendee'   => [
                self::TYPE          => self::TYPE_VIRTUAL, // RecordSet of Calendar_Model_Attender
                self::OMIT_MOD_LOG  => false,
            ],
            'alarms'   => [
                self::TYPE          => self::TYPE_VIRTUAL, // RecordSet of Tinebase_Model_Alarm
                self::OMIT_MOD_LOG  => false,
            ],
            'dtstart'      => [
                self::TYPE          => self::TYPE_DATETIME,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'recurid'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'members'   => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => false,
            ],
            'resources'   => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => false,
            ],
            'date'   => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => false,
            ],
            'duration'   => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => false,
            ],
            'time'   => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => false,
            ],
            'groups'   => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => false,
            ],
            'base_event_id'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 40,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'exdate'   => [
                self::TYPE          => self::TYPE_DATETIME, // legacy! needed :-/
                self::DOCTRINE_IGNORE => true, //  legacy! needed :-/
            ],
            'rrule'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'is_all_day_event'      => [
                self::TYPE          => self::TYPE_BOOLEAN,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
            ],
            'rrule_until'      => [
                self::TYPE          => self::TYPE_DATETIME,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'rrule_constraints'      => [
                self::TYPE          => self::TYPE_TEXT,
                self::INPUT_FILTERS => [], // we need this to overwrite default text filter!
                self::NULLABLE      => true,
            ],
            'originator_tz'      => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'poll_id'      => [
                self::TYPE          => self::TYPE_STRING, // record?
                self::LENGTH        => 40,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
            ],
            'mute'      => [
                self::TYPE          => self::TYPE_BOOLEAN,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => null,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => false,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            'event_types' => [
                self::TYPE       => self::TYPE_RECORDS,
                self::IS_VIRTUAL => true,
                self::NULLABLE   => true,
                self::LABEL     => 'Event Types', // _('Event Types')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::CONFIG => [
                    self::APP_NAME                  => 'Calendar',
                    self::MODEL_NAME                => 'EventTypes',
                    self::REF_ID_FIELD              => 'record',
                    self::DEPENDENT_RECORDS         => true,
                ],
                self::RECURSIVE_RESOLVING => true,
            ],
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
            Tinebase_Model_Grants::GRANT_READ => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
            Tinebase_Model_Grants::GRANT_SYNC => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
            Tinebase_Model_Grants::GRANT_EXPORT => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
            Tinebase_Model_Grants::GRANT_EDIT => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
            Tinebase_Model_Grants::GRANT_DELETE => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => [
                self::TYPE          => self::TYPE_VIRTUAL,
                self::OMIT_MOD_LOG  => true,
            ],
        ],
    ];
    
    /**
     * validators
     *
     * @var array
     *
    protected $_validators = array(

        // calendar only fields
        'external_seq'         => array(Zend_Filter_Input::ALLOW_EMPTY => true,  'Int'  ), // external seq for caldav / imip update handling
        'dtend'                => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'transp'               => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            array('InArray', array(self::TRANSP_OPAQUE, self::TRANSP_TRANSP))
        ),
        // ical common fields
        'class'                => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            array('InArray', array(self::CLASS_PUBLIC, self::CLASS_PRIVATE, /*self::CLASS_CONFIDENTIAL*))
        ),
        'description'          => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'geo'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'adr_lon'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'adr_lat'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'location'             => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'location_record'      => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'organizer'            => array(Zend_Filter_Input::ALLOW_EMPTY => false,        ),
        'priority'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, 'Int'   ),
        'status'            => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            array('InArray', array(self::STATUS_CONFIRMED, self::STATUS_TENTATIVE, self::STATUS_CANCELED))
        ),
        'summary'              => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'url'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'uid'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        'etag'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true          ),
        // ical common fields with multiple appearance
        //'attach'                => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'attendee'              => array(Zend_Filter_Input::ALLOW_EMPTY => true         ), // RecordSet of Calendar_Model_Attender
        'alarms'                => array(Zend_Filter_Input::ALLOW_EMPTY => true         ), // RecordSet of Tinebase_Model_Alarm
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true         ), // originally categories handled by Tinebase_Tags
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true         ), // originally comment handled by Tinebase_Notes
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        
        //'contact'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        //'related'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        //'resources'             => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        //'rstatus'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // ical scheduleable interface fields
        'dtstart'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'recurid'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'members'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'resources'            => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'date'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'duration'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'time'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'groups'                => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'base_event_id'         => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // ical scheduleable interface fields with multiple appearance
        'exdate'                => array(Zend_Filter_Input::ALLOW_EMPTY => true         ), //  array of Tinebase_DateTimeTinebase_DateTime's
        //'exrule'                => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        //'rdate'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'rrule'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'poll_id'               => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // calendar helper fields

        'is_all_day_event'      => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'rrule_until'           => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        // instanceof Calendar_Model_EventFilter
        'rrule_constraints'     => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'originator_tz'         => array(Zend_Filter_Input::ALLOW_EMPTY => true         ),
        'mute'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => false      ),

        // relations
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'customfields'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        
        // grant helper fields
        Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        Tinebase_Model_Grants::GRANT_READ     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        Tinebase_Model_Grants::GRANT_SYNC     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        Tinebase_Model_Grants::GRANT_EXPORT   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        Tinebase_Model_Grants::GRANT_EDIT     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        Tinebase_Model_Grants::GRANT_DELETE   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => array(Zend_Filter_Input::ALLOW_EMPTY => true),

        'xprops'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
     */

    protected static $_freebusyCleanUpKeys = null;
    protected static $_freebusyCleanUpVisibilty = null;
    /**
     * sets record related properties
     * 
     * @param string $_name of property
     * @param mixed $_value of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return void
     */
    public function __set($_name, $_value)
    {
        // ensure exdate as array
        if ($_name == 'exdate' && ! empty($_value) && ! is_array($_value) && ! $_value instanceof Tinebase_Record_RecordSet ) {
            $_value = array($_value);
        }
        
        if ($_name == 'attendee' && is_array($_value)) {
            $_value = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $_value);
        }
        
        if ($_name == 'rrule' && is_string($_value) && ! empty($_value)) {
            // normalize rrule
            $_value = new Calendar_Model_Rrule($_value);
            $_value = (string) $_value;
        }
        parent::__set($_name, $_value);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Record_Abstract::diff()
     */
    public function diff($record, $omitFields = array(), ?Tinebase_Record_DiffContext $context = null)
    {
        $checkRrule = false;
        if (! in_array('rrule', $omitFields)) {
            $omitFields[] = 'rrule';
            $checkRrule = true;
        }
        
        $diff = parent::diff($record, $omitFields, $context);
        
        if ($checkRrule) {
            $ownRrule    = ! $this->rrule instanceof Calendar_Model_Rrule ? Calendar_Model_Rrule::getRruleFromString((string) $this->rrule) : $this->rrule;
            $recordRrule = ! $record->rrule instanceof Calendar_Model_Rrule ? Calendar_Model_Rrule::getRruleFromString($record->rrule) : $record->rrule;
            
            $rruleDiff = $ownRrule->diff($recordRrule);
            if ($ownRrule->interval === 1 && $recordRrule->interval === 1) {
                if (isset($ownRrule['interval']) && !isset($recordRrule['interval'])) {
                    $rruleDiff->xprops('diff')['interval'] = null;
                    $rruleDiff->xprops('oldData')['interval'] = 1;
                } elseif (!isset($ownRrule['interval']) && isset($recordRrule['interval'])) {
                    $rruleDiff->xprops('diff')['interval'] = 1;
                    $rruleDiff->xprops('oldData')['interval'] = null;
                }
            }

            // don't take small ( < one day) rrule_until changes as diff
            if (
                    $ownRrule->until instanceof Tinebase_DateTime 
                    && (isset($rruleDiff->diff['until']) || array_key_exists('until', $rruleDiff->diff)) && $rruleDiff->diff['until'] instanceof Tinebase_DateTime
                    && abs($rruleDiff->diff['until']->getTimestamp() - $ownRrule->until->getTimestamp()) < 86400
            ){
                $rruleDiffArray = $rruleDiff->diff;
                unset($rruleDiffArray['until']);
                $rruleDiff->diff = $rruleDiffArray;

                $rruleDiffArray = $rruleDiff->oldData;
                unset($rruleDiffArray['until']);
                $rruleDiff->oldData = $rruleDiffArray;
            }
            
            if (! empty($rruleDiff->diff)) {
                $diffArray = $diff->diff;
                $diffArray['rrule'] = $rruleDiff;
                $diff->diff = $diffArray;

                $diffArray = $diff->oldData;
                $diffArray['rrule'] = $ownRrule->toArray();
                $diff->oldData = $diffArray;
            }
        }
        
        return $diff;
    }

    public function sortExdates()
    {
        $exdates = $this->exdate;
        if ($exdates instanceof Tinebase_Record_RecordSet) {
            $exdates = $exdates->asArray();
        }
        if (empty($exdates)) return;

        usort($exdates, function (Tinebase_DateTime $a, Tinebase_DateTime $b) {
            return $a->compare($b);
        });

        $this->exdate = $exdates;
    }

    /**
     * add given attendee if not present under given conditions
     * 
     * @param Calendar_Model_Attender  $attendee
     * @param bool                     $ifOrganizer        only add attendee if he's organizer
     * @param bool                     $ifNoOtherAttendee  only add attendee if no other attendee are present
     * @param bool                     $personalOnly       only for personal containers
     * @return Calendar_Model_Attender asserted attendee
     */
    public function assertAttendee($attendee, $ifOrganizer = true, $ifNoOtherAttendee = false, $personalOnly = false)
    {
        if ($personalOnly) {
            try {
                $container = Tinebase_Container::getInstance()->getContainerById($this->container_id);
                if ($container->type != Tinebase_Model_Container::TYPE_PERSONAL) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . " not adding attendee as container is not personal.");
                    return;
                }
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " cannot get container: $e");
            }
        }
        
        
        if ($ifNoOtherAttendee && $this->attendee instanceof Tinebase_Record_RecordSet && $this->attendee->count() > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " not adding attendee as other attendee are present.");
            return;
        }
        
        $assertionAttendee = Calendar_Model_Attender::getAttendee($this->attendee, $attendee);
        
        if (! $assertionAttendee) {
            if ($ifOrganizer && ! $this->isOrganizer($attendee)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " not adding attendee as he is not organizer.");
            }
            
            else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . " adding attendee.");
                
                $assertionAttendee = new Calendar_Model_Attender(array(
                    'user_id'   => $attendee->user_id,
                    'user_type' => $attendee->user_type,
                    'status'    => Calendar_Model_Attender::STATUS_ACCEPTED,
                    'role'      => Calendar_Model_Attender::ROLE_REQUIRED
                ));
                
                if (! $this->attendee instanceof Tinebase_Record_RecordSet) {
                    $this->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
                }
                $this->attendee->addRecord($assertionAttendee);
            }
        }
        
        return $assertionAttendee;
    }
    
    /**
     * returns the original dtstart of a recur series exception event 
     *  -> when the event should have started with no exception
     * 
     * @return Tinebase_DateTime
     */
    public function getOriginalDtStart($dtStartDiff=null)
    {
        $origianlDtStart = $this->dtstart instanceof stdClass ? clone $this->dtstart : $this->dtstart;
        
        if ($this->isRecurException()) {
            if ($this->recurid instanceof DateTime) {
                $origianlDtStart = clone $this->recurid;
            } else if (is_string($this->recurid)) {
                $origianlDtStartString = substr($this->recurid, -19);
                if (! Tinebase_DateTime::isDate($origianlDtStartString)) {
                    throw new Tinebase_Exception_InvalidArgument('recurid does not contain a valid original start date');
                }
                
                $origianlDtStart = new Tinebase_DateTime($origianlDtStartString, 'UTC');
            }
        }

        if ($dtStartDiff instanceof DateInterval) {
            $origianlDtStart->modifyTime($dtStartDiff);
        }
        return $origianlDtStart;
    }
    
    /**
     * gets translated field name
     * 
     * NOTE: this has to be done explicitly as our field names are technically 
     *       and have no translations
     *       
     * @param string         $_field
     * @param Zend_Translate_Adapter $_translation
     * @return string
     */
    public static function getTranslatedFieldName($_field, $_translation)
    {
        $t = $_translation;
        switch ($_field) {
            case 'dtstart':           return $t->_('Start');
            case 'dtend':             return $t->_('End');
            case 'transp':            return $t->_('Blocking');
            case 'class':             return $t->_('Classification');
            case 'description':       return $t->_('Description');
            case 'location':          return $t->_('Location');
            case 'organizer':         return $t->_('Organizer');
            case 'priority':          return $t->_('Priority');
            case 'status':            return $t->_('Status');
            case 'summary':           return $t->_('Summary');
            case 'url':               return $t->_('Url');
            case 'rrule':             return $t->_('Recurrance rule');
            case 'is_all_day_event':  return $t->_('Is all day event');
            case 'originator_tz':     return $t->_('Organizer timezone');
            default:                  return $_field;
        }
    }
    
    /**
     * gets translated value
     * 
     * NOTE: This is needed for values like Yes/No, Datetimes, etc.
     * 
     * @param  string           $_field
     * @param  mixed            $_value
     * @param  Zend_Translate   $_translation
     * @param  string           $_timezone
     * @return string
     */
    public static function getTranslatedValue($_field, $_value, $_translation, $_timezone)
    {
        $locale = new Zend_Locale($_translation->getAdapter()->getLocale());

        if ($_value instanceof Tinebase_DateTime) {
            return Tinebase_Translation::dateToStringInTzAndLocaleFormat($_value, $_timezone, $locale, 'datetime', true);
        }
        
        switch ($_field) {
            case 'organizer':
                if (! $_value instanceof Addressbook_Model_Contact) {
                    $organizer = Addressbook_Controller_Contact::getInstance()->getMultiple($_value, TRUE)->getFirstRecord();
                    return $organizer instanceof Addressbook_Model_Contact ? $organizer->n_fileas : '';
                } else {
                    return '';
                }
            case 'rrule':
                if ($_value) {
                    $rrule = $_value instanceof Calendar_Model_Rrule ? $_value : new Calendar_Model_Rrule($_value);
                    return $rrule->getTranslatedRule($_translation);
                }
                return '';
            case 'status':
                return Tinebase_Config_KeyFieldRecord::getTranslatedValue('Calendar', Calendar_Config::EVENT_STATUS, $_value);
            case 'transp':
                return Tinebase_Config_KeyFieldRecord::getTranslatedValue('Calendar', Calendar_Config::EVENT_TRANSPARENCIES, $_value);
            case 'class':
                return Tinebase_Config_KeyFieldRecord::getTranslatedValue('Calendar', Calendar_Config::EVENT_CLASSES, $_value);
            default:
                return $_value;
        }
    }
    
    /**
     * checks event for given grant
     * 
     * @param  string $_grant
     * @return bool
     */
    public function hasGrant($_grant)
    {
        $hasGrant = isset($this->_properties[$_grant]) && (bool)$this->{$_grant};

        // extra check for privat events. deleting is always possible though (to be able to delete the calendar itself)
        if ($hasGrant && $this->class !== Calendar_Model_Event::CLASS_PUBLIC &&
                $_grant !== Tinebase_Model_Grants::GRANT_DELETE) {
            $hasGrant =
                // private grant
                $this->{Calendar_Model_EventPersonalGrants::GRANT_PRIVATE} ||
                // I'm organizer
                Tinebase_Core::getUser()->contact_id == ($this->organizer instanceof Addressbook_Model_Contact ? $this->organizer->getId() : $this->organizer) ||
                // I'm attendee
                Calendar_Model_Attender::getOwnAttender($this->attendee);
        } else if ($hasGrant || (Tinebase_Core::getUser()->contact_id == ($this->organizer instanceof Addressbook_Model_Contact ? $this->organizer->getId() : $this->organizer) && $_grant == Tinebase_Model_Grants::GRANT_DELETE)) {
            //I have the grand or I'm organizer
            $hasGrant = true;
        }
        
        return $hasGrant;
    }
    
    /**
     * event is an exception of a recur event series
     * 
     * @return boolean
     */
    public function isRecurException()
    {
        return !!$this->recurid;
    }

    /**
     * event is non persistent recur instance
     * 
     * @return boolean
     */
    public function isRecurInstance()
    {
        return (boolean) preg_match('/^fakeid/', $this->getId());
    }

    /**
     * returns a URL with a deep link path to the node provided
     *
     * @return string
     */
    public function getDeepLink()
    {
        return Tinebase_Core::getUrl() . '/#/Calendar/Event/' . $this->getId();
    }

    /**
     * sets recurId of this model
     * 
     * @return string recurid which was set
     */
    public function setRecurId($baseEventId)
    {
        if (! ($this->uid && $this->dtstart)) {
            throw new Exception ('uid _and_ dtstart must be set to generate recurid');
        }
        
        // make sure we store recurid in utc
        $dtstart = $this->getOriginalDtStart();
        $dtstart->setTimezone('UTC');
        
        $this->recurid = $this->uid . '-' . $dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $this->base_event_id = $baseEventId;

        return $this->recurid;
    }
    
    /**
     * sets rrule until helper field
     *
     * @return void
     */
    public function setRruleUntil()
    {
        if (empty($this->rrule)) {
            $this->rrule_until = NULL;
        } else {
            $rrule = $this->rrule;
            if (! $rrule instanceof Calendar_Model_Rrule) {
                $rruleTmp = new Calendar_Model_Rrule(is_array($rrule) ? $rrule : array());
                if (!is_array($rrule)) {
                    $rruleTmp->setFromString($rrule);
                }
                $this->rrule = $rruleTmp;
                $rrule = $rruleTmp;
            }
            
            if (isset($rrule->count)) {
                $this->rrule_until = NULL;
                $exdates = $this->exdate;
                $this->exdate = NULL;
                
                $lastOccurrence = Calendar_Model_Rrule::computeNextOccurrence($this, new Tinebase_Record_RecordSet('Calendar_Model_Event'), $this->dtend, $rrule->count -1);
                if ($lastOccurrence) {
                    // with count == 1 lastOccurence === $this => we need to clone here, eventhough in most cases it wouldn't be required
                    $this->rrule_until = clone $lastOccurrence->dtend;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Could not find last occurrence of event ' . $this->getId());
                }
                $this->exdate = $exdates;
            } else {
                $this->rrule_until = $rrule->until;
            }
        }
        
        if ($this->rrule_until && $this->rrule_until->getTimeStamp() - $this->dtstart->getTimeStamp() < -1) {
            throw new Tinebase_Exception_Record_Validation('rrule until must not be before dtstart');
        }
    }
    public static function resetFreeBusyCleanupCache()
    {
        static::$_freebusyCleanUpVisibilty = null;
        static::$_freebusyCleanUpKeys = null;
    }
    /**
     * cleans up data to only contain freebusy infos
     * removes all fields except dtstart/dtend/id/modlog fields
     * 
     * @return boolean TRUE if cleanup took place
     */
    public function doFreeBusyCleanup()
    {
        if ($this->hasGrant(Tinebase_Model_Grants::GRANT_READ)) {
           return FALSE;
        }

        $oldAttendee = $this->attendee;
        if (!$oldAttendee instanceof Tinebase_Record_RecordSet) {
            $oldAttendee = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class);
        }

        if (null === static::$_freebusyCleanUpKeys) {
            static::$_freebusyCleanUpVisibilty =
                intval(Calendar_Config::getInstance()->{Calendar_Config::FREEBUSY_INFO_ALLOWED});
            $keys = [
                'id',
                'dtstart',
                'dtend',
                'transp',
                'seq',
                // TODO add again (but after recurrence calculation
                // remove it to avoid leaking the uid on freebusy which might be missued in spoofing attacks
                'uid',
                'is_all_day_event',
                'rrule',
                'rrule_until',
                'recurid',
                'exdate',
                'created_by',
                'creation_time',
                'last_modified_by',
                'last_modified_time',
                'is_deleted',
                'deleted_time',
                'deleted_by',
                'originator_tz',
            ];
            if (static::$_freebusyCleanUpVisibilty > Calendar_Config::FREEBUSY_INFO_ALLOW_DATETIME) {
                $keys[] = 'organizer';
            }
            if (static::$_freebusyCleanUpVisibilty > Calendar_Config::FREEBUSY_INFO_ALLOW_ORGANIZER) {
                $keys[] = 'attendee';
            }
            if (static::$_freebusyCleanUpVisibilty > Calendar_Config::FREEBUSY_INFO_ALLOW_RESOURCE_ATTENDEE) {
                $keys[] = 'container_id';
            }

            static::$_freebusyCleanUpKeys = array_flip($keys);
        }

        $this->_properties = array_intersect_key($this->_properties, static::$_freebusyCleanUpKeys);

        if (static::$_freebusyCleanUpVisibilty < Calendar_Config::FREEBUSY_INFO_ALLOW_RESOURCE_ATTENDEE) {
            $oldAttendee->removeAll();
        } elseif (static::$_freebusyCleanUpVisibilty < Calendar_Config::FREEBUSY_INFO_ALLOW_ALL_ATTENDEE) {
            /** @var Calendar_Model_Attender $oldAttendee */
            $oldAttendee = $oldAttendee->filter('user_type', Calendar_Model_Attender::USERTYPE_RESOURCE);
        }

        $this->attendee = $oldAttendee;
        return true;
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
        if (empty($_data['geo'])) {
            $_data['geo'] = NULL;
        }
        
        if (empty($_data['adr_lon'])) {
            $_data['adr_lon'] = NULL;
        }

        if (empty($_data['adr_lat'])) {
            $_data['adr_lat'] = NULL;
        }

        if (isset($_data['location_record']) && is_array($_data['location_record'])) {
            $_data['location_record'] = $_data['location_record']['id'];
        }
        
        if (empty($_data['class'])) {
            $_data['class'] = self::CLASS_PUBLIC;
        }
        
        if (empty($_data['priority'])) {
            $_data['priority'] = NULL;
        }
        
        if (empty($_data['status'])) {
            $_data['status'] = self::STATUS_CONFIRMED;
        }
        
        if (isset($_data['container_id']) && is_array($_data['container_id']) && isset($_data['container_id']['id'])) {
            $_data['container_id'] = $_data['container_id']['id'];
        }
        
        if (isset($_data['organizer']) && is_array($_data['organizer'])) {
            $_data['organizer'] = $_data['organizer']['id'];
        }

        if (empty($_data['originator_tz'])) {
            $_data['originator_tz'] = Tinebase_Core::getUserTimezone();
        }
        
        if (isset($_data['attendee']) && is_array($_data['attendee'])) {
            $_data['attendee'] = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $_data['attendee'], $this->bypassFilters, $this->convertDates);
        }

        if (isset($_data['rrule']) && ! empty($_data['rrule']) && ! $_data['rrule'] instanceof Calendar_Model_Rrule) {
            // rrule can be array or string
            $_data['rrule'] = new Calendar_Model_Rrule($_data['rrule'], $this->bypassFilters, $this->convertDates);
        }

        if (isset($_data['rrule_constraints']) && ! empty($_data['rrule_constraints']) && ! $_data['rrule_constraints'] instanceof Calendar_Model_EventFilter) {
            // rrule can be array or string
            $_data['rrule_constraints'] = new Calendar_Model_EventFilter($_data['rrule_constraints']);

        }

        if (isset($_data['alarms']) && is_array($_data['alarms'])) {
            $_data['alarms'] = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', $_data['alarms'], TRUE, $this->convertDates);
        }
        if (isset($_data['poll_id']) && is_array($_data['poll_id'])) {
            $_data['poll_id'] = new Calendar_Model_Poll($_data['poll_id'], $this->bypassFilters, $this->convertDates);
        }
        parent::setFromArray($_data);
    }
    
    /**
     * checks if event matches period filter
     * 
     * @param Calendar_Model_PeriodFilter $_period
     * @return boolean
     */
    public function isInPeriod(Calendar_Model_PeriodFilter $_period)
    {
        $result = TRUE;
        
        if ($this->dtend->compare($_period->getFrom()) == -1 || $this->dtstart->compare($_period->getUntil()) == 1) {
            $result = FALSE;
        }
        
        return $result;
    }
    
    /**
     * returns TRUE if comparison detects a resechedule / significant change
     *
     * ATTENTION: is may not be reversable all the time (because of the rrule logic below)
     * $event1->isRescheduled($event2) !== $event2->isRescheduled($event1)
     * 
     * @param  Calendar_Model_Event $_event
     * @return bool
     */
    public function isRescheduled($_event)
    {
        $diff = $this->diff($_event)->diff;

        if (isset($diff['rrule']) && $diff['rrule'] instanceof Tinebase_Record_Diff) {
            $rrDiff = $diff['rrule']->diff;
            if (isset($rrDiff['count']) && (int)$rrDiff['count'] > (int)$diff['rrule']->oldData['count']) {
                unset($rrDiff['count']);
            }
            if (empty($rrDiff)) {
                unset($diff['rrule']);
            }
        }

        return (isset($diff['dtstart']) || array_key_exists('dtstart', $diff))
            || (! $this->is_all_day_event && (isset($diff['dtend']) || array_key_exists('dtend', $diff)))
            || (isset($diff['rrule']) || array_key_exists('rrule', $diff));
    }
    
    /**
     * sets and returns the addressbook entry of the organizer
     * 
     * @return Addressbook_Model_Contact|null
     */
    public function resolveOrganizer()
    {
        if (! empty($this->organizer) && ! $this->organizer instanceof Addressbook_Model_Contact) {
            $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($this->organizer, TRUE);
            if (count($contacts)) {
                $this->organizer = $contacts->getFirstRecord();
            }
        }
        
        return is_object($this->organizer) ? $this->organizer : null;
    }
    
    /**
     * checks if given attendee is organizer of this event
     * 
     * @param Calendar_Model_Attender $_attendee
     */
    public function isOrganizer($_attendee=NULL)
    {
        $organizerContactId = NULL;
        if ($_attendee && in_array($_attendee->user_type, array(Calendar_Model_Attender::USERTYPE_USER, Calendar_Model_Attender::USERTYPE_GROUPMEMBER))) {
            $organizerContactId = $_attendee->user_id instanceof Tinebase_Record_Interface ? $_attendee->user_id->getId() : $_attendee->user_id;
        } else {
            $organizerContactId = Tinebase_Core::getUser()->contact_id;
        }
        
        return $organizerContactId == ($this->organizer instanceof Tinebase_Record_Interface ? $this->organizer->getId() : $this->organizer);
    }
    
    /**
     * returns true if organizer is external
     * 
     * @return boolean
     */
    public function hasExternalOrganizer()
    {
        $organizer = $this->resolveOrganizer();
        
        return $organizer instanceof Addressbook_Model_Contact && ! $organizer->account_id;
    }
    
    public function toShortString()
    {
        return $this->summary . '(' . $this->dtstart . ' - ' . $this->dtend . ')';
    }

    /**
     * @param string $_property
     * @param mixed $_diffValue
     * @param mixed $_oldValue
     * @return null|boolean
     */
    public function resolveConcurrencyUpdate($_property, $_diffValue, $_oldValue)
    {
        if ('rrule' === $_property) {
            $oldRrule = new Calendar_Model_Rrule($_oldValue, true);
            $oldRruleStr = (string)$oldRrule;
            if ($this->rrule instanceof Calendar_Model_Rrule) {
                $myRrule = (string)$this->rrule;
            } else {
                $myRrule = (string)Calendar_Model_Rrule::getRruleFromString($this->rrule);
            }
            if ($myRrule === $oldRruleStr) {
                $oldRrule->applyDiff(new Tinebase_Record_Diff($_diffValue));
                $this->rrule = $oldRrule;
                return true;
            }
            return false;
        }
        return null;
    }

    public function getTitle()
    {
        return $this->summary;
    }

    public static function resolveRelationId(string $id, $record = null)
    {
        if (strpos($id, 'fakeid') === 0) {
            if (is_array($record)) {
                $newRecord = new Calendar_Model_Event(null, true);
                $newRecord->setFromJsonInUsersTimezone($record);
                $record = $newRecord;
            }
            if (!$record instanceof Calendar_Model_Event) {
                $record = Calendar_Controller_Event::getInstance()->get($id);
            }
            $record = Calendar_Controller_Event::getInstance()->createRecurException($record);
            return $record->getId();
        }

        return $id;
    }

    // TODO remove the runConvert methods when migration to Modelconfig!
    public function runConvertToRecord()
    {
        if (isset($this->_properties['xprops'])) {
            $this->_properties['xprops'] = json_decode($this->_properties['xprops'], true);
        }
    }

    public function runConvertToData()
    {
        if (isset($this->_properties['xprops']) && is_array($this->_properties['xprops'])) {
            $this->_properties['xprops'] = json_encode($this->_properties['xprops']);
        }
    }

    /**
     * @return boolean
     */
    public function isReplicable()
    {
        $container = $this->container_id;
        if (empty($container)) {
            return false;
        }
        if (! $container instanceof Tinebase_Model_Container) {
            try {
                $container = Tinebase_Container::getInstance()->getContainerById($container);
            } catch (Tinebase_Exception_NotFound $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
                return false;
            }
        }

        if (isset($container->xprops()[self::XPROPS_REPLICATABLE]) && $container->xprops()[self::XPROPS_REPLICATABLE]) {
            return true;
        }

        return false;
    }
}
