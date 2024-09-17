<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * HTTP interface to Tine
 *
 * ATTENTION all public methods in this class are reachable without tine authentification
 * use $this->checkAuth(); if method requires authentification
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    const REQUEST_TYPE = 'HttpPost';

    protected $_applicationName = Tinebase_Config::APP_NAME;
    
    /**
     * get json-api service map
     * 
     * @return string
     */
    public static function getServiceMap()
    {
        $smd = Tinebase_Server_Json::getServiceMap();
        
        $smdArray = $smd->toArray();
        unset($smdArray['methods']);
        
        if (! isset($_REQUEST['method']) || $_REQUEST['method'] != 'Tinebase.getServiceMap') {
            return $smdArray;
        }
        
        header('Content-type: application/json');
        echo '_smd = ' . json_encode($smdArray);
        die();
    }

    /**
     * checks if a user is logged in. If not we redirect to login
     */
    protected function checkAuth()
    {
        try {
            if (!Tinebase_Core::getUser() instanceof Tinebase_Model_User) {
                header('HTTP/1.0 403 Forbidden');
                exit;
            }
        } catch (Exception $e) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }
    }
    
    /**
     * renders the login dialog
     *
     * @todo perhaps we could add a config option to display the update dialog if it is set
     */
    public function login()
    {
        if ($this->_redirect()) {
            return;
        }
        
        return $this->mainScreen();
    }

    protected function _redirect()
    {
        // redirect to REDIRECTURL if set
        $redirectUrl = Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL, '');

        if ($redirectUrl !== '' && Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTALWAYS, FALSE)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' redirecting to ' . $redirectUrl);
            header('Location: ' . $redirectUrl);
            return true;
        }

        return false;
    }

    public function setupRequired()
    {
        return $this->mainScreen();
    }

    /**
     * login from HTTP post 
     * 
     * redirects the tine main screen if authentication is successful
     * otherwise redirects back to login url 
     */
    public function loginFromPost($username, $password)
    {
        Tinebase_Core::startCoreSession(true);

        if (!empty($username)) {
            // try to login user
            Tinebase_Controller::getInstance()->forceUnlockLoginArea();
            $success = (Tinebase_Controller::getInstance()->login(
                $username,
                $password,
                Tinebase_Core::get(Tinebase_Core::REQUEST),
                self::REQUEST_TYPE // TODO FIXME that needs to go into tine20/Tinebase/Controller.php:916 too $accessLog->clienttype !== Tinebase_Frontend_Json::REQUEST_TYPE etc.
            ) === TRUE);
        } else {
            $success = FALSE;
        }
        
        if ($success === TRUE) {
            $ccAdapter = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter();
            if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
                $ccAdapter->setCache(Tinebase_Core::getUserCredentialCache());
            } else {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Something went wrong with the CredentialCache / no CC registered.');
                $success = FALSE;
                $ccAdapter->resetCache();
            }
        
        }

        $redirectUrl = str_replace('index.php', '', Tinebase_Core::getUrl());

        // authentication failed
        if ($success !== TRUE) {
            $_SESSION = array();
            Tinebase_Session::destroyAndRemoveCookie();
            
            // redirect back to loginurl if needed
            $redirectUrl = Tinebase_Config::getInstance()->get(Tinebase_Config::REDIRECTURL, $redirectUrl);
        }

        // load the client with GET
        header('Location: ' . $redirectUrl);
    }

    /**
     * display Tine 2.0 main screen
     */
    public function mainScreen(array $additionalData = [])
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles = ['Tinebase/js/fatClient.js'];
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

        $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
        if (! empty($customJSFiles)) {
            $jsFiles[] = "index.php?method=Tinebase.getCustomJsFiles";
        }

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, 'Tinebase/views/FATClient.html.twig', array_merge([
            'lang' => $locale
        ], $additionalData));
    }

    /**
     * returns javascript of translations for the currently configured locale
     *
     * @param  string $locale
     * @param  string $app
     * @return string (javascript)
     */
    public function getJsTranslations($locale = null, $app = 'all')
    {
        if (! in_array(TINE20_BUILDTYPE, array('DEBUG', 'RELEASE'))) {
            $translations = Tinebase_Translation::getJsTranslations($locale, $app);
            header('Content-Type: application/javascript');
            die($translations);
        }

        // production
        $filesToWatch = $this->_getFilesToWatch('lang', array($app));
        $this->_deliverChangedFiles('lang', $filesToWatch);
    }

    /**
     * check if js files have changed and return all js as one big file or return "HTTP/1.0 304 Not Modified" if files don't have changed
     * 
     * @param string $_fileType
     * @param array $filesToWatch
     * @throws Tinebase_Exception
     */
    protected function _deliverChangedFiles($_fileType, $filesToWatch=null)
    {
        // close session to allow other requests
        Tinebase_Session::writeClose(true);

        $filesToWatch = $filesToWatch ? $filesToWatch : $this->_getFilesToWatch($_fileType);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__
            . ' (' . __LINE__ .') Got files to watch: ' . print_r($filesToWatch, true));

        // cache for one day
        $maxAge = 86400;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // remove Pragma header from session
        header_remove('Pragma');

        $clientETag = isset($_SERVER['If_None_Match'])
            ? $_SERVER['If_None_Match']
            : (isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '');

        if (preg_match('/[a-f0-9]{40}/', $clientETag, $matches)) {
            $clientETag = $matches[0];
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__CLASS__ . '::' . __METHOD__
            . ' (' . __LINE__ .') $clientETag: ' . $clientETag);

        $serverETag = md5(implode('', array_map(function($fileName) {
            return file_exists($fileName) ? md5_file($fileName) : '';
        }, $filesToWatch)));

        if ($clientETag == $serverETag) {
            header("HTTP/1.0 304 Not Modified");
        } else {
            header('Content-Type: application/javascript');
            header('Etag: "' . $serverETag . '"');

            // send files to client
            foreach ($filesToWatch as $file) {
                if (file_exists($file)) {
                    readfile($file);
                } else {
                    if (preg_match('/^Tinebase/', $file)) {
                        // this is critical!
                        throw new Tinebase_Exception('client file does not exist: ' . $file);
                    } else if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(
                            __CLASS__ . '::' . __METHOD__
                            . ' (' . __LINE__ .') File ' . $file . ' does not exist');
                    }
                }
            }
            if ($_fileType != 'lang') {
                // adds assetHash for client version check
                $assetHash = Tinebase_Frontend_Http_SinglePageApplication::getAssetHash();
                echo "Tine = Tine || {}; Tine.clientVersion = Tine.clientVersion || {};";
                echo "Tine.clientVersion.assetHash = '$assetHash';";
            }
        }
    }

    /**
     * @param string $_fileType
     * @param array  $apps
     * @return array
     * @throws Exception
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _getFilesToWatch($_fileType, $apps = array())
    {
        $requiredApplications = array('Setup', 'Tinebase', 'Admin', 'Addressbook');
        if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            $orderedApplications = $requiredApplications;
        } else {
            $installedApplications = Tinebase_Application::getInstance()->getApplications(null, /* sort = */ 'order')->name;
            $orderedApplications = array_merge($requiredApplications, array_diff($installedApplications, $requiredApplications));
        }

        $filesToWatch = array();

        foreach ($orderedApplications as $application) {
            if (! empty($apps) && $apps[0] != 'all' && ! in_array($application, $apps)) {
                continue;
            }
            switch ($_fileType) {
                case 'js':
                    $filesToWatch[] = "{$application}/js/{$application}";
                    break;
                case 'lang':
                    $fileName = "{$application}/js/{$application}-lang-" . Tinebase_Core::getLocale()
                        . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js';
                    $lang = Tinebase_Core::getLocale();
                    $customPath = Tinebase_Config::getInstance()->translations;
                    $basePath = ! empty($customPath) && is_readable("$customPath/$lang/$fileName")
                        ? "$customPath/$lang"
                        : '.';

                    $langFile = "{$basePath}/{$application}/js/{$application}-lang-" . Tinebase_Core::getLocale()
                        . (TINE20_BUILDTYPE == 'DEBUG' ? '-debug' : null) . '.js';
                    $filesToWatch[] = $langFile;
                    break;
                default:
                    throw new Exception('no such fileType');
            }
        }

        return $filesToWatch;
    }

    /**
     * dev mode custom js delivery
     */
    public function getCustomJsFiles()
    {
        try {
            $customJSFiles = Tinebase_Config::getInstance()->get(Tinebase_Config::FAT_CLIENT_CUSTOM_JS);
            if (! empty($customJSFiles)) {
                $this->_deliverChangedFiles('js', $customJSFiles);
            }
        } catch (Exception $exception) {
            Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . " can't deliver custom js: \n" . $exception);

        }
    }

    /**
     * return last modified timestamp formated in gmt
     * 
     * @param  array  $_files
     * @return array
     */
    protected function _getLastModified(array $_files)
    {
        $timeStamp = null;
        
        foreach ($_files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $timeStamp) {
                $timeStamp = $mtime;
            }
        }

        return gmdate("D, d M Y H:i:s", $timeStamp) . " GMT";
    }

    /**
     * receives file uploads and stores it in the file_uploads db
     * 
     * @throws Tinebase_Exception_UnexpectedValue
     * @throws Tinebase_Exception_NotFound
     */
    public function uploadTempFile()
    {
        $this->checkAuth();
        $this->_uploadTempFile();
    }
    
    /**
     * downloads an image/thumbnail at a given size
     *
     * @param unknown_type $application
     * @param string $id
     * @param string $location
     * @param int $width
     * @param int $height
     * @param int $ratiomode
     */
    public function getImage($application, $id, $location, $width, $height, $ratiomode)
    {
        $this->checkAuth();

        // close session to allow other requests
        Tinebase_Session::writeClose(true);
        
        $clientETag      = null;
        $ifModifiedSince = null;
        
        if (isset($_SERVER['If_None_Match'])) {
            $clientETag     = trim($_SERVER['If_None_Match'], '"');
            $ifModifiedSince = trim($_SERVER['If_Modified_Since'], '"');
        } elseif (isset($_SERVER['HTTP_IF_NONE_MATCH']) && isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $clientETag     = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            $ifModifiedSince = trim($_SERVER['HTTP_IF_MODIFIED_SINCE'], '"');
        }

        try {
            $image = Tinebase_Controller::getInstance()->getImage($application, $id, $location);
        } catch (Tinebase_Exception_UnexpectedValue $teuv) {
            $this->_handleFailure(404);
        }

        $serverETag = sha1($image->blob . $width . $height . $ratiomode);
        
        // cache for 3600 seconds
        $maxAge = 3600;
        header('Cache-Control: private, max-age=' . $maxAge);
        header("Expires: " . gmdate('D, d M Y H:i:s', Tinebase_DateTime::now()->addSecond($maxAge)->getTimestamp()) . " GMT");
        
        // overwrite Pragma header from session
        header("Pragma: cache");
        
        // if the cache id is still valid
        if ($clientETag == $serverETag) {
            header("Last-Modified: " . $ifModifiedSince);
            header("HTTP/1.0 304 Not Modified");
            header('Content-Length: 0');
        } else {
            if ($width != -1 && $height != -1) {
                Tinebase_ImageHelper::resize($image, $width, $height, $ratiomode);
            }

            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header('Content-Type: '. $image->mime);
            header('Etag: "' . $serverETag . '"');
            
            flush();
            
            die($image->blob);
        }
    }
    
    /**
     * crops a image identified by an imgageURL and returns a new tempFileImage
     * 
     * @param  string $imageurl imageURL of the image to be croped
     * @param  int    $left     left position of crop window
     * @param  int    $top      top  position of crop window
     * @param  int    $widht    widht  of crop window
     * @param  int    $height   heidht of crop window
     * @return string imageURL of new temp image
     * 
     */
    public function cropImage($imageurl, $left, $top, $widht, $height)
    {
        $this->checkAuth();
        
        $image = Tinebase_Model_Image::getImageFromImageURL($imageurl);
        Tinebase_ImageHelper::crop($image, $left, $top, $widht, $height);
        
    }

    public function getBlob($hash)
    {
        $this->checkAuth();

        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::REPLICATION)) {
            header('HTTP/1.0 403 Forbidden');
            exit;
        }

        $fileObject = new Tinebase_Model_Tree_FileObject(array('hash' => $hash), true);
        $path = $fileObject->getFilesystemPath();

        if (is_file($path)) {
            if (!($fh = fopen($path, 'rb'))) {
                throw new Tinebase_Exception_Backend('could not open blob file: ' . $hash);
            }
            //header('Content-Length: ' . filesize($path));
            ob_end_flush();
            ob_implicit_flush(true);
            fpassthru($fh);
            fclose($fh);
            flush();
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }

    /**
     * download file attachment
     * 
     * @param string $nodeId
     * @param string $recordId
     * @param string $modelName
     */
    public function downloadRecordAttachment($nodeId, $recordId, $modelName)
    {
        $this->checkAuth();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Downloading attachment of ' . $modelName . ' record with id ' . $recordId);
        
        $recordController = Tinebase_Core::getApplicationInstance($modelName);
        try {
            $record = $recordController->get($recordId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
        }
        
        $node = Tinebase_FileSystem::getInstance()->get($nodeId);
        $node->grants = null;
        $path = Tinebase_Model_Tree_Node_Path::STREAMWRAPPERPREFIX
            . Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($record)
            . '/' . $node->name;
        
        $this->_downloadFileNode($node, $path, /* revision */ null, /* $ignoreAcl */ true);
        exit;
    }

    /**
     * Download temp file to review
     *
     * @param $tmpfileId
     */
    public function downloadTempfile($tmpfileId)
    {
        $this->checkAuth();

        $tmpFile = Tinebase_TempFile::getInstance()->getTempFile($tmpfileId);

        // some grids can house tempfiles and filemanager nodes, therefor first try tmpfile and if no tmpfile try filemanager
        if (!$tmpFile && Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $filemanagerNodeController = Filemanager_Controller_Node::getInstance();
            try {
                $file = $filemanagerNodeController->get($tmpfileId);
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_handleFailure(404);
            }

            $filemanagerHttpFrontend = new Filemanager_Frontend_Http();
            $filemanagerHttpFrontend->downloadFile($file->path, null);
        }

        $this->_downloadTempFile($tmpFile, $tmpFile->path);
        exit;
    }

    public function getPostalXWindow()
    {
        $context = [
            'path' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
        ];
        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML(
           'Tinebase/js/postal-xwindow-client.js',
            'Tinebase/views/XWindowClient.html.twig',
            $context
        );
    }

    /**
     * download file
     *
     * @param string $_path
     * @param string $_appId
     * @param string $_type
     * @param int $_num
     * @param string $_revision
     */
    public function downloadPreview($_path, $_appId, $_type, $_num = 0, $_revision = null)
    {
        $this->checkAuth();
        
        $_revision = $_revision ?: null;
        $node = null;

        if ($_path) {
            $path = ltrim($_path, '/');

            try {
                if (strpos($path, 'records/') === 0) {
                    $pathParts = explode('/', $path);
                    /** @var Tinebase_Controller_Record_Abstract $controller */
                    $controller = Tinebase_Core::getApplicationInstance($pathParts[1]);
                    // ACL Check
                    $controller->get($pathParts[2]);
                    $node = Tinebase_FileSystem::getInstance()->stat('/' . $_appId . '/folders/' . $path, $_revision);
                } else {
                    $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath('/' . $_appId . '/folders/' . $path);
                    $node = Filemanager_Controller_Node::getInstance()->getFileNode($pathRecord, $_revision);
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' A path is needed to download a preview file.');
            $this->_handleFailure(Tinebase_Server_Abstract::HTTP_ERROR_CODE_NOT_FOUND);
        }

        if ($node) {
            $this->_downloadPreview($node, $_type, $_num);
        }

        exit;
    }

    /**
     * openIDCLogin
     *
     * @return boolean
     */
    public function openIDCLogin()
    {
        /** @var Tinebase_Auth_OpenIdConnect $oidc */
        $oidc = Tinebase_Auth_Factory::factory('OpenIdConnect');
        // request gets redirected to login page on success
        try {
            $oidc->providerAuthRequest();
        } catch (Tinebase_Exception_Auth_Redirect $tear) {
            header('Location: ' . $tear->_url);
            exit();
        }
        return false;
    }
}
