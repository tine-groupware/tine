<?php
/**
 * backend class for Tinebase_Http_Server
 *
 * This class handles all Http requests for the UserManual application
 *
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class UserManual_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'UserManual';

    /**
     * show manual page by context
     *
     * @param  string $context
     */
    public function getContext($context = '')
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Search for page by context ' . $context
        );

        $contextRecord = UserManual_Controller_ManualContext::getInstance()->searchForContextByPath($context);
        $page = UserManual_Controller_ManualPage::getInstance()->getPageByContext($contextRecord);
        if ($page) {
            $this->_displayPage($page->file, $page, $contextRecord);
        } else {
            $file = 'bk01-toc.html';
            $page = UserManual_Controller_ManualPage::getInstance()->getPageByFilename($file);
            $this->_displayPage($file, $page);
        }
    }

    /**
     * show manual page
     *
     * @param  string  $file
     */
    public function get($file = '')
    {
        if (empty($file)) {
            $file = 'bk01-toc.html';
        }
        $page = UserManual_Controller_ManualPage::getInstance()->getPageByFilename($file);
        $this->_displayPage($file, $page);
    }

    /**
     * @param string $file
     * @param UserManual_Model_ManualPage $page
     * @param UserManual_Model_ManualContext $context
     */
    protected function _displayPage($file, $page, $context = null)
    {
        if (! headers_sent()) {
            if (preg_match('/\.css/i', $file)) {
                $contentType = 'text/css';
            } else {
                $contentType = 'text/html';
            }
            $this->_prepareHeader($file, $contentType, /* disposition */ null);
        }

        if ($page === null) {
            echo $this->_getErrorPageHtml();
        } else {
            $content = $this->_addMetaInfoToContent($page->content, $context);
            echo $content;
        }
    }

    /**
     * @param string $html
     * @param UserManual_Model_ManualContext $context
     * @return mixed
     */
    protected function _addMetaInfoToContent($html, $context = null)
    {
        // add jump target if set
        if ($context && preg_match('/#(.+)/', $context->target, $matches)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Adding jump target to ' . $matches[1]);

            // add meta name=initial_anchor behind '</title>'
            $initialAnchorMeta = '<meta name="initial_anchor" content="' . $matches[1] . '" />';
            $html = preg_replace('/<\/title>/', '</title>' . $initialAnchorMeta, $html);
        }

        return $html;
    }

    /**
     * @return string
     */
    protected function _getErrorPageHtml($additionalText = '')
    {
        // fetch index.html
        $index = UserManual_Controller_ManualPage::getInstance()->getPageByFilename('index.html');

        $translation = Tinebase_Translation::getTranslation('UserManual');
        $message = $translation->_('Manual page not found');
        $message .= $additionalText;

        if ($index) {

//        $dom = new DOMDocument;
//        $dom->loadHTML($index->content);
//        $dom->replaceChild($dom->getElementsByTagName("body")->item(0), $dom->createElement("pre", $message));
//        $html = $dom->saveHTML();

            $html = preg_replace("/<body[^>]*>.*<\/body>/is", '<body>' . $message . '</body>', $index->content);
        } else {
            // no index yet
            $html = '<html><body>' . $message . '</body></html>';
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . $html
        );

        return $html;
    }
}
