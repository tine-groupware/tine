<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching En Cheng <c.cheng@metaways.de>
 *
 */

/**
 * Filemanager exception
 *
 * @package     Filemanager
 * @subpackage  Exception
 */
class Filemanager_Exception_InvalidCharacter extends Tinebase_Exception_ProgramFlow
{
    /**
     *
     * @var string
     */
    protected $_title = 'Invalid Characters Found'; // _('Invalid Characters Found')

    public function __construct($_message = 'File name has invalid character', $_code = 906)
    {
        parent::__construct($_message, $_code);
    }
}
