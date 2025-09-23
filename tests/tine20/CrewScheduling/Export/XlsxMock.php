<?php
/**
 * Xlsx export generation class mock for tests
 *
 * Export into specific xlsx template and convert to pdf
 *
 * @package     CrewScheduling
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CrewScheduling Xlsx generation class mock for tests
 *
 * @package     CrewScheduling
 * @subpackage  Export
 *
 */
class CrewScheduling_Export_XlsxMock extends CrewScheduling_Export_Xlsx
{
    protected static $_gLMTSinvocations = 0;

    /**
     * @return int
     */
    protected function _getLastModifiedTimeStamp()
    {
        return time() - 100 + static::$_gLMTSinvocations++;
    }
}