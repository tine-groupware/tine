<?php
/**
 * Pdf export generation class
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
 * CrewScheduling Pdf generation class
 *
 * @package     CrewScheduling
 * @subpackage  Export
 *
 */
class CrewScheduling_Export_Pdf extends CrewScheduling_Export_Xlsx
{
    use Tinebase_Export_DocumentPdfTrait {
        write as protected traitWrite;
    }

    /**
     * output result
     *
     * @param string $_target
     */
    public function write($_target = null)
    {
        $result = $this->traitWrite($_target);

        if (true === $this->_sendEmail) {
            $this->_sendMail($result);
        }
    }

    /**
     * return download filename
     * @param string $_appName
     * @param string $_format
     * @return string
     */
    public function getDownloadFilename($_appName = null, $_format = null)
    {
        $result = parent::getDownloadFilename($_appName, $_format);
        return rtrim($result, 'x');
    }

    /**
     * @return string
     */
    protected function _getOldFormat()
    {
        return "xlsx";
    }
}