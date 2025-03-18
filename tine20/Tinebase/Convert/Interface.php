<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * interface for a class to convert between an external format and a Tine 2.0 record
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
interface Tinebase_Convert_Interface
{
    /**
     * converts external format to Tinebase_Record_Interface
     *
     * @param  Tinebase_Record_Interface|null  $_record  update existing record
     * @return Tinebase_Record_Interface
     */
    public function toTine20Model(mixed $_blob, Tinebase_Record_Interface $_record = null);
    
    /**
     * converts Tinebase_Record_Interface to external format
     * 
     * @param  Tinebase_Record_Interface  $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record);

    /**
     * converts Tinebase_Record_RecordSet to external format
     *
     * @param ?Tinebase_Record_RecordSet $_records
     * @param ?Tinebase_Model_Filter_FilterGroup $_filter
     * @param ?Tinebase_Model_Pagination $_pagination
     *
     * @return mixed
     */
    public function fromTine20RecordSet(?Tinebase_Record_RecordSet $_records = null,
                                        ?Tinebase_Model_Filter_FilterGroup $_filter = null,
                                        ?Tinebase_Model_Pagination $_pagination = null);
}
