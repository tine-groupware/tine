<?php
/**
 * ManualContexts Import for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ManualContexts Import class for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Import
 */
class UserManual_Import_ManualContext
{
    /**
     * import ManualContext context from xml file
     *
     * @param string  $filename
     * @return boolean success
     *
     * xml is build like this:
     *
     *   <path id="/ActiveSync/EditDialog/SyncDevice">
            <link>
                <url>ch12s05.html#idp16057024</url>
                <chapterNumber>12.5</chapterNumber>
                <title>ActiveSync</title>
            </link>
            <link>
                <url>ch13s12.html#idp18882016</url>
                <chapterNumber>13.12</chapterNumber>
                <title>ActiveSync Geräte</title>
            </link>
        </path>
     */
    public function import($filename)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Importing user manual context from ' . $filename);

        // extract context from zip archive ?
        if (strpos($filename, 'zip') !== false || strpos($filename, 'tar.gz') !== false) {
            // NOTE: file:// gets added by \Tinebase_Helper::getFilename - need to remove it here
            $filename = preg_replace('/^file:\/\//', '', $filename);
            $archive = new PharData($filename);
            foreach ($archive as $file) {
                if (is_dir($file)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Create RecursiveIterator for ' . $file);
                    $fh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file), RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($fh as $splFileInfo) {
                        if ($splFileInfo->getFilename() === 'tine20_component_paths_index.xml') {
                            $filename = $splFileInfo->getPathname();
                            break 2;
                        }
                    }
                }
            }
        }

        $xml = @simplexml_load_file($filename);
        if (! $xml) {
            throw new Tinebase_Exception('Could not load xml file ' . $filename);
        }
        $numberOfContextRecordsCreated = 0;
        foreach ($xml->path as $path) {
            $attributes = $path->attributes();
            foreach ($path->link as $link) {
                $url = (string) $link->url;
                $file = substr($url, 0, strpos($url, '#'));
                $contextPath = (string) $attributes['id'];
                if (substr($contextPath, 0, 1) !== '/') {
                    $contextPath = '/' . $contextPath;
                }
                $manualContext = new UserManual_Model_ManualContext(array(
                    'context' => $contextPath,
                    'title' => (string) $link->title,
                    'target' => $url,
                    'file' => $file,
                    'chapter' => (string) $link->chapterNumber,
                ));
                UserManual_Controller_ManualContext::getInstance()->create($manualContext);
                $numberOfContextRecordsCreated++;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Created ' . $numberOfContextRecordsCreated . ' context records');

        return true;
    }
}
