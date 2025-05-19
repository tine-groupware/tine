<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * server plugin to dispatch HTTP requests
 * 
 * should be the last plugins, as it handles all requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Http implements Tinebase_Server_Plugin_Interface
{
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        if (null !== $request->getQuery('method') || null !== $request->getPost('method') ||
            (($request::METHOD_GET === $request->getMethod() || $request::METHOD_POST === $request->getMethod()) &&
                    (trim($request->getUri()->getPath(), '/') === trim(Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH), '/') ||
                        $request->getUri()->getPath() === '/index.php')) ) {
                return new Tinebase_Server_Http();
        }
        return null;
    }
}