<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Container Filter
 * 
 * NOTE: In the Calendar app ACL is managed by the GrantFilter
 *       so we ignore container ACL stuff here!
 *       
 * NOTE: An Event might be part of multiple calendars:
 *  - The originate calender represented by container_id
 *  - Multiple attendee calender represented by multiple displaycontainer_ids
 *  So if a user filters for certain calendars, we have to look for originate 
 *  and display containers. (@see Calendar_Backend_Sql for details of the data model)
 *  
 * @package Calendar
 */
class Calendar_Model_CalendarFilter extends Tinebase_Model_Filter_Container
{
    
    /**
     * @var array One of these grants must be given
     */
    protected $_requiredGrants = NULL;
    
    /**
     * appends sql to given select statement
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_options['ignoreAcl'] = TRUE;
        $this->_resolve();
        
        $quotedDisplayContainerIdentifier = $_backend->getAdapter()->quoteIdentifier('attendee.displaycontainer_id');

        if (strpos($this->_operator, 'not') === 0) {
            $op = ' NOT IN (?)';
        } else {
            $op = ' IN (?)';
        }

        $_select->where($this->_getQuotedFieldName($_backend) . $op, empty($this->_containerIds) ? new Zend_Db_Expr('NULL') : $this->_containerIds);
        $_select->orWhere($quotedDisplayContainerIdentifier  .  $op, empty($this->_containerIds) ? new Zend_Db_Expr('NULL') : $this->_containerIds);
    }
    
    /**
     * appends sql to given select statement
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql1($_select, $_backend)
    {
        $this->_options['ignoreAcl'] = TRUE;
        $this->_resolve();
        
        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_containerIds) ? new Zend_Db_Expr('NULL') : $this->_containerIds);
    }
    
    /**
     * appends sql to given select statement
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql2($_select, $_backend)
    {
        $this->_options['ignoreAcl'] = TRUE;
        $this->_resolve();
        
        $quotedDisplayContainerIdentifier = $_backend->getAdapter()->quoteIdentifier('attendee.displaycontainer_id');
        
        $_select->where($quotedDisplayContainerIdentifier  .  ' IN (?)', empty($this->_containerIds) ? new Zend_Db_Expr('NULL') : $this->_containerIds);
    }
    
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
    }
    
    /**
     * create a attendee filter of users affected by this filter
     * 
     * @return Calendar_Model_AttenderFilter
     */
    public function getRelatedAttendeeFilter()
    {
        // always set current account
        $userIds = array(Tinebase_Core::getUser()->getId());
        
        // rip users from paths
        foreach ((array) $this->getValue() as $value) {
            if (preg_match("/^\/personal\/([0-9a-z_\-]+)/i", $value, $matches)) {
                // transform current user 
                $userIds[] = $matches[1] == Tinebase_Model_User::CURRENTACCOUNT ? Tinebase_Core::getUser()->getId() : $matches[1];
            }
        }
        
        // get contact ids
        $users = Tinebase_User::getInstance()->getMultiple(array_unique($userIds));
        
        $attendeeFilterData = array();
        foreach ($users as $user) {
            $attendeeFilterData[] = array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $user->contact_id
            );
        }
        
        $attenderFilter = new Calendar_Model_AttenderFilter('attender', 'in', $attendeeFilterData);
        return $attenderFilter;
    }

    /**
     * fetch shared containers
     *
     * NOTE: this is needed because we don't want external organizers events to be visible if 'shared' node is requested
     *
     * @param $currentAccount
     * @param $appName
     * @return array
     */
    protected function _getSharedContainer($currentAccount, $appName)
    {
        return Tinebase_Container::getInstance()->getSharedContainer($currentAccount, Calendar_Model_Event::class, [
            Tinebase_Model_Grants::GRANT_READ
        ])->getArrayOfIds();
    }
}
