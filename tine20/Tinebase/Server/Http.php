<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * HTTP Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Http extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    /**
     * the request method
     * 
     * @var string
     */
    protected $_method = NULL;
    
    /**
     * 
     * @var boolean
     */
    protected $_supportsSessions = true;

    protected ?Laminas\HttpHandlerRunner\Emitter\EmitterInterface $_emitter = null;

    public function __construct()
    {
        parent::__construct();
        $this->_emitter = new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter();
    }

    public function setEmitter(Laminas\HttpHandlerRunner\Emitter\EmitterInterface $emitter): self
    {
        $this->_emitter = $emitter;
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(?\Laminas\Http\Request $request = null, $body = null)
    {
        Tinebase_AreaLock::getInstance()->activatedByFE();

        $this->_request = $request instanceof \Laminas\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
        $this->_body    = $body ?? fopen('php://input', 'r');

        try {
            try {
                Tinebase_Core::startCoreSession();
            } catch (Zend_Session_Exception $zse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' ' . $zse->getMessage() . ' - expire session cookie for client');
                Tinebase_Session::expireSessionCookie();
            }

            Tinebase_Core::initFramework();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    .' Is HTTP request. method: ' . $this->getRequestMethod());
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                    .' REQUEST: ' . print_r($_REQUEST, TRUE));
            }

            if (null === Tinebase_Core::getUser() && ($this->_request->getHeader('Authorization') || $this->_request->getQuery('Authorization'))) {
                $this->_handleAppPwdAuth();
            }

            $server = new Tinebase_Http_Server();
            if ($appPwd = Tinebase_Session::getSessionNamespace()->{Tinebase_Model_AppPassword::class}) {
                $server->setAllowList($appPwd->{Tinebase_Model_AppPassword::FLD_CHANNELS});
            }
            $server->setClass('Tinebase_Frontend_Http', 'Tinebase');
            $server->setClass('Filemanager_Frontend_Download', 'Download');

            // register additional HTTP apis only available for authorised users
            if (Tinebase_Session::isStarted() && ($appPwd || Zend_Auth::getInstance()->hasIdentity())) {

                if (empty($_REQUEST['method'])) {
                    $_REQUEST['method'] = 'Tinebase.mainScreen';

                    // only load restricted apis if no area_login lock is set or if it is unlocked already
                } elseif ($appPwd || self::checkLoginAreaLock()) {

                    $definitions = self::_getModelConfigMethods('Tinebase_Server_Http');
                    if ($appPwd) {
                        $definitions = array_intersect_key($definitions, $appPwd->{Tinebase_Model_AppPassword::FLD_CHANNELS});
                    }
                    $server->loadFunctions($definitions);

                    $applicationParts = explode('.', (string) $this->getRequestMethod());
                    $applicationName = ucfirst($applicationParts[0]);

                    if (Tinebase_Core::getUser() && Tinebase_Core::getUser()->hasRight($applicationName, Tinebase_Acl_Rights_Abstract::RUN)) {
                        try {
                            if (class_exists($applicationName . '_Frontend_Http')) {
                                $server->setClass($applicationName . '_Frontend_Http', $applicationName);
                            } else {
                                $server->setClass('Tinebase_Frontend_Http_Generic', $applicationName);
                            }
                        } catch (Exception $e) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " Failed to add HTTP API for application '$applicationName' Exception: \n" . $e);
                            Tinebase_Exception::log($e, false);
                        }
                    }
                }
            } else {
                if (empty($_REQUEST['method'])) {
                    $_REQUEST['method'] = 'Tinebase.login';
                }

                // sessionId got send by client, but we don't use sessions for non authenticated users
                if (Tinebase_Session::sessionExists()) {
                    // expire session cookie on client
                    //Tinebase_Session::expireSessionCookie();
                }
            }

            $this->_method = $this->getRequestMethod();

            if (!$appPwd) {
                self::_checkAreaLock($this->_method);
            }
            self::_checkRateLimit(Tinebase_Server_Http::class, $this->_method);

            $response = $server->handle($_REQUEST);
            if ($response instanceof \Laminas\Diactoros\Response) {
                $this->_emitter->emit($response);
            }
            
        } catch (Zend_Json_Server_Exception $zjse) {
            // invalid method requested or not authenticated, etc.
            Tinebase_Exception::log($zjse);
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . ' Attempt to request a privileged Http-API method without valid session from "'
                . $_SERVER['REMOTE_ADDR']);

            if (!headers_sent()) {
                header('HTTP/1.0 403 Forbidden');
            }

        } catch (Tinebase_Exception_RateLimit $ter) {
            Tinebase_Exception::log($ter, false);

            if (!headers_sent()) {
                header('HTTP/1.0 429 Too Many Requests');
            }
        }
        catch (Tinebase_Exception_AreaLocked) {
            if (!headers_sent()) {
                header('HTTP/1.0 403 Forbidden');
            }
        } catch (Throwable $exception) {
            Tinebase_Exception::log($exception, false);
            
            try {
                $setupController = Setup_Controller::getInstance();
                if ($setupController->setupRequired()) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Setup required');
                    $this->_method = 'Tinebase.setupRequired';
                    $server = new Tinebase_Http_Server();
                    $server->setClass('Tinebase_Frontend_Http', 'Tinebase');
                    $server->handle(array('method' => $this->_method));

                } else if (preg_match('/download|export/', $this->_method)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Server error during download/export - exit with 500');
                    header('HTTP/1.0 500 Internal Server Error');
                    exit;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Show mainscreen with setup exception');
                    header('HTTP/1.0 500 Internal Server Error');
                    exit;
                }
            } catch (Throwable $e) {
                header('HTTP/1.0 503 Service Unavailable');
                Tinebase_Exception::log($e, false);
            }
        }
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        if (isset($_REQUEST['method'])) {
            $this->_method = $_REQUEST['method'];
        }
        
        return $this->_method;
    }

    public static function exposeApi($config)
    {
        return $config && $config->exposeHttpApi;
    }

    public static function getCommonApiMethods($simpleModelName)
    {
        return array(
            'export' => array(
                'params' => array(
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'filter',
                    )),
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'options',
                    )),
                ),
                'help'   => 'export ' . $simpleModelName . ' records',
                'plural' => true,
            ),
        );
    }

    protected static function _getFrontend($application)
    {
        $appHttpFrontendClass = $application->name . '_Frontend_Http';
        if (class_exists($appHttpFrontendClass)) {
            $object = new $appHttpFrontendClass();
        } else {
            $object = new Tinebase_Frontend_Http_Generic($application->name);
        }

        return $object;
    }
}
