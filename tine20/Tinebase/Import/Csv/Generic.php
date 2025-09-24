<?php
/**
 * tine Groupware
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */

/**
 * generic csv import class
 * 
 * @package     Tinebase
 * @subpackage  Import
 */
class Tinebase_Import_Csv_Generic extends Tinebase_Import_Csv_Abstract
{
    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $_options = [])
    {
        parent::__construct($_options);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Options: ' . print_r($_options, true));
        }
    }
}
