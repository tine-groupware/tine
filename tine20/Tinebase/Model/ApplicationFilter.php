<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * application filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_ApplicationFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Tinebase_Model_Application::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'    => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query' => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name'), 'modelName' => Tinebase_Model_Application::class)),
        'name'  => array('filter' => 'Tinebase_Model_Filter_Text')
    );
}
