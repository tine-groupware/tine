<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Instance
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';


class Tinebase_InstanceTest extends TestCase
{

    /**
     * @throws Tinebase_Exception
     */
    public function testUpdateTrustedMailDomains()
    {
        $domains = ['a.de, b.de, c.de'];
        $domainRecords = array_map(fn($domain) => [
            Tinebase_Model_InstanceMailDomain::FLD_DOMAIN_NAME => $domain,
        ], $domains);

        $instance = new Tinebase_Model_Instance([
            Tinebase_Model_Instance::FLD_NAME  => 'testInstance',
            Tinebase_Model_Instance::FLD_URL  => 'test.de',
            Tinebase_Model_Instance::FLD_MAIL_DOMAINS  => $domainRecords,
        ]);
        $instanceRecord = Tinebase_Controller_Instance::getInstance()->create($instance);

        $regex = '(' . implode('|', array_map(fn($d) => preg_quote($d, '/'), $domains)) . ')';

        $trustedMailDomains = Tinebase_Controller_Instance::getInstance()->getTrustedMailDomains();
        self::assertArrayHasKey($regex, $trustedMailDomains, print_r($trustedMailDomains, true));

        $instanceRecord->{Tinebase_Model_Instance::FLD_MAIL_DOMAINS} = [
            [
                Tinebase_Model_InstanceMailDomain::FLD_DOMAIN_NAME => 'd.de',
            ]
        ];
        $instanceRecord = Tinebase_Controller_Instance::getInstance()->update($instanceRecord);
        $trustedMailDomains = Tinebase_Controller_Instance::getInstance()->getTrustedMailDomains();
        self::assertArrayNotHasKey('(d.de)', $trustedMailDomains, print_r($trustedMailDomains, true));

        Tinebase_Controller_Instance::getInstance()->delete($instanceRecord);
        $trustedMailDomains = Tinebase_Controller_Instance::getInstance()->getTrustedMailDomains();
        self::assertArrayNotHasKey('(d.de)', $trustedMailDomains, print_r($trustedMailDomains, true));
    }

    /**
     * @throws Tinebase_Exception
     */
    public function testImportInstances()
    {
        $importer = new Tinebase_Import_Instance_Yaml([
            'model' => Tinebase_Model_Instance::class
        ]);
        $path = dirname(__FILE__) . '/files/import_instances.yaml';
        $importResult = $importer->importFile($path);
        static::assertEquals(2, $importResult['totalcount'], 'import instance failed');


        $domains = ['test1.primarydomain.de', 'test1.secondarydomains.de', 'test1.secondarydomains2.de'];
        $regex = '(' . implode('|', array_map(fn($d) => preg_quote($d, '/'), $domains)) . ')';

        $trustedMailDomains = Tinebase_Controller_Instance::getInstance()->getTrustedMailDomains();
        self::assertArrayHasKey($regex, $trustedMailDomains, print_r($trustedMailDomains, true));
    }
}