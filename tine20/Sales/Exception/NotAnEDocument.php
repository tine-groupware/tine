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
 * AlterOCNumberForbidden exception
 *
 * @package     Sales
 * @subpackage  Exception
 */
class Sales_Exception_NotAnEDocument extends Sales_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'File is not a valid EDocument'; // _('File is not a valid EDocument')

    /**
     * @see SPL Exception
     */
    protected $message = 'The provided file is not a valid EDocument'; // _('The provided file is not a valid EDocument!')

    /**
     * @see SPL Exception
     */
    protected $code = 920;
}
