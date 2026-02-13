<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 */

/**
 * Addressbook_Model_ListAccountOnlyFilter
 * 
 * @package     Addressbook
 * @subpackage  Filter
 */
class Addressbook_Model_ListAccountOnlyFilter extends Tinebase_Model_Filter_Bool
{
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $db = $_backend->getAdapter();
        $value = $this->_value ? 1 : 0;
        
        if ($value){
            // nothing to do -> show all lists!
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Query all lists.');
            }
        } else {
            $_select->where($db->quoteIdentifier('groups_ao.account_only').' = 0');
        }
    }
}


