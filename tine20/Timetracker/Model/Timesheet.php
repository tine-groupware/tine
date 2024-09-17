<?php
/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Timesheet data
 * 
 * @package     Timetracker
 *
 * @property integer $accounting_time
 * @property integer $duration
 * @property Tinebase_DateTime $start_date
 * @property string $start_time
 * @property string $end_time
 * @property string $timeaccount_id
 * @property string $account_id
 * @property string $accounting_time_factor
 */
class Timetracker_Model_Timesheet extends Tinebase_Record_Abstract implements Sales_Model_Billable_Interface
{
    const MODEL_NAME_PART = 'Timesheet';

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
        'version'           => 9,
        'recordName'        => 'Timesheet',
        'recordsName'       => 'Timesheets', // ngettext('Timesheet', 'Timesheets', n)
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'createModule'      => true,
        'containerProperty' => null,
        'copyEditAction'    => true,
        'copyNoAppendTitle' => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        Tinebase_ModelConfiguration::RUN_CONVERT_TO_RECORD_FROM_JSON => true,

        'titleProperty'     => 'description',
        'appName'           => Timetracker_Config::APP_NAME,
        'modelName'         => self::MODEL_NAME_PART,

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'timeaccount_id' => [
                    'targetEntity' => 'Timetracker_Model_Timeaccount',
                    'fieldName' => 'timeaccount_id',
                    'joinColumns' => [[
                        'name' => 'timeaccount_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        'table'             => array(
            'name'    => 'timetracker_timesheet',
            'indexes' => array(
                'start_date' => array(
                    'columns' => array('start_date')
                ),
                'timeaccount_id' => array(
                    'columns' => array('timeaccount_id'),
                ),
                'description' => array(
                    'columns' => array('description'),
                    'flags' => array('fulltext')
                ),
            ),
        ),

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'source' => [],
                'timeaccount_id' => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                        Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
                    ],
                ],
            ],
        ],

        // frontend
        'multipleEdit'      => true,
        'splitButton'       => true,
        'defaultFilter'     => 'start_date',

        'fields'            => array(
            'account_id'            => array(
                'label'                 => 'Account', //_('Account')
                'duplicateCheckGroup'   => 'account',
                'type'                  => 'user',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                self::CONVERTERS    => [
                    Tinebase_Model_Converter_CurrentUserIfEmpty::class
                ],
            ),
            'timeaccount_id'        => array(
                'label'                 => 'Time Account (Number - Title)', //_('Time Account (Number - Title)')
                'type'                  => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'config'                => array(
                    'appName'               => 'Timetracker',
                    'modelName'             => 'Timeaccount',
                    'idProperty'            => 'id',
                    'doNotCheckModuleRight'      => true
                ),
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_ForeignId',
                    'options'               => array(
                        'filtergroup'           => 'Timetracker_Model_TimeaccountFilter',
                        'controller'            => 'Timetracker_Controller_Timeaccount',
                        'useTimesheetAcl'       => true,
                        'showClosed'            => true,
                        'appName'               => 'Timetracker',
                        'modelName'             => 'Timeaccount',
                    ),
                    'jsConfig'              => array('filtertype' => 'timetracker.timeaccount')
                ),
            ),
            'is_billable'           => array(
                'label'                 => 'Project time billable', // _('Project time billable')
                'tooltip'               => 'Project time billable',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
                'type'                  => 'boolean',
                'default'               => 1,
                'shy'                   => true,
            ),
            // ts + ta fields combined
            'is_billable_combined'  => array(
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'                  => 'virtual',
                'config'                => [
                    'label'                 => 'Project time billable (combined)', // _('Project time billable (combined)')
                    'tooltip'               => 'Project time billable (combined)',
                    'type'                  => 'boolean',
                ],
                'filterDefinition'      => [
                    'filter'                => 'Tinebase_Model_Filter_Bool',
                    'title'                 => 'Billable', // _('Billable')
                    'options'               => array(
                        'leftOperand'           => '(timetracker_timesheet.is_billable*timetracker_timeaccount.is_billable)',
                        'requiredCols'          => array('is_billable_combined')
                    ),
                ],
            ),
            'billed_in'             => array(
                'label'                 => 'Project time cleared in', // _('Project time cleared in')
                'tooltip'               => 'Project time cleared in',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'shy'                   => true,
                'nullable'              => true,
                'copyOmit'              => true,
            ),
            'invoice_id'            => array(
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'                 => 'Project time Invoice', // _('Project time Invoice')
                'tooltip'               => 'Project time Invoice',
                'type'                  => 'record',
                'nullable'              => true,
                'inputFilters'          => array('Zend_Filter_Empty' => null),
                'config'                => array(
                    'appName'               => 'Sales',
                    'modelName'             => 'Invoice',
                    'idProperty'            => 'id',
                    // TODO we should replace this with a generic approach to fetch configured models of an app
                    // -> APP_Frontend_Json::$_configuredModels should be moved from json to app controller
                    'feature'               => 'invoicesModule', // Sales_Config::FEATURE_INVOICES_MODULE
                ),
                'copyOmit'              => true,
            ),
            'is_cleared'            => array(
                'label'                 => 'Project time is cleared', // _('Project time is cleared')
                'tooltip'               => 'Project time is cleared',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0,
                'shy'                   => true,
                'copyOmit'              => true,
            ),
            // TODO combine those three fields like this?
            // TODO create individual fields in MC and Doctrine Mapper? how to handle filter/validators/labels/...?
