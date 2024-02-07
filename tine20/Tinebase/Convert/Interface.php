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
     * @param  mixed                     $_blob
     * @param  Tinebase_Record_Interface|null  $_record  update existing record
     * @return Tinebase_Record_Interface
     */
    public function toTine20Model($_blob, Tinebase_Record_Interface $_record = null);
    
    /**
     * converts Tinebase_Record_Interface to external format
     * 
     * @param  Tinebase_Record_Interface  $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record);
}
