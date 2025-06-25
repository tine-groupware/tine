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
 * server plugin to dispatch JSON requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_Json implements Tinebase_Server_Plugin_Interface
{
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        /**************************** JSON API *****************************/
        if ($request->getHeaders('X-TINE20-REQUEST-TYPE', null)?->getFieldValue() === 'JSON'  ||

            /** TODO FIXME remove next line */
            !empty($request->getHeaders('X-TINE20-JSONKEY', null)?->getFieldValue()) || // for legacy frontends (floorplan)

            'JSON' === $request->getQuery('requestType') ||
            ($request->getMethod() == \Laminas\Http\Request::METHOD_OPTIONS && $request->getHeaders()->has('ACCESS-CONTROL-REQUEST-METHOD'))
        ) {
            return new Tinebase_Server_Json();
        }
        return null;
    }
}