//            'start'            => array(
//                'label'                 => 'Date', // _('Date')
//                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
//                'type'                  => 'datetime_separated',
//                // strip time information from datetime string
//                'inputFilters'          => array('Zend_Filter_PregReplace' => array('/(\d{4}-\d{2}-\d{2}).*/', '$1'))
//            ),
            'start_date'            => array(
                'label'                 => 'Date', // _('Date')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                //'type'                  => 'date',
                'type'                  => 'datetime_separated_date',
            ),
            'start_time'            => array(
                'label'                 => 'Start time', // _('Start time')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'          => array('Zend_Filter_Empty' => null),
                'type'                  => 'time',
                // 'type'                  => 'datetime_separated_time',
                'nullable'              => true,
                'shy'                   => true
            ),
            // TODO make this work
            // TODO set user / default tz for existing/new records?
//            'start_tz'            => array(
//                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
//                'inputFilters'          => array('Zend_Filter_Empty' => null),
//                'type'                  => 'datetime_separated_tz',
//                'shy'                   => true,
//                'nullable'              => true,
//            ),
            'end_time'            => array(
                'label'                 => 'End time', // _('End time')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters'          => array('Zend_Filter_Empty' => NULL),
                'nullable'              => true,
                'type'                  => 'time',
                'shy'                   => TRUE
            ),
            'duration'              => array(
                'label'                 => 'Duration', // _('Duration')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'type'                  => 'integer',
                'specialType'           => 'minutes',
                'default'               => '30',
            ),
            'description'           => array(
                'label'                 => 'Description', // _('Description')
                'type'                  => 'fulltext',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
                'queryFilter'           => true
            ),
            'need_for_clarification'    => array(
                'label'                 => 'Need for Clarification', // _('Need for Clarification')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0
            ),
            'accounting_time_factor'    => array(
                'label'                 => 'Projecttime Accounting factor', // _('Projecttime Accounting factor')
                'inputFilters'          => [
                    Zend_Filter_Callback::class => [['callback' => [self::class, 'filterEmptyNonZero']]]
                ],
                'type'                  => 'float',
                'default'               => 1
            ),
            'accounting_time'  => array(
                'label'                 => 'Accounting Projecttime', // _('Accounting Projecttime')
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'type'                  => 'integer',
                'specialType'           => 'minutes',
                'default'               => '30'
            ),
            'workingtime_is_cleared'    => array(
                'label'                 => 'Workingtime is cleared', // _('Workingtime is cleared')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'                  => 'boolean',
                'default'               => 0
            ),
            'workingtime_cleared_in'             => array(
                'label'                 => 'Workingtime cleared in', // _('Workingtime cleared in')
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'shy'                   => true,
                'nullable'              => true,
                'copyOmit'              => true,
            ),
            'process_status'            => [
                self::LABEL                 => 'Process Status', // _('Process Status')
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => Timetracker_Config::TS_PROCESS_STATUS,
                self::DEFAULT_VAL           => Timetracker_Config::TS_PROCESS_STATUS_ACCEPTED,
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
        )
    );
    
    /**
     * returns the interval of this billable
     *
     * @return array
     */
    public function getInterval()
    {
        $startDate = clone new Tinebase_DateTime($this->start_date);
        $startDate->setTimezone(Tinebase_Core::getUserTimezone());
        $startDate->setDate($startDate->format('Y'), $startDate->format('n'), 1);
        $startDate->setTime(0,0,0);
        
        $endDate = clone $startDate;
        $endDate->addMonth(1)->subSecond(1);
        
        return array($startDate, $endDate);
    }
    
    /**
     * returns the quantity of this billable
     *
     * @return float
     */
    public function getQuantity()
    {
        return $this->accounting_time / 60;
    }
    
    /**
     * returns the unit of this billable
     *
     * @return string
     */
    public function getUnit()
    {
        return 'hour'; // _('hour')
    }

    public static function filterEmptyNonZero($data)
    {
        return $data === null || $data === false || $data === '' ? 1 : $data;
    }
}
