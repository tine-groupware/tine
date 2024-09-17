<?php
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Contract data
 *
 * @package     HumanResources
 * @subpackage  Model
 *
 * @property Tinebase_DateTime                      start_date
 * @property Tinebase_DateTime                      end_date
 * @property HumanResources_Model_WorkingTimeScheme working_time_scheme
 * @property HumanResources_Model_Employee          employee_id
 * @property string                                 feast_calendar_id
 */
class HumanResources_Model_Contract extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Contract';
    public const TABLE_NAME = 'humanresources_contract';

    public const FLD_YEARLY_TURNOVER_GOAL = 'yearly_turnover_goal';
    public const FLD_WORKING_TIME_SCHEME = 'working_time_scheme';
    public const FLD_VACATION_ENTITLEMENT_DAYS = 'vacation_entitlement_days';
    public const FLD_VACATION_ENTITLEMENT_BASE =  'vacation_entitlement_base';

    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'version'         => 7,
        'recordName'      => 'Contract', // ngettext('Contract', 'Contracts', n)
        'recordsName'     => 'Contracts', // gettext('GENDER_Contract')
        'hasRelations'    => FALSE,
        'hasCustomFields' => FALSE,
        'hasNotes'        => FALSE,
        'hasTags'         => FALSE,
        'modlogActive'    => TRUE,
        'containerProperty' => NULL,
        'createModule'    => FALSE,
        'isDependent'     => TRUE,
        'titleProperty'   => 'start_date',
        'appName'         => 'HumanResources',
        'modelName'       => 'Contract',
        'requiredRight'   => HumanResources_Acl_Rights::MANAGE_EMPLOYEE,
        self::DELEGATED_ACL_FIELD => 'employee_id',

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'employee_id' => [
                    'targetEntity' => 'HumanResources_Model_Employee',
                    'fieldName' => 'employee_id',
                    'joinColumns' => [[
                        'name' => 'employee_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        'table'             => array(
            'name'    => self::TABLE_NAME,
            'indexes' => array(
                'employee_id' => array(
                    'columns' => array('employee_id'),
                ),
            ),
        ),

        'fields'          => array(
            'employee_id'       => array(
                'label'      => 'Employee',    // _('Employee')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'start_date'        => array(
                'label'      => 'Start Date',    // _('Start Date')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'date',
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE,
                'nullable' => true,
            ),
            'end_date'          => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
                'nullable' => TRUE,
                'label'   => 'End Date',    // _('End Date')
                'type'    => 'date',
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE
            ),
            'vacation_days'     => array(
                'label'   => 'Vacation Days',    // _('Vacation Days')
                'type'    => 'integer',
                'default' => 27,
                'queryFilter' => TRUE,
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE,
            ),
            self::FLD_VACATION_ENTITLEMENT_BASE => [
                self::LABEL => 'Vacation Days Base',    // _('Vacation Days Base')
                self::TYPE => self::TYPE_INTEGER,
                self::DEFAULT_VAL => 27,
                'sortable'   => false,
                'showInDetailsPanel' => true,
                self::DISABLED  => true, // not working yet, calculation in update script was wrong, no ui yet
            ],
            self::FLD_VACATION_ENTITLEMENT_DAYS => [
                self::LABEL => 'Vacations weekly working days',    // _('Vacations weekly working days')
                self::TYPE => self::TYPE_INTEGER,
                self::DEFAULT_VAL => 5,
                'sortable'   => false,
                'showInDetailsPanel' => true,
                self::DISABLED => true, // not working yet
            ],
            'feast_calendar_id' => array(
                'label' => 'Feast Calendar',    // _('Feast Calendar')
                'type'  => self::TYPE_RECORD,
                'config' => [
                    self::APP_NAME      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME    => Tinebase_Model_BankHolidayCalendar::MODEL_NAME_PART,
                ],
                'sortable'   => FALSE,
                'showInDetailsPanel' => TRUE,
                self::FILTER    => [
                    Zend_Filter_Empty::class => null,
                ],
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'nullable' => true,
            ),
            self::FLD_WORKING_TIME_SCHEME => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME        => HumanResources_Model_WorkingTimeScheme::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Working time scheme', // _('Working time scheme')
            ],
            self::FLD_YEARLY_TURNOVER_GOAL => [
                self::TYPE              => self::TYPE_MONEY,
                self::LABEL             => 'Yearly turnover goal', // _('Yearly turnover goal')
                self::DEFAULT_VAL       => 0,
            ],
        )
    );
    
    /**
     * resolves workingtime json
     * 
     * @return mixed
     */
    public function getWorkingTimeJson()
    {
        if (!$this->{self::FLD_WORKING_TIME_SCHEME} instanceof HumanResources_Model_WorkingTimeScheme) {
            $this->{self::FLD_WORKING_TIME_SCHEME} = HumanResources_Controller_WorkingTimeScheme::getInstance()
                ->get($this->{self::FLD_WORKING_TIME_SCHEME});
        }
        return $this->{self::FLD_WORKING_TIME_SCHEME}->{HumanResources_Model_WorkingTimeScheme::FLDS_JSON};
    }
    
    /**
     * sets workingtime_json as json
     * 
     * @param mixed $workingTimeJson
     */
    public function setWorkingTimeJson($workingTimeJson)
    {
        throw new Tinebase_Exception_Backend('deprecated method');
    }
    
    /**
     * returns the weekly working hours of this record as an integer
     * 
     * @return integer
     */
    public function getWeeklyWorkingHours()
    {
        $json = $this->getWorkingTimeJson();
        $whours = 0;
        foreach($json['days'] as $index => $hours) {
            $whours += $hours;
        }
        
        return $whours;
    }

    /**
     * @param Tinebase_DateTime $_date
     * @return bool
     */
    public function isValidAt(Tinebase_DateTime $_date)
    {
        $_date = $_date->getClone();
        $_date->hasTime(false);
        if ($this->start_date->isEarlierOrEquals($_date) && (empty($this->end_date) || $this->end_date
                    ->isLaterOrEquals($_date))) {
            return true;
        }
        return false;
    }
}
