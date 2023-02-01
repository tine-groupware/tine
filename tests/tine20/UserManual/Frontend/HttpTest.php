<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for UserManual_Frontend_Http
 */
class UserManual_Frontend_HttpTest extends TestCase
{
    /**
     * instance of test class
     *
     * @var UserManual_Frontend_Http
     */
    protected $_uit = null;

    /**
     * lazy init of uit
     *
     * @return UserManual_Frontend_Http
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $this->_uit = new UserManual_Frontend_Http();
        }

        return $this->_uit;
    }

    /**
     * testManualPageApi
     */
    public function testGet()
    {
        $cliTest = new UserManual_Frontend_CliTest();
        $cliTest->testImportManualPages();

        ob_start();
        $this->_getUit()->get('ch01.html');
        $out = ob_get_clean();

        self::assertStringContainsString('Kontakte synchronisieren', $out);
    }

    public function testGetByContext()
    {
        $cliTest = new UserManual_Frontend_CliTest();
        $cliTest->testImportManualPages();

        $context = '/Addressbook/MainScreen/Contact/Grid/PagingToolbar';

        ob_start();
        $this->_getUit()->getContext($context);
        $out = ob_get_clean();

        self::assertStringContainsString('Kontakte synchronisieren', $out);
        self::assertStringContainsString('<meta name="initial_anchor" content="idp5947104"', substr($out, 0, 800));
    }
}
