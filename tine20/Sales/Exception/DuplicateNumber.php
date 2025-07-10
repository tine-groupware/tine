<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * DuplicateNumber exception
 * 
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_DuplicateNumber extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Duplicate Number'; // _('Duplicate Number')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The number you tried to use is already taken. Please use another number, or leave the field blank to automatically assign the next available number.'; // _('The number you tried to use is already taken. Please use another number, or leave the field blank to automatically assign the next available number.')
    
    /**
     * @see SPL Exception
     */
    protected $code = 911;
}
