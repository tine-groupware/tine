<?php
/**
 * Filemanager public download frontend
 *
 * This class handles all public download requests for the Filemanager application
 * 
 * Apache rewrite rules
 * # Anonymous downloads
 * RewriteRule ^download/get/(.*)  index.php?method=Download.downloadNode&path=$1 [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
 * RewriteRule ^download/show/(.*) index.php?method=Download.displayNode&path=$1  [E=REMOTE_USER:%{HTTP:Authorization},L,QSA]
 *
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2014-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        allow to download a folder as ZIP file (see \Felamimail_Frontend_Http::_downloadAttachments)
 *
 * ATTENTION all public methods in this class are reachable without tine authentification
 */
class Filemanager_Frontend_Download extends Tinebase_Frontend_Http_Abstract
{
    /**
     * display download
     * 
     * @param string $path
     */
    public function displayNode(string $path)
    {
        try {
            $splittedPath = explode('/', trim($path, '/'));
            array_walk($splittedPath, fn(&$val) => $val = urldecode($val));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__
                . ' Display download node with path ' . print_r($splittedPath, true));
            
            $downloadId = array_shift($splittedPath);
            $download = $this->_getDownloadLink($downloadId);

            if (! $this->_verfiyPassword($download)) {
                return $this->_renderPasswordForm();
                exit;
            }

            $this->_setDownloadLinkOwnerAsUser($download);
            
            $node = Filemanager_Controller_DownloadLink::getInstance()->getNode($download, $splittedPath);
            
            switch ($node->type) {
                case Tinebase_Model_Tree_FileObject::TYPE_FILE:
                    return $this->_displayFile($download, $node, $splittedPath);
                    break;
                    
                case Tinebase_Model_Tree_FileObject::TYPE_FOLDER:
                    return $this->_listDirectory($download, $node, $splittedPath);
                    break;
            }
            
        } catch (Exception $e) {
            return $this->_handleExceptionAndShow404($e);
        }
        
        exit;
    }

    /**
     * @param Throwable $e
     */
    protected function _handleExceptionAndShow404(Throwable $e)
    {
        if ($e instanceof Tinebase_Exception_ProgramFlow) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
        } else {
            Tinebase_Exception::log($e);
        }
        return $this->_renderNotFoundPage();
    }

    protected function _verfiyPassword($download)
    {
        if (! Filemanager_Controller_DownloadLink::getInstance()->hasPassword($download)) {
            return true;
        }

        $password = $this->_getPassword();
        if (Filemanager_Controller_DownloadLink::getInstance()->validatePassword($download, $password)) {
            // save password in cookie / 1 hour lifetime
            setcookie('dlpassword', $password, time() + 3600, '/download');
            return true;
        }

        return false;
    }

    /**
     * fetch password from request
     *
     * @return string
     *
     * TODO improve this: maybe we can get the param from the Zend\Http\Request object
     *  -> $request = Tinebase_Core::get(Tinebase_Core::REQUEST);
     */
    protected function _getPassword()
    {
        if (isset($_REQUEST['dlpassword'])) {
            return $_REQUEST['dlpassword'];
        } elseif (isset($_COOKIE['dlpassword'])) {
                return $_COOKIE['dlpassword'];
        } else {
            return '';
        }
    }

    /**
     * renderPasswordForm
     */
    protected function _renderPasswordForm()
    {
        return $this->_getHTML('password');
    }

    protected function _getHTML($templateName, $context = [])
    {
        $locale = Tinebase_Core::getLocale();
        $jsFiles[] = "Filemanager/js/publicDownload/index.js";
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=Filemanager";
        $context = array_merge($context, [
            'noLoadingAnnimation' => true,
            'base'  => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
            'application'   =>  Filemanager_Config::APP_NAME
        ]);

        $html = Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, Filemanager_Config::APP_NAME,
            "$templateName.html.twig",
            $context);
        return $html;
    }

    /**
     * renderNotFoundPage
     */
    protected function _renderNotFoundPage()
    {
        return $this->_getHTML('notfound');
    }

    /**
     * download file
     * 
     * @param string $path
     */
    public function downloadNode(string $path)
    {
        try {
            $splittedPath = explode('/', trim($path, '/'));
            array_walk($splittedPath, fn(&$val) => $val = urldecode($val));
            $downloadId = array_shift($splittedPath);
            $download = $this->_getDownloadLink($downloadId);

            if (! $this->_verfiyPassword($download)) {
                $this->_renderPasswordForm();
                exit;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Download path: ' . $$path);

            $this->_setDownloadLinkOwnerAsUser($download);
            
            $node = Filemanager_Controller_DownloadLink::getInstance()->getNode($download, $splittedPath);
            
            if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                $nodeController = Filemanager_Controller_Node::getInstance();
                $nodeController->resolveMultipleTreeNodesPath($node);
                $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($nodeController->addBasePath($node->path));

                Filemanager_Controller_DownloadLink::getInstance()->increaseAccessCount($download);
                $this->_downloadFileNode($node, $pathRecord->streamwrapperpath);
            }
            
        } catch (Exception $e) {
            $this->_handleExceptionAndShow404($e);
        }
        
        exit;
    }
    
    /**
     * resolve download id
     * 
     * @param  string $id
     * @return Filemanager_Model_DownloadLink
     */
    protected function _getDownloadLink(string $id): Filemanager_Model_DownloadLink
    {
        return Filemanager_Controller_DownloadLink::getInstance()->get($id);
    }
    
    /**
     * generate directory listing
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param Tinebase_Model_Tree_Node       $node
     * @param array                          $path
     */
    protected function _listDirectory(Filemanager_Model_DownloadLink $download, Tinebase_Model_Tree_Node $node, $path)
    {
        $files = Filemanager_Controller_DownloadLink::getInstance()->getFileList($download, $path, $node);
        return $this->_getHTML('folder', [
            'files' => $files,
            'path' => $node->path
        ]);
    }

    public static function urlEncodeArray(array $array): array
    {
        array_walk($array, fn(&$val) => $val = urlencode($val));
        return $array;
    }

    /**
     * generate file overview
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param Tinebase_Model_Tree_Node       $node
     * @param array                          $path
     */
    protected function _displayFile(Filemanager_Model_DownloadLink $download, Tinebase_Model_Tree_Node $node, $path)
    {
        return $this->_getHTML('file', [
            'path' => $download->getDownloadUrl('get') . '/' . implode('/', static::urlEncodeArray($path)),
            'last_modified_time' => Tinebase_Translation::dateToStringInTzAndLocaleFormat($node->last_modified_time ?? $node->creation_time),
            'file' => $node,
            'timezone' => Tinebase_Core::getUserTimezone()
        ]);
    }
    
    /**
     * sets download link owner (creator) as current user to ensure ACL handling
     * 
     * @param Filemanager_Model_DownloadLink $download
     */
    protected function _setDownloadLinkOwnerAsUser(Filemanager_Model_DownloadLink $download)
    {
        $user = Tinebase_User::getInstance()->getFullUserById($download->created_by);
        Tinebase_Core::set(Tinebase_Core::USER, $user);
    }
}
