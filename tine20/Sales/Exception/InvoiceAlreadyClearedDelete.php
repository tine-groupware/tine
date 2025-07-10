<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * InvoiceAlreadyClearedDelete exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_InvoiceAlreadyClearedDelete extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Invoice is cleared already'; // _('Invoice is cleared already')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The invoice you tried to delete has already been cleared and can no longer be deleted!'; // _('The invoice you tried to delete has already been cleared and can no longer be deleted!')
    
    /**
     * @see SPL Exception
     */
    protected $code = 914;
}
