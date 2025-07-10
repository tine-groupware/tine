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
 * InvoiceAlreadyClearedEdit exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_InvoiceAlreadyClearedEdit extends Sales_Exception
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
    protected $message = 'The invoice you tried to edit has already been cleared, so no further editing is possible!'; // _('The invoice you tried to edit has already been cleared, so no further editing is possible!')
    
    /**
     * @see SPL Exception
     */
    protected $code = 913;
}
