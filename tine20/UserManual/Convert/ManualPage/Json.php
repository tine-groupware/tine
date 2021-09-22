<?php
/**
 * convert functions for records from/to json (array) format
 *
 * @package     UserManual
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     UserManual
 * @subpackage  Convert
 */
class UserManual_Convert_ManualPage_Json extends Tinebase_Convert_Json
{
    /**
     * resolves child records before converting the record set to an array
     *
     * @param Tinebase_Record_RecordSet $records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        // remove manual page content - we don't want to send each page to the client on search()
        if ($multiple) {
            $records->content = null;
        }

        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);
    }
}
