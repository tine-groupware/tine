<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Timetracker
 *
 * @package     Timetracker
 * @subpackage  Import
 *
 */
class Timetracker_Import_Timesheet_Csv extends Tinebase_Import_Csv_Generic
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
        'dates'             => array('billed_in','start_date')
    );
    
    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);

        $result['timeaccount_id'] = $this->_findTimeAccount($_data);
        $result['account_id'] = $this->_findUser($_data)->getId();

        return $result;
    }

    protected function _findTimeAccount(array $_data): Tinebase_Record_Interface
    {
        if (isset($_data['timeaccount_id'])) {
            try {
                return Timetracker_Controller_Timeaccount::getInstance()->get($_data['timeaccount_id']);
            } catch (Tinebase_Exception_NotFound) {
                // try to get by number
                try {
                    $result = Timetracker_Controller_Timeaccount::getInstance()->search(
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                            Timetracker_Model_Timeaccount::class, [
                            ['field' => 'number', 'operator' => 'equals', 'value' => $_data['timeaccount_id']]
                        ]));
                    if ($result->count() > 0) {
                        return $result->getFirstRecord();
                    }
                } catch (Tinebase_Exception_NotFound) {
                    // TODO try to get by name/title?
                }
            }
        }
        return Timetracker_Controller_Timeaccount::getInstance()->getAll()->getFirstRecord();
    }

    protected function _findUser(array $_data): Tinebase_Model_User
    {
        if (isset($_data['account_id'])) {
            try {
                // try to get user by id
                return Tinebase_User::getInstance()->getUserById($_data['account_id']);
            } catch (Tinebase_Exception_NotFound) {
                // try to get user by login name
                try {
                    return Tinebase_User::getInstance()->getUserByLoginName($_data['account_id']);
                } catch (Tinebase_Exception_NotFound) {
                    // use current user
                }
            }
        }
        return Tinebase_Core::getUser();
    }
}
