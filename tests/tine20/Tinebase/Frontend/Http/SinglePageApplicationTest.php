<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Tinebase
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Tinebase_Frontend_Http
 */
class Tinebase_Frontend_Http_SinglePageApplicationTest extends TestCase
{
    use GetProtectedMethodTrait;

    protected mixed $_oldRequest = null;
    protected ?string $_oldTineUrl = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_oldRequest = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $this->_oldTineUrl = Tinebase_Config::getInstance()->get(Tinebase_Config::TINE20_URL);
    }

    protected function tearDown(): void
    {
        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class, $this->_oldRequest);
        Tinebase_Config::getInstance()->set(Tinebase_Config::TINE20_URL, $this->_oldTineUrl);
        parent::tearDown();
    }

    /**
     * @group needsbuild
     */
    public function testGetAssetHash()
    {
        $this->assertTrue(is_string(Tinebase_Frontend_Http_SinglePageApplication::getAssetHash()));
    }

    /**
     * @dataProvider provideGetBasePaths
     */
    public function testGetBase(string $requestPath, string $expectedBase)
    {
        $hostAndProto = 'http://unittest';
        $tineUrl = $hostAndProto . $expectedBase;
        Tinebase_Config::getInstance()->set(Tinebase_Config::TINE20_URL, $tineUrl);
        Tinebase_Core::getContainer()->set(
            \Psr\Http\Message\RequestInterface::class,
            new \Zend\Diactoros\ServerRequest([], [], $hostAndProto . $requestPath)
        );

        $reflectionMethod = $this->getProtectedMethod(
            Tinebase_Frontend_Http_SinglePageApplication::class,
            '_getBase'
        );

        $result = $reflectionMethod->invoke(null);
        $this->assertEquals($expectedBase, $result);
    }

    public static function provideGetBasePaths(): array
    {
        return [
            'root install setup.php' => ['/setup.php', ''],
            'root install index.php' => ['/index.php', ''],
            'subdirectory setup.php' => ['/tine20/setup.php', '/tine20/'],
            'subdirectory index.php' => ['/tine20/index.php', '/tine20/'],
            'root install with trailing slash' => ['/setup.php/', ''],
            'subdirectory with trailing slash' => ['/tine20/setup.php/', '/tine20/'],
        ];
    }
}
