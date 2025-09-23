<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for CrewScheduling_Export_Xlsx
 */
class CrewScheduling_Export_XlsxTest extends CrewScheduling_Export_AbstractTest
{
    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function testExportWithMail()
    {
        self::markTestSkipped('FIXME!');
        $this->_testExport(true);
    }

    /**
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function testExportWithoutMail()
    {
        $this->_testExport(false);
    }

    /**
     * @param bool $_withMail
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _testExport($_withMail)
    {
        /** @var CrewScheduling_Model_SchedulingRole $schedulingRole */
        $schedulingRole = $this->_schedulingRoles->getFirstRecord();

        $this->_createSchedulingGroups();

        $event = $this->_createSchedulingEvent();

        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('crewscheduling_xlsx');
        $definition->plugin = CrewScheduling_Export_XlsxMock::class;
        Tinebase_ImportExportDefinition::getInstance()->update($definition);
        $crewHttp = new CrewScheduling_Frontend_Http();
        $cfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
            Tinebase_Application::getInstance()->getApplicationByName('Calendar'), 'church_event_type',
            Calendar_Model_Event::class);
        $mailer = null;
        if (!empty(Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray())) {
            /** @var Zend_Mail_Transport_Array $mailer */
            $mailer = Tinebase_Smtp::getDefaultTransport();
            $mailer->flush();
        }

        ob_start();

        /**
         * Content-Disposition: form-data; name="filter"

        [{"field":"period","operator":"within","value":{"from":"2017-08-31 22:00:00","until":"2017-09-30 22:00:00"}},
         {"field":"customfield","operator":"AND","value":{"cfId":"83a544a409ec9bc12833843278a740301bfc7f3f","value":[{"field":"liturgie","operator":"equals","value":1}]}}]
        -----------------------------2358250556177321221281005515
        Content-Disposition: form-data; name="options"

        {"roles":["CEL","MIN","LEK","KOM","ORG","KUE","KIR"],"sendEmail":false,"definitionId":"6b7049fd6875ddbb3d676dc95b58e43aee484c0a"}
         */

        $crewHttp->exportEvents(json_encode([
            ['field' => 'period', 'operator' => 'within', 'value' =>
                ["from" => '2015-02-20 06:15:00', "until" => '2015-02-27 06:15:00']
            ],
            ['field' => 'customfield', 'operator' => 'AND', 'value' => [
                'cfId' => $cfCfg->getId(), 'value' => [[
                    'field' => 'liturgie', 'operator' => 'equals', 'value' => 1
                    ]]
            ]]
        ]), json_encode([
            'definitionId' => $definition->getId(),
            'roles' =>  $this->_schedulingRoles->key,  //["CEL","MIN","LEK","KOM","ORG","KUE","KIR"]
            'sendEmail' => $_withMail
        ]));

        $tmpFile = Tinebase_TempFile::getTempPath() . '.xlsx';
        file_put_contents($tmpFile, ob_get_clean());
        $excelObject = PHPExcel_IOFactory::load($tmpFile);
        // need to unregister the zip stream wrapper because it is overwritten by PHPExcel!
        // TODO file a bugreport to PHPExcel
        // really?
        @stream_wrapper_restore("zip");
        $excelObject->setActiveSheetIndex(0);
        $sheet = $excelObject->getActiveSheet();
        static::assertStringContainsString($event['summary'], $sheet->getCell('A6')->getValue());
        static::assertEquals($schedulingRole->getTitle(), $sheet->getCell('B5')->getValue());
        static::assertEquals(Addressbook_Controller_Contact::getInstance()->get($this->_personas['sclever']->contact_id)
            ->n_fileas, $sheet->getCell('B6')->getValue());

        if (null !== $mailer) {
            $messages = $mailer->getMessages();
            if ($_withMail) {
                static::assertGreaterThanOrEqual(4, count($messages));
            } else {
                static::assertCount(0, $messages);
            }
        }
    }
}
