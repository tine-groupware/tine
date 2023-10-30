<?php declare(strict_types=1);

use Jumbojett\OpenIDConnectClient;

/**
 * Tine 2.0
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)>
 */

class SSO_Facade_OpenIdConnect_Client extends OpenIDConnectClient
{
    protected ?string $_redirectedToUrl = null;

    public function resetClient(): void
    {
        $this->_redirectedToUrl = null;
    }

    public function didRedirectOccur(): ?string
    {
        return $this->_redirectedToUrl;
    }

    public function redirect($url)
    {
        $this->_redirectedToUrl = $url;
    }

    protected function startSession() {}

    protected function commitSession() {}

    protected function getSessionKey($key)
    {
        $session = Tinebase_Session::getSessionNamespace();
        if (isset($session->{$key})) {
            return $session->{$key};
        }
        return false;
    }

    protected function setSessionKey($key, $value)
    {
        Tinebase_Session::getSessionNamespace()->{$key} = $value;
    }

    protected function unsetSessionKey($key)
    {
        unset(Tinebase_Session::getSessionNamespace()->{$key});
    }
}