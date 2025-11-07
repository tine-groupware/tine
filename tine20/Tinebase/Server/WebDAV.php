<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * webdav Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_WebDAV extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    public const REQUEST_TYPE = 'WebDAV';
    
   /**
    * @var \Sabre\DAV\Server
    */
    protected static $_server;

    /**
     * @var Tinebase_Auth_NtlmV2
     */
    protected $_ntlmV2 = null;

    public function __construct()
    {
        $this->_supportsSessions = true;
        parent::__construct();
    }

    /**
     * @return Tinebase_Auth_NtlmV2
     */
    public function getNtlmV2()
    {
        if (null === $this->_ntlmV2) {
            $this->_ntlmV2 = new Tinebase_Auth_NtlmV2();
        }
        return $this->_ntlmV2;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     * @param \Laminas\Http\Request|null $request
     * @param string|null $body
     * @return void
     */
    public function handle(?\Laminas\Http\Request $request = null, $body = null)
    {
        try {
            $this->_disallowAppPwdSessions();

            $this->_request = $request instanceof \Laminas\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);

            if ($body !== null) {
                $this->_body = $body;
            } else {
                if ($this->_request instanceof Tinebase_Http_Request) {
                    $this->_body = $this->_request->getContentStream();
                } else if ($this->_request instanceof \Laminas\Http\Request) {
                    $this->_body = fopen('php://temp', 'r+');
                    fwrite($this->_body, (string) $request->getContent());
                    rewind($this->_body);
                }
            }

            /*
             * JN: dirty hack for native Windows 7 & 10 webdav client (after early 2017):
             * client sends empty request instead empty xml-skeleton -> inject it here
             *(improvement: do not rely on user agent because other clients use windows stuff, too)
             */
            if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'PROPFIND') {
                $content = stream_get_contents($this->_body, 1);
                rewind($this->_body);


                if ($content === '') {
                    $broken_user_agent_body = '<?xml version="1.0" encoding="utf-8" ?><D:propfind xmlns:D="DAV:"><D:prop>';
                    $broken_user_agent_body .= '<D:creationdate/><D:displayname/><D:getcontentlength/>';
                    $broken_user_agent_body .= '<D:getcontenttype/><D:getetag/><D:getlastmodified/><D:resourcetype/>';
                    $broken_user_agent_body .= '</D:prop></D:propfind>';
                    fwrite($this->_body, $broken_user_agent_body);
                    rewind($this->_body);
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " broken userAgent detected: " .
                            ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown user agent') . " --> inserted xml body");
                    }
                }
            }

            $hasIdentity = false;

            if (isset($_SERVER['HTTP_USER_AGENT']) &&
                (str_starts_with((string) $_SERVER['HTTP_USER_AGENT'], 'Microsoft-WebDAV-MiniRedir') ||
                    str_starts_with((string) $_SERVER['HTTP_USER_AGENT'], 'Microsoft Office'))) {
                try {
                    Tinebase_Core::startCoreSession();
                    Tinebase_Core::initFramework();

                    if (Tinebase_Session::isStarted() && Zend_Auth::getInstance()->hasIdentity()) {
                        $hasIdentity = true;
                    }
                } catch (Zend_Session_Exception) {
                    // expire session cookie for client
                    Tinebase_Session::expireSessionCookie();

                    // session error, we just need to start over
                    // but we don't know where we failed, so better initFramework
                    Tinebase_Core::initFramework();
                    if (Tinebase_Auth_NtlmV2::isEnabled()) {
                        $this->getNtlmV2()->sendHeaderForAuthPase();
                        return;
                    }
                }

                if (!$hasIdentity && Tinebase_Auth_NtlmV2::isEnabled()) {
                    $ntlmAuthStatus = $this->getNtlmV2()->authorize($this->_request);

                    if (Tinebase_Auth_NtlmV2::AUTH_SUCCESS === $ntlmAuthStatus) {
                        try {
                            $hasIdentity = Tinebase_Controller::getInstance()->processLoginAuthResult(
                                loginName: $this->_ntlmV2->getUser()->accountLoginName,
                                request: $this->_request,
                                clientIdString: self::REQUEST_TYPE
                            );
                        } catch (Tinebase_Exception_MaintenanceMode) {
                            header('HTTP/1.1 503 Service Unavailable');
                            return;
                        }
                    } else {
                        $this->_ntlmV2->sendHeaderForAuthPase($ntlmAuthStatus);
                        return;
                    }
                }
            }

            if (!$hasIdentity) {
                try {
                    $authData = $this->_getAuthData($this->_request);
                    if (count($authData) === 2) {
                        [$loginName, $password] = $authData;
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                                . ' Login name or password missing from auth data');
                        }
                        $this->_sendUnauthorizedHeader();
                        return;
                    }
                } catch (Tinebase_Exception_NotFound) {
                    $this->_sendUnauthorizedHeader();
                    return;
                }
            }

            Tinebase_Core::startCoreSession();
            Tinebase_Core::initFramework();
            if (($saveHandler = Zend_Session::getSaveHandler()) instanceof Tinebase_Session_SaveHandler_Redis) {
                $saveHandler->setLifeTimeSec(30*60);
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' is CalDav, CardDAV or WebDAV request.');
            }


            if (!$hasIdentity && $this->_request->getMethod() !== 'GET'
                && null !== ($denyList = Tinebase_Config::getInstance()->get(Tinebase_Config::DENY_WEBDAV_CLIENT_LIST))
                && is_array($denyList)
            ) {
                foreach ($denyList as $deny) {
                    if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match($deny, (string) $_SERVER['HTTP_USER_AGENT'])) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                                . ' Blocked access of user agent string ' . $_SERVER['HTTP_USER_AGENT']);
                        }
                        header('HTTP/1.1 420 Policy Not Fulfilled User Agent Not Accepted');
                        return;
                    }
                }
            }

            try {
                if (!$hasIdentity && Tinebase_Controller::getInstance()->login(
                        $loginName,
                        $password,
                        $this->_request,
                        self::REQUEST_TYPE
                    ) !== true) {
                    $this->_sendUnauthorizedHeader();
                    return;
                }
            } catch (Tinebase_Exception_MaintenanceMode) {
                header('HTTP/1.1 503 Service Unavailable');
                return;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Starting to handle WebDAV request ( requestUri:' . $this->_request->getRequestUri()
                    . ' PID: ' . getmypid() . ')'
                );
            }
            self::getServer();
            \Sabre\DAV\Server::$exposeVersion = false;
            self::$_server->httpResponse = new Tinebase_WebDav_HTTP_LogResponse();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                self::$_server->debugExceptions = true;
                $hdrs = self::$_server->httpRequest->getHeaders();
                if (isset($hdrs['authorization'])) {
                    $hdrs['authorization'] = '*****';
                }
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " headers: " . print_r($hdrs, true));
                $contentType = self::$_server->httpRequest->getHeader('Content-Type');
                $method = strtoupper((string) $this->_request->getMethod());
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " requestContentType: " . $contentType . ' requestMethod: ' . $method);

                if ($contentType && (str_starts_with($contentType, 'text/') || str_ends_with($contentType, '/xml'))) {
                    $content = stream_get_contents($this->_body, 10 * 1024 * 1024);
                    rewind($this->_body);

                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " <<< *DAV request\n" . $content);
                } else {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " <<< *DAV request\n -- BINARY DATA --");
                }
            }

            // setup Resource Controller
            $calResourceCtrlRaii = new Tinebase_RAII(fn() => Calendar_Controller_Resource::destroyInstance());
            Calendar_Controller_Resource::getInstance()->setGetGrant(Calendar_Model_ResourceGrants::GRANT_SYNC);

            self::$_server->httpRequest->setBody($this->_body);

            // compute base uri
            self::$_server->setBaseUri($this->_request->getBaseUrl() . '/');

            if (Tinebase_Core::isFilesystemAvailable()) {
                self::$_server->addPlugin(
                    new \Sabre\DAV\Locks\Plugin(new Tinebase_WebDav_Plugin_LockBackend())
                );
            }

            self::$_server->addPlugin(
                new \Sabre\DAV\Auth\Plugin(new Tinebase_WebDav_Auth())
            );

            $aclPlugin = new Tinebase_WebDav_Plugin_ACL();
            $aclPlugin->principalCollectionSet = [
                Tinebase_WebDav_PrincipalBackend::PREFIX_USERS,
                Tinebase_WebDav_PrincipalBackend::PREFIX_GROUPS,
                Tinebase_WebDav_PrincipalBackend::PREFIX_INTELLIGROUPS
            ];
            $aclPlugin->principalSearchPropertySet = array(
                '{DAV:}displayname' => 'Display name',
                '{' . \Sabre\DAV\Server::NS_SABREDAV . '}email-address' => 'Email address',
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}email-address-set' => 'Email addresses',
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name' => 'First name',
                '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name' => 'Last name',
                '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set' => 'Calendar user address set',
                '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type' => 'Calendar user type'
            );

            self::$_server->addPlugin($aclPlugin);

            self::$_server->addPlugin(new \Sabre\CardDAV\Plugin());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_ScheduleOutbox());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPlugin); // this plugin must be loaded before CalDAV plugin
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_FixMultiGet404Plugin()); // extends \Sabre\CalDAV\Plugin;
            self::$_server->addPlugin(new \Sabre\DAV\Sharing\Plugin());
            self::$_server->addPlugin(new \Sabre\CalDAV\SharingPlugin());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginAutoSchedule());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginDefaultAlarms());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginManagedAttachments());
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_PluginPrivateEvents());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_OwnCloud());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_PrincipalSearch());
            self::$_server->addPlugin(new Tinebase_WebDav_Plugin_ExpandedPropertiesReport());
            self::$_server->addPlugin(new \Sabre\DAV\Browser\Plugin());
            self::$_server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
            if (Tinebase_Config::getInstance()->get(Tinebase_Config::WEBDAV_SYNCTOKEN_ENABLED)) {
                $userA = null;
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    [$userA, $tbVersion] = Calendar_Convert_Event_VCalendar_Factory::parseUserAgent($_SERVER['HTTP_USER_AGENT']);
                }
                if (Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD !== $userA || version_compare($tbVersion, '78.0') >= 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                        __LINE__ . ' SyncTokenSupport enabled');
                    self::$_server->addPlugin(new Tinebase_WebDav_Plugin_SyncToken());
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                    __LINE__ . ' SyncTokenSupport disabled');
            }
            self::$_server->addPlugin(new Calendar_Frontend_CalDAV_SpeedUpPropfindPlugin());
            self::$_server->httpResponse->startBodyLog(Tinebase_Core::isLogLevel(Zend_Log::DEBUG) &&
                $this->_request->getMethod() !== 'GET');

            // drop frontend query from rewrite rule
            $_SERVER['QUERY_STRING'] = str_replace('frontend=webdav&', '',
                $_SERVER['QUERY_STRING'] ?? '');

            self::_checkRateLimit(Tinebase_Server_WebDAV::class, $this->_request->getMethod() . '.' . $_SERVER['REQUEST_URI']);

            self::$_server->on('exception', fn(Throwable $t) => $this->_reportWebDavIssue($t));
            self::$_server->exec();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " >>> *DAV response: " .
                    http_response_code() . "\n" .
                    implode("\n", headers_list()) . "\n\n" .
                    self::$_server->httpResponse->stopBodyLog());
            }
        } catch (Tinebase_Exception_Unauthorized) {
            @header('HTTP/1.1 401 Not authorized');
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            }
            @header('HTTP/1.1 403 Forbidden');
        } catch (Zend_Session_Exception $zse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Maintenance mode / session problem: ' . $zse->getMessage());
            }
            @header('HTTP/1.1 503 Service Unavailable');
            $this->_reportWebDavIssue($zse);
        } catch (Tinebase_Exception_RateLimit $ter) {
            @header('HTTP/1.1 429 Too Many Requests');
        } catch (Throwable $e) {
            Tinebase_Exception::log($e, false);
            @header('HTTP/1.1 500 Internal Server Error');
            $this->_reportWebDavIssue($e);
        }
        unset($calResourceCtrlRaii);
    }

    protected function _reportWebDavIssue(Throwable $t): void
    {
        $cfg = Tinebase_Config::getInstance()->{Tinebase_Config::REPORT_WEBDAV};
        if (!$cfg->{Tinebase_Config::REPORT_WEBDAV_AFFECTED_USER} &&
                !$cfg->{Tinebase_Config::REPORT_WEBDAV_USER_LIST}) {
            return;
        }

        if (null !== $this->_body) {
            rewind($this->_body);
        }
        $hdrs = $this->_request->getHeaders()->toArray();
        if (isset($hdrs['authorization'])) {
            $hdrs['authorization'] = '*****';
        }

        Tinebase_Controller_WebDavIssue::getInstance()->create(new Tinebase_Model_WebDavIssue([
            Tinebase_Model_WebDavIssue::FLD_CREATION_TIME => Tinebase_DateTime::now(),
            Tinebase_Model_WebDavIssue::FLD_EXCEPTION => serialize($t),
            Tinebase_Model_WebDavIssue::FLD_URI => $this->_request->getUri()->toString(),
            Tinebase_Model_WebDavIssue::FLD_REQUEST_METHOD => strtoupper($this->_request->getMethod()),
            Tinebase_Model_WebDavIssue::FLD_REQUEST_HEADERS => print_r($hdrs, true),
            Tinebase_Model_WebDavIssue::FLD_REQUEST_BODY => null !== $this->_body ? stream_get_contents($this->_body, 3 * 1024) : null,
            Tinebase_Model_WebDavIssue::FLD_ACCOUNT_ID => Tinebase_Core::getUser()?->getId(),
        ]));
    }

    protected function _sendUnauthorizedHeader()
    {
        @header('WWW-Authenticate: Basic realm="' . $this->_getRealm() .  '"');
        @header('HTTP/1.1 401 Unauthorized');
    }

    protected function _getRealm(): string
    {
        return Tinebase_Core::getTineUserAgent('WebDAV Service');
    }

    /**
     * Set an odd parity bit for a given byte, in least-significant position.
     *
     * @link https://github.com/jclulow/node-smbhash/blob/edc48e2b/lib/common.js
     *   Implementation basis.
     * @param int $byte An 8-bit byte value.
     * @return int An 8-bit byte value.
     */
    private static function setParityBit($byte)
    {
        $parity = 1;
        for ($i = 1; $i < 8; $i++) {
            $parity = ($parity + (($byte >> $i) & 1)) %2;
        }
        $byte = $byte | ($parity & 1);
        return $byte;
    }

   /**
    * helper to return request
    *
    * @return ?Sabre\HTTP\Request
    */
    public static function getRequest()
    {
        return (self::$_server ?: self::getServer())->httpRequest;
    }

    /**
     * helper to return response
     *
     * @return ?Sabre\HTTP\Response
     */
    public static function getResponse()
    {
        return self::$_server?->httpResponse;
    }

    public static $_recreateServer = true;
    public static function getServer()
    {
        if (!self::$_server || self::$_recreateServer) {
            self::$_server = new \Sabre\DAV\Server(new Tinebase_WebDav_ObjectTree(new Filemanager_Frontend_WebDAV('',
                [Filemanager_Frontend_WebDAV::FM_REAL_WEBDAV_ROOT => new Tinebase_WebDav_Root()])));
        }
        return self::$_server;
    }


    /**
    * returns request method
    *
    * @return string
    */
    public function getRequestMethod()
    {
        return self::getRequest()->getMethod();
    }
}
