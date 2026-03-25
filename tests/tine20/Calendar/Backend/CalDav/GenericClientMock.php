<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar_Backend_CalDAV_ClientMock
 * 
 * @package     Calendar
 * @subpackage  Backend
 */
class Calendar_Backend_CalDav_GenericClientMock extends Calendar_Backend_CalDav_Client
{
    public ?Closure $propFindDelegator = null;
    public ?Closure $requestDelegator = null;
    public ?Closure $multiStatusRequestDelegator = null;

    public function propFind($url, array $properties, $depth = 0): array
    {
        if ($this->propFindDelegator)
            return ($this->propFindDelegator)($url, $properties, $depth);

        throw new Tinebase_Exception_NotImplemented(__METHOD__);
    }

    public function request($method, $url = '', $body = null, array $headers = [])
    {
        if ($this->requestDelegator)
            return ($this->requestDelegator)($method, $url, $body, $headers);

        throw new Tinebase_Exception_NotImplemented(__METHOD__);
    }

    public function multiStatusRequest(string $method, string $uri, string $body, int $depth = 0): array
    {
        if ($this->multiStatusRequestDelegator)
            return ($this->multiStatusRequestDelegator)($method, $uri, $body, $depth);

        throw new Tinebase_Exception_NotImplemented(__METHOD__);
    }
}
