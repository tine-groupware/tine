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
 * Test class for UserManual_Frontend_Json
 */
class UserManual_Frontend_CliTest extends TestCase
{
    /**
     * Backend
     *
     * @var UserManual_Frontend_Cli
     */
    protected $_cli = null;

    protected function _getCli()
    {
        if ($this->_cli === null) {
            $this->_cli = new UserManual_Frontend_Cli();
        }
        return $this->_cli;
    }

    /**
     * testImportManualPages
     *
     * @param boolean $importBuild
     */
    public function testImportManualPages($importBuild = true)
    {
        $docFileToImport = dirname(__DIR__) . '/files/tine20_handbuch_2017-01-31_base64_2941752.tar.gz';
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array(
            $docFileToImport,
            'clear=1'
        ));
        if ($importBuild) {
            try {
                $result = $this->_getCli()->importHandbookBuild($opts);
            } catch (Tinebase_Exception $te) {
                if (preg_match('/Could not load xml file/', $te->getMessage())) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(
                        __METHOD__ . '::' . __LINE__ . ' ' . $te);
                    self::markTestSkipped('xml file could not be loaded - skipping test');
                } else {
                    throw $te;
                }
            }
        } else {
            $result = $this->_getCli()->importManualPages($opts);
        }

        self::assertEquals(0, $result);

        $page = UserManual_Controller_ManualPage::getInstance()->getPageByFilename('ch01.html');

        self::assertTrue($page !== null, 'did not find page');
        self::assertEquals('ch01.html', $page->file);
        self::assertEquals('1 Adressverwaltung', $page->title);
        self::assertStringContainsString('Kontakte synchronisieren', $page->content);
        self::assertStringContainsString('href="index.php?method=UserManual.get&file=html_docbook-xsl_chunked.css"', $page->content, 'base url not found in page');
    }
}
