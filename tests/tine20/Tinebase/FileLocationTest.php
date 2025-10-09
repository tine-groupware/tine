<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_FileLocationTest extends TestCase
{

    protected function getFileLocationFromJson(array $data): Tinebase_Model_FileLocation
    {
        $fe = new Tinebase_Frontend_Json;
        $method = new ReflectionMethod($fe, '_jsonToRecord');
        $method->setAccessible(true);
        $fileLocation = $method->invoke($fe, $data, Tinebase_Model_FileLocation::class);

        return $fileLocation;
    }

    protected function doTestOnJson(array $json, array $tests): Tinebase_Model_FileLocation
    {
        $this->doTestsOnFL($fileLocation = $this->getFileLocationFromJson($json), $tests);
        return $fileLocation;
    }

    protected function doTestsOnFL(Tinebase_Model_FileLocation_Interface $fileLocation, array $tests): void
    {
        foreach ($tests as $method => $args) {
            if (!method_exists($this, $method)) {
                $this->fail('bad test, unknown method: '. $method);
            }
            if (is_array($args['multi'] ?? null)) {
                foreach ($args['multi'] as $subArgs) {
                    $this->{$method}($fileLocation, ...$subArgs);
                }
            } else {
                $this->{$method}($fileLocation, ...$args);
            }
        }
    }

    protected function checkExists(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->exists(), __FUNCTION__);
    }
    protected function checkIsFile(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->isFile(), __FUNCTION__);
    }
    protected function checkIsDirectory(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->isDirectory(), __FUNCTION__);
    }
    protected function checkCanReadData(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->canReadData(), __FUNCTION__);
    }
    protected function checkCanWriteData(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->canWriteData(), __FUNCTION__);
    }
    protected function checkCanGetChild(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->canGetChild(), __FUNCTION__);
    }
    protected function checkCanListChildren(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->canListChildren(), __FUNCTION__);
    }
    protected function checkCanGetParent(Tinebase_Model_FileLocation_Interface $fileLocation, bool $result): void
    {
        $this->assertSame($result, $fileLocation->canGetParent(), __FUNCTION__);
    }
    protected function checkGetName(Tinebase_Model_FileLocation_Interface $fileLocation, string $result): void
    {
        $this->assertSame($result, $fileLocation->getName(), __FUNCTION__);
    }
    protected function checkGetContent(Tinebase_Model_FileLocation_Interface $fileLocation, string $result): void
    {
        $this->assertSame($result, $fileLocation->getContent(), __FUNCTION__);
    }
    protected function checkWriteContent(Tinebase_Model_FileLocation_Interface $fileLocation, string $data, int $result): void
    {
        $this->assertSame($result, $fileLocation->writeContent($data), __FUNCTION__);
    }
    protected function checkGetChild(Tinebase_Model_FileLocation_Interface $fileLocation, string $name): void
    {
        $fileLocation->getChild($name);
    }
    protected function checkListChildren(Tinebase_Model_FileLocation_Interface $fileLocation, array $result): void
    {
        sort($result);
        $children = $fileLocation->listChildren();
        sort($children);
        $this->assertSame($result, $children, __FUNCTION__);
    }
    protected function checkGetParent(Tinebase_Model_FileLocation_Interface $fileLocation): void
    {
        $fileLocation->getParent();
    }
    protected function checkFailGetContent(Tinebase_Model_FileLocation_Interface $fileLocation): void
    {
        try {
            $fileLocation->getContent();
            $this->fail('expected exception in ' . __FUNCTION__);
        } catch (Tinebase_Exception) {}
    }
    protected function checkFailWriteContent(Tinebase_Model_FileLocation_Interface $fileLocation): void
    {
        try {
            $fileLocation->writeContent('');
            $this->fail('expected exception in ' . __FUNCTION__);
        } catch (Tinebase_Exception) {}
    }
    protected function checkFailGetChild(Tinebase_Model_FileLocation_Interface $fileLocation, string $name = 'name'): void
    {
        try {
            $fileLocation->getChild($name);
            $this->fail('expected exception in ' . __FUNCTION__ . '  with name = "' . $name . '"');
        } catch (Tinebase_Exception) {}
    }
    protected function checkFailGetParent(Tinebase_Model_FileLocation_Interface $fileLocation): void
    {
        try {
            $fileLocation->getParent();
            $this->fail('expected exception in ' . __FUNCTION__);
        } catch (Tinebase_Exception) {}
    }
    protected function checkFailListChildren(Tinebase_Model_FileLocation_Interface $fileLocation): void
    {
        try {
            $fileLocation->listChildren();
            $this->fail('expected exception in ' . __FUNCTION__);
        } catch (Tinebase_Exception) {}
    }

    public function testTreeNode(array $fileLocation = [], bool $shared = true): void
    {
        $fs = Tinebase_FileSystem::getInstance();
        $path = $fs->getApplicationBasePath(
                Tinebase_Application::getInstance()->getApplicationByName(Filemanager_Config::APP_NAME),
                $shared ? $fs::FOLDER_TYPE_SHARED : $fs::FOLDER_TYPE_PERSONAL
            ) . ($shared ? '' : '/' . Tinebase_Core::getUser()->getId()) . '/unittest';
        $fs->createAclNode($path);

        $fileLocation = $this->doTestOnJson($fileLocation ?: [
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_TreeNode::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_TreeNode::FLD_STAT_PATH => $path,
            ],
        ], [
            'checkExists' => [true],
            'checkIsFile' => [false],
            'checkIsDirectory' => [true],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [false],
            'checkCanGetChild' => [true],
            'checkCanListChildren' => [true],
            'checkCanGetParent' => [true],
            'checkGetName' => ['unittest'],
            //'checkGetChild' => ['foo'],
            'checkListChildren' => [[]],
            'checkGetParent' => [],
            'checkFailGetContent' => [],
            'checkFailWriteContent' => [],
            'checkFailGetChild' => ['multi' => [
                [''],
                [' '],
                ['/'],
                [' /'],
                ['/ '],
                [' / '],
                ['/a'],
                [' /a'],
            ]],
            // 'checkFailGetParent' => [],
            // 'checkFailListChildren' => [],

        ]);

        // test non existing file child
        $fileChild = $fileLocation->getChild('file');
        $this->doTestsOnFL($fileChild, [
            'checkExists' => [false],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [true],
            'checkGetName' => ['file'],
            //'checkGetChild' => ['foo'],
            //'checkListChildren' => [[]],
            'checkGetParent' => [],
            'checkWriteContent' => ['data', 4],
            'checkFailGetContent' => [],
            //'checkFailWriteContent' => [],
            'checkFailGetChild' => ['foo'],
            // 'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        // test non existing directory child
        $dirChild = $fileLocation->getChild('dir/');
        $this->doTestsOnFL($dirChild, [
            'checkExists' => [false],
            'checkIsFile' => [false],
            'checkIsDirectory' => [true],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [false],
            'checkCanGetChild' => [true],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [true],
            'checkGetName' => ['dir'],
            'checkGetChild' => ['foo'],
            //'checkListChildren' => [[]],
            'checkGetParent' => [],
            'checkFailGetContent' => [],
            'checkFailWriteContent' => [],
            //'checkFailGetChild' => ['foo'],
            //'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        $this->assertFalse($fs->fileExists($path . '/dir'));

        $subDirFileChild = $dirChild->getChild('file');
        $this->doTestsOnFL($subDirFileChild, [
            'checkExists' => [false],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [true],
            'checkGetName' => ['file'],
            //'checkGetChild' => ['foo'],
            //'checkListChildren' => [[]],
            'checkGetParent' => [],
            'checkWriteContent' => ['data', 4],
            'checkFailGetContent' => [],
            //'checkFailWriteContent' => [],
            'checkFailGetChild' => ['foo'],
            // 'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        $this->assertTrue($fs->fileExists($path . '/file'));
        $this->assertTrue($fs->fileExists($path . '/dir'));
        $this->assertTrue($fs->fileExists($path . '/dir/file'));
    }

    public function testTreeNodePersonal(): void
    {
        $this->testTreeNode(shared: false);
    }

    public function testFM(): void
    {
        $this->testTreeNode([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Filemanager_Model_FileLocation::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Filemanager_Model_FileLocation::FLD_FM_PATH => '/shared/unittest',
            ],
        ]);
    }

    public function testFMPersonal(): void
    {
        $this->testTreeNode([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Filemanager_Model_FileLocation::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Filemanager_Model_FileLocation::FLD_FM_PATH => '/personal/' . Tinebase_Core::getUser()->getId() . '/unittest',
            ],
        ], shared: false);
    }

    public function doFelamimailAttachmentCacheTest(string $cacheId, string $name, string $content): void
    {
        $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Felamimail_Model_AttachmentCache_FileLocation::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Felamimail_Model_AttachmentCache_FileLocation::FLD_CACHE_ID => $cacheId,
            ],
        ], [
            'checkExists' => [true],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [true],
            'checkCanWriteData' => [false],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [false],
            'checkGetName' => [$name],
            //'checkGetChild' => ['/a'],
            //'checkListChildren' => [[]],
            //'checkGetParent' => [],
            //'checkFailGetContent' => [],
            'checkFailWriteContent' => [],
            'checkFailGetChild' => ['a'],
            'checkFailGetParent' => [],
            'checkFailListChildren' => [],
            'checkGetContent' => [$content],
        ]);
    }

    public function testTempFile(): void
    {
        $fileLocation = $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_TempFile::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_TempFile::FLD_NAME => 'test.txt',
                Tinebase_Model_FileLocation_TempFile::FLD_TYPE => 'text/plain',
            ],
        ], [
            'checkExists' => [false],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [false],
            'checkGetName' => ['test.txt'],
            'checkFailGetContent' => [],
            'checkWriteContent' => ['data', 4],
            'checkGetContent' => ['data'],
            'checkFailGetChild' => [],
            'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        $this->assertNotEmpty($fileLocation->{Tinebase_Model_FileLocation::FLD_LOCATION}->{Tinebase_Model_FileLocation_TempFile::FLD_TEMP_FILE_ID});

        $this->doTestsOnFL($fileLocation, [
            'checkExists' => [true],
            'checkCanReadData' => [true],
            'checkWriteContent' => ['unit', 4],
        ]);

        $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_TempFile::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_TempFile::FLD_TEMP_FILE_ID => $fileLocation->{Tinebase_Model_FileLocation::FLD_LOCATION}->{Tinebase_Model_FileLocation_TempFile::FLD_TEMP_FILE_ID},
            ],
        ], [
            'checkExists' => [true],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [true],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [false],
            'checkGetName' => ['test.txt'],
            'checkGetContent' => ['unit'],
            'checkWriteContent' => ['data', 4],
            'checkFailGetChild' => [],
            'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);
    }

    public function testRecordAttachment(): void
    {
        $recordId = $this->_originalTestUser->getIdFromProperty('contact_id');
        $fileLocation = $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Addressbook_Model_Contact::class,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $recordId,
            ],
        ], [
            'checkExists' => [false],
            'checkIsFile' => [false],
            'checkIsDirectory' => [true],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [false],
            'checkCanGetChild' => [true],
            'checkCanListChildren' => [true],
            'checkCanGetParent' => [false],
            'checkGetName' => [$recordId],
            'checkGetChild' => ['/a'],
            'checkListChildren' => [[]],
            //'checkGetParent' => [],
            'checkFailGetContent' => [],
            'checkFailWriteContent' => [],
            'checkFailGetChild' => ['multi' => [
                [''],
                [' '],
                ['/'],
                [' /'],
                ['/ '],
                [' / '],
            ]],
            'checkFailGetParent' => [],
            // 'checkFailListChildren' => [],
        ]);

        $attachment = $fileLocation->getChild('a');
        $this->doTestsOnFL($attachment,  [
            'checkExists' => [false],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [false],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [true],
            'checkGetName' => ['a'],
            'checkWriteContent' => ['data', 4],
            //'checkGetChild' => ['/a'],
            //'checkListChildren' => [[]],
            'checkGetParent' => [],
            'checkFailGetContent' => [],
            //'checkFailWriteContent' => [],
            'checkFailGetChild' => ['a'],
            //'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        $attachment = $fileLocation->getChild('a');
        $this->doTestsOnFL($attachment, [
            'checkExists' => [true],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [true],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [true],
            'checkGetName' => ['a'],
            'checkGetContent' => ['data'],
            'checkWriteContent' => ['foo', 3],
            //'checkGetChild' => ['/a'],
            //'checkListChildren' => [[]],
            'checkGetParent' => [],
            //'checkFailGetContent' => [],
            //'checkFailWriteContent' => [],
            'checkFailGetChild' => ['a'],
            //'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        $this->checkGetContent($attachment, 'foo');

        $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
                Tinebase_Model_FileLocation::FLD_LOCATION => [
                    Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Addressbook_Model_Contact::class,
                    Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $recordId,
                    Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => 'a',
                ],
            ], [
            'checkExists' => [true],
            'checkIsFile' => [true],
            'checkIsDirectory' => [false],
            'checkCanReadData' => [true],
            'checkCanWriteData' => [true],
            'checkCanGetChild' => [false],
            'checkCanListChildren' => [false],
            'checkCanGetParent' => [true],
            'checkGetName' => ['a'],
            'checkGetContent' => ['foo'],
            'checkWriteContent' => ['bar', 3],
            //'checkGetChild' => ['/a'],
            //'checkListChildren' => [[]],
            'checkGetParent' => [],
            //'checkFailGetContent' => [],
            //'checkFailWriteContent' => [],
            'checkFailGetChild' => ['a'],
            //'checkFailGetParent' => [],
            'checkFailListChildren' => [],
        ]);

        $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Addressbook_Model_Contact::class,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $recordId,
            ],
        ], [
            'checkExists' => [true],
            'checkIsFile' => [false],
            'checkIsDirectory' => [true],
            'checkListChildren' => [['a']],
        ]);

        $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Addressbook_Model_Contact::class,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $recordId,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => null,
            ],
        ], [
            'checkExists' => [true],
            'checkIsDirectory' => [true],
        ]);

        $this->doTestOnJson([
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
            Tinebase_Model_FileLocation::FLD_LOCATION => [
                Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Addressbook_Model_Contact::class,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $recordId,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => '',
            ],
        ], [
            'checkExists' => [true],
            'checkIsDirectory' => [true],
        ]);

        foreach ([' ', ' /', '/ ', ' / '] as $name) {
            try {
                $this->doTestOnJson([
                    Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
                    Tinebase_Model_FileLocation::FLD_LOCATION => [
                        Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Addressbook_Model_Contact::class,
                        Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $recordId,
                        Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => $name,
                    ],
                ], [
                    'checkExists' => [true],
                ]);
                $this->fail('"' . $name . '" is not a valid name');
            } catch (Tinebase_Exception) {}
        }
    }
}
