<?php
/**
 * Tine 2.0
 * @package     EFile
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * cli server for EFile
 *
 * This class handles cli requests for the EFile
 *
 * @package     EFile
 * @subpackage  Frontend
 */
class EFile_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = EFile_Config::APP_NAME;
    
    /**
     * help array with function names and param descriptions
     */
    protected $_help = [];

    public function import(Zend_Console_Getopt $_opts): int
    {
        $this->_checkAdminRight();
        
        $args = $this->_parseArgs($_opts, ['file', 'path'], _splitSubArgs: false);

        if (!is_readable($args['file'])) {
            echo $args['file'] . ' is not readable' . PHP_EOL;
            return 1;
        }

        $importer = new EFile_Import_Csv(['path' => $args['path']]);
        $importer->importFile($args['file']);

        return 0;
    }
}
