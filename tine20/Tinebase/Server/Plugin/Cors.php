<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * server plugin to handle CORS headers
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Cors implements Tinebase_Server_Plugin_Interface
{
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        if ($request->getHeaders()->has('ORIGIN')) {
            /**
             * First the client sends a preflight request
             *
             * METHOD: OPTIONS
             * Access-Control-Request-Headers:x-requested-with, content-type
             * Access-Control-Request-Method:POST
             * Origin:http://other.site
             * Referer:http://other.site/example.html
             * User-Agent:Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36
             *
             * We have to respond with
             *
             * Access-Control-Allow-Credentials:true
             * Access-Control-Allow-Headers:x-requested-with, x-tine20-request-type, content-type, x-tine20-jsonkey
             * Access-Control-Allow-Methods:POST
             * Access-Control-Allow-Origin:http://other.site
             *
             * Then the client sends the standard request with two additional headers
             *
             * METHOD: POST
             * Origin:http://other.site
             * Referer:http://other.site/example.html
             * Standard-JSON-Request-Headers...
             *
             * We have to add two additional headers to our standard response
             *
             * Access-Control-Allow-Credentials:true
             * Access-Control-Allow-Origin:http://other.site
             */
            try {
                $origin = $request->getHeaders('ORIGIN')->getFieldValue();
            } catch (Exception) {
                return null;
            }
            $uri    = \Laminas\Uri\UriFactory::factory($origin);

            $allowedOrigins = array_merge(
                (array) Tinebase_Core::getConfig()->get(Tinebase_Config::ALLOWEDJSONORIGINS, []),
                (array) Tinebase_Core::getConfig()->get(Tinebase_Config::ALLOWEDORIGINS, []), [
                'appassets.tine-android-platform.local', // needed for android apps
                '127.0.0.1',
                'localhost',
            ], ($_SERVER['SERVER_NAME'] ?? false) ? [$_SERVER['SERVER_NAME']] : []);

            $allowed = false;
            if (in_array($uri->getHost(), $allowedOrigins)) {
                // this headers have to be sent, for any CORS'ed request
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Credentials: true');
                $allowed = true;
            }

            // check for CORS preflight request
            if ($request->getMethod() === \Laminas\Http\Request::METHOD_OPTIONS &&
                $request->getHeaders()->has('ACCESS-CONTROL-REQUEST-METHOD')
            ) {
                return new Tinebase_Server_Cors($allowed, $origin, $uri, $allowedOrigins);
            }
        }

        return null;
    }
}
