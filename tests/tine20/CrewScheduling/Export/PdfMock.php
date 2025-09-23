<?php
/**
 * Pdf export generation class mock for tests
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
 * CrewScheduling Pdf generation class mock for tests
 *
 * @package     CrewScheduling
 * @subpackage  Export
 *
 */
class CrewScheduling_Export_PdfMock extends CrewScheduling_Export_Pdf
{
    protected static $_gLMTSinvocations = 0;

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(
        Tinebase_Model_Filter_FilterGroup $_filter,
        Tinebase_Controller_Record_Interface $_controller = null,
        $_additionalOptions = array()
    ) {
        parent::__construct($_filter, $_controller, $_additionalOptions);

        $this->_previewService = new Tinebase_FileSystem_TestPreviewService();
    }

    /**
     * @return int
     */
    protected function _getLastModifiedTimeStamp()
    {
        return time() - 100 + static::$_gLMTSinvocations++;
    }
}