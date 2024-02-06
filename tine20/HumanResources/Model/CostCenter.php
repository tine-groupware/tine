<?php declare(strict_types=1);
/**
 * Tine 2.0

 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold CostCenter data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_CostCenter extends Tinebase_Record_Abstract implements Tinebase_Model_EvaluationDimensionCFHook
{
    public const TABLE_NAME = 'humanresources_costcenter';

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
        'version'           => 2,
        'recordName'        => 'Cost Center', // ngettext('Cost Center', 'Cost Centers', n)
        'recordsName'       => 'Cost Centers',
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
    
        'createModule'      => FALSE,
        'containerProperty' => NULL,
        'isDependent'       => TRUE,
        'titleProperty'     => 'eval_dim_cost_center.name',
        'appName'           => 'HumanResources',
        'modelName'         => 'CostCenter',

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'employee_id' => [
                    'targetEntity' => 'HumanResources_Model_Employee',
                    'fieldName' => 'employee_id',
                    'joinColumns' => [[
                        'name' => 'employee_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ]
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
        
        'fields'            => array(
            'employee_id'       => array(
                'label'      => 'Employee',    // _('Employee')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'doctrineIgnore'        => true, // already defined as association
                'config' => array(
                    'appName'     => 'HumanResources',
                    'modelName'   => 'Employee',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'start_date' => array(
                'label' => 'Start Date', //_('Start Date')
                'type'  => 'date',
            ),
        )
    );

    public static function evalDimCFHook(string $fldName, array &$definition): void
    {
        unset($definition[Tinebase_Model_CustomField_Config::DEF_FIELD][self::NULLABLE]);
        $definition[Tinebase_Model_CustomField_Config::DEF_FIELD][self::VALIDATORS] = [
            Zend_Filter_Input::ALLOW_EMPTY  => false,
            Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
        ];
    }
}
