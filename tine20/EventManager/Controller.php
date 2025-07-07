<?php
/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Philipp Schüle <p.schuele@metaways.de>
 * @copyright    Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * EventManager Controller
 *
 * @package      EventManager
 * @subpackage   Controller
 */
class EventManager_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    protected static $_defaultModel = EventManager_Model_Event::class;

    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
    }

    public static function addFastRoutes(\FastRoute\RouteCollector $routeCollector): void
    {
        $routeCollector->addGroup('/EventManager', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->get('/view', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiMainScreen',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/view/search/event', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiSearchEvents',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
            $routeCollector->get('/view/event/{eventId}', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiGetEvent',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());
           /* $routeCollector->post('/view/event/{eventId}/registration', (new Tinebase_Expressive_RouteHandler(
                EventManager_Controller_Event::class,
                'publicApiPostRegistration',
                [Tinebase_Expressive_RouteHandler::IS_PUBLIC => true]
            ))->toArray());*/
        });
    }
}
