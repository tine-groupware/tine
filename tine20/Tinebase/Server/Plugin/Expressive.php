<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Psr\Http\Message\RequestInterface;

/**
 * server plugin to dispatch Expressive requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Expressive implements Tinebase_Server_Plugin_Interface
{
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        Tinebase_Core::initFramework();

        /**************************** JSON API *****************************/
        if (null !== $request->getQuery(Tinebase_Server_Expressive::QUERY_PARAM_DO_EXPRESSIVE) ||
                (Tinebase_Expressive_Middleware_FastRoute::getRouteInfo(Tinebase_Core::getContainer()->get(RequestInterface::class))[0] ?? FastRoute\Dispatcher::NOT_FOUND) !== FastRoute\Dispatcher::NOT_FOUND) {
            return new Tinebase_Server_Expressive();
        }
        return null;
    }
}
