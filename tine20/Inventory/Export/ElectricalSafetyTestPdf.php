<?php declare(strict_types=1);
/**
 * @package     Inventory
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Inventory_Export_ElectricalSafetyTestPdf extends Tinebase_Export_DocV2
{
    use Tinebase_Export_DocumentPdfTrait;
    protected $_defaultExportname = 'inventory_electrical_safety_test_pdf';

    protected function _loadTwig()
    {
        if (class_exists('OnlyOfficeIntegrator_Config') &&
            Tinebase_Application::getInstance()->isInstalled(OnlyOfficeIntegrator_Config::APP_NAME, true)) {
            $this->_useOO = true;
        }

        parent::_loadTwig();
    }

    protected function _getOldFormat()
    {
        return 'docx';
    }
}