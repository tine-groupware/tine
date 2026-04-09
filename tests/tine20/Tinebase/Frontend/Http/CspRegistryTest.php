<?php

/**
 * Tine 2.0 - https://www.tine20.org
 *
 * @package     Tinebase
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Tonia Wulff <t.wulff@metaways.de>
 */

/**
 * Test class for Tinebase_Frontend_Http
 */
class Tinebase_Frontend_Http_CspRegistryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Tinebase_Frontend_Http_CspRegistry::getInstance()->reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Tinebase_Frontend_Http_CspRegistry::getInstance()->reset();
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testAddSourceAddsToCorrectDirective()
    {
        $registry = Tinebase_Frontend_Http_CspRegistry::getInstance();
        $registry->addSource('script-src', 'https://example.com');

        $this->assertContains('https://example.com', $registry->getSources('script-src'));
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testAddMultipleSourcesToSameDirective()
    {
        $registry = Tinebase_Frontend_Http_CspRegistry::getInstance();
        $registry->addSource('connect-src', 'https://api.example.com');
        $registry->addSource('connect-src', 'https://ws.example.com');

        $sources = $registry->getSources('connect-src');
        $this->assertContains('https://api.example.com', $sources);
        $this->assertContains('https://ws.example.com', $sources);
        $this->assertCount(2, $sources);
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testAddSourceDoesNotDuplicate()
    {
        $registry = Tinebase_Frontend_Http_CspRegistry::getInstance();
        $registry->addSource('script-src', 'https://example.com');
        $registry->addSource('script-src', 'https://example.com');

        $this->assertCount(1, $registry->getSources('script-src'));
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testAddSourceThrowsOnUnknownDirective()
    {
        $this->expectException(Tinebase_Exception_InvalidArgument::class);

        Tinebase_Frontend_Http_CspRegistry::getInstance()->addSource('invalid-src', 'https://example.com');
    }

    /**
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function testResetClearsAllDirectives()
    {
        $registry = Tinebase_Frontend_Http_CspRegistry::getInstance();
        $registry->addSource('script-src', 'https://example.com');
        $registry->addSource('img-src', 'https://images.example.com');

        $registry->reset();

        $this->assertSame([], $registry->getSources('script-src'));
        $this->assertSame([], $registry->getSources('img-src'));
    }

    public function testAllNeededSourcesAreAddedToHeader()
    {
        $header = Tinebase_Frontend_Http_SinglePageApplication::getHeaders();
        $csp = $header['Content-Security-Policy'];

        $this->assertStringContainsString('https://versioncheck.tine20.net', $csp);
        $this->assertStringContainsString(" frame-src 'self' blob:", $csp);
    }
}
