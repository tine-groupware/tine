<?php
/**
 * Sales xls generation class
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Sales xls generation class
 * 
 * @package     Sales
 * @subpackage  Export
 */
class Sales_Export_DebitorXls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Sales';
    
    /**
     * default export definition name
     * 
     * @var string
     */
    protected $_defaultExportname = 'debitor_default_xls';
}
