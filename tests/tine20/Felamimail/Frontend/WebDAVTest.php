<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test class for Felamimail WebDAV frontend
 * 
 * @package     Felamimail
 */
class Felamimail_Frontend_WebDAVTest extends TestCase
{
    /**
     * Tree
     *
     * @var Tine20\DAV\ObjectTree
     */
    protected $_webdavTree;
    
    /**
     * testgetNodeForPath
     */
    public function testgetNodeForPath()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('');
        
        $this->assertInstanceOf('Tinebase_WebDav_Root', $node, 'wrong node class');
        
        $children = $node->getChildren();
        
        $this->expectException('Tine20\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/');
    }
    
    public function testgetNodeForPath_webdav()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav');
        
        $this->assertInstanceOf('\Tine20\DAV\SimpleCollection', $node, 'wrong node class');
        
        $this->assertEquals('webdav', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertInstanceOf('Tinebase_WebDav_Collection_AbstractContainerTree', $children[0], 'wrong child class');
        
        $this->expectException('Tine20\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav');
    }
    
    public function testgetNodeForPath_webdav_felamimail()
    {
                $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail');
        
        $this->assertInstanceOf('Felamimail_Frontend_WebDAV', $node, 'wrong node class');
        
        $this->assertEquals('Felamimail', $node->getName());
        
        $children = $node->getChildren();
        
        $this->assertEquals(1, count($children));
        $this->assertInstanceOf('Felamimail_Frontend_WebDAV', $children[0], 'wrong node class');
        
        $this->expectException('Tine20\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Felamimail');
    }
    
    public function testgetNodeForPath_webdav_felamimail_personal()
    {
        $this->expectException('Tine20\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail/personal');
    }
    
    public function testgetNodeForPath_webdav_felamimail_shared()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail/shared');
        
        $this->assertInstanceOf('Felamimail_Frontend_WebDAV', $node, 'wrong node class');
        
        $this->assertEquals('shared', $node->getName());
        
        $children = $node->getChildren();
        
        $this->expectException('Tine20\DAV\Exception\Forbidden');
        
        $this->_getWebDAVTree()->delete('/webdav/Felamimail/shared');
    }
    
    /**
     * testgetNodeForPath_webdav_felamimail_shared_unittestdirectory
     * 
     * @return Felamimail_Frontend_WebDAV_Container
     */
    public function testgetNodeForPath_webdav_felamimail_shared_unittestdirectory()
    {
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail/shared');
        
        $node->createDirectory('unittestdirectory');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail/shared/unittestdirectory');
        
        $this->assertInstanceOf('Felamimail_Frontend_WebDAV_Container', $node, 'wrong node class');
        
        $this->assertEquals('unittestdirectory', $node->getName());
        
        $children = $node->getChildren();
        
        $properties = $node->getProperties(array());
        
        return $node;
    }
    
    /**
     * @return Felamimail_Frontend_WebDAV_File
     */
    public function testgetNodeForPath_webdav_felamimail_shared_unittestdirectory_file()
    {
        $this->markTestSkipped('0010194: fix felamimail webdav tests');

        $parent = $this->testgetNodeForPath_webdav_felamimail_shared_unittestdirectory();
        
        $etag = $parent->createFile('tine_logo.png', fopen(dirname(__FILE__) . '/../../Tinebase/files/tine_logo.png', 'r'));
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail/shared/unittestdirectory/tine_logo.png');
        
        $this->assertInstanceOf('Felamimail_Frontend_WebDAV_File', $node, 'wrong node class');
        $this->assertTrue(is_resource($node->get()));
        $this->assertEquals('tine_logo.png', $node->getName());
        $this->assertEquals(7246, $node->getSize());
        $this->assertEquals('image/png', $node->getContentType());
        $this->assertEquals('"7424e2c16388bf388af1c4fe44c1dd67d31f468b"', $node->getETag());
        $this->assertTrue(preg_match('/"\w+"/', $etag) === 1);
        
        return $node;
    }
    
    public function testDeleteFile()
    {
        $this->markTestSkipped('0010194: fix felamimail webdav tests');

        $node = $this->testgetNodeForPath_webdav_felamimail_shared_unittestdirectory_file();
    
        $this->_getWebDAVTree()->delete('/webdav/Felamimail/shared/unittestdirectory/tine_logo.png');
    
        $this->expectException('Tine20\DAV\Exception\NotFound');
        
        $node = $this->_getWebDAVTree()->getNodeForPath('/webdav/Felamimail/shared/unittestdirectory/tine_logo.png');
    }
    
    /**
     * 
     * @return \Tine20\DAV\ObjectTree
     */
    protected function _getWebDAVTree()
    {
        if (! $this->_webdavTree instanceof \Tine20\DAV\ObjectTree) {
            $this->_webdavTree = new \Tine20\DAV\ObjectTree(new Tinebase_WebDav_Root());
        }
        
        return $this->_webdavTree;
    }
}
