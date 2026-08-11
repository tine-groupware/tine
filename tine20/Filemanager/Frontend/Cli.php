<?php
/**
 * Tine 2.0
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Cli frontend for Filemanager
 *
 * This class handles cli requests for the Filemanager
 *
 * @package     Filemanager
 */
class Filemanager_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Filemanager';

    protected $_defaultDemoDataDefinition = [
        'Filemanager_Model_Node' => 'filemanager_struktur_import_csv'
    ];

    public function csvExportFolder($opt)
    {
        $data = $this->csvExportFolderHelper($opt);
        print_r($data);

        return 0;
    }

    public function createBackup($opt): int
    {
        $echoUsage = function() {
            echo 'usage: --method=Filemanager.createBackup -- type={dump|symlink|zip} out={targetDir|targetFile} [src={filemanager path}]' . PHP_EOL;
        };
        try {
            $args = $this->_parseArgs($opt, ['type', 'out']);
        } catch (Tinebase_Exception_InvalidArgument) {
            $echoUsage();
            return 1;
        }

        $fs = Tinebase_FileSystem::getInstance();

        if ($args['src'] ?? false) {
            $srcPaths = [$cutoffPath = Filemanager_Controller_Node::getInstance()->addBasePath(rtrim($args['src'], DIRECTORY_SEPARATOR))];
        } else {
            $cutoffPath = $fs->getApplicationBasePath(Filemanager_Config::APP_NAME) . '/folders';
            $srcPaths = [
                $cutoffPath . DIRECTORY_SEPARATOR . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL,
                $cutoffPath . DIRECTORY_SEPARATOR . Tinebase_FileSystem::FOLDER_TYPE_SHARED,
            ];
        }
        $cutoffLen = strlen($cutoffPath);

        switch ($args['type']) {
            case 'dump':
                $out = rtrim($args['out'], DIRECTORY_SEPARATOR);
                foreach ($srcPaths as $srcPath) {
                    $rootNodeId = $fs->stat($srcPath);
                    $fs->walkNodeTree($rootNodeId->getId(), function (Tinebase_Model_Tree_Node $node) use ($out, $cutoffLen, $fs): bool {
                        $path = substr($fs->getPathOfNode($node, true, true), $cutoffLen);
                        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                            mkdir($out . $path, recursive: true);
                        } elseif (Tinebase_Model_Tree_FileObject::TYPE_FILE === $node->type) {
                            copy($fs->getRealPathForHash($node->hash), $out . $path);
                        }
                        return true;
                    });
                }
                break;

            case 'symlink':
                $out = rtrim($args['out'], DIRECTORY_SEPARATOR);
                foreach ($srcPaths as $srcPath) {
                    $rootNodeId = $fs->stat($srcPath);
                    $fs->walkNodeTree($rootNodeId->getId(), function (Tinebase_Model_Tree_Node $node) use ($out, $cutoffLen, $fs): bool {
                        $path = substr($fs->getPathOfNode($node, true, true), $cutoffLen);
                        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                            mkdir($out . $path, recursive: true);
                        } elseif (Tinebase_Model_Tree_FileObject::TYPE_FILE === $node->type) {
                            symlink($fs->getRealPathForHash($node->hash), $out . $path);
                        }
                        return true;
                    });
                }
                break;

            case 'zip':
                $zipOptions = new \ZipStream\Option\Archive();
                $zipOptions->setOutputStream(fopen($args['out'], 'wb'));
                $zip = new ZipStream\ZipStream(opt: $zipOptions);
                foreach ($srcPaths as $srcPath) {
                    $rootNodeId = $fs->stat($srcPath);
                    $fs->walkNodeTree($rootNodeId->getId(), function (Tinebase_Model_Tree_Node $node) use ($zip, $cutoffLen, $fs): bool {
                        if (Tinebase_Model_Tree_FileObject::TYPE_FILE === $node->type) {
                            $path = substr($fs->getPathOfNode($node, true, true), $cutoffLen);
                            if ($fh = fopen($fs->getRealPathForHash($node->hash), 'rb')) {
                                $zip->addFileFromStream($path, $fh);
                                fclose($fh);
                            }
                        }
                        return true;
                    });
                }
                $zip->finish();
                fclose($zipOptions->getOutputStream());
                break;

            default:
                $echoUsage();
                return 1;
        }

        return 0;
    }

    /**
     * give all folder from the root directory(default /shared)
     *
     * @param Zend_Console_Getopt $opts
     * @param string $parentNodels
     * @param array $paths
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    public function csvExportFolderHelper(Zend_Console_Getopt $opts, $parentNode = '/shared', $paths = array())
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Filemanager_Model_NodeFilter', [
            ['field' => 'path', 'operator' => 'equals', 'value' => $parentNode],
            ['field' => 'type', 'operator' => 'equals', 'value' => 'folder']
        ]);

        $filter->isRecursiveFilter(true);
        $nodes = Filemanager_Controller_Node::getInstance()->search($filter);

        foreach ($nodes as $node) {
            $nodePath = Tinebase_FileSystem::getInstance()->getPathOfNode($node, true);
            $nodePath = array_pop(explode('/shared/', $nodePath));
            $paths[] = $nodePath;

            $childNodes = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($node['id']);

            foreach ($childNodes as $childNode) {
                $childPath = Tinebase_FileSystem::getInstance()->getPathOfNode($childNode, true);
                $childPath = array_pop(explode('/shared/', $childPath));
                $paths[] = $childPath;

                $paths = array_merge($paths, $this->csvExportFolder($opts, '/shared/' . $childPath));
            }
        }

        return $paths;
    }
}
