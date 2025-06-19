<?php
/**
 * Addressbook Ods generation class
 *
 * @package     Addressbook
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Addressbook Ods generation class
 * 
 * @package     Addressbook
 * @subpackage    Export
 * 
 */
class Addressbook_Export_Ods extends Tinebase_Export_Spreadsheet_Ods
{
    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, ?\Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        $this->_prefKey = Addressbook_Preference::DEFAULT_CONTACT_ODS_EXPORTCONFIG;
    
        parent::__construct($_filter, $_controller, $_additionalOptions);
    }
    
    /**
     * @var string application of this export class
     */
    protected $_applicationName = 'Addressbook';

    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'adb_default_ods';
}
