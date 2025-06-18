<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Container
 *
 * @package     Calendar
 * @subpackage  Exception
 */
class Calendar_Exception_ExdateContainer extends Calendar_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Different Calendar'; // _('Different Calendar')

    /**
     * @see SPL Exception
     */
    protected $message = 'Moving the EXDATE container is not allowed.'; //_('Moving the EXDATE container is not allowed.')

    /**
     * @see SPL Exception
     */
    protected $code = 912;
}
