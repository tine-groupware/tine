<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * server plugin to dispatch WebDAV requests
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Plugin_WebDAVCatchAll implements Tinebase_Server_Plugin_Interface
{
    public static function getServer(\Laminas\Http\Request $request): ?Tinebase_Server_Interface
    {
        return new Tinebase_Server_WebDAV();
    }
}
