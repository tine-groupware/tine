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
 * server plugin to dispatch WebDAV requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_WebDAV implements Tinebase_Server_Plugin_Interface
{
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        if ($request->getQuery('frontend') === 'webdav') {
            return new Tinebase_Server_WebDAV();
        }

        return null;
    }
}
