<?php
/**
 * @package     Courses
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Courses config class
 * 
 * @package     Courses
 * @subpackage  Config
 */
class Courses_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'Courses';

    /**
     * additional_group_memberships
     *
     * @var string
     */
    const ADDITIONAL_GROUP_MEMBERSHIPS = 'additional_group_memberships';

    const COURSE_DEPARTMENT_MAPPING = 'courseDepartmentMapping';

    /**
     * default department
     *
     * @var string
     */
    const DEFAULT_DEPARTMENT = 'default_department';

    /**
    * fields for internet access
    *
    * @var string
    */
    const INTERNET_ACCESS = 'internetAccess';
    
    /**
    * internet access group id
    *
    * @var string
    */
    const INTERNET_ACCESS_GROUP_ON = 'internet_group';
    
    /**
    * internet access filtered group id
    *
    * @var string
    */
    const INTERNET_ACCESS_GROUP_FILTERED = 'internet_group_filtered';
    
    /**
    * students group id
    *
    * @var string
    */
    const STUDENTS_GROUP = 'students_group';
    
    /**
    * students import definition
    *
    * @var string
    */
    const STUDENTS_IMPORT_DEFINITION = 'students_import_definition';

    /**
    * students username schema
    *
    * @var string
    */
    const STUDENTS_USERNAME_SCHEMA = 'students_username_schema';

    /**
    * fields for samba settings of course members
    *
    * @var string
    */
    const SAMBA = 'samba';
    
    /**
    * students loginname prefix
    *
    * @var string
    */
    const STUDENT_LOGINNAME_PREFIX = 'student_loginname_prefix';

    /**
     * students password suffix
     *
     * @var string
     */
    const STUDENT_PASSWORD_SUFFIX = 'password_suffix';

    /**
     * students loginname prefix
     *
     * @var string
     */
    const TEACHER_PASSWORD = 'teacher_password';

    const TEACHER_GROUPS = 'teacher_groups';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::ADDITIONAL_GROUP_MEMBERSHIPS => array(
            //_('Additional Group Memberships')
            'label'                 => 'Additional Group Memberships',
            //_('Array of IDs of Groups students can be members of')
            'description'           => 'Array of IDs of Groups students can be members of',
            'type'                  => 'array',
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
        ),
        self::INTERNET_ACCESS => array(
        //_('Internet Access')
            'label'                 => 'Internet Access',
        //_('Internet access options.')
            'description'           => 'Internet access options.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Tinebase_Config_KeyFieldRecord'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'ON',       'value' => 'On',       'image' => 'images/icon-set/icon_ok.svg',   'system' => true), //_('On')
                    array('id' => 'OFF',      'value' => 'Off',      'image' => 'images/icon-set/icon_stop.svg', 'system' => true), //_('Off')
                    array('id' => 'FILTERED', 'value' => 'Filtered', 'image' => 'images/icon-set/icon_IP.svg',   'system' => true), //_('Filtered')
                ),
            )
        ),
        self::INTERNET_ACCESS_GROUP_ON => array(
        //_('Internet Access Group (ON)')
            'label'                 => 'Internet Access Group (ON)',
        //_('Internet Access Group (ON)')
            'description'           => 'Internet Access Group (ON)',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
        ),
        self::INTERNET_ACCESS_GROUP_FILTERED => array(
        //_('Internet Access Group (FILTERED)')
            'label'                 => 'Internet Access Group (FILTERED)',
        //_('Internet Access Group (FILTERED)')
            'description'           => 'Internet Access Group (FILTERED)',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
        ),
        self::STUDENTS_GROUP => array(
        //_('Students Group')
            'label'                 => 'Students Group',
        //_('Students Group')
            'description'           => 'Students Group',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
        ),
        self::STUDENTS_IMPORT_DEFINITION => array(
        //_('Students Import Definition')
            'label'                 => 'Students Import Definition',
        //_('Students Import Definition')
            'description'           => 'Students Import Definition',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
        ),
        self::STUDENT_LOGINNAME_PREFIX => array(
        //_('Students login name prefix')
            'label'                 => 'Student login name prefix',
            'description'           => 'Student login name prefix',
            'type'                  => 'int',
            'clientRegistryInclude' => TRUE,
        ),
        self::STUDENT_PASSWORD_SUFFIX => array(
            //_('Students password suffix')
            'label'                 => 'Students password suffix',
            'description'           => 'Students password suffix',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
        ),
        self::TEACHER_PASSWORD => array(
            //_('Teacher password')
            'label'                 => 'Teacher password',
            'description'           => 'Teacher password',
            'type'                  => 'string',
            'clientRegistryInclude' => false,
            'setBySetupModule'      => true,
            'setByAdminModule'      => false,
        ),
        self::TEACHER_GROUPS => [
            'label'                 => 'Teacher groups on import',
            'description'           => 'Teacher groups on import',
            'type'                  => 'array',
            'clientRegistryInclude' => false,
            'setBySetupModule'      => true,
            'setByAdminModule'      => true,
        ],
        self::STUDENTS_USERNAME_SCHEMA => array(
        //_('Student username schema')
            'label'                 => 'Student username schema',
        //_('Student username schema (0 = only lastname (10 chars), 1 = lastname + 2 chars of firstname')
            'description'           => 'Student username schema (0 = only lastname (10 chars), 1 = lastname + 2 chars of firstname, 3 = 1-x chars of firstname . lastname',
            'type'                  => 'int',
            'default'               => 1,
            'clientRegistryInclude' => FALSE,
        ),
        self::SAMBA => array(
                                   //_('Samba user settings')
            'label'                 => 'Samba user settings',
                                   //_('Samba user settings')
            'description'           => 'Samba user settings',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
        ),
        self::DEFAULT_DEPARTMENT => array(
                                   //_('Default department')
            'label'                 => 'Default department',
                                   //_('The default department for new Courses')
            'description'           => 'The default department for new Courses',
            'type'                  => 'string',
            'class'                 => 'Tinebase_Config_Struct',
            'default'               => '',
            'clientRegistryInclude' => TRUE,
        ),
        self::COURSE_DEPARTMENT_MAPPING => [
            //_('Course -> Department Mapping')
            self::LABEL                 => 'Course -> Department Mapping',
            //_('Course -> Department Mapping')
            self::DESCRIPTION           => 'Course -> Department Mapping',
            self::TYPE                  => self::TYPE_ARRAY,
            self::DEFAULT_STR           => [],
        ],
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Courses';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
