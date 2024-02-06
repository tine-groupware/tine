<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Role Filter Class
 * @package     Tinebase
 * @subpackage  Acl
 * 
 */
class Tinebase_Model_RoleFilter extends Tinebase_Model_Filter_FilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Role';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Int'),
        'query'                 => array(
            'filter' => 'Tinebase_Model_Filter_Query',
            'options' => array('fields' => array('name', 'description'), 'modelName' => Tinebase_Model_Role::class)
        ),
        'name'                  => array('filter' => 'Tinebase_Model_Filter_Text'),
        'description'           => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
}
