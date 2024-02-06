<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Task-Status Record Class
 * @package Tasks
 */
class Tasks_Model_AttendeeStatus extends Tinebase_Config_KeyFieldRecord
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tasks';
    
    /**
     * additional status specific validators
     * 
     * @var array
     */
    protected $_additionalValidators = array(
        'is_open'              => array('allowEmpty' => true,  'Int'  ),
    );
}
