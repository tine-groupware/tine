<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Task Pagination Class
 * @package Tasks
 */
class Tasks_Model_Pagination extends Tinebase_Model_Pagination
{
    /**
     * @param Zend_Db_Select $_select
     * @param bool $_getDeleted
     * @param array|null $_schema
     * @return void
     * @throws Tinebase_Exception_Record_Validation
     */
    public function appendPaginationSql(Zend_Db_Select $_select, bool $_getDeleted = false, ?array $_schema = null)
    {
        if ($this->isValid()) {
            if (!empty($this->sort) && !empty($this->dir) && $this->sort == 'due'){
                $dir = $this->dir == 'ASC' ? 'DESC' : 'ASC';
                $_select->order('is_due' . ' ' . $dir);
            }
        }
        
        parent::appendPaginationSql($_select, $_getDeleted = false);
    }
}
