<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Copy or move into Subfolder Exception
 *
 * @package     Filemanager
 * @subpackage  Exception
 */
class Filemanager_Exception_DestinationIsOwnChild extends Filemanager_Exception
{
    /**
     * the title of the Exception (may be shown in a dialog)
     *
     * @var string
     */
    protected $_title = 'Destination is a Subfolder'; // _('Destination is a Subfolder')
    
    /**
     * @see SPL Exception
     */
    protected $message = 'The destination is a subfolder. Copying or moving a folder into one of its subfolders is not allowed.'; // _('The destination is a subfolder. Copying or moving a folder into one of its subfolders is not allowed.')
    
    /**
     * @see SPL Exception
    */
    protected $code = 902;
}
