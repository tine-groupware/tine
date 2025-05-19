<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 */

/**
 * server plugin to dispatch ActiveSync requests
 *
 * @package     ActiveSync
 * @subpackage  Server
 */
class ActiveSync_Server_Plugin implements Tinebase_Server_Plugin_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Plugin_Interface::getServer()
     */
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        if (str_starts_with($request->getUri()->getPath(), '/Microsoft-Server-ActiveSync')) {
            return new ActiveSync_Server_Http();
        }

        return null;
    }
}
