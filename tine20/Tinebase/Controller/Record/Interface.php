<?php
/**
 * interface of record controller for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * interface for record controller class for Tine 2.0 applications
 * 
 * @package     Tinebase
 * @subpackage  Controller
 *
 * @template T of Tinebase_Record_Interface
 */
interface Tinebase_Controller_Record_Interface
{
    /**
     * get by id
     *
     * @param string $_id
     * @return T
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function get($_id);

    /**
     * Returns a set of leads identified by their id's
     *
     * @param $_ids
     * @param bool $_ignoreACL
     * @para $_expander
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet<T> of $this->_modelName
     * @internal param array $array of record identifiers
     */
    public function getMultiple($_ids, $_ignoreACL = false, ?\Tinebase_Record_Expander $_expander = null, $_getDeleted = false);
    
    /**
     * Gets all entries
     *
     * @param string $_orderBy Order result by
     * @param string $_orderDirection Order direction - allowed are ASC and DESC
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_RecordSet<T>
     */
    public function getAll($_orderBy = 'id', $_orderDirection = 'ASC');
    
    /*************** add / update / delete lead *****************/    

    /**
     * add one record
     *
     * @param   T $_record
     * @return  T
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function create(Tinebase_Record_Interface $_record);
    
    /**
     * update one record
     *
     * @param   T $_record
     * @return  T
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Tinebase_Exception_Record_Validation
     */
    public function update(Tinebase_Record_Interface $_record);

    /**
     * update multiple records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param array $_data
     * @param Tinebase_Model_Pagination $_pagination
     * @return array
     */
    public function updateMultiple($_filter, $_data, $_pagination = null);
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array|T|Tinebase_Record_RecordSet<T> $_ids array of record identifiers
     * @return  Tinebase_Record_RecordSet
     */
    public function delete($_ids);

    /**
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false);

    /**
     * returns the model name
     *
     * @return class-string<T>
     */
    public function getModel();

    public function copy(string $id, bool $persist): Tinebase_Record_Interface;
}
