<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO use in Admin user module
 */

/**
 *  user filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_FullUserFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_FullUser';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => Tinebase_Model_Filter_User::class),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('login_name', 'full_name'))),
        'login_name'     => array('filter' => 'Tinebase_Model_Filter_Text'),
        'email'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'full_name'      => array('filter' => 'Tinebase_Model_Filter_Text'),
        'display_name'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'status'         => array('filter' => 'Tinebase_Model_Filter_Text'),
        'type'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'last_login'     => array('filter' => 'Tinebase_Model_Filter_DateTime'),
    );
}
