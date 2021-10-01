<?php
/**
 * ManualPages Import for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ManualPages Import class for UserManual application
 * 
 * @package     UserManual
 * @subpackage  Import
 */
class UserManual_Import_ManualPage
{
    /**
     * import ManualPages from zip file
     *
     * @param string  $filename
     * @return boolean success
     */
    public function import($filename)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Importing user manual from ' . $filename);

        // NOTE: file:// gets added by \Tinebase_Helper::getFilename - need to remove it here
        $filename = preg_replace('/^file:\/\//', '', $filename);
        $archive = new PharData($filename);

        $importedManualPages = 0;
        foreach ($archive as $file) {
            if (is_dir($file)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Create RecursiveIterator for ' . $file);
                $fh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file), RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($fh as $splFileInfo) {
                    if ($splFileInfo->isFile() && (strtolower($splFileInfo->getExtension()) === 'html' ||
                        strtolower($splFileInfo->getExtension()) === 'htm')
                    ) {
                        $this->_importManualPage($splFileInfo);
                        $importedManualPages++;
                    }
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Imported ' . $importedManualPages . ' manual pages');

        return $importedManualPages > 0;
    }

    /**
     * @param SplFileInfo $splFileInfo
     */
    protected function _importManualPage(SplFileInfo $splFileInfo)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Importing file ' . $splFileInfo->getPathname());

        $filename = $splFileInfo->getFilename();
        $content = $this->_getContent($splFileInfo);
        $title = $this->_getTitle($content);

        $manualPage = new UserManual_Model_ManualPage(array(
            'file' => $filename,
            'title' => empty($title) ? $filename : $title,
            'content' => $content,
        ));

        UserManual_Controller_ManualPage::getInstance()->create($manualPage);
    }

    /**
     * get content from file
     *
     * @param SplFileInfo $splFileInfo
     * @return mixed|string
     */
    protected function _getContent(SplFileInfo $splFileInfo)
    {
        $content = file_get_contents($splFileInfo->getPathname());

        if ($splFileInfo->getExtension() === 'html' || $splFileInfo->getExtension() === 'htm') {
            // add base url to hrefs
            $content = preg_replace('/ href="/', ' href="index.php?method=UserManual.get&file=', $content);
        }

        return $content;
    }

    /**
     * @param string $content
     * @return null
     */
    protected function _getTitle(string $content)
    {
        if (preg_match('/<title[^>]*>(.*)<\/title>/is', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
