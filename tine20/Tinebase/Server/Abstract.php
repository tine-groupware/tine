<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Server Abstract with handle function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
abstract class Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    public const HTTP_ERROR_CODE_FORBIDDEN = 403;
    public const HTTP_ERROR_CODE_NOT_FOUND = 404;
    public const HTTP_ERROR_CODE_SERVICE_UNAVAILABLE = 503;
    public const HTTP_ERROR_CODE_INTERNAL_SERVER_ERROR = 500;

    /**
     * the request
     *
     * @var \Zend\Http\PhpEnvironment\Request
     */
    protected $_request = NULL;
    
    /**
     * the request body
     * 
     * @var resource|string
     */
    protected $_body;
    
    /**
     * set to true if server supports sessions
     * 
     * @var boolean
     */
    protected $_supportsSessions = false;

    /**
     * cache for modelconfig methods by frontend
     *
     * @var array
     */
    protected static $_modelConfigMethods = array();

    public function __construct()
    {
        if ($this->_supportsSessions) {
            Tinebase_Session_Abstract::setSessionEnabled('TINE20SESSID');
        }
    }
    
    /**
     * read auth data from all available sources
     * 
     * @param \Zend\Http\PhpEnvironment\Request $request
     * @throws Tinebase_Exception_NotFound
     * @return array
     */
    protected function _getAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($authData = $this->_getPHPAuthData($request)) {
            return $authData;
        }
        
        if ($authData = $this->_getBasicAuthData($request)) {
            return $authData;
        }
        
        throw new Tinebase_Exception_NotFound('No auth data found');
    }
    
    /**
     * fetch auch from PHP_AUTH*
     * 
     * @param  \Zend\Http\PhpEnvironment\Request  $request
     * @return array
     */
    protected function _getPHPAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($request->getServer('PHP_AUTH_USER')) {
            return array(
                $request->getServer('PHP_AUTH_USER'),
                $request->getServer('PHP_AUTH_PW')
            );
        }
    }
    
    /**
     * fetch basic auth credentials
     * 
     * @param  \Zend\Http\PhpEnvironment\Request  $request
     * @return array
     */
    protected function _getBasicAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($header = $request->getHeaders('Authorization')) {
            return explode(
                ":",
                base64_decode(substr((string) $header->getFieldValue(), 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                2
            );
            
        } elseif ($header = $request->getServer('HTTP_AUTHORIZATION')) {
            return explode(
                ":",
                base64_decode(substr($header, 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                2
            );
            
        } else {
            // check if (REDIRECT_)*REMOTE_USER is found in SERVER vars
            $name = 'REMOTE_USER';
            
            for ($i=0; $i<5; $i++) {
                if ($header = $request->getServer($name)) {
                    return explode(
                        ":",
                        base64_decode(substr($header, 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                        2
                    );
                }
                
                $name = 'REDIRECT_' . $name;
            }
        }
    }

    /**
     * get default modelconfig methods
     *
     * @param string $frontend
     * @return array of Zend_Server_Method_Definition
     */
    protected static function _getModelConfigMethods($frontend)
    {
        if (array_key_exists($frontend, Tinebase_Server_Abstract::$_modelConfigMethods)) {
            return Tinebase_Server_Abstract::$_modelConfigMethods[$frontend];
        }

        // get all apps user has RUN right for
        try {
            $userApplications = Tinebase_Core::getUser() ? Tinebase_Core::getUser()->getApplications() : array();
        } catch (Tinebase_Exception_NotFound) {
            // session might be invalid, destroy it
            Tinebase_Session::destroyAndRemoveCookie();
            $userApplications = array();
        }

        $definitions = array();
        foreach ($userApplications as $application) {
            if (! Tinebase_License::getInstance()->isPermitted($application->name)) {
                continue;
            }
            try {
                $controller = Tinebase_Core::getApplicationInstance($application->name);
                $models = $controller->getModels();
                if (!$models) {
                    continue;
                }
            } catch (Exception $e) {
                if (! $e instanceof Tinebase_Exception_AccessDenied) {
                    Tinebase_Exception::log($e);
                }
                continue;
            }

            foreach ($models as $model) {
                if (! class_exists($model)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' Model class not found: ' . $model);
                    continue;
                }

                $config = $model::getConfiguration();
                if ($frontend::exposeApi($config)) {
                    $simpleModelName = Tinebase_Record_Abstract::getSimpleModelName($application, $model);
                    $commonApiMethods = $frontend::getCommonApiMethods($simpleModelName);

                    foreach ($commonApiMethods as $name => $method) {
                        $key = $application->name . '.' . $name . $simpleModelName . ($method['plural'] ? 's' : '');
                        $object = $frontend::_getFrontend($application);

                        $definitions[$key] = new Zend_Server_Method_Definition(array(
                            'name'            => $key,
                            'prototypes'      => array(array(
                                'returnType' => 'array',
                                'parameters' => $method['params']
                            )),
                            'methodHelp'      => $method['help'],
                            'invokeArguments' => array(),
                            'object'          => $object,
                            'callback'        => array(
                                'type'   => 'instance',
                                'class'  => $object::class,
                                'method' => $name . $simpleModelName . ($method['plural'] ? 's' : '')
                            ),
                        ));
                    }
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Got MC definitions: ' . print_r(array_keys($definitions), true));

        Tinebase_Server_Abstract::$_modelConfigMethods[$frontend] = $definitions;

        return $definitions;
    }

    /**
     * @return void
     * @throws Tinebase_Exception_Unauthorized
     * @throws Zend_Session_Exception
     */
    final protected function _disallowAppPwdSessions(): void
    {
        if (Tinebase_Session::sessionExists()) {
            if (!Tinebase_Session::isStarted()) {
                try {
                    Tinebase_Core::startCoreSession();
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                            . ' ' . $tenf->getMessage());
                    }
                    throw new Tinebase_Exception_Unauthorized('User not found');
                }
            }

            if (Tinebase_Session::getSessionNamespace()->{Tinebase_Model_AppPassword::class}) {
                throw new Tinebase_Exception_Unauthorized('Session not allowed for this api');
            }
        }
    }

    final protected function _handleAppPwdAuth()
    {
        $authValue = $this->_request->getHeader('Authorization')->getFieldValue();
        if (!str_starts_with((string) $authValue, 'Basic ') || false === ($authValue = base64_decode(substr((string) $authValue, 6), true))
                || 2 !== count($authValue = explode(':', $authValue, 2))) {
            return false;
        }

        $appPwd = $authValue[1];
        if (strlen($appPwd) !== Tinebase_Controller_AppPassword::PWD_LENGTH || strpos($appPwd, Tinebase_Controller_AppPassword::PWD_SUFFIX) !== Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH) {
            return false;
        }

        try {
            $user = Tinebase_User::getInstance()->getUserByLoginName($authValue[0], Tinebase_Model_FullUser::class);
        } catch (Tinebase_Exception_NotFound) {
            return false;
        }
        try {
            $encryptedPwd = sha1($appPwd);
        } catch (Tinebase_Exception) {
            return false;
        }

        $oldValue = Tinebase_Controller_AppPassword::getInstance()->doContainerACLChecks(false);
        try {
            $appPwd = Tinebase_Controller_AppPassword::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_AppPassword::class, [
                    [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_ACCOUNT_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $user->getId()],
                    [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_AUTH_TOKEN, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $encryptedPwd],
                    [TMFA::FIELD => Tinebase_Model_AppPassword::FLD_VALID_UNTIL, TMFA::OPERATOR => 'after', TMFA::VALUE => ''],
                ])
            )->getFirstRecord();
        } finally {
            Tinebase_Controller_AppPassword::getInstance()->doContainerACLChecks($oldValue);
        }

        if (null !== $appPwd) {
            if (!Tinebase_Session::sessionExists()) {
                try {
                    Tinebase_Core::startCoreSession();
                } catch (Zend_Session_Exception) {
                    $exception = new Tinebase_Exception_AccessDenied('Not Authorised', 401);

                    // expire session cookie for client
                    Tinebase_Session::expireSessionCookie();

                    return $exception;
                }
            }

            $session = Tinebase_Session::getSessionNamespace();
            $session->{Tinebase_Model_AppPassword::class} = $appPwd;
            $session->currentAccount = $user;
            Tinebase_Core::setUser($user);
        }

        return false;
    }

    /**
     * checks whether either no area_login lock is set or if it is unlocked already
     */
    final static public function checkLoginAreaLock(): bool
    {
        return !Tinebase_AreaLock::getInstance()->hasLock(Tinebase_Model_AreaLockConfig::AREA_LOGIN) ||
            !Tinebase_AreaLock::getInstance()->isLocked(Tinebase_Model_AreaLockConfig::AREA_LOGIN);
    }

    final static protected function _checkAreaLock($_method)
    {
        if (Tinebase_AreaLock::getInstance()->hasLock($_method)) {
            if (Tinebase_AreaLock::getInstance()->isLocked($_method)) {
                $teal = new Tinebase_Exception_AreaLocked('Application is locked: '
                    . $_method);
                $cfg = Tinebase_AreaLock::getInstance()->getLastAuthFailedAreaConfig();
                $teal->setArea($cfg->{Tinebase_Model_AreaLockConfig::FLD_AREA_NAME});
                $teal->setMFAUserConfigs($cfg->getUserMFAIntersection(Tinebase_Core::getUser()));
                throw $teal;
            }
        }
    }

    /**
     * @param string $frontend
     * @param string $_method
     * @return void
     * @throws Tinebase_Exception_RateLimit
     */
    final static protected function _checkRateLimit(string $frontend, string $_method = '*'): void
    {
        $rateLimit = new Tinebase_Server_RateLimit();
        if ($rateLimit->hasRateLimit($frontend, $_method)) {
            if (! $rateLimit->check($frontend, $_method)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' Rate limit hit for : ' . $frontend . '.' . $_method);
                }
                throw new Tinebase_Exception_RateLimit($frontend . ' Method is rate-limited: ' . $_method);
            }
        }
    }

    /**
     * @param int $code
     */
    public static function setHttpHeader($code)
    {
        if (! headers_sent()) {
            match ($code) {
                self::HTTP_ERROR_CODE_FORBIDDEN => header('HTTP/1.1 403 Forbidden'),
                self::HTTP_ERROR_CODE_NOT_FOUND => header('HTTP/1.1 404 Not Found'),
                self::HTTP_ERROR_CODE_SERVICE_UNAVAILABLE => header('HTTP/1.1 503 Service Unavailable'),
                default => header("HTTP/1.1 500 Internal Server Error"),
            };
        }
    }
}
