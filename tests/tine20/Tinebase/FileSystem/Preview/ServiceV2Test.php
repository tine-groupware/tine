<?php
/**
 * Created by PhpStorm.
 * User: milan
 * Date: 03.04.18
 * Time: 08:57
 */

class Tinebase_FileSystem_Preview_ServiceV2Test extends TestCase
{
    /**
     * @dataProvider getPreviewsForFileDataprovider
     */
    public function testGetPreviewsForFile($file_path, $config, $response, $expected) {
        $adapter = new Zend_Http_Client_Adapter_Test();
        $httpClient = new Zend_Http_Client('https://127.0.0.1', array('adapter' => $adapter));
        $adapter->setResponse($response);

        $mock = $this->getMockBuilder(Tinebase_FileSystem_Preview_ServiceV2::class)->setMethods(['getHttpClient'])->getMock();
        $mock->expects($this->once())->method('getHttpClient')->will($this->returnValue($httpClient));

        $this->assertEquals($expected, $mock->getPreviewsForFile($file_path, $config));
    }

    public function getPreviewsForFileDataprovider() {
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