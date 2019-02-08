<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Milan Mertens <m.mertens@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

require_once 'TestNetworkAdapter.php';

class Tinebase_FileSystem_Preview_ServiceV2Test extends TestCase
{
    /**
     * @dataProvider getPreviewsForFileDataprovider
     */
    public function testGetPreviewsForFile($file_path, $config, $response, $expected)
    {
        $networkAdapter = new TestNetworkAdapter($response);
        $docPre = new Tinebase_FileSystem_Preview_ServiceV2($networkAdapter);

        $this->assertSame($expected, $docPre->getPreviewsForFile($file_path, $config));
    }

    public function getPreviewsForFileDataprovider()
    {
        return [
            [__DIR__.'/document.txt',['synchronRequest'=>true,],"HTTP/1.1 404 Not Found\r\nContent-type: text/html\r\n\r\n<html><p>Not Found</p></html>",
                false],
            [__DIR__.'/document.txt',['synchronRequest'=>true,],"HTTP/1.1 200 OK\r\nContent-type: text/json\r\n\r\n",
                false],
            [__DIR__.'/document.txt',['synchronRequest'=>true,],"HTTP/1.1 200 OK\r\nContent-type: text/json\r\n\r\n{}",
                []],
            [__DIR__.'/document.txt',['synchronRequest'=>true,],"HTTP/1.1 200 OK\r\nContent-type: text/json\r\n\r\n{\"key\":[\"ZGF0YTE=\",\"ZGF0YTI=\"],\"key2\":[\"ZGF0YTM=\"]}",
                ['key'=>['data1','data2'],'key2'=>['data3']]],
        ];
    }
}