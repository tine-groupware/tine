<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     GDPR
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     GDPR
 * @subpackage  Convert
 */
class GDPR_Convert_DataIntendedPurpose_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        $result = parent::fromTine20Model($_record);
        $result[GDPR_Model_DataIntendedPurpose::FLD_URL] = Tinebase_Core::getUrl() . '/GDPR/view/register/for/' . $_record->getId();
        return $result;
    }

    /**
     * converts Tinebase_Record_RecordSet to external format
     *
     * @param Tinebase_Record_RecordSet  $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     *
     * @return mixed
     */
    public function fromTine20RecordSet(?\Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        foreach ($_records as $record) {
            $record[GDPR_Model_DataIntendedPurpose::FLD_URL] = Tinebase_Core::getUrl() . '/GDPR/view/register/for/' . $record->getId();
        }

        $result = parent::fromTine20RecordSet($_records, $_filter, $_pagination);

        return $result;
    }
}
