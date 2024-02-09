<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Filemanager exception
 *
 * @package     Filemanager
 * @subpackage  Exception
 */
class Filemanager_Exception_Quarantined extends Tinebase_Exception_ProgramFlow
{
    /**
     *
     * @var string
     */
    protected $_title = 'Quarantined File'; // _('Quarantined File')
    
    public function __construct($_message = 'File is quarantined', $_code = 904)
    {
        parent::__construct($_message, $_code);
    }
}
