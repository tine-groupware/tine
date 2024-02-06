<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *  preference filter filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_PreferenceFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = Tinebase_Model_Preference::class;
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name', 'value'), 'modelName' => Tinebase_Model_Preference::class)),
        'application_id' => array('filter' => 'Tinebase_Model_Filter_Id'),
        'account'        => array('filter' => 'Tinebase_Model_PreferenceAccountFilter'),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_Id'),
        'account_type'   => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'type'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'value'          => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
    
    /**
     * returns acl filter of this group or NULL if not set
     *
     * @return Tinebase_Model_PreferenceAccountFilter
     */
    public function getAccountFilter()
    {
        return $this->_findFilter('account');
    }
}
