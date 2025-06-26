<?php
/**
 * Bookmarks Controller
 *
 * @package      OnlyOfficeIntegrator
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Cornelius Weiß <c.weiss@metaways.de>
 * @copyright    Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * Bookmarks Controller
 *
 * @package      Bookmarks
 * @subpackage   Controller
 */
class Bookmarks_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    protected $_applicationName = Bookmarks_Config::APP_NAME;
    
    public static function addFastRoutes(\FastRoute\RouteCollector $r): void {
        $r->addGroup('/' . Bookmarks_Config::APP_NAME, function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/openBookmark/{id}', (new Tinebase_Expressive_RouteHandler(
                self::class, 'openBookmark', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => false
            ]))->toArray());
        });
    }

    /**
     * @param string $id
     * @return false|mixed|\Zend\Diactoros\Response
     */
    public function openBookmark($id)
    {
        $bc = Bookmarks_Controller_Bookmark::getInstance();

        try {
            $bookmark = $bc->get($id);
            $url = $bookmark->url = trim($bookmark->url);
            $response = new \Zend\Diactoros\Response('php://memory', 307, [
                'Referrer-Policy'   => 'no-referrer',
                'location'          => $url,
            ]);
            $response->getBody()->write("location: {$url}");
            
            $hooks = Bookmarks_Config::getInstance()->get(Bookmarks_Config::OPEN_BOOKMARK_HOOKS, []);
            foreach ($hooks as $pattern => $hookClass) {
                if (preg_match($pattern, $url)) {
                    if (! class_exists($hookClass)) {
                        @include($hookClass . '.php');
                    }

                    if (class_exists($hookClass)) {
                        $hook = new $hookClass();
                        if (method_exists($hook, 'openBookmark')) {
                            try {
                                $response = call_user_func_array(array($hook, 'openBookmark'), array($bookmark, $response));
                            } catch (Exception $e) {
                                Tinebase_Exception::log($e);
                            }
                        }
                    }
                }
            }
            
            if ($response instanceof \Zend\Diactoros\Response && $response->getStatusCode() < 400) {
                Bookmarks_Controller_Bookmark::getInstance()->increaseAccessCount($bookmark);
            }
            return $response;

        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            return new \Zend\Diactoros\Response('php://memory', 403, []);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
            return new \Zend\Diactoros\Response('php://memory', 404, []);
        }
    }
}
