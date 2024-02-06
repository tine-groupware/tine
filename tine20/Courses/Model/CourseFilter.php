<?php
/**
 * Tine 2.0
 * 
 * @package     Courses
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Course filter Class
 * @package     Courses
 */
class Courses_Model_CourseFilter extends Tinebase_Model_Filter_FilterGroup 
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Courses';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Courses_Model_Course::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'group_id'       => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => [
            'fields' => array('name', 'type'),
            'modelName' => Courses_Model_Course::class
        ]),
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'            => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'courses.id',
            'applicationName' => 'Courses',
        )),
        'type'           => array('filter' => 'Tinebase_Model_Filter_ForeignId',
            'options' => array(
                'filtergroup'       => 'Tinebase_Model_DepartmentFilter', 
                'controller'        => 'Tinebase_Department', 
            )
        ),
        'is_deleted'     => ['filter' => Tinebase_Model_Filter_Bool::class],
        'internet'       => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
