<?php
/**
 * Timetracker xls generation class
 *
 * @package     Timetracker
 * @subpackage  Export
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * 
 */

/**
 * Timetracker xls generation class
 * 
 * @package     Timetracker
 * @subpackage  Export
 */
class Timetracker_Export_Xls extends Tinebase_Export_Xls
{
    /**
     * @var string $_applicationName
     */
    protected $_applicationName = 'Timetracker';

    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'ts_overview_xls';
}
