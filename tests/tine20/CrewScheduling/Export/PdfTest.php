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
 * Test class for CrewScheduling_Export_Pdf
 */
class CrewScheduling_Export_PdfTest extends CrewScheduling_Export_AbstractTest
{
    public function testExportWithMail()
    {
        $this->markTestSkipped('needs to be fixed');
        $this->_testExport(true);
    }

    public function testExportWithoutMail()
    {
        $this->markTestSkipped('needs to be fixed');
        $this->_testExport(false);
    }

    /**
     * @param bool $_withMail
     */
    protected function _testExport($_withMail)
    {
        $this->_createSchedulingGroups();

        /*$event = */$this->_createSchedulingEvent();

        $previewUrl = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL};
        if (empty($previewUrl)) {
            Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                ->{Tinebase_Config::FILESYSTEM_PREVIEW_SERVICE_URL} = 'http://shooo.noTLD';
        }
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('crewscheduling_pdf');
        $definition->plugin = CrewScheduling_Export_PdfMock::class;
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


        $result = ob_get_clean();

        $file = Tinebase_TempFile::getTempPath();
        file_put_contents($file, $result);
        $this->assertEquals('application/pdf', mime_content_type($file));
        unlink($file);

        if (null !== $mailer) {
            $messages = $mailer->getMessages();
            static::assertEquals($_withMail ? 5 : 0, count($messages));
        }
    }
}
