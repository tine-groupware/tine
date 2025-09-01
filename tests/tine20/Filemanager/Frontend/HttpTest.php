<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Filemanager_Frontend_Tree
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_HttpTest extends TestCase
{
    use GetProtectedMethodTrait;

    public function testDownloadPwdPrompt(): void
    {
        $translation = Tinebase_Translation::getTranslation(Filemanager_Config::APP_NAME);
        $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), $translation);
        $template = $twig->load(Filemanager_Config::APP_NAME . '/views/password.html.twig');
        $html = $template->render();
        $this->assertStringContainsString('class="password-form"', $html);
    }

    public function testDownloadFile()
    {
        $jsonTests = new Filemanager_Frontend_JsonTests();
        $file = $jsonTests->testCreateFileNodeWithTempfile();

        $uit = $this->_getUit();
        ob_start();
        $reflectionMethod = $this->getProtectedMethod(Filemanager_Frontend_Http::class, '_downloadFileNodeByPathOrId');
        $reflectionMethod->invokeArgs($uit, [$file['path'], null]);
        $out = ob_get_clean();

        self::assertEquals('test file content', $out);
    }

    protected function _createDownloadFolderStruct(): string
    {
        $fs = Tinebase_FileSystem::getInstance();
        $testPath = $fs->getApplicationBasePath(Filemanager_Config::APP_NAME, Tinebase_FileSystem::FOLDER_TYPE_SHARED)
            . '/unittest';
        $fs->createAclNode($testPath);
        $fs->mkdir($testPath . '/subfolder1');
        $fs->mkdir($testPath . '/subfolder2');

        file_put_contents('tine20://' . $testPath . '/file1', 'file1c');
        file_put_contents('tine20://' . $testPath . '/file2', 'file2c');
        file_put_contents('tine20://' . $testPath . '/subfolder1/file3', 'file3c');

        return '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/unittest';
    }

    public function testDownloadFolderNotRecursive(): void
    {
        $path = $this->_createDownloadFolderStruct();

        ob_start();
        (new Filemanager_Frontend_Http())->downloadFolder($path, false);
        $zipData = ob_get_clean();

        $path = Tinebase_TempFile::getTempPath();
        try {
            file_put_contents($path, $zipData);
            unset($zipData);
            $z = new ZipArchive();
            $z->open($path);
            $this->assertSame('file1c', $z->getFromName('file1'));
            $this->assertSame('file2c', $z->getFromName('file2'));
            $this->assertSame(false, $z->getFromName('subfolder1/file3'));

            $found = false;
            for ($i = 0; $i < $z->numFiles; $i++) {
                if ('subfolder2/' === $z->getNameIndex($i)) {
                    $found = true;
                    break;
                }
            }
            $this->assertFalse($found, 'subfolder2 found');

        } finally {
            unlink($path);
        }
    }

    public function testDownloadFolderRecursive(): void
    {
        $path = $this->_createDownloadFolderStruct();

        ob_start();
        (new Filemanager_Frontend_Http())->downloadFolder($path, true);
        $zipData = ob_get_clean();

        $path = Tinebase_TempFile::getTempPath();
        try {
            file_put_contents($path, $zipData);
            unset($zipData);
            $z = new ZipArchive();
            $z->open($path);
            $this->assertSame('file1c', $z->getFromName('file1'));
            $this->assertSame('file2c', $z->getFromName('file2'));
            $this->assertSame('file3c', $z->getFromName('subfolder1/file3'));

            $found = false;
            for ($i = 0; $i < $z->numFiles; $i++) {
                if ('subfolder2/' === $z->getNameIndex($i)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'subfolder2 not found');

        } finally {
            unlink($path);
        }
    }

    public function testDownloadFileWithoutGrant()
    {
        $jsonTests = new Filemanager_Frontend_JsonTests();
        $file = $jsonTests->testCreateFileNodeWithTempfile();

        // remove download grant from folder node
        $testPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager', Tinebase_FileSystem::FOLDER_TYPE_SHARED)
            . '/testcontainer';
        $node = Tinebase_FileSystem::getInstance()->stat($testPath);
        Tinebase_FileSystem::getInstance()->setGrantsForNode($node, Tinebase_Model_Grants::getDefaultGrants());

        $uit = $this->_getUit();
        try {
            $reflectionMethod = $this->getProtectedMethod(Filemanager_Frontend_Http::class, '_downloadFileNodeByPathOrId');
            $reflectionMethod->invokeArgs($uit, [$file['path'], null]);
            self::fail('download should not be allowed');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            self::assertEquals('download not allowed', $tead->getMessage());
        }
    }
}
