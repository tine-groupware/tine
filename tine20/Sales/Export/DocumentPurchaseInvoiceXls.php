<?php declare(strict_types=1);
/**
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Sales_Export_DocumentPurchaseInvoiceXls extends Tinebase_Export_Xls
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
    protected $_defaultExportname = 'document_purchaseinvoice_xls';
}
