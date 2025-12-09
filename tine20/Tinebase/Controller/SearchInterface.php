<?php
/**
 * search interface for controller for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * search interface for controller
 * 
 * @package     Tinebase
 * @subpackage  Controller
 */
interface Tinebase_Controller_SearchInterface
{
    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool|array|Tinebase_Record_Expander $_getRelations
     * @param bool $_onlyIds
     * @param string $_action
     * @return Tinebase_Record_RecordSet
     */
    public function search(?\Tinebase_Model_Filter_FilterGroup $_filter = NULL, ?\Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get');
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter,
                                $_action = Tinebase_Controller_Record_Abstract::ACTION_GET): int;

    /**
     * Return array with total count of search with $_filter and additional sum / search count columns
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return array
     */
    public function searchCountSum(Tinebase_Model_Filter_FilterGroup $_filter,
                                   string $_action = Tinebase_Controller_Record_Abstract::ACTION_GET): array;
}
















