<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Addressbook
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Addressbook_Export_YealinkXmlTest extends TestCase
{
    public function testExport()
    {
        $export = new Addressbook_Export_Yealinkxml(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => 'container_id', 'operator' => 'equals', 'value' => Addressbook_Controller::getDefaultInternalAddressbook()],
            ]
        ));

        $export->generate();
        ob_start();
        $export->save();
        $xml = ob_get_clean();

        $this->assertStringContainsString('Name="James McBlack"', $xml);
        $this->assertStringContainsString('"+441273376616"', $xml);
    }

    public function testAppPwdExportApiCall()
    {
        $pwd = join('', array_fill(0, Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH, 'a')) . Tinebase_Controller_AppPassword::PWD_SUFFIX;
        $appPwd = Tinebase_Controller_AppPassword::getInstance()->create(new Tinebase_Model_AppPassword([
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID => $this->_originalTestUser->getId(),
            Tinebase_Model_AppPassword::FLD_AUTH_TOKEN => $pwd,
            Tinebase_Model_AppPassword::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addYear(10),
            Tinebase_Model_AppPassword::FLD_CHANNELS => [
                'Addressbook.exportContacts' => true,
            ],
        ]));
        $pwd = base64_encode($this->_originalTestUser->accountLoginName . ':' . $pwd);

        foreach (array_keys($_REQUEST) as $key) {
            unset($_REQUEST[$key]);
        }
        $_REQUEST['method'] = 'Addressbook.exportContacts';
        $_REQUEST['filter'] = json_encode([
            ['field' => 'container_id', 'operator' => 'equals', 'value' => Addressbook_Controller::getDefaultInternalAddressbook()],
        ]);
        $_REQUEST['options'] = json_encode(['format' => 'yealinkxml']);
        Tinebase_Core::unsetUser();
        Tinebase_Session::getSessionNamespace()->unsetAll();
        ob_start();
        (new Tinebase_Server_Http())->setEmitter($emitter = new Tinebase_Server_UnittestEmitter())->handle(\Laminas\Http\Request::fromString(
                'GET /index.php?method=Addressbook.exportContacts&filter=' . $_REQUEST['filter'] . '&options=' . $_REQUEST['options'] . ' HTTP/1.1' . "\r\n".
                "Authorization: Basic $pwd"
            ));
        $xml = ob_get_clean();

        $this->assertStringContainsString('Name="James McBlack"', $xml);
        $this->assertStringContainsString('"+441273376616"', $xml);
    }

    public function testAppPwdExportApiCallNoFilter(): void
    {
        $pwd = join('', array_fill(0, Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH, 'a')) . Tinebase_Controller_AppPassword::PWD_SUFFIX;
        $appPwd = Tinebase_Controller_AppPassword::getInstance()->create(new Tinebase_Model_AppPassword([
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID => $this->_originalTestUser->getId(),
            Tinebase_Model_AppPassword::FLD_AUTH_TOKEN => $pwd,
            Tinebase_Model_AppPassword::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addYear(10),
            Tinebase_Model_AppPassword::FLD_CHANNELS => [
                'Addressbook.exportContacts' => true,
            ],
        ]));
        $pwd = base64_encode($this->_originalTestUser->accountLoginName . ':' . $pwd);

        foreach (array_keys($_REQUEST) as $key) {
            unset($_REQUEST[$key]);
        }
        $_REQUEST['method'] = 'Addressbook.exportContacts';
        $_REQUEST['options'] = json_encode(['format' => 'yealinkxml']);
        $_REQUEST['filter'] = '[]';
        Tinebase_Core::unsetUser();
        Tinebase_Session::getSessionNamespace()->unsetAll();
        ob_start();
        (new Tinebase_Server_Http())->setEmitter($emitter = new Tinebase_Server_UnittestEmitter())->handle(\Laminas\Http\Request::fromString(
            'GET /index.php?method=Addressbook.exportContacts&filter=[]&options=' . $_REQUEST['options'] . ' HTTP/1.1' . "\r\n".
            "Authorization: Basic $pwd"
        ));
        $xml = ob_get_clean();

        $this->assertStringContainsString('Name="James McBlack"', $xml);
        $this->assertStringContainsString('"+441273376616"', $xml);
    }
}
