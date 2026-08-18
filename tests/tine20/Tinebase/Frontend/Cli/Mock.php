<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Frontend_Cli_Mock extends Tinebase_Frontend_Cli
{
    /**
     * @param array $dfOutput
     * @return int
     */
    public function monitoringCheckDiskUsageMock(array $dfOutput)
    {
        $usagePercent = $this->_parseDiskUsageFromDfOutput($dfOutput);

        if ($usagePercent === null) {
            return 1;
        }
        if ($usagePercent >= 99) {
            return 2;
        }
        if ($usagePercent >= 90) {
            return 1;
        }
        return 0;
    }
}
