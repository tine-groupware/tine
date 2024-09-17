<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Sales Debitor export tests
 *
 * @package     Sales
 * @subpackage  Export
 */
class Sales_Export_DebitorTest extends Sales_Document_Abstract
{
    public function testXlsExport()
    {
        $customer = $this->_createCustomer();
        $customer->{Sales_Model_Customer::FLD_DEBITORS}->addRecord(new Sales_Model_Debitor([
            Sales_Model_Debitor::FLD_NAME => 'unittest',
            Sales_Model_Debitor::FLD_DIVISION_ID => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION},
        ], true));
        $customer = Sales_Controller_Customer::getInstance()->update($customer);

        $export = new Sales_Export_DebitorXls(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Debitor::class, [
                ['field' => Sales_Model_Debitor::FLD_CUSTOMER_ID, 'operator' => 'equals', 'value' => $customer->getId()],
            ]), null,
            [
                'definitionId' => Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
                    'model' => Sales_Model_Debitor::class,
                    'name' => 'debitor_xls'
                ]))->getFirstRecord()->getId()
            ]);

        $xls = Tinebase_TempFile::getTempPath();
        $export->generate();
        $export->write($xls);

        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $doc = $reader->load($xls);
        $arrayData = $doc->getActiveSheet()->rangeToArray('A1:M4');

        for ($i = 2; $i < 4; ++$i) {
            $this->assertTrue(in_array($customer->getTitle(), $arrayData[$i]));
            $this->assertTrue(in_array($customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_DIVISION_ID}->getTitle(), $arrayData[$i])
                || in_array($customer->{Sales_Model_Customer::FLD_DEBITORS}->getLastRecord()->{Sales_Model_Debitor::FLD_DIVISION_ID}->getTitle(), $arrayData[$i]));
        }
    }
}
