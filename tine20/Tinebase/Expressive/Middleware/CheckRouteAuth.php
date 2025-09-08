<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Expressive
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Server\RequestHandlerInterface;
use \Psr\Http\Server\MiddlewareInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use \Laminas\Diactoros\Response;

/**
 * expressive route auth middleware, reads matched route for auth requirements and checks them
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_CheckRouteAuth implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $delegate
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' processing...');

        /** @var Tinebase_Expressive_RouteHandler $routeHandler */
        if (null === ($routeHandler = $request->getAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER, null))) {
            throw new Tinebase_Exception_UnexpectedValue('no matched route found');
        }

        Tinebase_Core::startCoreSession();

        if (!$routeHandler->ignoreMaintenanceMode()) {
            if (Tinebase_Core::inMaintenanceMode() ||
                Tinebase_Core::getApplicationInstance($routeHandler->getApplicationName())->isInMaintenanceMode()) {
                if (Tinebase_Core::inMaintenanceModeAll() || !is_object($user = Tinebase_Core::getUser()) ||
                        !$user->hasRight($routeHandler->getApplicationName(), Tinebase_Acl_Rights::MAINTENANCE)) {
                    throw new Tinebase_Exception_MaintenanceMode();
                }
                throw new Tinebase_Exception_MaintenanceMode();
            }
        }

        if (! $routeHandler->isPublic()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' in an auth route');

            $unauthorized = true;
            do {
                if (null === ($user = Tinebase_Core::getUser()) && $request->hasHeader('Authorization')) {
                    foreach ($request->getHeader('Authorization') as $authHeader) {
                        if (str_starts_with($authHeader, 'Bearer ')) {
                            $token = substr($authHeader, 7);
                            try {
                                Admin_Controller_JWTAccessRoutes::doRouteAuth($routeHandler->getName(), $token);
                                $user = Tinebase_Core::getUser();
                                $unauthorized = false;
                                break 2;
                            } catch (Tinebase_Exception_AccessDenied $tead) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ .
                                    '::' . __LINE__ . ' returning with HTTP 401 unauthorized: ' . $tead->getMessage());

                                break;
                            } catch (Tinebase_Exception $te) {
                                // something went wrong -> 500
                                throw $te;
                            } catch (Exception $e) {
                                // these are jwt fails, so basically bad requests ... yet we return 401
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ .
                                    '::' . __LINE__ . ' returning with HTTP 401 unauthorized: ' . $e->getMessage());

                                break;
                            }
                        }
                    }
                }

                if (null === $user) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                        . __LINE__ . ' returning with HTTP 401 unauthorized');

                    break;
                }
                if (!Tinebase_Server_Abstract::checkLoginAreaLock()) {
                    $areaLock = Tinebase_AreaLock::getInstance();
                    $userConfigIntersection = new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
                    foreach ($areaLock->getAreaConfigs(Tinebase_Model_AreaLockConfig::AREA_LOGIN) as $areaConfig) {
                        $userConfigIntersection->mergeById($areaConfig->getUserMFAIntersection($user));
                    }

                    // user has 2FA config -> currently its sort of optional -> only then we 401
                    if (count($userConfigIntersection->mfa_configs) > 0) {
                        break;
                    }
                }

                $unauthorized = false;
            } while (false);

            if ($unauthorized) {
                if ($routeHandler->unauthorizedRedirectLogin()) {
                    $uri = $request->getUri();
                    $path = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_NOPATH) . $uri->getPath() . ($uri->getQuery() ? '?' . $uri->getQuery() : '') .
                        ($uri->getFragment() ? '#' . $uri->getFragment() : '');

                    if (!Tinebase_Session::sessionExists()) {
                        Tinebase_Core::startCoreSession();
                    }
                    Tinebase_Session::getSessionNamespace()->login = ['afterLoginRedirect' => [
                        'method' => 'GET',
                        'url' => $path,
                    ]];

                    return new Response('php://memory', 302, [
                        'Location' => Tinebase_Core::getUrl(),
                    ]);
                }
                return new Response('php://memory', 401);
            }

            if (! $user->hasRight($routeHandler->getApplicationName(), Tinebase_Acl_Rights_Abstract::RUN)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' returning with HTTP 403 forbidden');

                // forbidden
                return new Response('php://memory', 403);
            }

            // TODO add more sophisticated stuff
            // if ( $routeHandler->requiresRights() ) {
            // foreach ($routeHandler->getRequiredRights() as $right) {
            // if (! $user->hasRight($routeHandler->getApplicationName(), $right)) {

            return $delegate->handle($request);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' in a public route');

            $routeHandler->setPublicRoles();

            try {
                return $delegate->handle($request);
            }  finally{
                // TODO eventually we want this to happen in the ResponseEnvelop actually! if expanding would happen
                // TODO there... if expanding happens inside the delegate above we are fine
                $routeHandler->unsetPublicRoles();
            }
        }
    }
}
