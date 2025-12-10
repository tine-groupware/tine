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

    /**
     * holds search sums twig context
     *
     * available properties:
     * [sum_is_billable]
     * [sum_duration]
     * [sum_accounting_time_billable]
     * [sum_cleared_amount]
     * [sum_recorded_amount]
     * [turnOverGoal]
     * [workingTimeTarget]
     *
     * @var null
     */
    protected $_searchCountSum = null;

    protected function _onBeforeExportRecords()
    {
        parent::_onBeforeExportRecords();
        $this->_searchCountSum = $this->_controller->searchCountSum($this->_filter);
    }

    protected function _getTwigContext(array $context)
    {
        $context = parent::_getTwigContext($context);
        $context['searchCountSum'] = $this->_searchCountSum;
        return $context;
    }
}
