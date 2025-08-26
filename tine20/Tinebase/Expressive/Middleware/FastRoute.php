<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Expressive
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Server\RequestHandlerInterface;
use \Psr\Http\Server\MiddlewareInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use \Laminas\Diactoros\Response;

/**
 * FastRoute middleware, continues if a route matched, puts the matched Tinebase_Expressive_RouteHandler in the request
 * returns a Response 404/405 if no route matched
 *
 * matching is only done on the path, not on the query parameters
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_FastRoute implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $delegate
     * @throws Tinebase_Exception_UnexpectedValue
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' processing...');

        $dispatcher = $this->_getDispatcher();

        $uri = $request->getUri()->getPath();

        // remove trailing slashes - FastRoute is not handling uris with them correctly it seems
        $uri = preg_replace('/\/+$/', '', $uri);

        // if the tine20 server is located in a subdir, we need to remove the server path from the uri
        if ('/' !== ($serverPath = Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH)) && '' !== $serverPath) {
            $uri = preg_replace('/^' . preg_quote($serverPath, '/') . '/', '/', $uri);
        }

        // remove multiple slashes as well - FastRoute is not handling uris with them correctly it seems
        $uri = preg_replace('/\/+/', '/', $uri);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . " FastRoute dispatching:\n" . $request->getMethod() . ' '. $uri . array_reduce(array_keys($request->getHeaders()), function($headers, $name) use ($request)  {
                return $headers .= PHP_EOL . $name . ': ' . ('authorization' === strtolower($name) ? '*****' : $request->getHeaderLine($name));
            }, ''));

        $routeInfo = $dispatcher->dispatch($request->getMethod(), $uri);
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' returning 404 method not found');

                // 404 not found
                return new Response('php://memory', 404);
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' returning 405 method not allowed');

                //$allowedMethods = $routeInfo[1];
                // 405 method not allowed
                return new Response('php://memory', 405);
            case FastRoute\Dispatcher::FOUND:
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                    . __LINE__ . ' FastRoute dispatching result: ' . print_r($routeInfo, true));

                $handler = Tinebase_Expressive_RouteHandler::fromArray($routeInfo[1]);
                $handler->setVars($routeInfo[2]);
                return $delegate->handle($request->withAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER, $handler));
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('fast route dispatcher returned unexpected route info');
        }

        // in case you ever want to call $delegate->process without add the Tinebase_Expressive_Const::ROUTE_HANDLER
        // then do it like this: $delegate->process($request->withoutAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER)
    }

    /**
     * @return \FastRoute\Dispatcher
     */
    protected function _getDispatcher()
    {
        if (! Setup_Controller::getInstance()->isInstalled('Tinebase')) {
            $enabledApplications = new Tinebase_Record_RecordSet(Tinebase_Model_Application::class);
        } else {
            $enabledApplications = Tinebase_Application::getInstance()->getApplications()
                ->filter('status', Tinebase_Application::ENABLED);
        }
        $apps = array_combine(
            $enabledApplications->id,
            $enabledApplications->version
        );
        ksort($apps);
        $appsHash = Tinebase_Helper::arrayHash($apps, true);

        try {
            $cachedDispatcher = $this->_getCachedDispatcher($enabledApplications, $appsHash);
        } catch (RuntimeException|Error $runtimeException) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                . __LINE__ . ' Got exception: ' . $runtimeException->getMessage() . ' - clear cache & trying again');
            clearstatcache();
            $cachedDispatcher = $this->_getCachedDispatcher($enabledApplications, $appsHash);
        }
        return $cachedDispatcher;
    }

    /**
     * TODO add base path in case tine20 was not installed in /
     * TODO if we do that, the base path needs to be in the cache key $appsHash too!
     *
     * @param Tinebase_Record_RecordSet $enabledApplications
     * @param string $appsHash
     * @return \FastRoute\Dispatcher
     */
    protected function _getCachedDispatcher(Tinebase_Record_RecordSet $enabledApplications, string $appsHash)
    {
        return \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) use ($enabledApplications) {
            /** @var Tinebase_Model_Application $application */
            foreach ($enabledApplications as $application) {
                /** @var Tinebase_Controller_Abstract $className */
                $className = $application->name . '_Controller';
                if (class_exists($className)) {
                    $className::addFastRoutes($r);
                }
            }
        }, [
            'cacheFile' => Tinebase_Core::getCacheDir() . '/route.cache.'
                . $appsHash,
            'cacheDisabled' => $this->_isCacheDisabled(),
        ]);
    }

    /**
     * @return bool
     */
    protected function _isCacheDisabled()
    {
        return TINE20_BUILDTYPE === 'DEVELOPMENT' || Tinebase_Core::inMaintenanceMode();
    }
}
