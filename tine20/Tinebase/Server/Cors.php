<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * CORS Server class with handle() function
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Cors extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    public function __construct (
        protected bool $allowed,
        protected string $origin,
        protected Laminas\Uri\Uri $uri,
        protected array $allowedOrigins
    ) {}

    public function handle(?\Laminas\Http\Request $request = null, $body = null)
    {
        if ($this->allowed) {
            header('Access-Control-Allow-Methods: POST');
            header('Access-Control-Allow-Headers: x-requested-with, x-tine20-request-type, content-type, x-tine20-jsonkey, authorization');
            header('Access-Control-Max-Age: 3600'); // cache result of OPTIONS request for 1 hour

        } else {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " unhandled CORS preflight request from $this->origin");
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " you may want to set \"'allowedOrigins' => array('{$this->uri->getHost()}'),\" to config.inc.php");
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " allowed origins: " . print_r($this->allowedOrigins, true));
        }

    }

    public function getRequestMethod()
    {
        return 'OPTIONS';
    }
}